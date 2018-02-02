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


function is_valid_schema_path($type){
	return preg_match('#^([A-Z0-9_]+)(/[A-Z0-9_]+)*$#', $type);
}

function get_country_schema($schema){
	if (is_object($schema)){
		if (in_array($schema->type, array('country', 'continent')))
			return $schema;
		$schema = $schema->id;
	}
	$country = strstr($schema, '/', true);
	return get_schema($country === false ? $schema : $country);
}

function get_provider_schema($type, $fill = false, $keepVocabulary = false){
	$schema = is_object($type) ? $type : get_schema($type);
	if ($fill){
		if (!empty($schema->country)){
			$schema->country = get_schema($schema->country);
			unset($schema->country->vocabulary);
		}
	}
	return $schema;
}

function get_schema($type, $raw = false){
	static $cache = array();
	$type = strtoupper($type);
	
	if (isset($cache[$type]) && !$raw)
		return $cache[$type];

	if (!is_valid_schema_path($type))
		return false;

	// correct ES => ES/ES and ES/BOE => ES/BOE/BOE
	$type = get_schema_path($type);
	/*
	if (strpos($type, '/') === false) // correct country "ES" => "ES/ES"
		$type = $type.'/'.$type;
	*/

	$schemaUrl = SCHEMAS_PATH.'/'.$type.'.json';
	if (!file_exists($schemaUrl) || !is_file($schemaUrl))
		return false;

	if (!($schema = file_get_contents($schemaUrl)))
		return false;

	if ($raw)
		return $schema;
	
	if (!($obj = parse_schema($schema, $linted))){
		echo '<div style="color: red"><i class="fa fa-warning"></i> Malformed '.$type.' schema: </div>'.print_json($linted, false);
		die();
	}

	$cache[$type] = $obj;
	return $obj;
}

function get_schema_path($schema){
	if (preg_match('#^(.*/)?([^/]+)$#i', $schema, $m))
		return $m[1].$m[2].'/'.$m[2];
	return $schema;
}

function parse_schema($schema, &$linted = null){
	$linted = strip_comments($schema, 'json');
	$linted = preg_replace('/^(\s*)(\w+)(\s*:\s*(".*?"|.))/sm', '$1"$2"$3', $linted); // correct keys between quotes
	$linted = str_replace('\\', '\\\\', $linted); // correct backslash bug

	return @json_decode($linted);
}
	
function get_remote_schema($schema){
	if (!GITHUB_SYNC)
		return null;
		
	$remoteSchema = file_get_contents('https://raw.githubusercontent.com/'.SMAP_GITHUB_REPOSITORY.'/master/schemas/'.get_schema_path($schema).'.json');
	if (!empty($remoteSchema) && ($remoteSchema = parse_schema($remoteSchema)))
		return $remoteSchema;
	return null;
}

function get_country_from_schema($schema){
	if (is_object($schema))
		$schema = $schema->id;
	return preg_match('#^([a-z]{2,3})(/.*)?$#iu', $schema, $m) ? $m[1] : null;
}

function is_country($str){
	return !!get_country_schema($str);
}

function get_schema_title($schema, $query = array(), $short = false){
	
	if ($short && !empty($schema->shortName))
		return $schema->shortName;
		
	$title = $schema->name;
	if (!empty($schema->shortName))
		$title .= ' ('.$schema->shortName.')';
	return $title;
}

