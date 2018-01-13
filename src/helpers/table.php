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
	

function print_td($v){
	if (is_object($v) || is_array($v))
		return print_table($v, null, false);
	return $v;
}

function print_table($trs, $th = null, $echo = true, $class = 'basic-table'){
	$ntrs = array();
	$isAssoc = is_array($trs) && array_key_exists(0, $trs);

	if ($th){
		$trTh = array();
		foreach ($th as $cth)
			$trTh[] = '<th>'.$cth.'</th>';
		$ntrs[] = '<tr>'.implode('', $trTh).'</tr>';
	}
	if ($isAssoc && !is_object($trs[0]) && !is_array($trs[0])){
		foreach ($trs as $v)
			$ntrs[] = '<tr><td>'.print_td($v).'</td></tr>';

	} else
		foreach ($isAssoc ? $trs : array($trs) as $t){
			$tr = array();
			foreach ($t as $k => $v){
				$tr[] = '<td>'.print_td($v).'</td>';
			}
			$ntrs[] = '<tr>'.implode('', $tr).'</tr>';
		}
	$ret = $ntrs ? '<div><table class="'.$class.'">'.implode('', $ntrs).'</table></div>' : '';
	if ($echo){
		echo $ret;
		return $ret != '';
	}
	return $ret;
}
