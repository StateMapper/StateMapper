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

@session_start();

// CLI
define('KAOS_IS_CLI', !empty($argv));

libxml_disable_entity_loader(true); // protect against XEE. See: https://www.owasp.org/index.php/XML_External_Entity_(XXE)_Prevention_Cheat_Sheet#PHP

// increment to force recaching the project's CSS and JS files
define('KAOS_ASSETS_INC', 18);

// define constants
define('BASE_PATH', dirname(__FILE__));
define('APP_PATH', BASE_PATH.'/src');
define('ASSETS_PATH', BASE_PATH.'/src/assets');

if (file_exists(BASE_PATH.'/config.php')){
	require BASE_PATH.'/config.php'; 
	define('KAOS_IS_INSTALL', false);
} else {
	require BASE_PATH.'/config.sample.php'; 
	define('KAOS_IS_INSTALL', true);
}
define('ALLOW_LOGIN', KAOS_DEBUG); // for the moment, only allow login on development

if (BASE_URL == 'PUT_YOUR_BASE_URL_HERE')
	define('REAL_BASE_URL', !empty($_POST['kaosInstall_base_url']) ? $_POST['kaosInstall_base_url'] : '.');
else
	define('REAL_BASE_URL', BASE_URL);

define('APP_URL', REAL_BASE_URL.'src');
define('ASSETS_URL', REAL_BASE_URL.'src/assets');
define('SCHEMAS_PATH', BASE_PATH.'/schemas');

ini_set('max_execution_time', MAX_EXECUTION_TIME);

// constants to lighten the database
define('FIRST_NAME', 1);
define('LAST_NAME', 2);

// output
define('P_DILIMITER', "\n\n"); // paragraph delimiter

// project constants
define('KAOS_GITHUB_REPOSITORY', 'StateMapper/StateMapper');
	
	
// includes
require APP_PATH.'/helpers/core.php';

// lang
if (!empty($_GET['lang']))
	kaosSetLocale($_GET['lang']);
if (defined('LANG') && LANG)
	kaosSetLocale(LANG);
if (!defined('LANG'))
	define('LANG', 'en_US');

// includes
require APP_PATH.'/controller/Controller.php';
require APP_PATH.'/fetcher/BulletinFetcher.php';
require APP_PATH.'/parser/BulletinParser.php';
require APP_PATH.'/extractor/BulletinExtractor.php';

// init globals 
global $kaosCall;
$kaosCall = array(
	'begin' => time(),
	'cliArgs' => !empty($argv) ? array_slice($argv, 1) : null,
	'query' => array(),
	'debug' => false,
);


if ($kaosCall['cliArgs']){
	// CLI license print
	if (in_array('-l', $kaosCall['cliArgs'])){
		echo file_get_contents(BASE_PATH.'/COPYING');
		exit();
	}
	
	// CLI debug mode
	if (in_array('-dd', $kaosCall['cliArgs'])){
		$kaosCall['debug'] = true;
		$kaosCall['debugQueries'] = true;
		array_splice($kaosCall['cliArgs'], array_search('-dd', $kaosCall['cliArgs']), 1);
	} else if (in_array('-d', $kaosCall['cliArgs'])){
		$kaosCall['debug'] = true;
		array_splice($kaosCall['cliArgs'], array_search('-d', $kaosCall['cliArgs']), 1);
	}
		
	// copy -KEY=VALUE CLI params to $_GET variable
	do {
		$changed = false;
		foreach ($kaosCall['cliArgs'] as $a)
			if (preg_match('#^[-]([^=]+)=(.*)$#iu', $a, $m)){
				$_GET[$m[1]] = $m[2];
				array_splice($kaosCall['cliArgs'], array_search($a, $kaosCall['cliArgs']), 1);
				$changed = true;
				break;
			}
	} while ($changed);
}

if (!defined('LOAD_ONLY_CONFIG') || !LOAD_ONLY_CONFIG){
	// call the controller
	$c = new Controller();
	$c->exec();
	exit();
}
