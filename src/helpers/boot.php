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


// LOAD ALL HELPERS   		<-- leave this comment as it is, the following list is parsed from PHP to be injected in the DEVELOPERS manual 

//require 'boot.php'; 		// helpers' initialization (includes all the following)
//require 'compile.php'; 	// manuals' compilation methods (generates this manual)
//require 'export.php'; 	// export function for database structure dump
require 'actions.php';		// actions (hooks) and filters, to add modularity
require 'cli.php';			// command-line help (CLI)
require 'system.php';		// system/disk helpers
require 'access.php';		// access, roles, auth..
require 'live.php';			// method to lazy-load/process pieces of HTML
require 'table.php';		// print uniform tables
require 'cache.php';		// database caching
require 'api.php';			// JSON and document APIs
require 'locks.php';		// locks, for workers to be able to process in parallel
require 'log.php';			// log / output for logging
require 'options.php';		// manage global persistent options
require 'error.php';		// error handling
require 'encoding.php';		// encoding/charset conversion
require 'language.php';		// internationalization
require 'templates.php';	// templating system
require 'entities.php';		// entities helper functions
require 'spiders.php';		// spiders helpers
require 'locations.php';	// geolocation methods
require 'string.php';		// string processing
require 'currency.php';		// currencies handling
require 'fetch.php';		// remote URL fetching
require 'urls.php';			// URL/permalinks helpers
require 'seo.php';			// search engine optimization (SEO)
//require 'images.php';		// images optimization (still under development, not called yet)
require 'time.php';			// time/duration/date functions
require 'db.php';			// database and query handler
require 'bulletins.php';	// bulletin helpers
require 'schemas.php';		// schemas helpers
require 'labels.php';		// label sets
require 'map.php';			// map/rewind methods
require 'file.php';			// local file handling
require 'names.php';		// people's name helpers
require 'assets.php';		// asset management (css, js..)
require 'license.php';		// licensing helpers

// END LOAD ALL HELPERS		<-- leave this comment as it is, the preceeding list is parsed from PHP to be injected in the DEVELOPERS manual 



// load enabled addons
if (!empty(LOAD_ADDONS))
	foreach (explode(',', LOAD_ADDONS) as $a)
		require APP_PATH.'/addons/'.$a.'.php';

// load classes
require APP_PATH.'/controller/MainController.php';
require APP_PATH.'/fetcher/BulletinFetcher.php';
require APP_PATH.'/parser/BulletinParser.php';
require APP_PATH.'/extractor/BulletinExtractor.php';

