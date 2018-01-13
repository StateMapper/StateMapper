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


function format_bytes($size, $precision = 0, $sep = ' '){ 
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');   

    return round(pow(1024, $base - floor($base)), $precision) . $sep . $suffixes[floor($base)];
} 


function get_disk_size($id, $convert = false){
	$time = get_option('disksize-time_'.$id);
	
	$val = null;
	if (($time && $time > strtotime('-'.DISKSPACE_CHECK_FREQUENCY)) || !($lock = lock('disksize-'.$id))){
		$val = get_option('disksize_'.$id);
		
	} else {
		$output = array();

		if ($id == 'free')
			exec('df -h "'.BASE_PATH.'" | tr -s " " | cut -d" " -f4 | tail -1', $output, $returnVar);
		else if ($id == 'total')
			exec('df -h "'.BASE_PATH.'" | tr -s " " | cut -d" " -f2 | tail -1', $output, $returnVar);
		else
			exec('du -hs "'.BASE_PATH.'/'.$id.'"', $output, $returnVar);
			
		if (empty($returnVar) && $output){
			$size = preg_replace('#^(\S+)(.*?)$#', '$1', $output[0]);
			update_option('disksize_'.$id, $size);
			update_option('disksize-time_'.$id, time());
			
			$val = $size;
		}
		unlock($lock);
	}
	if ($convert && $val)
		$val = parse_bytes($val);
	return $val;
}

function get_disk_free_pct($asString = false){
	$free = get_disk_size('free', true);
	$total = get_disk_size('total', true);
	if (!$total)
		return null;
	$pct = 100 * $free / $total;
	if (!$asString)
		return $pct;
	return number_format($pct, 1).'%';
}

function smap_ajax_ajax_disk_size($args){
	if (!is_admin())
		die_error();
		
	$ret = array();
	ignore_user_abort(true);
	foreach ($args['sizes'] as $id)
		if (in_array($id, array('free', 'data', 'schemas', 'total'))){
			$ret[$id] = get_disk_size($id);
		}
		
	if (in_array('freepct', $args['sizes']))
		$ret['freepct'] = get_disk_free_pct(true);
		
	ignore_user_abort(false);
	return array('success' => true, 'sizes' => $ret);
}

function ls_dir($dir_path){
	$files = array();
	$folders = array();
	$dir = opendir($dir_path);
	while ($file = readdir($dir))
		if ($file != '.' && $file != '..' && substr($file, -1) != '~'){ // avoid Unix temp files (ending in ~)
			if (is_file(rtrim($dir_path, '/').'/'.$file))
				$files[] = $file;
			else
				$folders[] = $file;
		}
	closedir($dir);
	return array_merge($folders, $files);
}


// TODO: implement using this order by preference
function get_format_preference(){
	return array('xml', 'html', 'pdf');
}

function get_formats(){
	return array('xml', 'pdf', 'json', 'txt');
}

function is_format($str){
	return in_array($str, get_formats());
}

function parse_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}
