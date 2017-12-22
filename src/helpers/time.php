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



function humanTimeDiff($time, $timeRef = null){
	if (!is_numeric($time))
		$time = strtotime($time);
	$diff = abs(($timeRef !== null ? (is_numeric($timeRef) ? $timeRef : strtotime($timeRef)) : time()) - $time);
	$min = ($diff - ($diff % 60)) / 60;
	$diff -= $min * 60;
	return ($min ? $min.'m ' : '').$diff.'s';
}

function date_i18n($format, $time = null){
	if (!$time)
		$time = time();
	return strftime(dateFormatToStrftime($format, $time), $time);
}

function kaosAddMonth($date, $months = 1){
	for ($i=0; $i<$months; $i++)
		$date = strtotime(date('Y-m', strtotime('+33 days', $date)).'-01');
	return $date;
}
