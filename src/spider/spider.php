<?php 
global $kaosCall;

if (!defined('BASE_PATH'))
	die();
	
/*
 * TODO: implement this vvvvvv
 * 
 * Spiders' CPU strategy:
 * 
 * if CPU is inferior by 15% and goal reached, increase goal by 10% (respecting CPU max)
 * if CPU is inferior by 3% to goal, throw 3 spiders at once then wait 20s. otherwise, wait 1min and recheck
 * if CPU is superior by 20% and no goal set or goal reached, decrease goal by 20%
 * if CPU is superior by 3% and no goal set or goal reached, decrease goal by 10%
 * 
 * 
 * 
 */

// spider script
$maxAttempts = 3;
$maxFixed = 3;

$query = $kaosCall['query'];
$config = !KAOS_SPIDER_ID ? $kaosCall['spiderConfig'] : getSpiderConfig(KAOS_SPIDER_ID);

/*
echo 'spider config: '.PHP_EOL;
print_r($kaosCall['query']);
print_r($config);
echo PHP_EOL.PHP_EOL;
*/

$workers = $config['workersCount'];


if (empty($kaosCall['query']['date']))
	$kaosCall['query']['date'] = $query['date'] = $config['dateBack'];

kaosPrintLog('spider starting with '.$workers.' workers back until '.$config['dateBack'].' (CPU rate: '.$config['cpuRate'].'%)', array('color' => 'lgreen', 'spider_id' => KAOS_SPIDER_ID));

$pids = array();
$lastCPUCheck = $last_recheck = $goal = null;
$overload = false;

