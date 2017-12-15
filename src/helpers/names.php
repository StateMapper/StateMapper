<?php
	
if (!defined('BASE_PATH'))
	die();


function loadNames(){
	foreach (array(
		'first_names.txt' => FIRST_NAME,
		'last_names.txt' => LAST_NAME,
	) as $f => $type){
		//echo 'loading '.$f.'<br>';
		$names = preg_split("/\\r\\n|\\r|\\n/", file_get_contents(BASE_PATH.'/database/'.$f));
		if ($names){
			$i = 0;
			foreach ($names as $name)
				if (trim($name) != ''){
					insert('names', array(
						'type' => $type,
						'name' => mb_strtolower(remove_accents(preg_replace('#\s+#', ' ', trim($name)))),
					));
					$i++;
				}
			
			//echo 'loaded '.$i.' names<br>';
		} //else
			//echo 'error!<br>';
		//echo '<br>';
	}
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

/*
if (1){
	foreach (array(
		'Martinez Pradas Enrique',
		'Alexander Sonderer',
		'Alexander John Oliver Sonderer',
		'Sonderer Alexander John Oliver',
		'Ruiz Peinado Miguel Angel',
		'Escudero Rodriguez Maria Purificacion',
		'Escudero Rodriguez María Purificación',
		'Ahata Julienne Kongo Lumumba',
		'Mooij Anna Maria Elisabeth',
		'Madueño Ruiz Florencio Victor',
		'Menezes Guilhermina-Maria-Joanesse',
	) as $name)
		echo print_r(kaosLintPerson(array(
			'original' => $name,
			'name' => $name,
			'first_name' => null
		)), true).'<br>';

	die();
}
*/
