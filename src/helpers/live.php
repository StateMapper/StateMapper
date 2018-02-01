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


// live placeholders
add_action('live', 'live_action', 0, 2);
function live_action($live_id, $opts){
	if (function_exists('live_'.$live_id)){
		$args = array_slice(func_get_args(), 1);
		if ($placeholder = call_user_func_array('live_'.$live_id, $args)){
			if (is_array($placeholder) && $placeholder['success'])
				echo $placeholder['html'];
			else
				echo '<span class="live" '.related(array('live_id' => $live_id, 'args' => $args)).'>'.$placeholder.'</span>';
		}
	}
}

function get_live($live_id, $opts = array()){
	$args = func_get_args();
	array_unshift($args, 'live');
	ob_start();
	call_user_func_array('\\StateMapper\\do_action', $args);
	return ob_get_clean();
}

function smap_ajax_live($args){
	$ret = array();
	foreach ($args['lives'] as $live){
		array_unshift($live['args'], $live['live_id']);
		$ret[] = call_user_func_array('\\StateMapper\\get_live', $live['args']);
	}
	return array('success' => true, 'result' => $ret);
}
