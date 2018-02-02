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

@session_start();
define('SMAP_VERSION', '1.4a');
define('IS_ALPHA', true);

// CLI
define('IS_CLI', !empty($argv));

libxml_disable_entity_loader(true); // protect against XEE. See: https://www.owasp.org/index.php/XML_External_Entity_(XXE)_Prevention_Cheat_Sheet#PHP

// increment to force recaching the project's CSS and JS files
define('ASSETS_INC', 136);
define('DEFAULT_RESULTS_COUNT', 77);
define('MAX_RESULTS_COUNT', 200);
define('MAX_FOLLOW_DEPTH', 3);

// define constants
define('BASE_PATH', dirname(__FILE__));
define('APP_PATH', BASE_PATH.'/src');
if (!defined('ASSETS_PATH'))
	define('ASSETS_PATH', BASE_PATH.'/src/assets');

// include the right config file
if (file_exists(BASE_PATH.'/config.php')){
	require BASE_PATH.'/config.php'; 
	define('IS_INSTALL', false);
} else {
	require BASE_PATH.'/config.sample.php'; 
	define('IS_INSTALL', true);
}

// print all PHP errors if IS_DEBUG is set to true
if (IS_DEBUG || IS_CLI){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}
define('MINIFY_JS', !IS_DEBUG);

// backward compatibility
if (!defined('ALLOW_LOGIN'))
	define('ALLOW_LOGIN', false);
	
if (BASE_URL == 'PUT_YOUR_BASE_URL_HERE') // tolerate links without config.php
	define('REAL_BASE_URL', guess_base_url());
else
	define('REAL_BASE_URL', BASE_URL);

define('APP_URL', REAL_BASE_URL.'src');
define('PROD_APP_URL', 'https://statemapper.net/src');
define('ASSETS_URL', REAL_BASE_URL.'src/assets');
define('SCHEMAS_PATH', BASE_PATH.'/schemas');

ini_set('max_execution_time', MAX_EXECUTION_TIME);

// use views? (experimental)
define('DATABASE_USE_VIEWS', false);

// constants to lighten the database
define('FIRST_NAME', 1);
define('LAST_NAME', 2);

// output
define('P_DILIMITER', "\n\n"); // paragraph delimiter
define('SEPARATOR_AND', 1);
define('SEPARATOR_OR', 2);

// project constants
define('SMAP_GITHUB_REPOSITORY', 'StateMapper/StateMapper');

// image sizes
define('IMAGE_SIZE_TINY', 30);
define('IMAGE_SIZE_SMALL', 250);

// load all files
include APP_PATH.'/helpers/boot.php';

// init globals 
global $smap, $smapDebug;

$smap = array(
	'begin' => microtime(true),
	'cli_args' => !empty($argv) ? array_merge(explode(' ', $argv[1]), array_slice($argv, 2)) : null, // all CLI arguments were put between quotes as a first argument, before being sent to this script
	'page' => null,
	'call' => null,
	'raw' => false,
	'query' => array(),
	'filters' => array(),
	'query' => array(
		'q' => null,
		'date' => null,
		'id' => null,
	),
	'debug' => false,
	'fetches' => 0,
	'spider' => array(),
	'filters' => (!empty($_GET) ? $_GET : array()) + array(
		'q' => null,
	),
);
$smapDebug = array();
cli_init();

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
	
	} else if (IS_INSTALL){
		
		$guess_url = guess_base_url();
		if (empty($_POST) && trailingslashit($guess_url) != trailingslashit(current_url()))
			redirect($guess_url);
	}
	
	// manually convert all the database
	// if (IS_DEBUG && is_admin() && !empty($_GET['setNewEngine']))
	// 	convert_db_engine($_GET['setNewEngine']);
	
	// finally, call the controller
	$c = new MainController();
	$c->init();
	$route = $c->get_route(IS_INSTALL ? null : get_uri_bits());
	$c->exec($route);
	exit();
}


// only useful before installing
function guess_base_url(){
	$base_url = (!empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http').'://';
	
	if (!empty($_SERVER['HTTP_HOST']))
		$base_url .= $_SERVER['HTTP_HOST'];
	else
		$base_url .= 'localhost';
		
	$base_url .= '/'.preg_replace('#^(/var/www(/html)?/?)(.*?)$#i', '$3', BASE_PATH);
	
	$base_url = rtrim($base_url, '/').'/';
	return $base_url;
}
