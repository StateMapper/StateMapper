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

// init CLI variables
function cli_init(){
	global $smap;
	if ($smap['cli_args']){
		
		// print license (-l)
		if (in_array('-l', $smap['cli_args'])){
			echo file_get_contents(BASE_PATH.'/LICENSE');
			exit();
		}
		
		// raw (-raw)
		if (in_array('-raw', $smap['cli_args'])){
			$smap['raw'] = true;
			$smap['human'] = false;
			array_splice($smap['cli_args'], array_search('-raw', $smap['cli_args']), 1);
		}
		
		// raw shortcuts (-json, -xml..)
		foreach (get_formats() as $f)
			if (in_array('-'.$f, $smap['cli_args'])){
				$smap['raw'] = $f;
				$smap['human'] = false;
				array_splice($smap['cli_args'], array_search('-'.$f, $smap['cli_args']), 1);
				break;
			}
		
		// debug level 2 (-dd)
		if (in_array('-dd', $smap['cli_args'])){
			$smap['debug'] = true;
			$smap['debugQueries'] = true;
			array_splice($smap['cli_args'], array_search('-dd', $smap['cli_args']), 1);
		
		// debug level 1 (-d)
		} else if (in_array('-d', $smap['cli_args'])){
			$smap['debug'] = true;
			array_splice($smap['cli_args'], array_search('-d', $smap['cli_args']), 1);
		}
		
		// execute PHP command from CLI
		if ($smap['cli_args'] && $smap['cli_args'][0] == '-e'){
			array_shift($smap['cli_args']);
			$cmd = implode(' ', $smap['cli_args']);
			$cmd = rtrim($cmd, ';').';';
			
			ob_start();
			$ret = eval($cmd);
			$output = ob_get_clean();
			
			if ($output != '')
				echo $output;
			else if ($ret)
				debug($ret);
				
			exit;
		}
		
		// interpret query args on first parameter
		if ($smap['cli_args'] && ($query_str = parse_url($smap['cli_args'][0], PHP_URL_QUERY))){
			parse_str($query_str, $params);
			if ($params)
				$smap['filters'] = $params + $smap['filters'];
		}
			
		// copy -KEY=VALUE CLI params to $smap['filters'] variable
		do {
			$changed = false;
			foreach ($smap['cli_args'] as $a)
				if (preg_match('#^-([^=]+)=(.*)$#iu', $a, $m)){
					$smap['filters'][$m[1]] = $m[2];
					array_splice($smap['cli_args'], array_search($a, $smap['cli_args']), 1);
					$changed = true;
					break;
				}
		} while ($changed);
		
	}
}

function cli_print_dir($prefix = '', $pad = 25){
	global $smap;
	$filter = !empty($smap['filters']['loc']) ? $smap['filters']['loc'] : null;

	$last = null;
	foreach (get_schemas($filter) as $file){
		$schema = get_schema($file);
		if ($schema->type == 'bulletin')
			echo $prefix.'      ';
		else if ($schema->type == 'country')
			echo $prefix.'  ';
		else if ($schema->type != 'continent')
			echo $prefix.'    ';
		else {
			if ($last)
				echo PHP_EOL;
			$last = $schema;
			echo $prefix;
		}
		echo str_pad($file, $pad).'  ';
		if ($schema->type == 'country')
			echo '- ';
		else if ($schema->type == 'bulletin')
			echo ' - ';
		else
			echo ' - ';
		echo $schema->name.PHP_EOL;
	}
}

function cli_print(){
	global $smap;
	$filter = !empty($smap['filters']['loc']) ? $smap['filters']['loc'] : null;
	
	if (!$smap['raw'])
		cli_print_dir();
	else
		return_wrap(get_schemas($filter));
	exit();
}

