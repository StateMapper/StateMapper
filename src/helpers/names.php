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

//if (!empty($_GET['reload_names'])) 
//	install_load_names(); 
	
function install_load_names($conn = null, $args = array()){ // provide $conn if installing only
	
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
							insert_name($last, $type, $conn);
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
								insert_name($name, $type, $conn);
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

function insert_name($name, $type, $conn = null){
	$cname = mb_strtolower(remove_accents(preg_replace('#\s+#', ' ', trim($name))));
	if ($conn)
		mysqli_query($conn, "INSERT INTO names (type, name) VALUES ('".mysqli_real_escape_string($conn, $type)."', '".mysqli_real_escape_string($conn, $cname)."')");
	else
		insert('names', array(
			'type' => $type,
			'name' => $cname,
		));
}

function is_name($str, $type = null, $soft = false){
	$clean = mb_strtolower(remove_accents(preg_replace('#\s+#', ' ', trim($str))));
	if (empty($clean))
		return false;
		
	$types = get_col('SELECT DISTINCT type FROM names WHERE name = %s', $clean);
	if (!$types)
		return false;
	
	if ($type)
		return in_array($type, $types) && ($soft || !in_array($type == FIRST_NAME ? LAST_NAME : FIRST_NAME, $types));
	return $types;
}

function sanitize_person($a){
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
				else if (is_name($n, FIRST_NAME, $level > 0))
					$scores[$dir]++;
				else
					break;
				$nameI++;
			}
		}
			
		$min = $level > 1 ? 1 : 0;
		if ($scores['rtl'] > $min && $scores['rtl'] > $scores['ltr']){
			while ($scores['rtl'] < 3 && $scores['rtl'] < count($names) - 1 && is_name($names[$scores['rtl']], FIRST_NAME, $scores['rtl'] < 3))
				$scores['rtl']++;
			$a['first_name'] = array_slice($names, count($names) - $scores['rtl']);
			$a['name'] = array_slice($names, 0, count($names) - $scores['rtl']);
			break;
		
		} else if ($scores['ltr'] > $min || $scores['ltr'] > $scores['rtl'] || ($level > 1 && $scores['ltr'] > 1)){
			while ($scores['ltr'] < 3 && $scores['ltr'] < count($names) - 1 && is_name($names[$scores['ltr']], FIRST_NAME, $scores['ltr'] < 3))
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

function normalize_name($name, $country){
	$name = preg_replace('#\.#u', '', $name);
	
	$name = preg_replace_callback('#\b([\S]+)\b#iu', function($cword) use ($country){
		return is_usual_word($cword[0], $country) ? '' : $cword[0];
	}, $name);
	
	$name = preg_replace('#[^\pL0-9\s]#u', ' ', mb_strtolower(remove_accents($name)));
	$name = minimize_spaces($name);
	
	$words = explode(' ', $name);
	sort($words);
	$name = implode(' ', array_unique($words));
	return $name;
}

function beautify_name($name, $country){
	

	// remove weird characters
	$name = preg_replace('#[^\pL0-9\.\s&-]#u', ' ', $name); 
	
	// minimize double letters and spaces
	$name = preg_replace('#([^\pL0-9&])+#u', '$1', $name); 
	$name = minimize_spaces($name);
	
	// ucfirst all words
	$name = preg_replace_callback('#(\b[\pL0-9]+\b)#u', function($m){
		return mb_convert_case($m[0], MB_CASE_TITLE, 'UTF-8');
	}, $name);
	
	// lowercase usual words
	$name = preg_replace_callback('#\b([\pL0-9&]){1,5}\b#u', function($m) use ($country) {
		return ($word = is_usual_word($m[0], $country)) ? $word : $m[0];
	}, $name);
	
	// uppercase all acronyms
	$name = preg_replace_callback('#(\b[\pL0-9&]\b\.?\s?)#u', function($m) use ($country) {
		$ret = preg_replace('#^(.*?)(\.[\pL0-9]+)(\s*)$#iu', '$1$2.$3', $m[0]); // fix last dot
		return mb_strtoupper($ret);
	}, $name);
	
	// uppercase seeming acronyms
	$name = preg_replace_callback('#\b([\pL0-9&]\s){1,5}\b#u', function($m) use ($country) {
		return mb_strtoupper($m[0]);
	}, $name);
	
	// very first letter always uppercase
	$name = preg_replace_callback('#^[\pL0-9]#u', function($m){
		return mb_convert_case($m[0], MB_CASE_UPPER, 'UTF-8');
	}, $name);

	return $name;
}
