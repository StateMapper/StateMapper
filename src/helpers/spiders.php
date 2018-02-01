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

function smap_ajax_spider_config($args){
	
	if (!is_admin())
		die_error();
	
	if (!@$args['session']['query']['schema'])
		return 'missing schema';
		
	if (empty($args['configVar']))
		return 'bad config var';
	if (empty($args['configVal']))
		return 'bad config value';
	
	$var = $args['configVar'];
	$value = $args['configVal'];
	$schema = $args['session']['query']['schema'];
	
	if ($error = set_spider_config($schema, $var, $value))
		return $error;
	
	return array('success' => true, 'val' => $value);
}

function set_spider_config($schema, $var, $value){
	$opts = array_keys(get_default_spider_config());
	
	if (!is_string($value) || !in_array($var, $opts))
		return 'bad config var';
		
	if ($var == 'date_back'){
		if (is_numeric($value))
			$value = $value.'-01-01';
		if (!is_valid_date($value))
			return 'bad date or year, correct format is YYYY or YYYY-MM-DD';
	
	} else if (!is_numeric($value))
		return 'bad format';
		
	$update = array(
		'bulletin_schema' => $schema,
	);
	
	$update[$var] = $value;
	if (upsert_spider($update) === false)
		return 'upsert failed';
		
	return true;
}

function smap_ajax_spider_extract($args){
	
	if (!is_admin())
		die_error();
		
	if (empty($args['schema']))
		return 'missing schema';
		
	$on = !empty($args['turnOn']) && $args['turnOn'] !== 'false';
	query('UPDATE spiders SET extract = '.($on ? 1 : '0').' WHERE bulletin_schema = %s', $args['schema']);
	return array('success' => true);
}

function smap_ajax_spider_power($args){
	
	if (!is_admin())
		die_error();
		
	if (empty($args['schema']))
		return 'missing schema';
		
	$on = !empty($args['turnOn']) && $args['turnOn'] !== 'false';
	
	if ($error = toggle_spider_status($args['schema'], $on))
		return $error;
	
	return array('success' => true, 'button' => get_spider_button($args['schema']));
}
	
function toggle_spider_status($schema, $on){
	if ($lock = wait_for_lock('spider-'.$schema)){
		$status = get_spider_status($schema);
		
		if ($on)
			$nstatus = in_array($status, array('waiting', 'active')) ? null : 'waiting';
		else {
			
			$count = get_var('SELECT COUNT(w.pid) FROM spiders AS s LEFT JOIN workers AS w ON s.id = w.spider_id WHERE s.bulletin_schema = %s AND w.status = "active"', $schema);
			
			$nstatus = in_array($status, array('stopping', 'stopped')) ? null : ($count ? 'stopping' : 'stopped');
		}
		
		if ($nstatus && upsert_spider(array(
			'bulletin_schema' => $schema,
			'status' => $nstatus,
		), true) === false){
			unlock($lock);
			return 'spider upsert failed';
		}
		unlock($lock);
		return true;
	}
	return 'can\'t get spider lock';
}
	

