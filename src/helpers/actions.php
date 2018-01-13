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


// wordpress-style actions (hooks)

function add_action($action, $cb = null){
	static $actions = array();

	if (!$cb)
		return isset($actions[$action]) ? $actions[$action] : array();

	if (!isset($actions[$action]))
		$actions[$action] = array();
	$actions[$action][] = $cb;
}

function do_action($action){
	$args = (array) func_get_args();
	array_splice($args, 0, 1);
	foreach (add_action($action) as $cb)
		call_user_func_array($cb, $args);
}


function add_filter($name, $cb = null){
	static $cbs = array();
	if ($cb){
		if (!isset($cbs[$name]))
			$cbs[$name] = array();
		$cbs[$name][] = $cb;
	
	} 
	return $cbs;
}

function apply_filters($name){
	$cbs = add_filter($name);
	$vars = func_get_args();
	array_shift($vars);
	$var = $vars[0];
	if (isset($cbs[$name])){
		foreach ($cbs[$name] as $cb){
			$cvars = $vars;
			$cvars[0] = $var;
			$var = call_user_func_array($cb, $cvars);
		}
	}
	return $var;
}
