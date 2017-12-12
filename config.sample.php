<?php
/*
 * StateMapper, an official bulletins browser and corruption analyzer.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net> & Ingoberlab <hacklab@ingobernable.net>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */ 

/* Main StateMapper config file */

if (!defined('BASE_PATH')) // leave this
	die();
 
global $kaosConfig; // leave this

// Base URL 
define('BASE_URL', 'http://localhost/statemapper/'); // with trailing slash!

// MySQL database
define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// dev/debug
define('KAOS_DEBUG', true); // set to false when in production
define('KAOS_DEV_REDUCE_ENTITIES', false); // set to 5 to reduce dev time, set to false in production or real rewind mode!!

// print all PHP errors
if (KAOS_DEBUG){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

// system limits
ini_set('memory_limit','2048M'); // maximum memory dedicated to each call to this app
define('MAX_EXECUTION_TIME', 900); // 15min

// folders
define('DATA_PATH', BASE_PATH.'/bulletins'); // folder to store files

// caching
define('KAOS_PROCESSED_FILE_CACHE', false);//true); // enable or disable processed cache files (.parsed.json)
define('KAOS_DISKSPACE_CHECK_FREQUENCY', '3 minutes'); // how often to recalculate free and folder disk space

// spiders' default behavour
define('KAOS_SPIDE_WORKER_COUNT', 80); // should be less than max connection count
define('KAOS_SPIDE_CPU_MAX', 20); // proportion of CPU to use for the spider (%)

// define('LANG', 'es_ES'); // do not set (or comment) to leave it in English

// TOR fetch config
define('TOR_ENABLED', false); // enable or disable TOR support
// add bin path!
define('TOR_PROXY_URL', '127.0.0.1:9050'); // you might leave this, just make sure you have Tor started: "sudo service tor start"
define('TOR_CONTROL_URL', '127.0.0.1:9051'); // you might leave this too
define('TOR_RENEW_EVERY', 1000); // renew Tor IP every X bulletin fetches (persistent over session)

// proxies 
define('KAOS_USE_PROXY', false); // useless if Tor is enabled

// caches
define('BULLETIN_CACHES_READ', 'local');//,ipfs'); // list of caches to read from, in order
define('CURL_TIMEOUT', 120); // better 2min	
define('CURL_RETRY', 2); // better leave to 3 when not rewinding?
define('CURL_WAIT_BETWEEN', 1); // minimum wait between fetches, in seconds
define('CURL_RANDOM_WAIT', 1); // max additional random wait between fetches, in seconds

// IPFS config
define('IPFS_ENABLED', true); // enable or disable IPFS support
define('IPFS_API_URL', 'http://127.0.0.1:5001'); // no trailing slash
define('IPFS_WEB_URL', 'http://127.0.0.1:8080'); // no trailing slash
// TODO: add bin path!

$kaosConfig['IPFS'] = array( // list of Kaos IPFS nodes
	'fetchFrom' => array(
		'/ipns/QmPxfeJeq97aK5Xr26eG1caWz4Q7qeqMhD7EiNNXWtxXFK' => array(
			'name' => 'Main Kaos node', // main Kaos IPFS node
		),
	),
	'uploadTo' => array(
		'/ipns/QmPxfeJeq97aK5Xr26eG1caWz4Q7qeqMhD7EiNNXWtxXFK' => array(
			'name' => 'Main Kaos node', // main Kaos IPFS node
		),
	)
);

