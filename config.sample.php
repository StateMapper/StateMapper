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

/********************************************************************
 * Main $tateMapper config file 
 */

if (!defined('BASE_PATH')) // leave this
	die();
 
global $smapConfig; // leave this

// Base URL 
define('BASE_URL', 'PUT_YOUR_BASE_URL_HERE'); // the base URL (with trailing slash)
define('FORCE_SSL', false); // force SSL/HTTPS (set to true when in production! see https://letsencrypt.org/ for free certificates)

// MySQL database
define('DB_HOST', 'PUT_YOUR_DATABASE_HOST_HERE'); // your database's host
define('DB_NAME', 'PUT_YOUR_DATABASE_NAME_HERE'); // your database's name
define('DB_USER', 'PUT_YOUR_DATABASE_USER_HERE'); // your database's user
define('DB_PASS', 'PUT_YOUR_DATABASE_PASS_HERE'); // your database's password

// dev/debug
define('IS_DEBUG', true); // set to false when in production!
define('ALLOW_LOGIN', IS_DEBUG); // for the moment, only allow login on development
define('DEV_REDUCE_ENTITIES', false); // set to 5 to reduce the amount of parsed entities and speed up development, set to false in production or real rewind mode!
define('SMAP_FRONTPAGE_MESSAGE', false); // a static message to show on the homepage

// system limits
ini_set('memory_limit','2048M'); // maximum memory dedicated to each call to this app
define('MAX_EXECUTION_TIME', 900); // maximum execution time (900 is 15min)

// folders
define('DATA_PATH', BASE_PATH.'/bulletins'); // folder to store fetched bulletin files

// caching
define('USE_PROCESSED_FILE_CACHE', false);//true); // enable or disable processed cache files (.parsed.json)
define('DISKSPACE_CHECK_FREQUENCY', '3 minutes'); // how often to recalculate free and folder disk space

// spiders' default behavour
define('SPIDER_WORKERS_COUNT', 80); // default maximum amount of workers to throw (should be less than MySQL's max connection count)
define('SPIDER_MAX_CPU', 20); // default proportion of CPU to use for the spider (%)

// define('LANG', 'es_ES'); // do not set (or comment) to leave it in English

// caches
define('BULLETIN_CACHES_READ', 'local');//,ipfs'); // list of caches to read from, in order
define('CURL_TIMEOUT', 120); // better 2min	
define('CURL_RETRY', 2); // better leave to 3 when not rewinding?
define('CURL_WAIT_BETWEEN', 1); // minimum wait between fetches, in seconds
define('CURL_RANDOM_WAIT', 1); // max additional random wait between fetches, in seconds

// proxies 
define('FETCH_USE_PROXY', false); // useless if Tor is enabled
define('FETCH_PROXY_LIST', ''); // comma-separated list of proxy IPs to use (randomly) for fetching

// API
define('API_RATE_LIMIT', 30); // allowed amount of calls within API_RATE_PERIOD
define('API_RATE_PERIOD', '1 hour'); // time frame of the rate-limitation (PHP duration string format, see strtotime)

// addons
define('LOAD_ADDONS', '');//wikipedia,relatives,here_com,company_website,schema_links,searx');
define('GITHUB_SYNC', false); // sync schema Soldiers and Ambassadors with the ones from the repository

// Here.com
define('HERE_COM_APP_ID', false); // copy your app ID from https://developer.here.com/projects
define('HERE_COM_APP_SECRET', false); // copy the corresponding app secret

// TOR fetch config 
// @todo: add bin path!
define('TOR_ENABLED', false); // enable or disable TOR support
define('TOR_PROXY_URL', '127.0.0.1:9050'); // you might leave this, just make sure you have Tor started: "sudo service tor start"
define('TOR_CONTROL_URL', '127.0.0.1:9051'); // you might leave this too
define('TOR_RENEW_EVERY', 1000); // renew Tor IP every X bulletin fetches (persistent over session)

// IPFS config
// @todo: add bin path!
define('IPFS_ENABLED', false); // enable or disable IPFS support
define('IPFS_API_URL', 'http://127.0.0.1:5001'); // no trailing slash
define('IPFS_WEB_URL', 'http://127.0.0.1:8080'); // no trailing slash

$smapConfig['IPFS'] = array( // list of $tateMapper IPFS nodes
	'fetchFrom' => array(
		'/ipns/QmPxfeJeq97aK5Xr26eG1caWz4Q7qeqMhD7EiNNXWtxXFK' => array(
			'name' => 'Main StateMapper node', // main $tateMapper IPFS node
		),
	),
	'uploadTo' => array(
		'/ipns/QmPxfeJeq97aK5Xr26eG1caWz4Q7qeqMhD7EiNNXWtxXFK' => array(
			'name' => 'Main StateMapper node', // main $tateMapper IPFS node
		),
	)
);

