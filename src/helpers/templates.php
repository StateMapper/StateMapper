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

function print_template($id, $vars = array()){
	global $smap, $smapDebug;
	static $varStack = array();
	array_unshift($varStack, $vars);
	
	$cvars = array();
	foreach ($varStack as $v)
		$cvars += $v;
	extract($cvars);
	
	$ret = include(APP_PATH.'/templates/'.$id.'.php');
	array_shift($varStack);

	return $ret;
}

function get_template($id, $vars = array()){
	ob_start();
	$ret = print_template($id, $vars);
	$html = ob_get_clean();
	return $html == '' ? $ret : $html;
}

// $id is a template page (without extension)
// $vars are variables sent to both the API and the template file
// $template_vars are variables only sent to the template file
function print_page($id, $vars = array(), $template_vars = array(), $wrap = false){
	global $smap;
	if (IS_CLI || IS_API)
		return_wrap(array(
			'success' => true, 
			'result' => $vars && is_array($vars) && count($vars) == 1 && !isset($vars[0]) 
				? array_pop($vars) 
				: $vars
		));
	
	else if (!$wrap){
		print_template($id, $template_vars + $vars);
		
	} else // wrap up in the 'page' template
		 return_wrap(get_template($id, $template_vars + $vars));
		
	exit();
}

function return_wrap($obj){
	global $smap, $smapDebug;

	// add stats to returned objects
	if (is_array($obj) && isset($obj['success'])){
		$obj = array('success' => $obj['success'], 'stats' => array()) + $obj;

		$keys = array('fetched_origins', 'fetched_urls', 'tor_ip_changes');
		if (is_rate_limited())
			$keys[] = 'rate_limit';
		foreach ($keys as $k)
			$obj['stats'][$k] = !empty($smap[$k]) ? $smap[$k] : array();
	}

	if (!IS_CLI){
		
		// normal printing (web page)
		if (empty($smap['raw'])){
			print_template('page', array('obj' => $obj));
			exit();
		} 
	
		// human json (web page)
		if (!empty($smap['human'])){
			print_template('api', array('obj' => $obj));
			exit();
		} 
		
		// json API
		header('Content-type: application/json');
	
	} else if (!empty($smap['human'])){
		print_json($obj);
		exit();
	}

	// json API
	echo json_encode($obj, JSON_UNESCAPED_UNICODE);
	exit();
}

// print entity tooltips
function smap_ajax_inline_popup($args){
	if ($ret = apply_filters('inline_popup', null, $args))
		return $ret;
	
	return 'Entity not found';
}

function print_header($header_type = 'page'){
	print_template('parts/header', array('header' => $header_type));
}

function print_footer(){
	print_template('parts/footer');
}
