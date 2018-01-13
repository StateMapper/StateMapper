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
	
	
		
add_action('head', function(){
	echo '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
});

function utf8_recursive_encode($mixed){
	if (is_array($mixed)){
		foreach ($mixed as $key => $value)
			$mixed[$key] = utf8_recursive_encode($value);
	} else if (is_string($mixed))
		return convert_encoding($mixed);
	return $mixed;
}

function convert_encoding($str, $encoding = null){
	if (!function_exists('mb_detect_encoding') || !function_exists('iconv'))
		return $str;
	$str = @iconv($encoding ? $encoding : mb_detect_encoding($str, mb_detect_order(), true), "UTF-8", $str);
	return $str;
}
