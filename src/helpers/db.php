<?php
	
if (!defined('BASE_PATH'))
	die();


function getConnexion($closing = false){
	static $conn = null;
	if ($conn == null){
		try {
			$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
		} catch (Exception $e){
			$conn = false;
			return new KaosError('db connection failed: '.$e->getMessage());
		}
		if ($conn->connect_error){
			$err = $conn->connect_error;
			$conn = false;
			return new KaosError('db connection failed: '.$err);
		}
		if (!mysqli_select_db($conn, DB_NAME)){
			$conn = false;
			return new KaosError('db '.DB_NAME.' not found');
		}

		kaosSqlQuery($conn, 'SET sql_mode = ""');
	} else if ($conn === false)
		return false;
	
	if ($closing){
		mysqli_close($conn);
		$conn = null;
		return true;
	}
	
	return $conn;
}

function connexionClose(){
	return getConnexion(true);
}

function get($query, $injectVars = array()){
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

function getCol($query, $injectVars = array()){
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

function getRow($query, $injectVars = array()){
	$ret = query($query, $injectVars);
	return $ret ? array_shift($ret) : null;
}

function queryPrepare($query, $injectVars){
	if (!($conn = getConnexion()) || kaosIsError($conn))
		return $conn;
		
	if (!empty($injectVars) && !is_array($injectVars))
		$injectVars = array($injectVars);
		
	// TODO: protect against double injecting with %s in first injection: use pair number of quotes before %s in regexp
	if ($injectVars)
		foreach ($injectVars as $v)
			$query = preg_replace('/%s/', "'".mysqli_real_escape_string($conn, $v)."'", $query, 1);

	//echo "FINAL QUERY: ".$query.PHP_EOL;		
	return $query;
}

function esc_like($str, $dir = 'both'){
	if (!($conn = getConnexion()) || kaosIsError($conn))
		return $conn;
	return "'".(in_array($dir, array('left', 'both')) ? '%' : '').mysqli_real_escape_string($conn, $str).(in_array($dir, array('right', 'both')) ? '%' : '')."'";
}

function query($query, $injectVars = array(), $returnType = null){
	if (!($conn = getConnexion()) || kaosIsError($conn))
		return $conn;
		
	$query = queryPrepare($query, $injectVars);
		
	$result = kaosSqlQuery($conn, $query);
	if (!$result){
		$err = mysqli_error($conn);
		$err = preg_replace('#\s*'.preg_quote('You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near ', '#').'(.*)\s*$#ius', 'syntax error near $1', $err);
		kaosDie('MySQL query error: <b>'.$err.'</b> about query <b>'.$query.'</b>');
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
	if (!($conn = getConnexion()) || kaosIsError($conn))
		return $conn;
		
//		kaosJSON($vars);
	
	$values = array();
	foreach ($vars as $k => $v)
		$values[] = $v === null ? 'NULL' : "'".mysqli_real_escape_string($conn, $v)."'";

	$query = 'INSERT INTO '.$table.' ( '.implode(', ', array_keys($vars)).' ) VALUES ( '.implode(', ', $values).' )';
		
	$result = kaosSqlQuery($conn, $query);
	if (!$result)
		return false;
	
	return mysqli_insert_id($conn);
}

function update($table, $data = array(), $where = array(), $notWhere = array()){
	if (!($conn = getConnexion()) || kaosIsError($conn))
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
	if (!kaosSqlQuery($conn, $query))
		return false;
	return mysqli_affected_rows($conn);
}

function kaosSqlQuery($conn, $query){
	global $kaosCall;
	if (empty($kaosCall['queries']))
		$kaosCall['queries'] = array();
	$begin = time();
	$ret = mysqli_query($conn, $query);

	if (KAOS_DEBUG && (KAOS_IS_CLI || isAdmin())){
		$explain = array();
		if ($eRet = mysqli_query($conn, 'EXPLAIN '.$query))
			while ($eRow = mysqli_fetch_assoc($eRet))
				$explain[] = $eRow;
	}
			
	if (!KAOS_IS_CLI){
		$kaosCall['queries'][] = array(
			'query' => $query,
			'explain' => $explain,
			'duration' => time() - $begin,
		);
	
	} else if (!empty($kaosCall['debugQueries']))
		kaosPrintLog('[SQL] '.$query.' ('.(time() - $begin).'s)', array('color' => 'grey'));
	
	return $ret;
}

function lintSqlVar($var){
	return preg_replace_callback('#[A-Z]#u', function($m){
		return '_'.strtolower($m[0]);
	}, $var);
}

function convertDBEngine($newEngine){
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

// Convert all tables to TokuDB engine with "?setNewEngine=TokuDB"
if (KAOS_DEBUG && !empty($_GET['setNewEngine']))
	convertDBEngine($_GET['setNewEngine']);
