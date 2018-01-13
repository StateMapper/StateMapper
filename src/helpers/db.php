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


function get_connection($closing = false){
	static $conn = null;
	if ($conn == null){
		try {
			$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
		} catch (Exception $e){
			$conn = false;
			die_error('db connection failed: '.$e->getMessage());
		}
		if ($conn->connect_error){
			$err = $conn->connect_error;
			$conn = false;
			die_error('db connection failed: '.$err);
		}
		if (!mysqli_select_db($conn, DB_NAME)){
			$conn = false;
			die_error('db '.DB_NAME.' not found');
		}

		execute_query($conn, 'SET sql_mode = ""');
		
	} else if ($conn === false)
		return false;
	
	if ($closing){
		mysqli_close($conn);
		$conn = null;
		return true;
	}
	
	return $conn;
}

function close_connection(){
	return get_connection(true);
}

function get_var($query, $injectVars = array()){
	if (!preg_match('#\bLIMIT\s+[0-9]+\s*$#ius', $query))
		$query = $query.' LIMIT 1';
		
	$ret = query($query, $injectVars);
	if (is_array($ret) && !empty($ret)){
		$r = array_shift($ret[0]);
		if (is_numeric($r))
			return intval($r);
		return $r;
	}
	return null;
}

function get_col($query, $injectVars = array()){
	$ret = query($query, $injectVars);
	if (is_array($ret) && !empty($ret)){
		$values = array();
		foreach ($ret as $r){
			$v = array_shift($r);
			$values[] = is_numeric($v) ? intval($v) : $v;
		}
		return $values;
	}
	return array();
}

function get_row($query, $injectVars = array()){
	$ret = query($query, $injectVars);
	return $ret ? array_shift($ret) : null;
}

function prepare($query, $injectVars){
	if (!($conn = get_connection()) || is_error($conn))
		return $conn;
		
	if (!is_array($injectVars))
		$injectVars = array($injectVars);
		
	// TODO: protect against double injecting with %s in first injection: use pair number of quotes before %s in regexp
	foreach ($injectVars as $v)
		$query = preg_replace('/%s/', "'".mysqli_real_escape_string($conn, $v)."'", $query, 1);

	//echo "FINAL QUERY: ".$query.PHP_EOL;		
	return $query;
}

function esc_like($str, $dir = 'both'){
	if (!($conn = get_connection()) || is_error($conn))
		return $conn;
	return "'".(in_array($dir, array('left', 'both')) ? '%' : '').mysqli_real_escape_string($conn, $str).(in_array($dir, array('right', 'both')) ? '%' : '')."'";
}

function query($query, $injectVars = array(), $returnType = null){
	if (!($conn = get_connection()) || is_error($conn))
		return $conn;
		
	$query = prepare($query, $injectVars);
		
	$result = execute_query($conn, $query);
	if (!$result){
		$err = mysqli_error($conn);
		$err = preg_replace('#\s*'.preg_quote('You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near ', '#').'(.*)\s*$#ius', 'syntax error near $1', $err);
		
		die_error('MySQL query error: <b>'.$err.'</b> about query <b>'.$query.'</b>');
		return false;
	}
	
	if ($returnType == 'num_rows')
		return mysqli_num_rows($conn);
	
	if ($result === true)
		return true;
		
	$ret = array();
	while ($row = mysqli_fetch_assoc($result))
		$ret[] = $row;
	return $ret;
}

function insert($table, $vars = array()){
	if (!($conn = get_connection()) || is_error($conn))
		return $conn;
		
//		print_json($vars);
	
	$values = array();
	foreach ($vars as $k => $v)
		$values[] = $v === null ? 'NULL' : "'".mysqli_real_escape_string($conn, $v)."'";

	$query = 'INSERT INTO '.$table.' ( '.implode(', ', array_keys($vars)).' ) VALUES ( '.implode(', ', $values).' )';
		
	return execute_query($conn, $query, true);
}

