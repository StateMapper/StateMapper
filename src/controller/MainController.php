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
use \StateMapper\BulletinController as BulletinController;

if (!defined('BASE_PATH'))
	die();

class MainController {
	
	public function init(){
		
		// clean
		clean_tables(IS_DEBUG && !empty($_GET['clean_tables']));
		
		// repair
		if (is_admin() && IS_DEBUG && !empty($_GET['repair_slugs']))
			repair_location_slugs();
		
	}
	
	public function get_route($bits){
		global $smap;	
		
		if (!empty($smap['filters']['q']))
			$smap['query']['q'] = $smap['filters']['q'];

		// handle ajax requests
		handle_ajax();
		
		// detect if it's an API call (.[format] at the end of the URL). Only json supported at this time.
		if ($bits && (IS_CLI || in_array($bits[0], array('api', 'api.json'))) && preg_match('#^(.+)(\.(json))$#', $bits[count($bits)-1], $m)){
			define('IS_API', true);
			
			// check api rates
			if ($ip = is_rate_limited()){
				$count = get_var('SELECT COUNT(*) FROM api_rates WHERE ip = %s AND date > %s', array($ip, date('Y-m-d H:i:s', strtotime('-'.API_RATE_PERIOD))));
				
				$smap['rateLimit'] = array(
					'count' => $count,
					'limit' => API_RATE_LIMIT,
					'period' => API_RATE_PERIOD,
				);
				
				if ($count >= API_RATE_LIMIT)
					die_error('API rate-limit exceeded: '.$count.' on '.API_RATE_LIMIT.' within '.API_RATE_PERIOD);
				
				// insert this call
				insert('api_rates', array(
					'ip' => $ip,
					'date' => date('Y-m-d H:i:s'),
				));
			}
			
			if (in_array($bits[0], array('api', 'api.json')) && !IS_CLI)
				array_shift($bits);
			$smap['raw'] = $m[3]; // store format into 'raw'
			if ($m[1] != 'api')
				$bits[count($bits)-1] = $m[1]; 
		
		// default API settings
		} else {
			
			define('IS_API', false);
			if ($bits && $bits[count($bits)-1] == 'raw'){
				$smap['raw'] = true;
				array_pop($bits);
			
			} 
		}

		$smap['human'] = !empty($smap['filters']['human']) || (IS_CLI && empty($smap['raw']));
		
		// force to install if defined so
		if (IS_INSTALL){ 
			if (IS_CLI)
				die('Please visit '.BASE_URL.' to complete the installation process.');
				
			$smap['page'] = 'install';
			return array(
				'page' => 'install'
			);
		}
	
		if ($bits && preg_match('#^[a-z]{2,3}$#i', $bits[0]) && is_country($bits[0])){
			$country = $smap['filters']['loc'] = strtolower(urldecode(array_shift($bits)));
			if ($bits && !is_mode($bits[0]) && !is_valid_date($bits[0])){
				if ($loc_id = get_location_id_by_slug(urldecode($bits[0]), 'states', $country))
					$smap['filters']['loc'] .= '/'.array_shift($bits);
			}
		}
		
		$smap['filters']['loc'] = !empty($smap['filters']['loc']) ? urldecode($smap['filters']['loc']) : null;
			
		$smap['page'] = $bits ? array_shift($bits) : 'browser';
		
		// detect pages such as /(institution|company|person)
		foreach (get_entity_types() as $etype => $c)
			if ($smap['page'] == $c['slug']){
				$smap['page'] = 'browser';
				
				$smap['filters']['etype'] = $etype;
				if ($bits){
					if (count($bits) < 2)
						array_unshift($bits, $smap['filters']['loc']);
					$smap['filters']['etype'] .= '/'.implode('/', $bits);
					$bits = array();
				}
				break;
			}
			
		
		$smap['page'] = apply_filters('page', $smap['page'], $bits);
		do_action('redirect');
		
		// print CLI help if no argument
		if (IS_CLI && !current_url() && !is_search())
			return array(
				'template' => 'cli',
			);
		
		switch ($smap['page']){
			
			// CLI-only methods
			case 'daemon':
			case 'admin':
			case 'compile':
			
				if (!IS_CLI)
					return false;
				require APP_PATH.'/helpers/'.$smap['page'].'.php';
				return call_user_func('\\StateMapper\\'.$smap['page']);
			
			case 'spiders':
				if (!IS_CLI)
					return false;
					
				if (!($spiders = query('SELECT * FROM spiders')))
					return 'no spider configured so far';
				
				echo PHP_EOL;
				echo '   '.str_pad('SCHEMA', 12, ' ');
				echo '   '.str_pad('STATUS', 20, ' ');
				echo '   '.str_pad('DATE_BACK', 13, ' ');
				echo '   '.str_pad('EXTRACT', 16, ' ');
				echo '   '.str_pad('MAX_WORKERS', 15, ' ');
				echo '   '.str_pad('MAX_CPU_RATE', 16, ' ');
				echo PHP_EOL;
				echo ' | '.str_pad('', 12, '-');
				echo ' | '.str_pad('', 20, '-');
				echo ' | '.str_pad('', 13, '-');
				echo ' | '.str_pad('', 16, '-');
				echo ' | '.str_pad('', 15, '-');
				echo ' | '.str_pad('', 16, '-');
				echo PHP_EOL;
				 
				foreach ($spiders as $s){
					echo ' | '.str_pad($s['bulletin_schema'], 12, ' ');
					echo ' | '.str_pad($s['status'].' ('.($s['pid'] && is_active_pid($s['pid']) ? 'PID '.$s['pid'] : 'inactive').')', 20, ' ');
					echo ' | '.str_pad($s['date_back'], 13, ' ');
					echo ' | '.str_pad($s['extract'] ? '1' : '0', 16, ' ');
					echo ' | '.str_pad($s['max_workers'], 15, ' ');
					echo ' | '.str_pad($s['max_cpu_rate'], 16, ' ');
					echo PHP_EOL;
				}
				echo PHP_EOL;
					
				exit(0);
				
			case 'spider':
				if (!IS_CLI)
					return false;
				$args = $smap['cli_args'];
				if ($args && $args[0] == 'spider')
					array_shift($args);
				$schema = array_shift($args);
				if (!$schema)
					return 'missing schema';
				$schema = strtoupper($schema);
				if (!is_valid_schema_path($schema) || !($s = get_schema(strtoupper($schema))))
					return 'missing schema '.$schema;
				
				if (!$args)
					return 'missing command';
				$cmd = strtolower(array_shift($args));
				
				switch ($cmd){
					
					case 'turn':
						$on = array_shift($args);
						if (!in_array($on, array('on', 'off')))
							return 'bad command '.$on;
							
						if (($error = toggle_spider_status($schema, $on == 'on')) !== true)
							return $error;
						
						echo 'Spider '.$schema.' turned '.$on.PHP_EOL;
						exit(0);
						
					case 'config':
						$var = array_shift($args);
						if ($var === null)
							return 'missing var';
						$value = array_shift($args);
						if ($value === null)
							return 'missing value';
						
						$error = set_spider_config($schema, strtolower($var), strtolower($value));
						if ($error !== true)
							return $error;
						echo 'Variable '.$var.' set to '.$value.' for spider '.$schema.PHP_EOL;
						exit(0);
						
				}
						
				return 'bad spider command';
			
			case 'bulletins':
				$smap['page'] = 'bulletin';
				$smap['call'] = 'rewind';
				
				if (IS_CLI && $bits && in_array($bits[count($bits)-1], array('extract'))){
					array_pop($bits);
					$smap['extract'] = true;
				
				} 
				
			case 'schema':
				if ($smap['page'] == 'schema'){
					$smap['page'] = 'bulletin';
					$smap['call'] = 'schema';
				}
			
			case 'provider':
				if ($smap['page'] == 'provider'){
					$smap['page'] = 'bulletin';
					
					if ($bits && in_array($bits[count($bits)-1], array('schema', 'soldiers')))
						$smap['call'] = $bits[count($bits)-1];
					else
						$smap['call'] = 'schema';
				}
				
			case 'providers':
			case 'bulletin':
			
				// reroute to BulletinController
				require_once 'BulletinController.php';
				$c = new BulletinController();
				return $c->route($bits);
				
			// countries' (and providers'?) soldiers
			case 'soldiers':
				$smap['page'] = 'bulletin';
				$smap['call'] = 'soldiers';
				
				$schema = get_schema(!empty($smap['query']['schema']) ? $smap['query']['schema'] : $smap['filters']['loc']);
				$soldiers = get_schema_prop($schema, 'soldiers', true);

				return array(
					'page' => 'soldiers', 
					'vars' => array(
						'soldiers' => $soldiers
					),
					'template_vars' => array(
						'schema' => $schema
					),
				);

			case 'ambassadors':
				$schema = get_schema($smap['filters']['loc']);
				$ambassadors = get_schema_prop($schema, 'ambassadors', true);
				
				return array(
					'page' => 'ambassadors', 
					'vars' => array(
						'ambassadors' => $ambassadors,
					), 
					'template_vars' => array(
						'schema' => $schema,
					),
				);
				
			case 'browser':
				$res = get_results();
				return array(
					'page' => is_home() ? 'home' : 'browser', 
					'vars' => array(
						'results' => $res,
					),
				);
			
			// entity page
			case 'entity':
			case 'person':
			case 'company':
			case 'institution':
				$etype = $smap['page'];
				$smap['page'] = 'browser';
			
				// retrieve targeted entity
				if (
					($entity_slug = array_shift($bits)) 
					&& ($etype == 'entity' || !empty($smap['filters']['loc']))
					&& ($entity = $etype == 'entity' 
						? get_entity_by_id($entity_slug) 
						: get_entity_by_slug($entity_slug, $etype, $smap['filters']['loc'])
					)
				){

					
					// @todo: put summaries into entity
					// $entity['summary'] = get_entity_summary($entity, 'sheet');
					$smap['entity'] = $entity;
					
					$entity['summary'] = get_entity_summary($entity);
					$entity['activity'] = get_entity_activity($entity, true);

					return array(
						'page' => 'entity', 
						'vars' => array(
							'entity' => $entity,
						),
					);
				}
				break;
				
			case 'logout':
				if (IS_CLI)
					return false;
				
				logout_do();
				return array(
					'redirect' => add_lang(!empty($_REQUEST['redirect']) ? $_REQUEST['redirect'] : url()),
				);
				
			case 'settings':
				if (!is_admin() || IS_CLI)
					return false;
				return array(
					'page' => 'settings',
				);
				
			case 'api':
				if (IS_CLI)
					return false;
				return array(
					'page' => 'api_root',
				);
				
			case 'lists':
				if (!is_logged())
					return array(
						'redirect' => add_url_arg('redirect', current_url(), url(null, 'login')),
					);
					
				$lists = get_my_lists(true);
				return array(
					'page' => 'lists', 
					'vars' => array(
						'lists' => $lists,
					),
				);
				
			case 'test':
				if (!is_admin() || !$bits || !preg_match('#^[a-z0-9_]+$#i', $bits[0]))
					return false;
					
				if (file_exists($path = APP_PATH.'/tests/'.$bits[0].'.php')){
					require_once APP_PATH.'/helpers/tests.php';
					return array(
						'route' => $path,
					);
				}
				break;
		}
		
		if (apply_filters('page_'.urlencode($smap['page']), false, $bits))
			exit(0);
			
		return 'bad call';
	}
	
	function exec($cmd){
		
		if (!$cmd)
			die_error();
		else if (is_string($cmd))
			die_error($cmd);
		else if (is_error($cmd))
			die_error($cmd);
			
		if (!empty($cmd['redirect']))
			redirect($cmd['redirect']);
		else if (!empty($cmd['page']))
			print_page($cmd['page'], isset($cmd['vars']) ? $cmd['vars'] : array(), isset($cmd['template_vars']) ? $cmd['template_vars'] : array());
		else if (!empty($cmd['template']))
			print_template($cmd['template'], isset($cmd['vars']) ? $cmd['vars'] : array(), isset($cmd['template_vars']) ? $cmd['template_vars'] : array());
		else if (!empty($cmd['route']))
			require_once $cmd['route'];
		else if (!empty($cmd['result']))
			return_wrap($cmd['result']);
		
		die_error();
	}
}