function get_cli_commands(){

	// calc a past date for the example
	$date = '2017-01-05';
	$query = array('schema' => 'ES/BOE', 'date' => $date);
	$queryRaw = array('country' => 'es');
	
	return array(
		'' => 'print this help',
		'-l' => 'show this software\'s license (GNU AGPLv3)',
		'',
		
		get_providers_uri() => 'show all schemas',
		get_providers_uri('es') => 'show country schemas',
		'',
		
		uri($queryRaw, 'schema') => 'country schema',
		uri($queryRaw, 'ambassadors') => 'country ambassadors',
		uri($queryRaw, 'soldiers') => 'country soldiers',
		uri($query, 'schema') => 'bulletin schema',
		uri($query, 'fetch') => 'retrieve a bulletin by date',
		uri($query, 'redirect') => 'get the original URL of a bulletin',
		uri($query, 'parse') => 'parse a bulletin',
		uri($query, 'extract') => 'extract a bulletin',
		uri($query, 'soldiers') => 'bulletin\'s soldiers',
		
		'',
		uri($query, 'fetch').'/DOCUMENT_ID' => 'retrieve a bulletin by date and ID',
		'es/bulletin/boe/DOCUMENT_ID' => 'retrieve a bulletin by ID (its date will be guessed)',
		'es/bulletin/boe/DOCUMENT_ID/MODE' => 'call any previous MODE to a specific document',
		'es/bulletin/boe/'.$date.'/DOCUMENT_ID/MODE' => 'call any previous MODE to a specific document and date',

		'',
		
		'"es?q=rise&limit=3"' => 'search up to 3 Spanish entities matching "rise"',
		'es -q=rise -limit=3' => 'exact same query in CLI style',
		'es/institution/ayuntamiento-de-madrid' => 'entity sheet, by URL',
		'entity/77' => 'entity sheet, by ID',
		'',
		
		'Rewind commands',
		'',
		uri($query, 'rewind') => 'rewind back to '.$date,
		uri($query, 'rewind').'/30%' => 'rewind back to '.$date.' with 30% CPU',
		uri($query, 'rewind').'/extract' => 'rewind back to '.$date.' (extracting)',
		uri($query, 'rewind').'/extract/30%' => 'rewind back to '.$date.' with 30% CPU (extracting)',
		'',

		'JSON API endpoints',
		'',

		'CALL.json' => 'return most of the previous calls in pure json',
		'CALL -[json|raw]' => 'exact same query in CLI style',
		'',

		'Daemon commands',
		'',
		'daemon start' => 'start the daemon',
		'daemon restart' => 'restart the daemon',
		'daemon status' => 'print the daemon\'s status',
		'daemon -d' => 'start the daemon in debug mode (not daemonized)',
		'daemon stop' => 'stop the daemon (waiting for workers)',
		'daemon kill' => 'kill the daemon (emergency use only)',
		'',

		'Spider commands',
		'',
		'spiders' => 'display a summary of the spiders\' states and configuration',
		'spider '.strtolower($query['schema']).' turn [on|off]' => 'turn the '.$query['schema'].' spider on/off',
		'spider '.strtolower($query['schema']).' config PARAM VALUE' => 'configure the parameter PARAM with value VALUE for the '.$query['schema'].' spider',
		'"' => 'where PARAM is [date_back|max_workers|max_cpu_rate|extract]',
		'',
		
		'Administrator commands',
		'',
		'admin create_user' => 'create a new user (interactive)',
		'admin change_user_pass' => 'change a user\'s password (interactive)',
		'admin change_user_status' => 'change a user\'s status (interactive)',
		'',

		'Miscellaneous',
		'',
		'-d ...' => 'enable debugging',
		'-dd ...' => 'enable debugging and print all database queries',
		'pull' => 'update local files with the repository',
		'push [-m "COMMENT"]' => 'push all local changes to the remote repository (optionally with comment COMMENT)',
		'replace STRING_A STRING_B' => 'replace STRING_A with STRING_B in all PHP files. Use with caution!',
		'admin clear' => 'empty several tables to throw a fresh spider (do NOT use in production!)',
		'admin db_export' => 'export the current database\'s schema to database/structure.sql (use with caution!)',
		'compile' => 'compile manuals and translations. Called by "smap push" before pushing changes.'
	);
}
