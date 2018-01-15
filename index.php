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

@session_start();
define('SMAP_VERSION', '1.3a');
define('IS_ALPHA', true);

// CLI
define('IS_CLI', !empty($argv));

libxml_disable_entity_loader(true); // protect against XEE. See: https://www.owasp.org/index.php/XML_External_Entity_(XXE)_Prevention_Cheat_Sheet#PHP

// increment to force recaching the project's CSS and JS files
define('ASSETS_INC', 69);
define('DEFAULT_RESULTS_COUNT', 77);

// define constants
define('BASE_PATH', dirname(__FILE__));
define('APP_PATH', BASE_PATH.'/src');
if (!defined('ASSETS_PATH'))
	define('ASSETS_PATH', BASE_PATH.'/src/assets');

if (file_exists(BASE_PATH.'/config.php')){
	require BASE_PATH.'/config.php'; 
	define('IS_INSTALL', false);
} else {
	require BASE_PATH.'/config.sample.php'; 
	define('IS_INSTALL', true);
}

// backward compatibility
if (!defined('ALLOW_LOGIN'))
	define('ALLOW_LOGIN', false);
	
if (BASE_URL == 'PUT_YOUR_BASE_URL_HERE') // tolerate links without config.php
	define('REAL_BASE_URL', './');
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
define('SEPARATOR_AND', 1);
define('SEPARATOR_OR', 1);

// project constants
define('SMAP_GITHUB_REPOSITORY', 'StateMapper/StateMapper');

// init globals 
global $smap, $smapDebug;
$smap = array(
	'begin' => microtime(true),
	'cliArgs' => !empty($argv) ? array_slice($argv, 1) : null,
	'page' => null,
	'query' => array(),
	'filters' => array(),
	'query' => array(),
	'debug' => false,
	'fetches' => 0,
	'spiderConfig' => array(),
);
$smapDebug = array();
	
// load all files
include APP_PATH.'/helpers/boot.php';

if (!defined('LOAD_ONLY_CONFIG') || !LOAD_ONLY_CONFIG){
	
	// force the use of SSL
	if (!IS_CLI && (FORCE_SSL && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on'))){
		$ssl_url = preg_replace('#^(https?)(://.*)$#iu', 'https$2', current_url(false));
		redirect($ssl_url);
	}
	
	// upgrade check
	if (!IS_INSTALL && !IS_CLI){
		$v = get_option('v');
		if (empty($v) || $v < SMAP_VERSION){
			
			if (empty($v) || $v < '1.2') // ask a full reinstallation if version is < 1.2
				die('Big upgrade, full reinstall needed! Please delete your config.php and all your database tables, then reload this page to reinstall $tateMapper.');
				
			update_option('v', SMAP_VERSION);
		}
	}
	
	// manually convert all the database
	// if (IS_DEBUG && is_admin() && !empty($_GET['setNewEngine']))
	// 	convert_db_engine($_GET['setNewEngine']);
	
	// finally, call the controller
	$c = new MainController();
	$c->route(IS_INSTALL ? null : get_uri_bits());
	exit();
}
