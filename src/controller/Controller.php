<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net>
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

class Controller {
	
	public function exec(){
		cleanLocks(KAOS_DEBUG && !empty($_GET['clean_locks']));
		
		global $kaosCall;
		kaosCallInit();
		
		// first, check if it's an ajax request
		if (!empty($_POST['action']) && preg_match('#^[a-z0-9_]+$#i', $_POST['action'])){
			$fn = 'kaosAjax'.ucfirst($_POST['action']);
			if (function_exists($fn)){
				define('IS_AJAX', true);
				
				if (!empty($_POST['session']['query']))
					$kaosCall['query'] = $_POST['session']['query'];
				if (isset($kaosCall['query']['schema']))
					$kaosCall['schemaObj'] = kaosGetSchema($kaosCall['query']['schema']);
				
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
		
		if (!($curUrl = kaosCurrentURL())){
			// CLI help (no argument)
			require_once APP_PATH.'/api/Api.php';
			require_once APP_PATH.'/templates/CLIRoot.php';
			kaosPrintCLIRoot();
			exit();
		}
		
		$bits = array();
		foreach (explode('/', preg_replace('#^(.*?)(/?)(\?.*)?$#', '$1', str_replace(BASE_URL, '', $curUrl))) as $bit)
			if (trim($bit) != '')
				$bits[] = trim($bit);

		if ($bits && $bits[count($bits)-1] == 'raw'){
			$kaosCall['raw'] = true;
			array_pop($bits);
		}
		
		global $kaosPage;
		$kaosPage = $bits ? array_shift($bits) : 'browser';
		
		if (KAOS_IS_INSTALL)
			$kaosPage = 'install';

		else if (preg_match('#^[a-z]{2}$#', $kaosPage)){
			$kaosCall['query']['country'] = $kaosPage;
			$kaosPage = $bits ? array_shift($bits) : 'browser';
		}
		
		$kaosCall['currentQuery'] = !empty($_GET['q']) ? $_GET['q'] : '';
		
		switch ($kaosPage){
				
			case 'logout':
			case 'login':
				if ($kaosPage == 'login' && !ALLOW_LOGIN)
					kaosDie();

				$_SESSION['kaos_authed'] = $kaosPage == 'login' ? 1 : 0; // TODO: implement a login form/system
				header('Location: '.(!empty($_GET['redirect']) ? $_GET['redirect'] : BASE_URL));
				exit();
				
			case 'settings':
				if (!isAdmin())
					kaosDie('Operation forbidden');

			case 'install':
				if (!KAOS_IS_INSTALL)
					kaosDie();
					
				require APP_PATH.'/templates/'.ucfirst($kaosPage).'.php';
				exit();
				
			case 'daemon':
				if (!KAOS_IS_CLI)
					kaosDie('This daemon is only for CLI purpose');
					
				if (!empty($kaosCall['cliArgs']) && count($kaosCall['cliArgs']) > 1 && $kaosCall['cliArgs'][1] == 'stop'){
					// stopping smoothly
					
					query('UPDATE spiders SET status = "stopped" WHERE status = "stopping"');
					query('UPDATE spiders SET status = "waiting" WHERE status = "active"');
					
					$i = 0;
					while (true){
						$count = 0;
						foreach (getCol('SELECT pid FROM workers') as $pid)
							if (isActivePid($pid))
								$count++;
								
						if (!$count)
							break;
						
						if (!$i)
							echo 'Stopping the StateMapper daemon..'.PHP_EOL;
						echo $count.' workers remaining..'.PHP_EOL;
						sleep(5);
						$i++;
					}
					echo 'All workers stopped'.PHP_EOL;
					exit(0);
				}
				
				// clean spiders
				foreach (query('SELECT id, pid FROM spiders WHERE status = "active"') as $w)
					if (!isActivePid($w['pid']))
						query('UPDATE spiders SET status = "waiting" WHERE id = %s AND status = "active"', $w['id']);
				
				// clean workers		
				foreach (query('SELECT id, pid FROM workers') as $w)
					if (!isActivePid($w['pid']))
						query('DELETE FROM workers WHERE id = %s', $w['id']);
						
				// run spiders at needs
				$pids = array();
				while (true){
					foreach (query('SELECT id, bulletin_schema FROM spiders WHERE status = "waiting"') as $spider){
						if ($lock = waitForLock('spider-'.$spider['bulletin_schema'], 1)){
							
							query('UPDATE spiders SET status = "active" WHERE bulletin_schema = %s', $spider['bulletin_schema']);
							unlock($lock);
							
							$kaosCall['query']['schema'] = $spider['bulletin_schema'];
						
							// throw a spider
							connexionClose(); // leave this just before forking!
							$pid = pcntl_fork(); 
							if (!$pid){ 
								define('KAOS_SPIDER_ID', $spider['id']);
								//kaosPrintLog('spider '.$spider['bulletin_schema'].' started', array('color' => 'lgreen', 'spider_schema' => $spider['schema']));

								// in a spider
								require(APP_PATH.'/spider/spider.php');
								exit(0);
							}
							
							// in the parent script
							query('UPDATE spiders SET pid = %s WHERE bulletin_schema = %s AND status = "active"', array($pid, $spider['bulletin_schema']));
							$pids[$pid] = $spider['bulletin_schema'];
						}
					}
					
					sleep(5);
					
					$begin = time();
					while (($pid = pcntl_waitpid(0, $status, WNOHANG)) != -1){ // -1 forno more pid to wait for
						if ($pid === 0) // get 0 for no pid returned
							break;
							
						$status = pcntl_wexitstatus($status); 
						$schema = $pids[$pid];
						if ($status !== 0){
							update('spiders', array('status' => 'failed'), array('bulletin_schema' => $query['schema']));
							kaosPrintLog('spider '.$schema.' crashed', array('color' => 'red', 'spider_schema' => $schema));
						}
						unset($pids[$pid]);
						
						if (time() - $begin > 20) // allow many spiders at once
							break;
						
						$countPids = array_filter($pids, function($x){ 
							return !empty($x); 
						});
						if (!$countPids)
							break;
					} 
				}
				//echo getmypid(); // to fill the daemon lock
				exit(0);
			
			case 'api':
				// reroute to API class
				require_once APP_PATH.'/api/Api.php';
				$api = new BulletinAPI();
				$api->call($bits);
				kaosDie('bad call');
				
			case 'browser':

				$ret = array('results' => array(), 'resultsCount' => 0);
				
				if (!empty($kaosCall['currentQuery']) || hasFilter()){
					$etype = !empty($_GET['etype']) ? explode(' ', $_GET['etype']) : null;
					$loc = null;
					if (!empty($_GET['loc'])){
						$loc = array();
						foreach (explode(' ', $_GET['loc']) as $l){
							$l = explode(':', $l);
							$loc[] = array(
								'country' => array_shift($l),
								'state' => array_shift($l),
								'county' => array_shift($l),
								'city' => array_shift($l),
							);
						}
					}
					$ret['query'] = $kaosCall['query'] = array(
						'query' => !empty($kaosCall['currentQuery']) ? $kaosCall['currentQuery'] : null,
						'etypes' => $etype,
						'locations' => $loc,
						'limit' => min(200, !empty($_GET['limit']) ? intval($_GET['limit']) : 77),
						'misc' => !empty($_GET['misc']) ? $_GET['misc'] : null,
					);
					$ret['results'] = kaosSearchResults($ret['query'], $ret['resultsCount']);
				}
				$kaosCall += $ret;
				
				if (!empty($kaosCall['raw'])){
					foreach ($ret['results'] as &$e){
						$e['icon'] = kaosGetEntityIcon($e);
						$e['label'] = kaosGetEntityTitle($e);
						$e['url'] = kaosGetEntityUrl($e);
					}
					unset($e);
					kaosAPIReturn(array('success' => true) + $ret);
				}
				echo kaosGetTemplate('Browser');
				exit();

			case 'person':
			case 'company':
			case 'institution':
				if (($entityId = array_shift($bits)) && !empty($kaosCall['query']['country']) && ($entity = kaosGetEntityBySlug($entityId, $kaosPage, $kaosCall['query']['country']))){
					
					$entity['summary'] = kaosGetEntitySummary($entity);
					
					if (!empty($kaosCall['raw'])){
						
						$entity['icon'] = kaosGetEntityIcon($entity);
						$entity['label'] = kaosGetEntityTitle($entity);
						$entity['url'] = kaosGetEntityUrl($entity);
						
						kaosAPIReturn(array('success' => true, 'entity' => $entity));
					}
					
					$kaosCall['entity'] = $entity;
					echo kaosGetTemplate('Browser', array('entity' => $entity));
					exit(0);
				}
				break;
				
			case 'compile':
				if (!KAOS_IS_CLI)
					kaosDie();
				
				echo 'generating manuals..'.PHP_EOL;
				
				// TODO: generate .md tables from kaosGetStatusLabels() (after putting it to a JSON for pushes) with dynamic (fav?)icons thanks to http://php.net/manual/es/function.imageloadfont.php
				
				$count = 0;
				$statusTable = array();
				
				foreach (kaosGetStatusLabels() as $type => $c)
					foreach ((array) $c as $action => $cc){
						$required = array();
						if (isset($cc->required))
							foreach ($cc->required as $k => $l)
								$required[] = $k.': '.$l;
								
						$icon = '';
						if (!empty($cc->icon))
							$icon = $cc->icon;
						$statusTable[] = '| '.($icon ? '<img src="https://statemapper.net/src/addons/fontawesome_favicons/'.$icon.'.ico" valign="middle" />' : '').' | ```'.$type.'``` | ```'.$action.'``` | '.(isset($cc->meaning) ? $cc->meaning : '').' | '.implode("<br>", $required).' |';
					}
				
				$statusTable = '
| | Type | Action | Meaning | Required attributes |
| ---- | ---- | ----- | ----- | ---- |
'.implode("\n", $statusTable).'
';
				
				foreach (kaosLsdir(BASE_PATH.'/documentation/manuals/templates') as $file)
					if (preg_match('#^(.*)\.tpl\.md$#iu', $file, $fileParts)){
						$content = file_get_contents(BASE_PATH.'/documentation/manuals/templates/'.$file);
						if (preg_match_all('#\{\s*(Include(?:Inline)?)\s+([a-z0-9_-]+)(?:\((.*?)\))?\s*\}#ius', $content, $matches, PREG_SET_ORDER)){
							foreach ($matches as $m){
								
								$inputParams = array();
								if (isset($m[3])){
									$inputParamsStr = trim($m[3]);
									if ($inputParamsStr != '')
										foreach (explode(', ', $inputParamsStr) as $cinputParams)
											$inputParams[] = str_replace(array('\\(', '\\)'), array('(', ')'), trim($cinputParams));
								}
								
								$subfile = $m[2].'.part.md';
								if (!file_exists($path = BASE_PATH.'/documentation/manuals/parts/'.$subfile))
									kaosDie('missing '.$subfile);
									
								$part = file_get_contents($path);
								if (preg_match_all('#\{\s*\$([0-9]+)\s*\}#ius', $part, $vars, PREG_SET_ORDER))
									foreach ($vars as $var){
										$i = intval($var[1]);
										$part = str_replace($var[0], isset($inputParams[$i-1]) ? $inputParams[$i-1] : '', $part);
									}
								
								if (strtolower($m[1]) == 'includeinline')
									$part = preg_replace( "/\r|\n/", "", $part);
								$content = str_replace($m[0], $part, $content);
							}
						}
						$content = str_replace('{StatusTable}', $statusTable, $content);
						if ($content != ''){
							@unlink(BASE_PATH.'/documentation/manuals/'.$fileParts[1].'.md');
							if (!file_put_contents(BASE_PATH.'/documentation/manuals/'.$fileParts[1].'.md', $content))
								kaosDie('can write documentation/manuals/'.$fileParts[1].'.md');
							echo 'documentation/manuals/'.$fileParts[1].'.md'.PHP_EOL;
							$count++;
						}
					}
				
				echo 'generated '.number_format($count, 0).' manuals'.PHP_EOL;
				exit(0);
		}
		
		kaosDie('bad call');
	}
}

function kaosAjaxRefreshMap($args){
	$vars = array(
		'currentYear' => $args['year'],
		'extract' => !empty($args['extract']) && $args['extract'] !== 'false',
	);
	return array('success' => true, 'html' => kaosGetTemplate('APIMapYear', $vars));
}

function kaosGetTemplate($id, $vars = array()){
	return include(APP_PATH.'/templates/'.$id.'.php');
}

function cleanLocks($all = false){
	if (KAOS_IS_INSTALL)
		return;
		
	static $lastCleaned = null;
	if ($all)
		query('DELETE FROM locks');
	
	else if (!$lastCleaned || $lastCleaned < time() - 60){ // clean every minute top
		$lastCleaned = time();
		query('DELETE FROM locks WHERE created < %s', array(date('Y-m-d H:i:s', time() - (max(MAX_EXECUTION_TIME, 900) + 60)))); // clean after max(MAX_EXECUTION_TIME, 15min) + 1 minute
	}
}

function kaosAjaxSearch($args){
	$count = 0;
	$results = kaosSearchResults(array(
		'query' => $args['query'],
		'limit' => 30,
	), $count);
	
	ob_start();
	if ($results){
		foreach ($results as $r){
			$title = kaosGetEntityTitle($r, true);
			foreach (explode(' ', $args['query']) as $w){
				$title = preg_replace('#'.preg_quote(htmlentities($w), '#').'#ius', '<b>$0</b>', $title);
				$title = preg_replace('#<b>([^<]*)<b>([^<]*)</b>([^<]*)</b>#ius', '<b>$1$2$3</b>', $title);
			}
			?>
			<div><a href="<?= kaosGetEntityUrl($r) ?>"><i class="fa fa-<?= kaosGetEntityIcon($r) ?>"></i><span><?= $title ?><?php if ($r['subtype'] || $r['country']){ ?><span class="searchSugg-sugg-metas<?php if ($r['subtype']) echo ' search-sugg-flag-more'; ?>"><?= ($r['subtype'] ? $r['subtype'] : '') ?><?= ($r['country'] ? '<img class="sugg-flag" src="'.kaosGetFlagUrl($r['country']).'" />' : '') ?></span><?php } ?></span></a></div>
			<?php
		}
		$html = ob_get_clean();
		
		ob_start();
		?>
		<input type="submit" class="searchsugg-results-show" value="<?= esc_attr(sprintf(_('Show all %s results'), number_format($count, 0))) ?>" />
		<?php
		$more = ob_get_clean();
	} else {
		$html = '<div class="kaos-results-none">Nothing found</div>';
		$more = false;
	}
	
	return array('success' => true, 'results' => $html, 'resultsMore' => $more);
}

function kaosAjaxDeleteExtractedData($args){
	if (!isAdmin())
		kaosDie();
		
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
		
		/* TODO: implement hard-reset button
		'bulletins',
		'bulletin_uses_bulletin',
		'spiders',
		'workers',
		* */
	);
	$error = 0;
	foreach ($tables as $table)
		if (!query('TRUNCATE '.$table))
			$error++;
	
	cleanLocks(true);
	query('UPDATE bulletins SET status = "fetched" WHERE status IN ( "extracting", "extracted" )');
		
	return array('success' => true, 'msg' => 'Tables '.implode(', ', $tables).' were '.($error ? 'emptied with '.$error.' errors' : 'successfuly empties'));
}

function kaosAjaxLoadStatuses($args){
	
	if (empty($args['related']) || empty($args['related']['id']) || !($target = kaosGetEntityById($args['related']['id'])))
		return 'Bad id';
	
	$statuses = kaosQueryStatuses($args['related']);

	ob_start();
	echo '<div class="entity-stat-children">';
	kaosPrintStatuses($statuses, $target, $args['related']['id'], isset($args['related']['date']) ? array('date' => $args['related']['date']) : array());
	echo '</div>';

	return array('success' => true, 'html' => ob_get_clean());
}

function kaosAjaxStatusAction($args){
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
					if (empty($args['related']['status_id']) || !($status = getRow('SELECT * FROM statuses WHERE id = %s', $args['related']['status_id'])))
						return 'Bad status id';
					
					$a = $status['amount'] ? kaosGetAmount($status['amount']) : null;
					
					$arg3 = null;
					if (!empty($status['target_id'])){
						$arg3 = kaosGetEntityById($status['target_id']);
						$arg3 = $arg3 ? kaosGetEntityTitle($arg3, true) : $arg3;
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
					if (empty($args['related']['id']) || !($entity = kaosGetEntityById($args['related']['id'])))
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