$begin = $lastConfigReload = time();
while (true){
	$starting = time() - $begin < 120; // 2min startup mode
	
	// reload spider params every 15 seconds
	if (KAOS_SPIDER_ID && $lastConfigReload < strtotime('-15 seconds')){
		$lastConfigReload = time();
		$config = getSpiderConfig(KAOS_SPIDER_ID);
	}
	
	// recheck from yesterday every 3 months, to fix errors during the spide
	if (!$last_recheck || strtotime($last_recheck) > strtotime('+3 months', strtotime($query['date']))){
		$last_recheck = $query['date'];
		$query['date'] = date('Y-m-d', strtotime('-1 day'));
	}
	
	// adjusting workers count according to CPU load
	$cpu = sys_getloadavg(); // may be replaced by http://php.net/manual/es/function.sys-getloadavg.php#118673 for linux+windows
	if ($cpu){
		$load = $cpu[0];

		if (!$lastCPUCheck || ($config['cpuRate'] != 100 && ($lastCPUCheck < strtotime('-1 minute') || ($starting && $lastCPUCheck < strtotime('-20 seconds'))))){
			
			if (!$goal){
				$lastCPUCheck = time();
				
				if ($config['workersCount'] > 25 && $load > min($config['cpuRate'] + 15, 95))
					$workers -= 5;
				else if ($config['workersCount'] > 15 && $load > min($config['cpuRate'] + 5, 95))
					$workers -= 3;
				else if ($load < $config['cpuRate'] - 15 && $workers < $config['workersCount'])
					$workers += 5;
					
				$goal = $workers;
			}
		}
		$overload = $load > min($config['cpuRate'] + 15, 95);
	}
	
	$workers = max(min($workers, $config['workersCount']), 1);
	
	if (KAOS_IS_CLI)
		kaosPrintLog('workers goal: '.$workers.'/'.$config['workersCount'], array('spider_id' => KAOS_SPIDER_ID));
		
	cleanLocks();
	
	$countPids = array_filter($pids, function($x){ 
		return !empty($x); 
	});
	
	
	if (count($countPids) < $workers && !$overload){
		
		// fill up $pid in $pids where first null
		$i = null;
		foreach ($pids as $ci => $p)
			if (empty($p)){
				$i = $ci;
				break;
			}
		if ($i === null)
			$i = count($pids);
		
		$lock = null;

		// calculate the next worker date
		while (true){

			$bulletinStatus = getBulletinStatus($query['schema'], $query['date']);
			$stop = true;

			// case fetching
			if (!($lock = lock('rewind-'.$query['schema'].'-'.$query['date'])))
				$stop = false;
							
			// case too many retries
			else if ($bulletinStatus == 'error'){
				
				if (getBulletinAttempts($query['schema'], $query['date']) >= $maxAttempts){
					
					if (getBulletinFixes($query['schema'], $query['date']) >= $maxFixed)
						$stop = false;
					
					else {
						// fix
						kaosFixBulletin($query['schema'], $query['date']);
					}
				}
			
			// case extracted
			} else if (in_array($bulletinStatus, array('none', 'extracting', 'extracted')))
				$stop = false;
			
			// case fetched (and not extracting)
			else if (!$config['extract'] && in_array($bulletinStatus, array('fetched', 'parsed')))
				$stop = false;
				
			if ($stop) // important!
				break;
				
			if ($config['dateBack'] && $query['date'] < $config['dateBack'])
				break;
			
			// go to previous day
			$query['date'] = date('Y-m-d', strtotime('-1 day', strtotime($query['date'])));
		}
		$kaosCall['query']['date'] = $query['date'];
		
		// stop at dateBack
		if ($config['dateBack'] && $query['date'] < $config['dateBack']){
			unlock($lock);
			break;
		}
		
		if (KAOS_SPIDER_ID)
			$worker_id = insert('workers', array(
				'spider_id' => KAOS_SPIDER_ID,
				'type' => 'fetcher',
				'date' => $query['date'],
				'status' => 'starting',
				'pid' => null,
				'started' => date('Y-m-d H:i:s'),
			));
			
		connexionClose(); // leave this just before forking!
		$pid = pcntl_fork(); 
		
		if (!$pid){ 
			// in worker
			
			unset($workers);
			
			kaosPrintLog('worker '.($i+1).' started', array('color' => 'lgreen', 'worker_id' => $i));
			define('KAOS_WORKER_ID', $i);
			
			if (KAOS_SPIDER_ID)
				update('workers', array('status' => 'active'), array(
					'spider_id' => KAOS_SPIDER_ID, 
					'type' => 'fetcher',
					'date' => $query['date'],
				));
			
			$bulletinParser = new BulletinParser();
			
			kaosPrintLog('starting fetch for '.$query['schema'].'/'.$query['date'].(!empty($query['id']) ? '/'.$query['id'] : '').($config['extract'] ? ' (extracting)' : ' (not extracting)'));
			$ret = $bulletinParser->fetchAndParseBulletin($query);
			
			if (!$ret || kaosIsError($ret))
				kaosPrintLog('could not fetch '.$query['schema'].'/'.$query['date'].(!empty($query['id']) ? '/'.$query['id'] : '').($ret ? ': '.$ret->msg : ''), array('color' => 'red'));
			
			else 
				kaosPrintLog('ended fetch for '.$query['schema'].'/'.$query['date'].(!empty($query['id']) ? '/'.$query['id'] : ''), array('color' => 'lgreen'));
				
			if ($config['extract']){	
				kaosPrintLog('starting to extract');
	
				$extracter = new BulletinExtractor($ret);
				$ret = $extracter->extract($query, true);
				
				if (!$ret || kaosIsError($ret))
					kaosPrintLog('could not extract '.$query['schema'].'/'.$query['date'].(!empty($query['id']) ? '/'.$query['id'] : '').($ret ? ': '.$ret->msg : ''), array('color' => 'red'));
				
				else 
					kaosPrintLog('ended extraction of '.$query['schema'].'/'.$query['date'].(!empty($query['id']) ? '/'.$query['id'] : ''), array('color' => 'lgreen'));
			}
		
			//$args['done'] = date('Y-m-d H:i:s');
			
			if (KAOS_SPIDER_ID)
				query('DELETE FROM workers WHERE id = %s', $worker_id);
			
			unlock($lock);
			unset($bulletinParser);
			
			exit(0); // worker done
		} 
		
		// in parent (spider)
		
		if (KAOS_SPIDER_ID)
			query('UPDATE workers SET pid = %s WHERE spider_id = %s AND type = "fetcher" AND date = %s AND status IN ( "starting", "active" )', array($pid, KAOS_SPIDER_ID, $query['date']));
		
		// go to previous day
		$query['date'] = date('Y-m-d', strtotime('-1 day', strtotime($query['date'])));
		
		$pids[$i] = $pid;
		
		if ($goal){
			$goal = null;
			sleep(5);
		
		} else if ($starting)
			sleep(2); // wait 2s on the startup
	}
	
	if (KAOS_SPIDER_ID){
		$status = getSpiderStatus(KAOS_SPIDER_ID);
		if (!in_array($status, array('active'))){
			kaosPrintLog('spider status is now '.$status);
			break;
		}
	}
	
	$countPids = array_filter($pids, function($x){ 
		return !empty($x); 
	});
	
	if (count($countPids) >= $workers || $overload){
		kaosSpiderWorkerWait($pids, $workers);
		if ($goal && count($countPids) > $goal){
			$goal = null;
			sleep(20);
		}
	}
	
	if ($config['dateBack'] && $query['date'] < $config['dateBack'])
		break;
}
kaosSpiderWorkerWait($pids, null, true);

if (KAOS_SPIDER_ID && !in_array(getSpiderStatus(KAOS_SPIDER_ID), array('waiting')))
	update('spiders', array('status' => 'stopped'), array('id' => KAOS_SPIDER_ID));

kaosPrintLog('spider '.$query['schema'].' ('.(KAOS_SPIDER_ID ? '#'.KAOS_SPIDER_ID : 'manual').') ended on '.$query['date'], array('color' => 'lgreen', 'spider_id' => KAOS_SPIDER_ID));
exit(0);
