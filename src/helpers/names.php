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

//if (!empty($_GET['reload_names'])) 
//	loadNames(); 
	
function loadNames($conn = null, $args = array()){ // provide $conn if installing only
	
	// empty the names table
	if ($conn)
		mysqli_query($conn, 'DELETE * FROM names');
	else
		query('DELETE * FROM names');
	
	foreach (array(
		'first_names' => FIRST_NAME,
		'last_names' => LAST_NAME,
	) as $f => $type){
		
		// import from .sql? (installation mode only)
		if ($args && is_file(BASE_PATH.'/database/'.$f.'.sql')){
			exec('mysql -u'.$args['user'].(!empty($args['pass']) ? ' -p'.$args['pass'] : '').' -h '.$args['host'].' '.$args['name'].' < "'.BASE_PATH.'/database/'.$f.'.sql"', $output, $return);
				
			if (!empty($return))
				return 'Could not import '.BASE_PATH.'/database/'.$f.'.sql';
		
		// import from .txt otherwise
		} else {
			$handle = fopen(BASE_PATH.'/database/'.$f.'.txt', 'r');
			if (!$handle)
				return 'Could not read '.BASE_PATH.'/database/'.$f.'.txt';
			
			if ($handle){
				$last = '';
				while (!feof($handle)){
					$name = fgets($handle, 500);
					
					if (!$name){
						if ($last != '')
							loadName($last, $type, $conn);
						break;
					}
					$name = $last.$name;
			
					$names = preg_split("/\\r\\n|\\r|\\n/", $name);
					if ($names){
						$count = count($names);
						$i = 0;
						foreach ($names as $name){
							if ($i == $count-1)
								$last = $name;
							else if (trim($name) != '')
								loadName($name, $type, $conn);
							$i++;
						}
					}
				}
				fclose($handle);
			}
		}
	}
	return true;
}

function loadName($name, $type, $conn = null){
	$cname = mb_strtolower(remove_accents(preg_replace('#\s+#', ' ', trim($name))));
	if ($conn)
		mysqli_query($conn, "INSERT INTO names (type, name) VALUES ('".mysqli_real_escape_string($conn, $type)."', '".mysqli_real_escape_string($conn, $cname)."')");
	else
		insert('names', array(
			'type' => $type,
			'name' => $cname,
		));
}

function kaosIsName($str, $type = null, $soft = false){
	$clean = mb_strtolower(remove_accents(preg_replace('#\s+#', ' ', trim($str))));
	if (empty($clean))
		return false;
		
	$types = getCol('SELECT DISTINCT type FROM names WHERE name = %s', $clean);
	if (!$types)
		return false;
	
	if ($type)
		return in_array($type, $types) && ($soft || !in_array($type == FIRST_NAME ? LAST_NAME : FIRST_NAME, $types));
	return $types;
}



function kaosLintPerson2($a){
	if (!empty($a['first_name']) || (!empty($a['type']) && $a['type'] != 'person'))
		return $a;

	// lint name
	$a['name'] = preg_replace('#\s+#', ' ', preg_replace('#[^\s\pL-]#ius', '', $a['name']));
		
	$names = explode(' ', $a['name']);
	for ($level = 0; $level < 2; $level++){
		$a['name'] = array();
		$a['first_name'] = array();
		$types = array();
		$i = 0;
		foreach ($names as $n){
			
			if (!$a['name'] || !$a['first_name']){ // follow after both are started
				
				// queue all as name after 3 first names and no names
				if (count($a['first_name']) >= 3 || ($i == count($names) - 1 && !$a['name']))
					$queueAs = 'name';
					
				// queue as first name if detected as such and not the last one and no first name
				else if (kaosIsName($n, FIRST_NAME, $level > 0) || ($i == count($names) - 1 && !$a['first_name']))
					$queueAs = 'first_name';
					
				else if (kaosIsName($n, LAST_NAME))
					$queueAs = 'name';
					
				else
					$queueAs = 'first_name';
			}
			
			$a[$queueAs][] = $n;
			$i++;
		}
		if ($a['first_name'])
			break;
	}
	$a['name'] = implode(' ', $a['name']);
	$a['first_name'] = $a['first_name'] ? implode(' ', $a['first_name']) : null;
	
	if (empty($a['name'])){
		$a['name'] = $a['first_name'];
		$a['first_name'] = null;
	}
	
	return $a;
}


function kaosLintPerson($a){
	if (!empty($a['first_name']) || (!empty($a['type']) && $a['type'] != 'person'))
		return $a;

	// lint name
	$a['name'] = preg_replace('#\s+#', ' ', preg_replace('#[^\s\pL-\']#ius', '', $a['name']));
		
	$names = explode(' ', $a['name']);

	for ($level = 0; $level <= 2; $level++){
		$a['name'] = array();
		$a['first_name'] = array();
		$types = array();
		$i = 0;
		
		$scores = array();
		foreach (array('ltr', 'rtl') as $dir){
			$scores[$dir] = 0;
			$nameI = 0;
			foreach ($dir == 'ltr' ? $names : array_reverse($names) as $n){
				if ($level > 1 && !$nameI)
					$scores[$dir]++;
				else if (kaosIsName($n, FIRST_NAME, $level > 0))
					$scores[$dir]++;
				else
					break;
				$nameI++;
			}
		}
			
		$min = $level > 1 ? 1 : 0;
		if ($scores['rtl'] > $min && $scores['rtl'] > $scores['ltr']){
			while ($scores['rtl'] < 3 && $scores['rtl'] < count($names) - 1 && kaosIsName($names[$scores['rtl']], FIRST_NAME, $scores['rtl'] < 3))
				$scores['rtl']++;
			$a['first_name'] = array_slice($names, count($names) - $scores['rtl']);
			$a['name'] = array_slice($names, 0, count($names) - $scores['rtl']);
			break;
		
		} else if ($scores['ltr'] > $min || $scores['ltr'] > $scores['rtl'] || ($level > 1 && $scores['ltr'] > 1)){
			while ($scores['ltr'] < 3 && $scores['ltr'] < count($names) - 1 && kaosIsName($names[$scores['ltr']], FIRST_NAME, $scores['ltr'] < 3))
				$scores['ltr']++;
			$a['first_name'] = array_slice($names, 0, $scores['ltr']);
			$a['name'] = array_slice($names, $scores['ltr']);
			break;
		} 

		$a['first_name'] = null;
		$a['name'] = $names;
	}
	$a['name'] = implode(' ', $a['name']);
	$a['first_name'] = $a['first_name'] ? implode(' ', $a['first_name']) : null;
	
	if (empty($a['name'])){
		$a['name'] = $a['first_name'];
		$a['first_name'] = null;
	}
	
	return $a;
}


function lintName($name){
	$name = preg_replace('#\s+#u', ' ', $name);
	if (mb_strtoupper($name) == $name || mb_strtolower($name) == $name){
		
		// words of 3+ chars in first-letter cap
		// TODO: use a list of meaningless words from country schema: "of", "from", "for"..
		
		$name = preg_replace_callback('#[a-z]{3,}#u', function($m){
			return mb_convert_case(mb_strtolower($m[0]), MB_CASE_TITLE, 'UTF-8');
		}, mb_strtolower($name));
		
		// first letter uppercase
		$name = preg_replace_callback('#^[a-z]#u', function($m){
			return mb_convert_case($m[0], MB_CASE_UPPER, 'UTF-8');
		}, $name);
	}
	return $name;
}
