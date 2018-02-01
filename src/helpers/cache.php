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

function get_cache($key){
	global $smap;
	if (is_dev() && !empty($smap['filters']['no_cache']))
		return null;
		
	$cache = get_var('SELECT cache_value FROM caches WHERE cache_key = %s AND expire > %s ORDER BY id DESC', array($key, date('Y-m-d H:i:s')));
	return $cache ? unserialize($cache) : null;
}

function set_cache($key, $value, $cache_duration = '1 day'){
	return insert('caches', array(
		'cache_value' => serialize($value),
		'cache_key' => $key,
		'expire' => date('Y-m-d H:i:s', is_numeric($cache_duration) ? time() + $cache_duration : strtotime('+'.$cache_duration, time())),
	));
}

add_action('clean_tables', 'clean_caches');
function clean_caches($all){

	// clear expired caches
	if ($all)
		query('DELETE FROM caches');
	else
		query('DELETE FROM caches WHERE expire < %s', date('Y-m-d H:i:s'));
}
