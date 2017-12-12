<?php
	
if (!defined('BASE_PATH'))
	die();

function kaosAjaxSpiderConfig($args){
	
	if (!isAdmin())
		kaosDie();
		
	$opts = array_keys(kaosGetDefaultSpiderConfig(false));
	
	if (!@$args['session']['query']['schema'])
		return 'missing schema';
	
	if (empty($args['configVar']) || !isset($args['configVal']) || !is_string($args['configVal']) || !in_array($args['configVar'], $opts))
		return 'bad config var';
		
	if ($args['configVar'] == 'dateBack'){
		if (is_numeric($args['configVal']))
			$args['configVal'] = $args['configVal'].'-01-01';
		if (date('Y-m-d', strtotime($args['configVal'])) !== $args['configVal'])
			return 'bad date or year, correct format is YYYY or YYYY-MM-DD';
	
	} else if (!is_numeric($args['configVal']))
		return 'bad format';
		
	$update = array(
		'bulletin_schema' => $args['session']['query']['schema'],
	);
	
	$update[lintSqlVar($args['configVar'])] = $args['configVal'];
	if (upsertSpider($update) === false)
		return 'upsert failed';
		
	return array('success' => true, 'val' => $args['configVal']);
}

function kaosAjaxSpiderExtract($args){
	
	if (!isAdmin())
		kaosDie();
		
	if (empty($args['schema']))
		return 'missing schema';
		
	$on = !empty($args['turnOn']) && $args['turnOn'] !== 'false';
	query('UPDATE spiders SET extract = '.($on ? 1 : '0').' WHERE bulletin_schema = %s', $args['schema']);
	return array('success' => true);
}

function kaosAjaxSpiderPower($args){
	
	if (!isAdmin())
		kaosDie();
		
	if (empty($args['schema']))
		return 'missing schema';
		
	$on = !empty($args['turnOn']) && $args['turnOn'] !== 'false';
	
	if ($lock = waitForLock('spider-'.$args['schema'])){
		$status = getSpiderStatus($args['schema']);
		
		if ($on)
			$nstatus = in_array($status, array('waiting', 'active')) ? null : 'waiting';
		else {
			
			$count = get('SELECT COUNT(w.pid) FROM spiders AS s LEFT JOIN workers AS w ON s.id = w.spider_id WHERE s.bulletin_schema = %s AND w.status = "active"', $args['schema']);
			
			$nstatus = in_array($status, array('stopping', 'stopped')) ? null : ($count ? 'stopping' : 'stopped');
		}
		
		if ($nstatus && upsertSpider(array(
			'bulletin_schema' => $args['schema'],
			'status' => $nstatus,
		), true) === false){
			unlock($lock);
			return 'spider upsert failed';
		}
		unlock($lock);
		return array('success' => true, 'button' => kaosSpiderPowerButton($args['schema']));
	}
	return 'can\'t get spider lock';
}

function upsertSpider($args, $noLock = false){
	$status = getSpiderStatus($args['bulletin_schema'], false);
	if (!$status){
		if (!$noLock){
			$lock = waitForLock('spider-'.$args['bulletin_schema'], 1);
			if ($lock === false)
				return false; // can't get spider lock
		}
		$args += array(
			'status' => 'stopped',
		) + kaosGetDefaultSpiderConfig(true);
		
		$ret = insert('spiders', $args);
		
		if (!$noLock)
			unlock($lock);
		
	} else {
		$where = array('bulletin_schema' => $args['bulletin_schema']);
		unset($args['bulletin_schema']);
		$ret = update('spiders', $args, $where);
	}
		
	return $ret;
}

function kaosGetDefaultSpiderConfig($sqlFormat = true){
	$ret = array(
		'status' => 'waiting',
		'dateBack' => date('Y-m-d', strtotime('-1 day')),
		'workersCount' => KAOS_SPIDE_WORKER_COUNT,
		'cpuRate' => KAOS_SPIDE_CPU_MAX,
		'extract' => false,
	);
	if ($sqlFormat){
		$rret = array();
		foreach ($ret as $k => $v)
			$rret[lintSqlVar($k)] = $v;
		return $rret;
	}
	return $ret;
}


