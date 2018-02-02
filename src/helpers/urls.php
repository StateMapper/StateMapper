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
	
	
function anonymize($url){
	return 'https://anon.to/?'.$url;
}

function current_url($stripArgs = false){
	if (isset($_SERVER, $_SERVER['HTTP_HOST']))
		$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	else {

		global $smap;
		if (!empty($smap['cli_args']))
			$url = $smap['cli_args'][0];
		else
			return null;
	}
	if ($stripArgs && ($nurl = strstr($url, '?', true)))
		return $nurl;
	return $url;
}

function current_uri($stripArgs = false){
	return preg_replace('#^('.preg_quote(BASE_URL, '#').')(.*)$#iu', '$2', current_url($stripArgs));
}

function add_url_arg($name, $val, $url = null, $encode = true){
	if ($url === null)
		$url = current_url();
	$url = rtrim(rtrim(preg_replace('#([&\?])('.$name.'=[^&\#]*&?)#', '$1', $url), '&'), '?');
	$url .= (strpos($url, '?') !== false ? '&' : '?').$name.'='.($encode ? urlencode($val) : $val);
	return $url;
}

function remove_url_arg($name, $url = null){
	if (!$url)
		$url = current_url();
	$url = rtrim(rtrim(preg_replace('#([&\?])('.$name.'=[^&\#]+&?)#', '$1', $url), '&'), '?');
	return $url;
}

function get_filter_url($query, $factorizable = true, $merge = true){
	global $smap;
	if ($merge)
		$query += $smap['filters'];
		
	$url = BASE_URL;
	$params = array();
	
	$noCountry = false;
	if (!isset($query['loc']) && !empty($query['etype'])){
		$etypes = explode(' ', $query['etype']);
		if (count($etypes) == 1){
			$etypes = explode('/', $etypes[0]);
			if (count($etypes) > 2)
				$query['loc'] = $etypes[1];
		}
	}
		
	if (!empty($query['loc'])){
		$locs = explode(' ', $query['loc']);
		if ($factorizable || count($locs) > 1)
			$params['loc'] = strtolower($query['loc']);
		else {
			$url .= strtolower($query['loc']);

			if (preg_match('#^[a-z]+$#i', $query['loc']) && !empty($query['etype']) && preg_match('#^company/'.$query['loc'].'/.*#i', $query['etype'])) // country included in subtype
				$noCountry = true;
		}
	}
		
	if (!empty($query['etype'])){
		$etypes = get_entity_types();
		$etype = explode(' ', $query['etype']);
		if ($factorizable || count($etype) > 1){
			$params['etype'] = strtolower($query['etype']);
		} else {
			$etype = explode('/', $etype[0]);
			$k = array_shift($etype);
			$url = rtrim($url, '/');
			if (isset($etypes[$k]))
				$url .= '/'.$etypes[$k]['slug'];
			if ($etype){
				if ($noCountry)
					array_shift($etype);
				$url .= '/'.strtolower(implode('/', $etype));
			}
		}
	}
	
	if (!empty($query['q']))
		$params['q'] = $query['q'];
	
	if ($params)
		$url .= '?'.http_encode($params);
	
	return add_lang($url);	
}

function get_providers_url($filter = null){
	return add_lang(BASE_URL.get_providers_uri($filter));
}

function get_providers_uri($filter = null){
	return ($filter ? strtolower($filter).'/' : '').'providers';
}

function url($query = null, $apiCall = null, $passCurrentArgs = array()){
	return add_lang(BASE_URL.uri($query, $apiCall, $passCurrentArgs));
}

