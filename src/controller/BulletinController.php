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
 
if (!defined('BASE_PATH'))
	die();


class BulletinController {

	public function route($bits){
		global $smap;

		if (!isset($smap['raw']))
			$smap['raw'] = $bits && $bits[count($bits)-1] == 'raw' && array_pop($bits);

		//if (IS_CLI && $bits && $bits[0] == 'api')
			//array_shift($bits);

		// CLI API Root (useful?)
		if (IS_CLI && $smap['page'] == 'schemas'){
			cli_print();
			exit();
		}

		if (!empty($smap['filters']['loc']) && !get_country_schema($smap['filters']['loc']))
			die_error('no such schema country');
			
		$query = array();
		$query['followLevels'] = $bits && is_numeric($bits[count($bits)-1]) ? intval(array_pop($bits)) : 2;

		if ($bits && preg_match('#^([0-9]+)%$#', $bits[count($bits)-1], $m)){
			$smap['spiderConfig']['cpuRate'] = min(intval($m[1]), 95);
			array_pop($bits);
		} else
			$smap['spiderConfig']['cpuRate'] = 100;

		if (empty($smap['call'])){
			if ($smap['page'] == 'providers')
				$smap['call'] = null;
			else
				$smap['call'] = $bits && is_mode($bits[count($bits)-1]) && (count($bits) > 1 || !is_dated_mode($bits[count($bits)-1])) ? array_pop($bits) : 'fetch';
		}
		
		// (redirect|download)/[format] url detection
		if (count($bits) > 1 && is_format($bits[count($bits)-1]) && in_array($bits[count($bits)-2], array('redirect', 'download'))){ 
			$query['format'] = array_pop($bits);
			if ($query['format'] == 'txt'){
				unset($query['format']);
				$query['lint'] = true;
			}
			$smap['call'] = array_pop($bits);
		}

		if ($smap['call'] == 'spide'){
			$smap['spiderConfig']['workersCount'] = $query['followLevels'] ? $query['followLevels'] : SPIDER_WORKERS_COUNT;
			$query['followLevels'] = 2;
		}
		
		if ($smap['call'] == 'lint')
			$query['lint'] = true;
		$smap['query'] = $query;
		
		if (empty($smap['call'])){
			// providers page

			if (IS_CLI){
				// CLI help
				cli_print();
				exit;
			}

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
						);
					}
				return_wrap(array('success' => true, 'query' => array('filters' => $smap['filters']), 'results' => $schemas));
			}

			$smap['outputNoFilter'] = true;
			return_wrap(get_template('providers'));
			exit;
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
			die_error('bad schema');
		$query['schema'] = strtoupper(implode('/', $query['schema']));
		
		// grab date
		
		if (is_dated_mode($smap['call']) && $bits && is_date($bits[0])){
			$query['date'] = array_shift($bits);
			
			// check date is valid
			if (!is_valid_date($query['date']))
				die_error('given date does not exist: '.$query['date'].' (format is YY-MM-DD)');
		}
		
		if (is_dated_mode($smap['call'])){
			if ($bits && !is_numeric($bits[0]) && !is_mode($bits[0])){
				$query['id'] = strtoupper(array_shift($bits));
				
				if (!preg_match('#^([a-z0-9-_]+)$#i', $query['id']))
					die_error('bad id: '.htmlentities($query['id']));
					
				if (!($query['date'] = get_var('SELECT date FROM bulletins WHERE bulletin_schema = %s AND external_id = %s', array($query['schema'], $query['id']))))
					die_error('unknown id: '.htmlentities($query['id']));

			} else if (empty($query['date'])){
				if (!empty($_GET['url'])){
					if (!($query['url'] = filter_var($_GET['url'], FILTER_VALIDATE_URL)))
						die_error('bad url');
				} else
					$query['date'] = date('Y-m-d', strtotime('-1 day'));
			}
		}

		if (!empty($_GET['as']) && preg_match('#^([a-z0-9_]+)$#i', $_GET['as']))
			$query['type'] = $_GET['as'];
		else if (empty($query['type']) && !empty($query['id']))
			$query['type'] = 'document';

		if (in_array($smap['call'], array('parse', 'rewind', 'extract', 'spide')) && $query['followLevels'] < 1)
			$query['followLevels'] = 1;

		$query['allowProcessedCache'] = empty($_GET['noProcessedCache']) && !in_array($smap['call'], array('rewind', 'spide'));
		$smap['query'] = $query;
		
		// check schema
		if (!is_valid_schema_path($query['schema']))
			die_error('invalid schema');
		if (!($smap['schemaObj'] = get_schema($query['schema'])))
			die_error('no such schema '.$query['schema']);
			
		if ($smap['schemaObj']->type != 'bulletin' && !in_array($smap['call'], array('schema', 'soldiers', 'ambassadors'))){
			$smap['call'] = null;
			die_error();
		}

		switch ($smap['call']){

			case 'schema':
				if ($smap['page'] != 'bulletin')
					die_error();
					
				if (!empty($query['id']) || !empty($query['date']))
					die_error('id or date in arguments');

				if (!empty($smap['schemaObj']->providerId))
					$smap['schemaObj']->provider = get_provider_schema($smap['schemaObj']->providerId, true, false);

				return_wrap($smap['raw'] ? array(
					'success' => true,
					'query' => $query,
					'result' => $smap['schemaObj']
				) : get_schema($query['schema'], true));
				exit();

			case 'fetch':
			case 'lint':
			case 'download':
			case 'redirect':
				if ($smap['page'] != 'bulletin')
					die_error();
					
				$bulletinFetcher = new BulletinFetcher();
				$bulletin = $bulletinFetcher->fetch_bulletin($query, $smap['call'] == 'redirect');//, $smap['call'] == 'lint' ? '.parsed.json' : false);

				if (is_error($bulletin))
					die_error($bulletin);

				// iframe inside API
				if (!IS_CLI && in_array($smap['call'], array('fetch', 'lint')) && !$smap['raw']){
					$smap['isIframe'] = true;

					return_wrap('<iframe class="bulletin-iframe" src="'.current_url(true).'/raw?loadCSS=1" onload="if (smapGetChromeVersion()) {smapRedrawElement(this,100)}"></iframe>'); // attempt to fix Chromium bug displaying XML in iframes

				} else {
					$content = $bulletinFetcher->serve_bulletin($bulletin, $smap['call'], get_schema_title($smap['schemaObj'], $query), $query);

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
						return_wrap($content);

				}
				exit();

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
					die_error($ret->msg);
				}

				if ($smap['call'] == 'extract'){
					$extracter = new BulletinExtractor($ret);
					$ret = $extracter->extract($smap['query'], is_admin() && !empty($_GET['save']));
					$smap['apiResultPreview'] = $extracter;
				}

				unlock($lock);
				return_wrap(array(
					'success' => true,
					'query' => $query,
					'result' => $ret
				));
				exit(0);

			case 'rewind':
			
				if ($smap['page'] != 'bulletin')
					die_error();
					
				if (IS_CLI){
					define('SMAP_FORCE_OUTPUT', true);

					$smap['spiderConfig'] = array(
						'schema' => $smap['query']['schema'],
						'status' => 'manual',
						'dateBack' => !empty($smap['query']['date']) ? $smap['query']['date'] : date('Y-m-d', strtotime('-2 days')),
						'extract' => !empty($smap['extract']),
					) + get_default_spider_config(false);

					define('KAOS_SPIDER_ID', 0);
					require(APP_PATH.'/spider/spider.php');
					exit(0);
				} 
				print_page('rewind');
				exit(0);
				
			case 'soldiers':
				print_page('soldiers');
				exit;
		}
		die_error('unknown call');
	}
}

