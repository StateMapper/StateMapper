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


function print_header_actions($actions, $urlQuery = array(), $schema = null){
	$modes = get_modes();

	foreach ($actions as $mode => $a){
		if (!isset($a['url']))
			$a['url'] = url($urlQuery, $mode, !empty($urlQuery['tr']) ? $urlQuery['tr'] : array());
		if (!isset($a['active']))
			$a['active'] = is_call($mode);
			
		if (!isset($a['disabled']))
			$a['disabled'] = !empty($urlQuery['schema']) && !schema_has_feature($urlQuery['schema'], $mode);
			
		if (!isset($a['html'])){
			$class = isset($a['class']) ? $a['class'] : '';	
			
			if (!empty($a['disabled']))
				$class .= ' header-action-disabled';
			
			if (!empty($a['active']))
				$class .= ' header-action-active';
			
			if (!isset($a['title']))
				$a['title'] = $modes[$mode]['headerTip'];
			if (!isset($a['label']))
				$a['label'] = !empty($modes[$mode]['shortTitle']) ? $modes[$mode]['shortTitle'] : $modes[$mode]['title'];
				
			$a['html'] = '<a href="'.$a['url'].'" class="'.$class.'" title="'.esc_attr($a['title']).'"><i class="fa fa-'.$modes[$mode]['icon'].'"></i><span>'.$a['label'].'</span></a>';
		}
		
		echo $a['html'];
	}
}
