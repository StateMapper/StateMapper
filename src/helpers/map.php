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


function get_map_year_stats($schema, $year){
	$stats = array();
	$lastDay = min(date('Y-m-d', strtotime('+1 day', strtotime($year.'-12-31'))), date('Y-m-d', strtotime('+1 day', time())));

	// TODO: this may be all precalculated.. if too slow
	
	/*
	foreach (get_col('SELECT DISTINCT status FROM bulletins') as $status){
		$optionId = 'yearstat-'.$schema.'-'.$year.'-'.$status;
		if (!($count = get_option($optionId))){
			
			update_option($optionId, $count);
		}
		TO CONTINUE..
	*/
	
	// count every bulletin statuses
	$res = query('SELECT status, COUNT(id) AS count FROM bulletins WHERE bulletin_schema = %s AND date >= %s AND date < %s AND external_id IS NULL GROUP BY status', array($schema, $year.'-01-01', $lastDay)); 
	foreach ($res as $r)
		$stats[$r['status']] = $r['count'];
	
	// count documents + sub-documents amount
	$stats['document'] = get_var('SELECT COUNT(*) AS count FROM bulletins WHERE bulletin_schema = %s AND date >= %s AND date < %s', array($schema, $year.'-01-01', $lastDay)); 

	// count precepts
	$stats['precepts'] = get_var('SELECT COUNT(p.id) FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id WHERE b.bulletin_schema = %s AND b.date >= %s AND b.date < %s', array($schema, $year.'-01-01', $lastDay)); 
	
	// count statuses
	$stats['statuses'] = get_var('SELECT COUNT(s.id) FROM bulletins AS b LEFT JOIN precepts AS p ON b.id = p.bulletin_id LEFT JOIN statuses AS s ON p.id = s.precept_id WHERE b.bulletin_schema = %s AND b.date >= %s AND b.date < %s', array($schema, $year.'-01-01', $lastDay)); 
	
	$stats += array(
		'not_fetched' => 0,
		'not_published' => 0,
		'error' => 0,
		'fetched' => (!empty($stats['fetched']) ? $stats['fetched'] : 0) + (!empty($stats['extracting']) ? $stats['extracting'] : 0) + (!empty($stats['extracted']) ? $stats['extracted'] : 0),
		'extracted' => 0,
	);
	
	/*static $acc = 0;
	$begin = time();
	$acc += time() - $begin;
	echo "TIME YEAR: ".$acc.'s <br>';*/
	
	$statuses = array();
	foreach (query('SELECT date, status FROM bulletins WHERE bulletin_schema = %s AND date IS NOT NULL AND date >= %s AND date < %s AND external_id IS NULL', array($schema, $year.'-01-01', $lastDay)) as $s)
		$statuses[$s['date']] = $s['status'];
		
	for ($date = $year.'-01-01'; $date < $lastDay; $date = date('Y-m-d', strtotime('+1 day', strtotime($date)))){
		$shouldHaveBulletin = is_bulletin_expected($schema, $date);
			
		// check if bulletin has been fetched
		$bulletinStatus = isset($statuses[$date]) ? $statuses[$date] : null;
		if (!$bulletinStatus || (!$shouldHaveBulletin && $bulletinStatus == 'none'))
			$stats[$shouldHaveBulletin ? 'not_fetched' : 'not_published']++;
	}
	
	// calculate bulletin days' progress
	$pct = 100 * $stats['fetched'] / ($stats['not_fetched'] + $stats['error'] + $stats['fetched']);
	$stats['progress'] = $pct == 0 ? '&nbsp;' : ($pct == 100 ? '<i class="fa fa-check"></i>' : number_format(floor($pct), 0).'%');

	return $stats;
}

function get_map_square($date, &$bulletinStatus, &$monthHas, &$monthTotal, $dbstats, $mode = 'fetch'){
	global $smap;
	
	$shouldHaveBulletin = is_bulletin_expected($smap['schemaObj'], $date);
	
	$cdbstats = null;
	$cdbstats = isset($dbstats[$date]) ? $dbstats[$date] : null;
	$bulletin = $cdbstats ? $cdbstats : null;
	$bulletinStatus = $cdbstats ? $cdbstats['status'] : ($shouldHaveBulletin ? 'not_fetched' : 'not_published');
	
	$has = in_array($bulletinStatus, array('fetched', 'parsed'));
	if ($has){
		$monthHas++;
		$monthTotal++;
	} else if ($shouldHaveBulletin)
		$monthTotal++;
		
	if ($bulletinStatus == 'none')
		$squareInner = 0;
	else if ($bulletin){
		
		if (in_array($bulletinStatus, array('extracting', 'extracted')))
			$squareInner = $bulletinCount = $cdbstats ? format_number_nice($cdbstats['precepts'], false) : 0;
		
		else
			$squareInner = $bulletinCount = $cdbstats ? format_number_nice($cdbstats['count'], false) : 0;
		
	} else if ($has)
		$squareInner = $mode == 'fetch' ? 1 : '';
	else
		$squareInner = '';
		
	if ($date > date('Y-m-d')) // future
		$bulletinStatus = 'not_published';
	
	// build tooltip	
	$title = date_i18n(_('l jS \o\f F Y'), strtotime($date)).'<br>';
	$title .= '<b>'.strtoupper(str_replace('_', ' ', $bulletinStatus)).'</b>';
	
	if (!empty($cdbstats['count'])){
		$title .= '<br><br>Documents count: '.number_format($cdbstats['count']);
		if (!empty($cdbstats['precepts']))
			$title .= '<br>Precepts count: '.number_format($cdbstats['precepts']);
	}
	
	// show errors in tooltip
	if ($bulletin && $bulletin['last_error'])
		$title .= '<br><br><b>LAST ERROR:</b> '.$bulletin['last_error'];

	return '<a href="'.url(array(
		'schema' => $smap['query']['schema'], 
		'date' => $date
	), 'fetch').'" title="'.esc_attr($title).'" class="map-fetched-ind map-fetched-ind-'.$bulletinStatus.'">'.$squareInner.'</a>';
	
	// TODO: could/should convert status to a human sentence (NOT_PUBLISHED => Not expected)
}

function smap_ajax_refresh_map($args){
	global $smap;
		
	$vars = array(
		'current_year' => is_numeric(@$smap['query']['year']) ? $smap['query']['year'] : intval(date('Y')),
		'extract' => !empty($args['extract']) && $args['extract'] !== 'false',
	);
	return array('success' => true, 'html' => get_template('parts/rewind_map', $vars));
}