function schema_has_feature($schema, $feature){
	static $cache = array();
	if (!isset($cache[$schema])){
		$s = get_schema($schema);
		$features = array('providers', 'browse', 'schema');
		if (!empty($s->fetchProtocoles)){
			$features[] = 'fetch';
			$features[] = 'lint';
			$features[] = 'rewind';
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

function get_schema_countries(){
	$files = array();
	foreach (ls_dir(SCHEMAS_PATH) as $file)
		if (preg_match('#^[A-Z]{2,3}$#i', $file))
			$files[] = $file;
	return $files;
}

function get_schemas($filter = null){

	$files = ls_dir_schemas(SCHEMAS_PATH.($filter ? '/'.strtoupper($filter) : ''));
	if ($filter)
		array_unshift($files, strtoupper($filter));

	$sorted = array();
	$dims = array('continent', 'country', 'institution', 'bulletin');
	if ($filter){
		$s = get_schema($filter);
		if ($s->type != 'continent')
			array_shift($dims);
	}

	queue_schemas($sorted, $files, $dims);

	// add continent countries
	if ($filter && $s->type == 'continent'){
		$countries = array();
		foreach (ls_dir_schemas(SCHEMAS_PATH) as $f){
			if (($c = get_schema($f)) && $c->type == 'country' && $c->continent == $s->id)
				$countries[] = $f;
		}
		if ($countries){
			usort($countries, function($a, $b){
				$a = get_schema($a);
				$b = get_schema($b);
				return (!empty($a->originalName) ? $a->originalName : $a->name) >= (!empty($b->originalName) ? $b->originalName : $b->name);
			});
			$sorted = array_merge($sorted, $countries);
		}
	}
	return $sorted;
}

function queue_schemas(&$sorted, $files, $types, $parent = null){
	$type = array_shift($types);
	foreach ($files as $f){
		if (($schema = get_schema($f)) && $schema->type == $type){
			switch ($type){
				case 'continent':
					$sorted[] = $f;
					queue_schemas($sorted, $files, array_slice($types, 1), $schema);
					queue_schemas($sorted, $files, $types, $schema);
					break;
				case 'country':
					if (!$parent || $schema->continent == $parent->id){
						$sorted[] = $f;
						queue_schemas($sorted, $files, $types, $schema);
					}
					break;
				case 'institution':
					if ((empty($schema->country) && $schema->continent == $parent->id) || $schema->country == $parent->id){
						$sorted[] = $f;
						queue_schemas($sorted, $files, $types, $schema);
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

function ls_dir_schemas($dir, $level = 0){
	$ret = array();

	foreach (ls_dir($dir) as $file){
		$isDir = is_dir($dir.'/'.$file);
		if (!$isDir && !preg_match('#^(.*)(\.json)$#', $file)) // skip if not a .json
			continue;

		$fileBase = preg_replace('#^(.*)(\.json)$#', '$1', $file); // strip .json
		$schema = strtoupper(str_replace(SCHEMAS_PATH.'/', '', $dir.'/'.$fileBase));

		if (!preg_match('#^([a-z]+)/\1$#i', $schema)) // do not add XX/XX (country schemas)
			$ret[] = $schema;
	}

	usort($ret, function($a, $b){
		$a = get_schema($a);
		$b = get_schema($b);
		if (!$a)
			return -1;
		if (!$b)
			return 1;
		return (!empty($a->originalName) ? $a->originalName : $a->name) >= (!empty($b->originalName) ? $b->originalName : $b->name);
	});

	$fret = array();
	foreach ($ret as $file){
		$fret[] = $file;
		if (is_dir($dir.'/'.$file))
			foreach (ls_dir_schemas($dir.'/'.$file, $level+1) as $f)
				$fret[] = $f;
	}
	return $fret;
}

function get_schema_prop($schema, $prop, $allow_remote_update = false){
	
	$val = !empty($schema->{$prop}) ? $schema->{$prop} : array();

	// update property from the remote repository's schema file
	if ($allow_remote_update && ($remoteSchema = get_remote_schema($schema->id)))
		$val = !empty($remoteSchema->{$prop}) ? $remoteSchema->{$prop} : array();
		
	return $val;
}

function get_schema_avatar_url($schema){
	
	if (in_array($schema->type, array('continent', 'country'))) // all countries and continents have a flag
		return get_flag_url($schema->id, IMAGE_SIZE_SMALL);
		
	$path = get_schema_path($schema->id);
	if (SCHEMAS_PATH.'/'.$path.'.png')
		return SCHEMAS_URL.'/'.$path.'.png';
	return null;
}
