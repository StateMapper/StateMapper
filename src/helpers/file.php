<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net>
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


function kaosFormatBytes($size, $precision = 0, $sep = ' '){ 
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');   

    return round(pow(1024, $base - floor($base)), $precision) . $sep . $suffixes[floor($base)];
} 


function kaosGetDisksize($id, $convert = false){
	$time = getOption('disksize-time_'.$id);
	
	$val = null;
	if (($time && $time > strtotime('-'.KAOS_DISKSPACE_CHECK_FREQUENCY)) || !($lock = lock('disksize-'.$id))){
		$val = getOption('disksize_'.$id);
		
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
			updateOption('disksize_'.$id, $size);
			updateOption('disksize-time_'.$id, time());
			
			$val = $size;
		}
		unlock($lock);
	}
	if ($convert && $val)
		$val = kaosStrtobytes($val);
	return $val;
}

function kaosGetDiskfreepct($asString = false){
	$free = kaosGetDisksize('free', true);
	$total = kaosGetDisksize('total', true);
	if (!$total)
		return null;
	$pct = 100 * $free / $total;
	if (!$asString)
		return $pct;
	return number_format($pct, 1).'%';
}

function kaosAjaxDisksize($args){
	if (!isAdmin())
		kaosDie();
		
	$ret = array();
	ignore_user_abort(true);
	foreach ($args['sizes'] as $id)
		if (in_array($id, array('free', 'data', 'schemas', 'total'))){
			$ret[$id] = kaosGetDisksize($id);
		}
		
	if (in_array('freepct', $args['sizes']))
		$ret['freepct'] = kaosGetDiskfreepct(true);
		
	ignore_user_abort(false);
	return array('success' => true, 'sizes' => $ret);
}
