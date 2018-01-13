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


function time_diff($time, $timeRef = null, $microseconds = false){
	if (!is_numeric($time))
		$time = strtotime($time);
	$diff = abs(($timeRef !== null ? (is_numeric($timeRef) ? $timeRef : strtotime($timeRef)) : time()) - $time);
	$min = $diff >= 60 ? ($diff - ($diff % 60)) / 60 : 0;
	$diff -= $min * 60;
	$s = floor($diff);
	$ms = floor(($diff - $s) * 1000);
	
	$str = $min ? $min.'m' : '';
	if ($s || $min)
		$str .= ($str == '' ? '' : ' ').$s.'s';
	if ($microseconds && !$min && ($s < 3 && ($ms > 1 || $s < 1)))
		$str .= ($str == '' ? '' : ' ').$ms.($str == '' && !$ms ? 's' : 'ms');
	if ($str == '')
		$str = '0s';
	return $str;
}

function date_i18n($format, $time = null){
	if (!$time)
		$time = time();
	return strftime(convert_regexp_to_ftime($format, $time), $time);
}

function add_month($date, $months = 1){
	for ($i=0; $i<$months; $i++)
		$date = strtotime(date('Y-m', strtotime('+33 days', $date)).'-01');
	return $date;
}

function is_date($str){
	return preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#', $str);
}

function is_valid_date($str){
	return date('Y-m-d', strtotime($str)) == $str;
}

function convert_regexp_to_ftime($dateFormat, $time) { 
    $caracs = array( 
        // Day - no strf eq : S 
        'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j', 
        // Week - no date eq : %U, %W 
        'W' => '%V',  
        // Month - no strf eq : n, t 
        'F' => '%B', 'm' => '%m', 'M' => '%b', 
        // Year - no strf eq : L; no date eq : %C, %g 
        'o' => '%G', 'Y' => '%Y', 'y' => '%y', 
        // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X 
        'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S', 
        // Timezone - no strf eq : e, I, P, Z 
        'O' => '%z', 'T' => '%Z', 
        // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x  
        'U' => '%s',
        
        'S' => date('S', $time),
    ); 
    
    // TODO: use regexp to avoid \o\f
    $regs = array();
    foreach ($caracs as $c => $rep)
		$regs['#((?<!\\\\|%)'.$c.')#'] = $rep;
	
	$dateFormat = preg_replace(array_keys($regs), array_values($regs), $dateFormat);
	$dateFormat = str_replace('\\', '', $dateFormat);
	
    return $dateFormat;//strtr((string)$dateFormat, $caracs); 
} 

