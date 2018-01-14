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

function get_api_type(){
	global $smap;
	if (is_home(true))
		return false;
	if (in_array($smap['page'], array('api', 'settings', 'login', 'logout')))
		return false;
	if (!empty($smap['call']) && in_array($smap['call'], array('fetch', 'lint')))
		return 'document';
	return 'json';
}

function get_api_url($url = null){
	if (!($type = get_api_type()))
		return null;
		
	$uri = preg_replace('#^('.preg_quote(BASE_URL, '#').')(.*)$#iu', '$2', $url ? $url : get_canonical_url());
	switch ($type){
		case 'document': 
			$end = '/raw';
			break;
		case 'json':
			$end = '.json';
			$uri = 'api/'.$uri;
			break;
	}
	return add_lang(BASE_URL.preg_replace('#^([^\#\?]*?)/?(\?[^\#]*)?(\#.*)?$#iu', '$1'.$end.'$2$3', $uri));
}

function is_rate_limited(){
	return defined('IS_API') && IS_API && API_RATE_LIMIT && IS_CLI && ($ip = get_visitor_ip()) && !is_admin() && (!IS_DEBUG || !empty($_GET['force_rate_limited'])) ? $ip : false;
}

function is_api(){
	return defined('IS_API') && IS_API;
}

add_action('clean_tables', 'clean_api_rates');
function clean_api_rates($all){
		
	// clear api rates
	if ($all)
		query('DELETE FROM api_rates');
	else
		query('DELETE FROM api_rates WHERE date < %s', date('Y-m-d H:i:s', strtotime('-'.API_RATE_PERIOD)));
}