function getSpiderConfig($schema){
	$dbconfig = getRow('SELECT status, date_back, workers_count, cpu_rate, extract FROM spiders WHERE '.(is_numeric($schema) ? 'id = %s' : 'bulletin_schema = %s'), $schema);
	$default = kaosGetDefaultSpiderConfig(false);
	$config = array();
	foreach (array_keys($default) as $k){
		$dbk = lintSqlVar($k);
		$config[$k] = isset($dbconfig[$dbk]) ? $dbconfig[$dbk] : $default[$k];
	}
	return $config;
}


function kaosSpiderPowerButton($schema){
	$status = getSpiderStatus($schema);
	$labels = array(
		'waiting' => 'Spider starting..',
		'active' => 'Spider running',
		'stopping' => 'Spider stopping..',
		'stopped' => 'Spider stopped',
	);
	return '<button class="kaos-api-spider-button kaos-spider-status-'.$status.'" data-kaos-schema="'.$schema.'"><i class="fa fa-'.(in_array($status, array('active', 'waiting', 'stopping')) ? 'pause' : 'power-off').'"></i> '.$labels[$status].'</button>';
}

function getSpiderStatus($schema, $default = 'stopped'){
	$status = get('SELECT status FROM spiders WHERE '.(is_numeric($schema) ? 'id = %s' : 'bulletin_schema = %s'), $schema);
	if (!$status)
		return $default;
	return $status;
}

function kaosSpiderWorkerWait(&$pids, $stopAtWorkers = null, $all = false){
	$begin = time();
	
	while (($pid = pcntl_waitpid(0, $status)) != -1){ 
		$status = pcntl_wexitstatus($status); 
		query('DELETE FROM workers WHERE pid = %s', $pid);
		
		$i = array_search($pid, $pids);
		kaosPrintLog('worker '.($i+1).' freed', array('color' => 'lgreen', 'worker_id' => $i));
		$pids[$i] = null;
		//array_splice($pids, array_search($pid, $pids), 1);
		
		//if (!$all || empty($pids))
		//	break;
		
		if (!$all){
			if (time() - $begin > 10) 
				break;
			
			$countPids = array_filter($pids, function($x){ 
				return !empty($x); 
			});
			if ($stopAtWorkers && count($countPids) < $stopAtWorkers)
				break;
		}
	} 
}



