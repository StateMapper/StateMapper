<?php
	
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
