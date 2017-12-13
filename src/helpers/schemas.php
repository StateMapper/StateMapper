<?php

if (!defined('BASE_PATH'))
	die();



function kaosIsValidSchemaPath($type){
	return !!preg_match('#^([A-Z0-9_]+)(/[A-Z0-9_]+)*$#', $type);
}

function kaosGetProviderSchema($type, $fill = false, $keepVocabulary = false){
	$schema = is_object($type) ? $type : kaosGetSchema($type);
	if ($fill){
		if (!empty($schema->country)){
			$schema->country = kaosGetSchema($schema->country);
			unset($schema->country->vocabulary);
		}
	}
	return $schema;
}

function kaosGetSchema($type, $raw = false){
	static $cache = array();

	if (isset($cache[$type]) && !$raw)
		return $cache[$type];

	if (strpos($type, '/') === false) // correct country "ES" => "ES/ES"
		$type = $type.'/'.$type;

	if (!kaosIsValidSchemaPath($type))
		return false;

	$schemaUrl = SCHEMAS_PATH.'/'.$type.'.json';
	if (!file_exists($schemaUrl) || !is_file($schemaUrl))
		return false;

	if (!($schema = @file_get_contents($schemaUrl)))
		return false;

	if ($raw)
		return $schema;
	
	if (!($obj = lintSchema($schema, $linted))){
		echo '<div style="color: red"><i class="fa fa-warning"></i> Malformed '.$type.' schema: </div>'.kaosJSON($linted, false);
		die();
	}

	$cache[$type] = $obj;
	return $obj;
}

function lintSchema($schema, &$linted = null){
	$linted = kaosStripComments($schema);
	$linted = preg_replace('/^(\s*)(\w+)(\s*:\s*(".*?"|.))/sm', '$1"$2"$3', $linted); // correct keys between quotes
	$linted = str_replace('\\', '\\\\', $linted); // correct backslash bug

	return @json_decode($linted);
}
	
function kaosGetRemoteSchema($schema){
	if (!GITHUB_SYNC)
		return null;
		
	$remoteSchema = file_get_contents('https://raw.githubusercontent.com/'.KAOS_GITHUB_REPOSITORY.'/master/schemas/'.$schema.(strpos($schema, '/') === false ? '/'.$schema : '').'.json');
	if (!empty($remoteSchema) && ($remoteSchema = lintSchema($remoteSchema)))
		return $remoteSchema;
	return null;
}

function kaosGetCountrySchema($schema){
	if (is_object($schema))
		$schema = $schema->id;
	$schemaParts = explode('/', $schema);
	$code = array_shift($schemaParts);
	return kaosGetSchema($code.'/'.$code);
}


function kaosGetSchemaTitle($schema, $query = array()){
	$title = $schema->name;

	if (!empty($schema->shortName))
		$title .= ' ('.$schema->shortName.')';
		/*
	$noProvider = false;
	if (!empty($schema->provider))
		$title .= ' <br>by "'.$schema->provider->name.'"';
	else if (!empty($schema->providerId) && ($pSchema = kaosGetSchema($schema->providerId)))
		$title .= ' <br>by "'.$pSchema->name.'"';
	else
		$noProvider = true;
	if (!$noProvider && !empty($schema->official))
		$title .= ' (official)';

	$str = array();

	$country = $region = null;
	if (!empty($schema->region))
		$region = $schema->region;
	if (!empty($schema->country))
		$country = $schema->country;
	else if (!empty($schema->providerId) && ($pSchema = kaosGetSchema($schema->providerId))){
		$country = $pSchema->country;
		if (!empty($pSchema->region))
			$region = $pSchema->region;
	}
	if (!empty($schema->provider) && !empty($schema->provider->region))
		$region = $schema->provider->region;

	if ($country && ($countrySchema = kaosGetCountrySchema($country))){
		if (!empty($region))
			$str[] = $region.' region';
		$str[] = $countrySchema->name;
	}

	if ($str)
		$title .= ', '.implode(', ', $str);
	*/
	return $title;
}

