<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017-2018  StateMapper.net <statemapper@riseup.net>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */ 
 
namespace StateMapper;


if (!defined('BASE_PATH'))
	die();

class BulletinController {

	public function route($bits){
		global $smap;

		if (!isset($smap['raw']))
			$smap['raw'] = $bits && $bits[count($bits)-1] == 'raw' && array_pop($bits);

		//if (IS_CLI && $bits && $bits[0] == 'api')
			//array_shift($bits);

		// CLI API root
		/*if (IS_CLI && $smap['page'] == 'schemas'){
			cli_print();
			exit();
		}*/

		if (!empty($smap['filters']['loc']) && !get_country_schema($smap['filters']['loc']))
			return 'no such schema country';
			
		$query = array();
		$query['max_depth'] = MAX_FOLLOW_DEPTH;//$bits && is_numeric($bits[count($bits)-1]) ? intval(array_pop($bits)) : 2;
		
		// retrieve cpu-rate parameter (a percentage at the end of the call string)
		if ($bits && preg_match('#^([0-9]+)%$#', $bits[count($bits)-1], $m)){
			$smap['spider']['max_cpu_rate'] = min(intval($m[1]), 95);
			array_pop($bits);
		} else
			$smap['spider']['max_cpu_rate'] = 100;
		
		// detect the call id
		if (empty($smap['call']) && $smap['page'] != 'providers')
			$smap['call'] = $bits && is_mode($bits[count($bits)-1]) && (count($bits) > 1 || !is_dated_mode($bits[count($bits)-1])) ? array_pop($bits) : 'fetch';
		
		// (redirect|download)/[format] url detection
		if (count($bits) > 1 && is_format($bits[count($bits)-1]) && in_array($bits[count($bits)-2], array('redirect', 'download'))){ 
			$query['format'] = array_pop($bits);
			if ($query['format'] == 'txt'){
				unset($query['format']);
				$query['lint'] = true;
			}
			$smap['call'] = array_pop($bits);
		}
		
		// force spider config
		if ($smap['call'] == 'spide'){
			$smap['spider']['workers_count'] = $query['workers_count'] ? $query['workers_count'] : SPIDER_WORKERS_COUNT;
			$query['max_depth'] = 2;
		}
		
		if ($smap['call'] == 'lint')
			$query['lint'] = true;
			
		$smap['query'] = $query + $smap['query'];
		
		if (empty($smap['call'])){

			// CLI help
			if (IS_CLI){
				cli_print();
				exit;
			}

			// providers page
			$smap['schemas'] = get_schemas($smap['filters']['loc']);

			if (!empty($smap['raw'])){
				$schemas = array();
				foreach ($smap['schemas'] as $s)
					if ($s = get_schema($s)){
						$schemas[] = array(
							'id' => $s->id,
							'type' => $s->type,
							'name' => $s->name,
							'shortName' => !empty($s->shortName) ? $s->shortName : null,
							'providerId' => !empty($s->providerId) ? $s->providerId : null,
							'region' => !empty($s->region) ? $s->region : null,
							'country' => !empty($s->country) ? $s->country : null,
							'continent' => !empty($s->continent) ? $s->continent : null,
							'avatar' => get_schema_avatar_url($s),
						);
					}
					
				// @todo: substitute?
				return array(
					'result' => array(
						'success' => true, 
						'query' => array(
							'filters' => $smap['filters']
						), 
						'results' => $schemas
					)
				);
			}
			//print_page('providers'); // testing!

			return array(
				'page' => 'providers',
			);
		}
		
		// grab schema
		$query['schema'] = array();
		if (!empty($smap['filters']['loc'])){
			$query['schema'][] = $smap['filters']['loc'];
			// $smap['filters']['loc'] = null;
		}
		
		if ($bits && is_alphanum($bits[0]) && !is_valid_mode($bits[0]))
			$query['schema'][] = strtoupper(array_shift($bits));
		
		if (!$query['schema'])
			return 'bad schema';
		$query['schema'] = strtoupper(implode('/', $query['schema']));
		// grab date
		
		if (is_dated_mode($smap['call']) && $bits && is_date($bits[0])){
			$query['date'] = array_shift($bits);
			
			// check date is valid
			if (!is_valid_date($query['date']))
				die_error('given date does not exist: '.$query['date'].' (format is YY-MM-DD)');
			else if ($query['date'] > date('Y-m-d'))
				die_error('the date must be today or in the past (format is YY-MM-DD)');
		}
		
		if (is_dated_mode($smap['call'])){
			if ($bits && !is_numeric($bits[0]) && !is_mode($bits[0])){
				$query['id'] = strtoupper(array_shift($bits));
				
				if (!preg_match('#^([a-z0-9-_]+)$#i', $query['id']))
					return 'bad id: '.htmlentities($query['id']);
					
				if (!($query['date'] = get_var('SELECT date FROM bulletins WHERE bulletin_schema = %s AND external_id = %s', array($query['schema'], $query['id']))))
					return 'unknown id: '.htmlentities($query['id']);

			} else if (empty($query['date'])){
				if (!empty($_GET['url'])){
					if (!($query['url'] = filter_var($_GET['url'], FILTER_VALIDATE_URL)))
						return 'bad url';
				} else
					$query['date'] = date('Y-m-d', strtotime('-1 day'));
			}
		}

		if (!empty($_GET['as']) && preg_match('#^([a-z0-9_]+)$#i', $_GET['as']))
			$query['type'] = $_GET['as'];
		else if (empty($query['type']) && !empty($query['id']))
			$query['type'] = 'document';

		if (in_array($smap['call'], array('parse', 'rewind', 'extract', 'spide')) && $query['max_depth'] < 1)
			$query['max_depth'] = 1;

		$query['use_processed_cache'] = empty($_GET['no_processed_cache']) && !in_array($smap['call'], array('rewind', 'spide')) && USE_PROCESSED_FILE_CACHE;
		
		$smap['query'] = $query + $smap['query'];
		
		// check schema
		if (!is_valid_schema_path($query['schema']))
			return 'invalid schema';
		if (!($smap['schemaObj'] = get_schema($query['schema'])))
			return 'no such schema '.$query['schema'];
			
		if ($smap['schemaObj']->type != 'bulletin' && !in_array($smap['call'], array('schema', 'soldiers', 'ambassadors'))){
			$smap['call'] = null;
			return false;
		}

		switch ($smap['call']){

			case 'schema':
				if ($smap['page'] != 'bulletin')
					return false;
					
				if (!empty($query['id']) || !empty($query['date']))
					return 'id or date in arguments';

				if (!empty($smap['schemaObj']->providerId))
					$smap['schemaObj']->provider = get_provider_schema($smap['schemaObj']->providerId, true, false);

				return array(
					'result' => $smap['raw'] ? array(
						'success' => true,
						'query' => $query,
						'result' => $smap['schemaObj']
					) : get_schema($query['schema'], true)
				);

			case 'fetch':
			case 'lint':
			case 'download':
			case 'redirect':
				if ($smap['page'] != 'bulletin')
					return false;
					
				$bulletinFetcher = new BulletinFetcher();
				$bulletin = $bulletinFetcher->fetch_bulletin($query, $smap['call'] == 'redirect');//, $smap['call'] == 'lint' ? '.parsed.json' : false);

				if (is_error($bulletin))
					return $bulletin;

				// iframe inside API
				if (!IS_CLI && in_array($smap['call'], array('fetch', 'lint')) && !$smap['raw']){
					$smap['is_iframe'] = true;

					return array(
						'page' => 'bulletin_iframe',
					); // attempt to fix Chromium bug displaying XML in iframes

				} else {
					$content = $bulletinFetcher->serve_bulletin($bulletin, $smap['call'], get_schema_title($smap['schemaObj'], $query), $query);
					debug($content); die();

					if ($smap['raw'] && !IS_CLI){
						// not a file, print the returned content

						$content = htmlentities($content);

						if (!empty($smap['call']) && $smap['call'] == 'lint')
							$content = preg_replace_callback('#(?!href=["\'])(https?://[^"\'\s]+)#ius', function($m){
								return '<a href="'.anonymize($m[1]).'" target="_blank">'.$m[0].'</a>';
							}, $content);

						$content = nl2br($content);
						if (empty($_GET['loadCSS']))
							echo $content;
						else {
							print_template('parts/header', array('body_class' => 'main-iframe', 'is_iframe' => true));
							echo $content;
							print_template('parts/footer', array('is_iframe' => true));
						}
					} else
						return array(
							'result' => $content
						);

				}
				return false;

			case 'parse':
			case 'extract':
				if ($smap['page'] != 'bulletin')
					die_error();
					
				$bulletinParser = new BulletinParser();

				$lock = null;
				if ($smap['call'] == 'rewind'){
					while (!empty($query['date']) && !($lock = lock('rewind-'.$query['schema'].'-'.$query['date'])))
						$query['date'] = date('Y-m-d', strtotime('-1 day', strtotime($query['date'])));
				}

				$ret = $bulletinParser->fetch_and_parse($query);

				if (is_error($ret)){
					unlock($lock);
					return $ret->msg;
				}
				
				if ($ret === true){
					// no bulletin this day
					return new SMapError('no bulletin this day');
				}

				if ($smap['call'] == 'extract'){
					$extracter = new BulletinExtractor($ret);
					$ret = $extracter->extract($smap['query'], is_admin() && !empty($_GET['save']));
					$smap['preview_api_result'] = $extracter;
				}

				unlock($lock);
				
				// @todo: improve this..
				return array(
					'result' => array(
						'success' => true,
						'query' => $query,
						'result' => $ret
					)
				);

			case 'rewind':
			
				if ($smap['page'] != 'bulletin')
					die_error();
					
				if ($bits && is_numeric($bits[0])){
					$year = intval($bits[0]);
					if ($year > 1900)
						$smap['query']['year'] = $year;
				}
					
				if (IS_CLI){
					define('SMAP_FORCE_OUTPUT', true);

					$smap['spider'] = array(
						'schema' => $smap['query']['schema'],
						'status' => 'manual',
						'date_back' => !empty($smap['query']['date']) ? $smap['query']['date'] : date('Y-m-d', strtotime('-2 days')),
						'extract' => !empty($smap['extract']),
					) + get_default_spider_config();

					define('KAOS_SPIDER_ID', 0);
					return array(
						'route' => APP_PATH.'/spider/spider.php'
					);
				} 
				return array(
					'page' => 'rewind'
				);
				
			case 'soldiers':
				$schema = get_schema(!empty($smap['query']['schema']) ? $smap['query']['schema'] : $smap['filters']['loc']);
				$soldiers = get_schema_prop($schema, 'soldiers', true);

				return array(
					'page' => 'soldiers', 
					'vars' => array(
						'soldiers' => $soldiers
					), 
					'template_vars' => array(
						'schema' => $schema
					)
				);
		}
		return 'unknown call';
	}
}

