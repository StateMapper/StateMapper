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

function print_template($id, $vars = array()){
	global $smap;
	static $varStack = array();
	array_unshift($varStack, $vars);
	
	$cvars = array();
	foreach ($varStack as $v)
		$cvars += $v;
	extract($cvars);
	
	include(APP_PATH.'/templates/'.$id.'.php');
	array_shift($varStack);
}

function get_template($id, $vars = array()){
	ob_start();
	print_template($id, $vars);
	return ob_get_clean();
}

function print_page($id, $vars = array()){
	return_wrap(get_template($id, $vars));
	exit();
}

function return_wrap($obj, $title = null){
	global $smap;

	// add stats to returned objects
	if (is_array($obj) && isset($obj['success'])){
		$obj = array('success' => $obj['success'], 'stats' => array()) + $obj;

		$keys = array('fetchOrigins', 'fetchedUrls', 'torIpChanges');
		if (is_rate_limited())
			$keys[] = 'rateLimit';
		foreach ($keys as $k)
			$obj['stats'][$k] = !empty($smap[$k]) ? $smap[$k] : array();
	}

	if (!IS_CLI){
		
		// normal printing (web page)
		if (empty($smap['raw'])){
			print_template('page', array('obj' => $obj, 'title' => $title));
			exit();
		} 
	
		// human json (web page)
		if (!empty($_GET['human'])){
			print_template('api', array('obj' => $obj, 'title' => $title));
			exit();
		} 
		
		// json API
		header('Content-type: application/json');
	
	} else if (empty($smap['raw'])){
		print_json($obj, true);
		exit();
	}

	// json API
	echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	exit();
}
