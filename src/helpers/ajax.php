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


function handle_ajax(){
	global $smap;
	if (!empty($_POST['action']) && preg_match('#^[a-z0-9_]+$#i', $_POST['action'])){
		define('IS_AJAX', true);
		
		$fn = '\\StateMapper\\smap_ajax_'.preg_replace_callback('#[A-Z]#', function($m){
			return '_'.strtolower($m[0]);
		}, $_POST['action']);
		
		if (function_exists($fn)){
			
			if (!empty($_POST['session']['query']))
				$smap['query'] = $_POST['session']['query'];
			if (!empty($_POST['session']['filters']))
				$smap['filters'] = $_POST['session']['filters'];
			if (isset($smap['query']['schema']))
				$smap['schemaObj'] = get_schema($smap['query']['schema']);
			
			// exec ajax function
			$ret = call_user_func($fn, isset($_POST['data']) ? $_POST['data'] : array());
			if ($ret === true)
				$ret = array('success' => true);
			else if (is_string($ret))
				$ret = array('success' => false, 'error' => $ret);
			echo json_encode($ret);
			exit();
		
		} 
		return is_dev() ? 'bad ajax action '.$fn : 'bad action';
	}
	define('IS_AJAX', false);
}

