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

// init CLI variables
if ($smap['cliArgs']){
	// CLI license print
	if (in_array('-l', $smap['cliArgs'])){
		echo file_get_contents(BASE_PATH.'/LICENSE');
		exit();
	}
	
	// CLI debug mode
	if (in_array('-dd', $smap['cliArgs'])){
		$smap['debug'] = true;
		$smap['debugQueries'] = true;
		array_splice($smap['cliArgs'], array_search('-dd', $smap['cliArgs']), 1);
	} else if (in_array('-d', $smap['cliArgs'])){
		$smap['debug'] = true;
		array_splice($smap['cliArgs'], array_search('-d', $smap['cliArgs']), 1);
	}
		
	// copy -KEY=VALUE CLI params to $_GET variable
	do {
		$changed = false;
		foreach ($smap['cliArgs'] as $a)
			if (preg_match('#^[-]([^=]+)=(.*)$#iu', $a, $m)){
				$_GET[$m[1]] = $m[2];
				array_splice($smap['cliArgs'], array_search($a, $smap['cliArgs']), 1);
				$changed = true;
				break;
			}
	} while ($changed);
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
	$date = '2017-01-04';
	$query = array('schema' => 'ES/BOE', 'date' => $date);
	$queryRaw = array('country' => 'es');
	
	return array(
		'' => 'print this help',
		'-l' => 'show this software\'s license (GNU AGPLv3)',
		'',
		get_providers_uri() => 'schemas list',
		get_providers_uri('es') => 'country schemas list',
	
	) + get_url_patterns() + array(
		'',
		uri($query, 'rewind') => 'rewind back to '.$date,
		uri($query, 'rewind').'/30%' => 'rewind back to '.$date.' with 30% CPU',
		uri($query, 'rewind').'/extract' => 'rewind back to '.$date.' (extracting)',
		uri($query, 'rewind').'/extract/30%' => 'rewind back to '.$date.' with 30% CPU (extracting)',
		'',
		'JSON API endpoints',
		'',
		'CALL.json' => 'where CALL is one of the above calls',
		'',
		'Raw documents endpoints',
		'',
		uri($query, 'fetch/raw') => 'retrieve the original bulletin file',
		uri($query, 'lint/raw') => 'retrieve the linted bulletin file',
		'',
		'Daemon commands',
		'',
		'daemon [start]' => 'start the daemon',
		'daemon restart' => 'restart the daemon',
		'daemon status' => 'print the daemon\'s status',
		'daemon -d' => 'start the daemon in debug mode (not daemonized)',
		'daemon stop' => 'stop the daemon (waiting for workers)',
		'daemon kill' => 'kill the daemon (emergency use only)',
		'',
		'Misc.',
		'',
		'pull' => 'update local files with the repository',
		'push [-m "COMMENT"]' => 'push all local changes to the remote repository (optionally with comment COMMENT)',
		'replace STRING_A STRING_B' => 'replace STRING_A with STRING_B in all PHP files. Use with caution!',
	);
}
