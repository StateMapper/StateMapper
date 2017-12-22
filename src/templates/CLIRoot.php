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


function kaosPrintCLIRoot(){
	ob_start();
			
?>StateMapper Copyright (C) <?= getCopyrightRange() ?>  StateMapper.net <statemapper@riseup.net> 
  
  * This program comes with ABSOLUTELY NO WARRANTY; type the same command followed by "-l" for details.
  * This is free software, and you are welcome to redistribute it
  * under certain conditions; type the same command followed by "-l" for details.
	
  * This program is a redesign of Kaos155 <https://github.com/ingobernable/Kaos155> developped by the same Ingoberlab team.
  * It aims at providing a worldwide, collaborative, public data reviewing and monitoring tool.

[ Usage: ] _______________________________________________________________

  smap                            - print this help
  smap -l                         - show StateMapper's license (GNU AGPLv3)
  
  smap api/schemas                - schemas list
  smap api/es                     - country schemas list
  smap api/es/schema              - print country schema
  smap api/es/boe/schema          - print a bulletin schema
  smap api/es/boe/redirect        - print a bulletin's original URL
  smap api/es/boe/fetch           - fetch and print an original bulletin
  smap api/es/boe/parse           - print a parsed bulletin
  smap api/es/boe/extract         - print the extract of a bulletin
  
  smap api/es/boe/<?= date("Y-m-d") ?>/anyaction         - apply anyaction to a specific bulletin by date
  smap api/es/boe/BOE-A-XXXXXXXX/anyaction     - apply anyaction to a specific bulletin by ID
  smap api/es/boe/BOE-A-XXXXXXXX/fetch/3       - fetch a specific bulletin by date, following 3 levels
  smap api/es/boe/rewind/2017-01-01/30%        - rewind back to 2017-01-01 with 30% CPU
  
  Raw JSON API: __________________________
  
  smap api/schemas/raw            - schema list
  smap api/es/raw                 - country schemas list
  smap api/es/schema/raw          - print country schema
  smap api/es/boe/schema/raw      - print a bulletin's schema
  smap api/es/boe/parse/raw       - print a parsed bulletin
  
  Daemon commands: _______________________
  
  smap daemon [start]		  - start the daemon
  smap daemon restart		  - restart the daemon
  smap daemon status		  - print the daemon's status
  smap daemon -d		  - start the daemon in debug mode (not daemonized)
  smap daemon stop		  - stop the daemon (waiting for workers)
  smap daemon kill		  - kill the daemon (emergency use only)

[ Schema list: ] _________________________________________________________

<?php 		

	kaosAPIPrintDirCLI('  ', 35); 
	echo str_replace("\n", PHP_EOL, ob_get_clean()).PHP_EOL;
	exit();
}


function kaosAPIPrintDirCLI($prefix = '', $pad = 25){
	global $kaosCall;
	$filter = !empty($kaosCall['rootSchema']) ? $kaosCall['rootSchema'] : null;

	$last = null;
	foreach (kaosAPIGetSchemas($filter) as $file){
		$schema = kaosGetSchema($file);
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

function kaosAPIPrintCLIAPIRoot(){
	global $kaosCall;
	$filter = !empty($kaosCall['rootSchema']) ? $kaosCall['rootSchema'] : null;
	
	if (!$kaosCall['raw'])
		kaosAPIPrintDirCLI();
	else
		kaosAPIReturn(kaosAPIGetSchemas($filter));
	exit();
}