function upsert_spider($args, $noLock = false){
	$status = get_spider_status($args['bulletin_schema'], false);
	if (!$status){
		if (!$noLock){
			$lock = wait_for_lock('spider-'.$args['bulletin_schema'], 1);
			if ($lock === false)
				return false; // can't get spider lock
		}
		$args += array(
			'status' => 'stopped',
		) + get_default_spider_config();
		
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

function get_default_spider_config(){
	return array(
		'status' => 'waiting',
		'date_back' => date('Y-m-d', strtotime('-1 day')),
		'max_workers' => SPIDER_WORKERS_COUNT,
		'max_cpu_rate' => SPIDER_MAX_CPU,
		'extract' => false,
	);
}


function get_spider_config($schema){
	$dbconfig = get_row('SELECT bulletin_schema, status, date_back, max_workers, max_cpu_rate, extract FROM spiders WHERE '.(is_numeric($schema) ? 'id = %s' : 'bulletin_schema = %s'), $schema);
	$default = get_default_spider_config();
	$config = array();
	foreach ($default as $k => $v)
		$config[$k] = isset($dbconfig[$k]) ? $dbconfig[$k] : $v;
	$config['schema'] = $dbconfig['bulletin_schema'];
	unset($config['bulletin_schema']);
	return $config;
}


function get_spider_button($schema){
	$status = get_spider_status($schema);
	$labels = array(
		'waiting' => 'Spider starting..',
		'active' => 'Spider running',
		'stopping' => 'Spider stopping..',
		'stopped' => 'Spider stopped',
	);
	return '<button class="spider-button spider-status-'.$status.'" data-schema="'.$schema.'"><i class="fa fa-'.(in_array($status, array('active', 'waiting', 'stopping')) ? 'pause' : 'power-off').'"></i> '.$labels[$status].'</button>';
}

function get_spider_status($schema, $default = 'stopped'){
	$status = get_var('SELECT status FROM spiders WHERE '.(is_numeric($schema) ? 'id = %s' : 'bulletin_schema = %s'), $schema);
	if (!$status)
		return $default;
	return $status;
}

function worker_wait(&$pids, $stopAtWorkers = null, $all = false){
	$begin = time();
	
	while (($pid = pcntl_waitpid(0, $status)) != -1){ 
		$status = pcntl_wexitstatus($status); 
		query('DELETE FROM workers WHERE pid = %s', $pid);
		
		$i = array_search($pid, $pids);
		print_log('worker '.($i+1).' freed', array('color' => 'lgreen', 'worker_id' => $i));
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



function get_workers_stats(&$count = 0, $schema = null, &$countPerYear = array()){
	global $smap;
	if (!$schema)
		$schema = $smap['query']['schema'];
	
	$minTime = $maxTime = $minDate = $maxDate = null;
	foreach (query('SELECT w.pid, w.date, w.started FROM spiders AS s LEFT JOIN workers AS w ON s.id = w.spider_id WHERE s.bulletin_schema = %s AND w.status = "active"', $schema) as $w){
		if (!is_active_pid($w['pid']))
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
	
	$config = get_spider_config($schema);
	
	$editable = is_admin() ? 'spider-ctrl-field-editable' : '';
	
	$html = '<div><span class="spider-ctrl-field '.$editable.'" data-smap-prompt="'.esc_attr('How many workers do you want to use as a maximum?').'" data-smap-ctrl-var="max_workers" data-smap-ctrl-val="'.$config['max_workers'].'"><i class="fa fa-bug spider-ctrl-icon"></i> Workers: '.$count.' / <span class="spider-ctrl-field-val">'.$config['max_workers'].'</span> '.($count ? '(older '.time_diff(time() + $maxTime).') ' : '').'<i class="fa fa-pencil"></i></span></div>';
	
	$html .= '<div><span class="spider-ctrl-field '.$editable.'" data-smap-prompt="'.esc_attr('How much CPU proportion do you want to use as a maximum?').' (%)" data-smap-ctrl-var="max_cpu_rate" data-smap-ctrl-val="'.$config['max_cpu_rate'].'"><i class="fa fa-microchip spider-ctrl-icon"></i> CPU '.$core_nums.'x: '.number_format($cpu[0]).'% (max <span class="spider-ctrl-field-val">'.$config['max_cpu_rate'].'</span>%) <i class="fa fa-pencil"></i></span></div>';
	
	$html .= '<div><span class="spider-ctrl-field '.$editable.'" data-smap-prompt="'.esc_attr('Back until which date do you want to fetch bulletin from?').' (YYYY or YYYY-MM-DD, '.esc_attr('inclusive').')" data-smap-ctrl-var="date_back" data-smap-ctrl-val="'.$config['date_back'].'"><i class="fa fa-step-backward spider-ctrl-icon"></i> Back until: <span class="spider-ctrl-field-val">'.$config['date_back'].'</span> '.($count ? '(fetching '.ucfirst(date_i18n('M j, Y', strtotime($minDate))).' <i class="fa fa-long-arrow-right"></i> '.ucfirst(date_i18n('M j, Y', strtotime($maxDate))).') ' : '').'<i class="fa fa-pencil"></i></span></div>';
	
	$docs = get_var('SELECT COUNT(*) FROM bulletins WHERE bulletin_schema = %s AND external_id IS NULL', $schema);
	
	$docsLast = get_var('SELECT COUNT(*) FROM bulletins WHERE bulletin_schema = %s AND external_id IS NULL AND created > %s', array($schema, date('Y-m-d H:i:s', strtotime('-5 minute'))));
	
	$docsLast += get_var('SELECT COUNT(*) FROM bulletins WHERE bulletin_schema = %s AND external_id IS NULL AND created > %s', array($schema, date('Y-m-d H:i:s', strtotime('-1 hour'))));
	
	$html .= '<div><span class="spider-ctrl-field"><i class="fa fa-file-text-o spider-ctrl-icon"></i> Documents: '.number_format($docs, 0).($docsLast ? ' ('.number_format($docsLast / 5, 0).' per minute)' : '').'</span></div>';
	
	
	$stats = query('
		SELECT s.type AS _type, s.action AS _action, 
		SUM(a.original_value) AS amount, a.original_unit AS unit, COUNT(s.id) AS count
		
		FROM precepts AS p 
		LEFT JOIN statuses AS s ON p.id = s.precept_id 
		LEFT JOIN bulletins AS b ON p.bulletin_id = b.id 
		LEFT JOIN amounts AS a ON s.amount = a.id
		
		WHERE b.bulletin_schema = %s
		GROUP BY s.type, s.action
		ORDER BY s.type = "name" ASC, s.type, s.action
	', array($schema));
	
	/* bulletin's status stats..
	 * 
	$html .= '<div style="font-size: 70%">';
	$labels = get_status_labels();
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
		$html .= '<span class="spider-ctrl-field"><i class="fa fa-'.$icon.' spider-ctrl-icon"></i> '.strtr($label, array(
			'[count]' => number_format($s['count']),
			'[amount]' => number_format($s['amount']).' '.$s['unit'],
		)).'</span>';
	}
	$html .= '</div>';
	*/
	return $html;
}

function is_active_pid($pid){
	return is_numeric($pid) && file_exists('/proc/'.$pid);
}