function uri($query = null, $apiCall = null, $passCurrentArgs = array()){
	
	if ($apiCall == 'providers')
		return get_providers_uri($query);
	
	else if ($apiCall == 'entity'){
		if (!empty($query['entity_id']))
			return get_entity_uri($query['entity_id'], !empty($query['keep_entity_id']));
		else
			return get_entity_uri(get_entity_by_slug($query['slug'], $query['type'], $query['country']));
	}
		
	if (!$query || !is_array($query))
		$query = array(
			'schema' => $query,
		);
		
	$schema = !empty($query['schema']) ? strtolower($query['schema']) : null;

	if ($apiCall){
		$cleanCall = explode('/', $apiCall);
		$cleanCall = $cleanCall[0];
	} else 
		$cleanCall = null;
		
	$schemaObj = get_schema($schema);
	if ($schema){
		$schema = explode('/', $schema);
		//if (count($schema) > 1)
			$query['country'] = array_shift($schema);
		$schema = implode('/', $schema);
	}
	
	$url = '';
	if (!empty($query['country']))
		$url .= strtolower($query['country']);
	
	if ($apiCall != 'ambassadors'){
		if ($schema !== null){
			if ($cleanCall == 'rewind')
				$url .= '/bulletins';
			else if ($schemaObj->type == 'institution')
				$url .= '/provider';
			else if ($schema)
				$url .= '/bulletin';
		}
	}
	$url = ltrim($url, '/');
		
	if ($schema)
		$url .= '/'.$schema;

	if ($apiCall){
		
		if ($schema && is_dated_mode($cleanCall)){
			$query += array(
				'date' => date('Y-m-d', strtotime('-1 day'))
			);
			if ($query['date'])
				$url .= '/'.$query['date'];
			if (!empty($query['id']))
				$url .= '/'.$query['id'];
		}
		
		if ($apiCall == 'fetch/raw')
			$url .= '/raw';
		else if (!in_array($cleanCall, array('fetch', 'rewind', 'search')))
			$url .= '/'.$apiCall;
			
		if (in_array($apiCall, array('redirect')) && !empty($query['format']))
			$url .= '/'.$query['format'];
		else if ($apiCall == 'rewind' && !empty($query['year']) && $query['year'] != date('Y'))
			$url .= '/'.$query['year'];
	} 
	
	if (!empty($query['precept']))
		$url = add_url_arg('precept', $query['precept'], $url);
	
	if ($cleanCall == 'search')
		foreach ($query as $k => $v)
			$url = add_url_arg($k, $v, $url);
	
	foreach ($passCurrentArgs as $k)
		if (isset($_GET[$k]))
			$url = add_url_arg($k, $_GET[$k], $url);

	return ltrim($url, '/');
}

function get_uri_bits(){
	$bits = array();
	foreach (explode('/', preg_replace('#^(.*?)(/?)(\?.*)?$#', '$1', str_replace(BASE_URL, '', current_url()))) as $bit)
		if (trim($bit) != '')
			$bits[] = trim($bit);
	return $bits;
}

function get_canonical_url(){
	global $smap;
	
	// static cache
	static $url = null;
	if ($url !== null)
		return $url;
		
	if (defined('IS_ERROR') && IS_ERROR)
		$url = url();
	else if (!empty($smap['entity']))
		$url = get_entity_url($smap['entity']);
	else if ($smap['page'] == 'browser')
		$url = get_filter_url($smap['filters'], false);
	else if ($smap['page'] == 'providers')
		$url = get_providers_url(!empty($smap['filters']['loc']) ? $smap['filters']['loc'] : null);
	else {
		if (empty($smap['query']['country']) && !empty($smap['filters']['loc']))
			$smap['query']['country'] = $smap['filters']['loc'];
		$url = url($smap['query'], !empty($smap['call']) ? $smap['call'] : $smap['page']);
	}
	return $url;
}

// make sure to couple with lock(...) before inserting!
function generate_slug($table, $col, $title, $length = null, $where = array()){
	$title = sanitize_title($title, $length);
	$i = 1;
	
	$and_where = array();
	foreach ($where as $k => $v)
		$and_where[] = $v === null ? $k.' IS NULL' : prepare($k.' = %s', $v);
	$and_where = $and_where ? ' AND '.implode(' AND ', $and_where) : '';
	
	do {
		$slug = $title.($i > 1 ? '-'.$i : '');
		$i++;
	} while (get_var('SELECT COUNT(*) FROM '.$table.' WHERE '.$col.' = %s'.$and_where, array($slug)));
	return $slug;
}

