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

class MainController {
	
	public function route($bits){
		global $smap;		

		// clean
		clean_tables(IS_DEBUG && !empty($_GET['clean_tables']));
		
		// repair
		if (is_admin() && IS_DEBUG && !empty($_GET['repair_slugs']))
			repair_location_slugs();
		
		// check if it's an ajax request
		if (!empty($_POST['action']) && preg_match('#^[a-z0-9_]+$#i', $_POST['action'])){
			
			$fn = 'smap_ajax_'.preg_replace_callback('#[A-Z]#', function($m){
				return '_'.strtolower($m[0]);
			}, $_POST['action']);
			
			if (function_exists($fn)){
				define('IS_AJAX', true);
				
				if (!empty($_POST['session']['query']))
					$smap['query'] = $_POST['session']['query'];
				if (!empty($_POST['session']['filters']))
					$smap['filters'] = $_POST['session']['filters'];
				if (isset($smap['query']['schema']))
					$smap['schemaObj'] = get_schema($smap['query']['schema']);
				
				// exec ajax function
				$ret = call_user_func($fn, $_POST);
				if ($ret === true)
					$ret = array('success' => true);
				else if (is_string($ret))
					$ret = array('success' => false, 'error' => $ret);
				echo json_encode($ret);
				exit();
			}
		}
		define('IS_AJAX', false);
		
		if (IS_CLI && !current_url()){
			
			// print CLI help if no argument
			print_template('cli');
			exit();
		}
		
		// detect if it's an API call (.[format] at the end of the URL). Only json supported at this time.
		if ($bits && (IS_CLI || $bits[0] == 'api') && preg_match('#^(.+)(\.(json))$#', $bits[count($bits)-1], $m)){
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
			
			if ($bits[0] == 'api' && !IS_CLI)
				array_shift($bits);
			$smap['raw'] = $m[3]; // store format into 'raw'
			if ($m[1] != 'api')
				$bits[count($bits)-1] = $m[1]; 
		
		} else
			define('IS_API', false);
		
		// force to install if decided so
		if (IS_INSTALL){ 
			if (IS_CLI)
				die('Please visit '.BASE_URL.' to complete the installation process.');
				
			$smap['page'] = 'install';
			if (!IS_INSTALL)
				die_error();
			print_page('install');
			exit();
		}
	
		if ($bits && preg_match('#^[a-z]{2,3}$#i', $bits[0]) && is_country($bits[0]))
			$smap['filters']['loc'] = strtolower(array_shift($bits));
		
		// build filters
		if (!empty($_GET))
			$smap['filters'] += $_GET;
		if (empty($smap['filters']['q']))
			$smap['filters']['q'] = '';
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
			
		switch ($smap['page']){
			case 'daemon':
				if (IS_CLI){
					require APP_PATH.'/daemon/daemon.php';
					exit();
				} else 
					die_error();
			
			case 'bulletins':
				$smap['page'] = 'bulletin';
				$smap['call'] = 'rewind';
				if (IS_CLI && $bits && in_array($bits[count($bits)-1], array('extract'))){
					$smap['call'] = 'rewind';
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
				require 'BulletinController.php';
				$c = new BulletinController();
				$c->route($bits);
				exit;
				
			case 'soldiers':
				$smap['page'] = 'bulletin';
				$smap['call'] = 'soldiers';
				print_page('soldiers');
				exit;
			
			case 'ambassadors':
				print_page('ambassadors');
				exit;
				
			case 'browser':
				load_search_results();
				
				if (!empty($smap['raw'])){
					foreach ($smap['results'] as &$e){
						$e['icon'] = get_entity_icon($e);
						$e['label'] = get_entity_title($e);
						$e['url'] = get_entity_url($e);
					}
					unset($e);
					return_wrap(array('success' => true, 'results' => $smap['results']));
				}
				print_template('browser');
				exit();

			case 'person':
			case 'company':
			case 'institution':
			
				// retrieve targeted entity
				if (($entityId = array_shift($bits)) && !empty($smap['filters']['loc']) && ($entity = get_entity_by_slug($entityId, $smap['page'], $smap['filters']['loc']))){
					
					$entity['summary'] = get_entity_summary($entity);

					$entity['icon'] = get_entity_icon($entity);
					$entity['label'] = get_entity_title($entity);
					$entity['url'] = get_entity_url($entity);
					$smap['entity'] = $entity;

					if (!empty($smap['raw']))
						return_wrap(array('success' => true, 'entity' => $entity));
					
					print_template('browser', array('entity' => $entity));
					exit(0);
				}
				break;
				
			// web-only methods
			case 'login':
				if (!ALLOW_LOGIN)
					die_error();
					
			case 'logout':
				if (IS_CLI)
					die_error();

				$_SESSION['smap_authed'] = $smap['page'] == 'login' ? 1 : 0; // TODO: implement a login form/system
				redirect(add_lang(!empty($_GET['redirect']) ? $_GET['redirect'] : url()));
				
			case 'settings':
				if (!is_admin() || IS_CLI)
					die_error();
				print_template('settings');
				exit();
				
			case 'api':
				if (IS_CLI)
					die_error();
				print_template('api_root');
				exit();
			
			// CLI-only methods
			case 'compile':
			case 'export':
				if (!IS_CLI)
					die_error();
				require APP_PATH.'/helpers/'.$smap['page'].'.php';
				call_user_func($smap['page']);
				exit(0);
		}
		
		die_error('bad call');
	}
}

function smap_ajax_refresh_map($args){
	$vars = array(
		'currentYear' => $args['year'],
		'extract' => !empty($args['extract']) && $args['extract'] !== 'false',
	);
	return array('success' => true, 'html' => get_template('rewind', $vars));
}


function smap_ajax_search($args){
	$count = 0;
	$results = query_entities(array(
		'q' => $args['query'],
		'limit' => 30,
	), $left);
	
	ob_start();
	if ($results){

		$vars = array('results' => $results, 'args' => $args, 'count' => count($results) + $left);
		$html = get_template('parts/autocomplete_results', $vars);
		$more = get_template('parts/autocomplete_footer', $vars);
		
	} else {
		$html = '<div class="results-none">Nothing found</div>';
		$more = false;
	}
	
	return array('success' => true, 'results' => $html, 'resultsMore' => $more);
}

function smap_ajax_delete_extracted_data($args){
	if (!is_admin())
		die_error();
		
	$tables = array(
		'entities', 
		'precepts', 
		'statuses', 
		'status_has_service', 
		'amounts', 
		'locations',
		'location_states',
		'location_counties',
		'location_cities',
		
		/* TODO: implement several hard-reset button
		'bulletins',
		'spiders',
		'workers',
		* */
	);
	$error = 0;
	foreach ($tables as $table)
		if (!query('TRUNCATE '.$table))
			$error++;
	
	clean_tables(true);
	query('UPDATE bulletins SET status = "fetched" WHERE status IN ( "extracting", "extracted" )');
		
	return array('success' => true, 'msg' => 'Tables '.implode(', ', $tables).' were '.($error ? 'emptied with '.$error.' errors' : 'successfuly empties'));
}

function smap_ajax_load_statuses($args){
	
	if (empty($args['related']) || empty($args['related']['id']) || !($target = get_entity_by_id($args['related']['id'])))
		return 'Bad id';
	
	$statuses = query_statuses($args['related']);

	ob_start();
	echo '<div class="entity-stat-children">';
	print_statuses($statuses, $target, $args['related']['id'], isset($args['related']['date']) ? array('date' => $args['related']['date']) : array());
	echo '</div>';

	return array('success' => true, 'html' => ob_get_clean());
}

function smap_ajax_status_action($args){
	if (empty($args['related']))
		return 'Bad id';
	if (empty($args['status_action']))
		return 'Bad action';
	
	$abits = explode(':', $args['status_action']);
	switch (array_shift($abits)){
		
		case 'markAsBuggy':
			$bug = null;
			switch ($abits ? $abits[0] : ''){
				
				case 'status':
					if (empty($args['related']['status_id']) || !($status = get_row('SELECT * FROM statuses WHERE id = %s', $args['related']['status_id'])))
						return 'Bad status id';
					
					$a = $status['amount'] ? get_amount($status['amount']) : null;
					
					$arg3 = null;
					if (!empty($status['target_id'])){
						$arg3 = get_entity_by_id($status['target_id']);
						$arg3 = $arg3 ? get_entity_title($arg3, true) : $arg3;
					}
					
					$bug = array(
						'type' => 'status',
						'related_id' => $status['id'],
						'arg1' => $status['type'],
						'arg2' => $status['action'],
						'arg3' => $arg3,
						'arg4' => $a && (is_object($a) || is_array($a)) ? serialize($a) : $a, // TODO: get real amount
						'arg5' => $status['note'],
					);
					break;
				
				case 'entity':	
					if (empty($args['related']['id']) || !($entity = get_entity_by_id($args['related']['id'])))
						return 'Bad entity id';
						
					$bug = array(
						'type' => 'entity',
						'related_id' => $entity['id'],
						'arg1' => $entity['type'],
						'arg2' => $entity['subtype'],
						'arg3' => $entity['name'],
						'arg4' => $entity['first_name'],
					);
					break;
			}
			if ($bug){
				// TODO: implement a bug tracking table and web UX. it must be very flexible, and not related to auto_incremeneted ids (because it's gonna remain through re-parsings!)
				
				debug($bug); die();
				insert('bugs', $bug);
				return array('success' => true);
			}
			break;
	}
	return 'Bad action';
}

function smap_ajax_load_more_results($args){
	global $smap;
	$smap['query']['loaded_count'] = $args['loaded_count'];
	$smap['query']['after_id'] = $args['after_id'];
	load_search_results();
	ob_start();
	print_template('parts/results');
	return array('success' => true, 'results' => ob_get_clean(), 'resultsLabel' => get_results_count_label(count($smap['results']) + $args['loaded_count'], $smap['resultsCount']));
}