function kaosSchemaHasFeature($schema, $feature){
	static $cache = array();
	if (!isset($cache[$schema])){
		$s = kaosGetSchema($schema);
		$features = array();
		if (!empty($s->fetchProtocoles)){
			$features[] = 'fetch';
			if (!empty($s->parsingProtocoles)){
				$features[] = 'parse';
				if (!empty($s->extractProtocoles))
					$features[] = 'extract';
			}
		}
		$cache[$schema] = $features;
	}
	return in_array($feature, $cache[$schema]);
}

function kaosAPIGetSchemas($filter = null){

	$files = kaosAPIPrintDir(SCHEMAS_PATH.($filter ? '/'.$filter : ''));
	if ($filter)
		array_unshift($files, $filter);

	$sorted = array();
	$dims = array('continent', 'country', 'institution', 'bulletin');
	if ($filter){
		$s = kaosGetSchema($filter);
		if ($s->type != 'continent')
			array_shift($dims);
	}

	kaosAddSchemas($sorted, $files, $dims);

	if ($filter && $s->type == 'continent'){
		// add continent countries
		$countries = array();
		foreach (kaosAPIPrintDir(SCHEMAS_PATH) as $f){
			if (($c = kaosGetSchema($f)) && $c->type == 'country' && $c->continent == $s->id)
				$countries[] = $f;
		}
		if ($countries){
			usort($countries, function($a, $b){
				$a = kaosGetSchema($a);
				$b = kaosGetSchema($b);
				return (!empty($a->originalName) ? $a->originalName : $a->name) >= (!empty($b->originalName) ? $b->originalName : $b->name);
			});
			$sorted = array_merge($sorted, $countries);
		}
	}
	return $sorted;
}

function kaosAddSchemas(&$sorted, $files, $types, $parent = null){
	$type = array_shift($types);
	foreach ($files as $f){
		if (($schema = kaosGetSchema($f)) && $schema->type == $type){
			switch ($type){
				case 'continent':
					$sorted[] = $f;
					kaosAddSchemas($sorted, $files, array_slice($types, 1), $schema);
					kaosAddSchemas($sorted, $files, $types, $schema);
					break;
				case 'country':
					if (!$parent || $schema->continent == $parent->id){
						$sorted[] = $f;
						kaosAddSchemas($sorted, $files, $types, $schema);
					}
					break;
				case 'institution':
					if ((empty($schema->country) && $schema->continent == $parent->id) || $schema->country == $parent->id){
						$sorted[] = $f;
						kaosAddSchemas($sorted, $files, $types, $schema);
					}
					break;
				case 'bulletin':
					if ($schema->providerId == $parent->id)
						$sorted[] = $f;
					break;
			}
		}
	}
}

function kaosAPIPrintDir($dir, $level = 0){
	$ret = array();
	foreach (kaosLsdir($dir) as $file){

		$isDir = is_dir($dir.'/'.$file);
		if (!$isDir && !preg_match('#^(.*)(\.json)$#', $file))
			continue;

		$fileBase = preg_replace('#^(.*)(\.json)$#', '$1', $file);
		$schema = strtoupper(str_replace(SCHEMAS_PATH.'/', '', $dir.'/'.$fileBase));

		if (!preg_match('#^([a-z]+)/\1$#i', $schema)) // do not add XX/XX (country schemas)
			$ret[] = $schema;
	}

	usort($ret, function($a, $b){
		$a = kaosGetSchema($a);
		$b = kaosGetSchema($b);
		return (!empty($a->originalName) ? $a->originalName : $a->name) >= (!empty($b->originalName) ? $b->originalName : $b->name);
	});

	$fret = array();
	foreach ($ret as $file){
		$fret[] = $file;
		if (is_dir($dir.'/'.$file))
			foreach (kaosAPIPrintDir($dir.'/'.$file, $level+1) as $f)
				$fret[] = $f;
	}
	return $fret;
}