function get_flag_url($country, $size = 'original'){
	if (is_object($country))
		$country = $country->id;
	$country = strtoupper($country);
	//$size = IMAGE_SIZE_SMALL;
	return file_exists(APP_PATH.'/assets/images/flags/'.$size.'/'.$country.'.png') ? ASSETS_URL.'/images/flags/'.$size.'/'.$country.'.png' : null;
}


function get_domain($url, $root_domain = false){
	return preg_match('#^https?://([^/\.]*\.)*([^\./]+\.[^\./]+)(/.*)?$#iu', $url, $m) ? ($root_domain ? $m[2] : $m[1].$m[2]) : $url;
//	return preg_match('#^https?://(?:www\.)?([^/\?\#]+)(?:[/\?\#].*)?$#iu', $url, $m) ? $m[1] : $url;
}

function get_repository_url($uri = null){
	return 'https://github.com/'.SMAP_GITHUB_REPOSITORY.($uri ? '/'.ltrim($uri, '/') : '');
}

function redirect($url){
	header('Location: '.$url);
	exit(); // leave this, very important!
}


function strip_root($path){
	return str_replace(BASE_PATH.'/', '', $path);
}

function is_call($call){
	global $smap;
	if (!empty($smap) && !empty($smap['call']))
		return is_array($call) ? in_array($smap['call'], $call) : $smap['call'] == $call;
	return false;
}

function is_home(){
	global $smap;
	return $smap['page'] == 'browser' && empty($smap['call']) && empty($smap['filters']['q']) && empty($smap['entity']) && !has_filter();
}

function is_browser(){
	global $smap;
	return $smap['page'] == 'browser';/*
		&& (!empty($smap['query']['q']) || (
			!in_array($smap['page'], array('bulletin', 'bulletins')) 
			&& (!empty($smap['entity']) || empty($smap['filters']['loc'])) 
			&& empty($smap['query']['schema'])
		));
		*/
	//return $smap['page'] == 'browser' && (!$root || (empty($smap['call']) && empty($_GET['q']) && !has_filter()));
}

function is_mode($str){
	return in_array($str, array('schema', 'fetch', 'lint', 'parse', 'extract', 'redirect', 'download', 'rewind', 'rewindextract', 'soldiers'));
}

function dated_modes(){
	$modes = array('fetch', 'lint', 'parse', 'extract', 'redirect', 'download');
	if (IS_CLI){
		$modes[] = 'rewind';
		$modes[] = 'rewindextract';
	}
	return $modes;
}

function is_dated_mode($str){
	return in_array($str, dated_modes());
}

function is_valid_mode($str){
	$modes = get_modes();
	return isset($modes[$str]);
} 

function get_mode_icon($mode){
	$c = get_modes();
	return isset($c[$mode]) ? $c[$mode]['icon'] : 'warning';
}

function get_mode_title($mode, $short = false){
	$c = get_modes();
	return isset($c[$mode]) ? $c[$mode][$short && isset($c[$mode]['shortTitle']) ? 'shortTitle' : 'title'] : $mode;
}


function show_mode($mode){
	$c = get_modes();
	return isset($c[$mode]) && (!isset($c[$mode]['show']) || $c[$mode]['show']);
}

function has_filter(){
	global $smap;
	return !empty($smap['filters']['etype']) || !empty($smap['filters']['esubtype']) || !empty($smap['filters']['year']) || !empty($smap['filters']['atype']) || (!empty($smap['filters']['loc']) && empty($smap['entity']) && empty($smap['query']['schema']));
}

function get_filter(){
	if (!empty($_GET['filter']))
		return $_GET['filter'];
	if (!empty($_GET['precept']))
		return get_var('SELECT title FROM precepts WHERE id = %s', $_GET['precept']);
	return null;
}

function is_page($page){
	global $smap;
	return $smap['page'] == $page;
}

function is_search(){
	global $smap;
	return (IS_CLI || is_browser()) && !empty($smap['query']['q']);
}

function get_uri_from_url($url){
	return preg_replace('#^('.preg_quote(BASE_URL, '#').')(.*)$#iu', '$2', $url);
}