function kaosWorkersStats(&$count = 0, $schema = null, &$countPerYear = array()){
	global $kaosCall;
	if (!$schema)
		$schema = $kaosCall['query']['schema'];
	
	$minTime = $maxTime = $minDate = $maxDate = null;
	foreach (query('SELECT w.pid, w.date, w.started FROM spiders AS s LEFT JOIN workers AS w ON s.id = w.spider_id WHERE s.bulletin_schema = %s AND w.status = "active"', $schema) as $w){
		if (!isActivePid($w['pid']))
			continue;
		
		$count++;
		$y = intval(date('Y', strtotime($w['date'])));
		$countPerYear[$y] = (isset($countPerYear[$y]) ? $countPerYear[$y] : 0) + 1;

		if (!empty($w)){
			$time = time() - strtotime($w['started']);
			$minTime = $minTime ? min($minTime, $time) : $time;
			$maxTime = $maxTime ? max($maxTime, $time) : $time;
			$minDate = $minDate ? min($minDate, $w['date']) : $w['date'];
			$maxDate = $maxDate ? max($maxDate, $w['date']) : $w['date'];
		}
	}
	$core_nums = trim(shell_exec("grep -P '^physical id' /proc/cpuinfo|wc -l"));
	$cpu = sys_getloadavg(); // may be replaced by http://php.net/manual/es/function.sys-getloadavg.php#118673 
	
	$config = getSpiderConfig($schema);
	
	$editable = isAdmin() ? 'kaos-spider-ctrl-field-editable' : '';
	
	$html = '<div><span class="kaos-spider-ctrl-field '.$editable.'" data-kaos-prompt="'.esc_attr('How many workers do you want to use as a maximum?').'" data-kaos-ctrl-var="workersCount" data-kaos-ctrl-val="'.$config['workersCount'].'"><i class="fa fa-bug kaos-spider-ctrl-icon"></i> Workers: '.$count.' / <span class="kaos-spider-ctrl-field-val">'.$config['workersCount'].'</span> '.($count ? '(older '.humanTimeDiff(time() + $maxTime).') ' : '').'<i class="fa fa-pencil"></i></span></div>';
	
	$html .= '<div><span class="kaos-spider-ctrl-field '.$editable.'" data-kaos-prompt="'.esc_attr('How much CPU proportion do you want to use as a maximum?').' (%)" data-kaos-ctrl-var="cpuRate" data-kaos-ctrl-val="'.$config['cpuRate'].'"><i class="fa fa-microchip kaos-spider-ctrl-icon"></i> CPU '.$core_nums.'x: '.number_format($cpu[0]).'% (max <span class="kaos-spider-ctrl-field-val">'.$config['cpuRate'].'</span>%) <i class="fa fa-pencil"></i></span></div>';
	
	$html .= '<div><span class="kaos-spider-ctrl-field '.$editable.'" data-kaos-prompt="'.esc_attr('Back until which date do you want to fetch bulletin from?').' (YYYY or YYYY-MM-DD, '.esc_attr('inclusive').')" data-kaos-ctrl-var="dateBack" data-kaos-ctrl-val="'.$config['dateBack'].'"><i class="fa fa-step-backward kaos-spider-ctrl-icon"></i> Back until: <span class="kaos-spider-ctrl-field-val">'.$config['dateBack'].'</span> '.($count ? '(fetching '.ucfirst(date_i18n('M j, Y', strtotime($maxDate))).' <i class="fa fa-long-arrow-right"></i> '.ucfirst(date_i18n('M j, Y', strtotime($minDate))).') ' : '').'<i class="fa fa-pencil"></i></span></div>';
	
	$docs = get('SELECT COUNT(bb.id) FROM bulletins AS b LEFT JOIN bulletin_uses_bulletin AS bb ON b.id = bb.bulletin_in LEFT JOIN bulletins AS b2 ON bb.bulletin_id = b2.id WHERE b.bulletin_schema = %s AND b.date IS NOT NULL', $schema);
	
	$docsLast = get('SELECT COUNT(id) FROM bulletins WHERE bulletin_schema = %s AND created > %s', array($schema, date('Y-m-d H:i:s', strtotime('-5 minute'))));
	
	$docsLast += get('SELECT COUNT(b.id) FROM bulletins AS b WHERE b.bulletin_schema = %s AND b.date IS NOT NULL AND b.created > %s', array($schema, date('Y-m-d H:i:s', strtotime('-1 hour'))));
	
	$html .= '<div><span class="kaos-spider-ctrl-field"><i class="fa fa-file-text-o kaos-spider-ctrl-icon"></i> Documents: '.number_format($docs, 0).($docsLast ? ' ('.number_format($docsLast / 5, 0).' per minute)' : '').'</span></div>';
	
	
	$stats = query('
		SELECT s.type AS _type, s.action AS _action, 
		SUM(a.originalValue) AS amount, a.originalUnit AS unit, COUNT(s.id) AS count
		
		FROM precepts AS p 
		LEFT JOIN statuses AS s ON p.id = s.precept_id 
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id 
		LEFT JOIN bulletin_uses_bulletin AS bb ON p.bulletin_id = bb.bulletin_id 
		LEFT JOIN bulletins AS b_in ON bb.bulletin_in = b_in.id 
		LEFT JOIN amounts AS a ON s.amount = a.id
		
		WHERE s.type IS NOT NULL AND s.action IS NOT NULL AND (b.bulletin_schema = %s OR b_in.bulletin_schema = %s)
		GROUP BY s.type, s.action
		ORDER BY s.type = "name" ASC, s.type, s.action
	', array($schema, $schema));
	
	/* bulletin's status stats..
	 * 
	$html .= '<div style="font-size: 70%">';
	$labels = kaosGetStatusLabels();
	foreach ($stats as $s){
		$c = @$labels[$s['_type']][$s['_action']];
		$icon = 'question-circle';
		$label = $s['_type'].':'.$s['_action'];
		if ($c){
			if (!empty($c['icon']))
				$icon = $c['icon'];
			if (!empty($c['stats'])){
				if (is_array($c['stats']) && !empty($c['stats']['label']))
					$label = $c['stats']['label'];
				else
					$label = $c['stats'];
				if (is_array($c['stats']) && !empty($c['stats']['icon']))
					$icon = $c['stats']['icon'];
			}
		}
		$html .= '<span class="kaos-spider-ctrl-field"><i class="fa fa-'.$icon.' kaos-spider-ctrl-icon"></i> '.strtr($label, array(
			'[count]' => number_format($s['count']),
			'[amount]' => number_format($s['amount']).' '.$s['unit'],
		)).'</span>';
	}
	$html .= '</div>';
	*/
	return $html;
}

function isActivePid($pid){
	return is_numeric($pid) && file_exists('/proc/'.$pid);
}