function update($table, $data = array(), $where = array(), $notWhere = array()){
	if (!($conn = get_connection()) || is_error($conn))
		return $conn;
	
	$set = array();
	foreach ($data as $k => $v)
		$set[] = $k.' = '.($v === null ? 'NULL' : "'".mysqli_real_escape_string($conn, $v)."'");

	$w = array();
	foreach ($where as $k => $v)
		$w[] = $k.' = '.($v === null ? 'NULL' : "'".mysqli_real_escape_string($conn, $v)."'");
	foreach ($notWhere as $k => $v)
		$w[] = $k.' != '.($v === null ? 'NULL' : "'".mysqli_real_escape_string($conn, $v)."'");

	$query = 'UPDATE '.$table.' SET '.implode(', ', $set).' WHERE '.implode(' AND ', $w);
	if (!execute_query($conn, $query))
		return false;
	return mysqli_affected_rows($conn);
}

function execute_query($conn, $query, $returnInsertedId = false){
	global $smap, $smapDebug;
	if (empty($smapDebug['queries']))
		$smapDebug['queries'] = array();
	$begin = microtime(true);
	$ret = mysqli_query($conn, $query);
	if ($returnInsertedId)
		$ret = $ret ? mysqli_insert_id($conn) : false;

	$explain = array();
	if (IS_DEBUG && (IS_CLI || is_admin())){
		if ($eRet = mysqli_query($conn, 'EXPLAIN '.$query))
			while ($eRow = mysqli_fetch_assoc($eRet))
				$explain[] = $eRow;
	}
			
	if (!IS_CLI){
		$smapDebug['queries'][] = array(
			'query' => $query,
			'explain' => $explain,
			'duration' => microtime(true) - $begin,
		);
	
	} else if (!empty($smap['debugQueries']))
		print_log('[SQL] '.$query.' ('.time_diff(microtime(true) - $begin, 0, true).')', array('color' => 'grey'));
	
	return $ret;
}

function lint_sql_var($var){
	return preg_replace_callback('#[A-Z]#u', function($m){
		return '_'.strtolower($m[0]);
	}, $var);
}

// Convert all tables to TokuDB engine with "?setNewEngine=TokuDB" - for development purpose only
function convert_db_engine($newEngine){
	if (!in_array($newEngine, array('TokuDB')))
		die('bad engine');
		
	$sql = '
		SELECT TABLE_NAME, ENGINE FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = "'.DB_NAME.'"
    ';

    foreach (query($sql) as $t){
		$alter = strcasecmp($t['ENGINE'], $newEngine);
		if ($alter){
			echo 'Altering table "'.$t['TABLE_NAME'].'" engine from '.$t['ENGINE'].' to '.$newEngine.'<br>';
			query('ALTER TABLE '.$t['TABLE_NAME'].' ENGINE="'.$newEngine.'"');
		} else 
			echo 'Leaving table "'.$t['TABLE_NAME'].'" with engine '.$t['ENGINE'].'<br>';
			
    }
    die('done');
}

function clean_tables($all = false){
	if (IS_INSTALL)
		return;
		
	static $lastCleaned = null;
	if ($all)
		query('DELETE FROM locks');
	
	else if (!$lastCleaned || $lastCleaned < time() - 60){ // clean every minute top
		$lastCleaned = time();
		query('DELETE FROM locks WHERE created < %s', array(date('Y-m-d H:i:s', time() - (max(MAX_EXECUTION_TIME, 900) + 60)))); // clean after max(MAX_EXECUTION_TIME, 15min) + 1 minute
	}
	
	// clear expired caches
	if ($all)
		query('DELETE FROM cache');
	else
		query('DELETE FROM cache WHERE expire < %s', date('Y-m-d H:i:s'));
		
	// clear api rates
	if ($all)
		query('DELETE FROM api_rates');
	else
		query('DELETE FROM api_rates WHERE date < %s', date('Y-m-d H:i:s', strtotime('-'.API_RATE_PERIOD)));
}
