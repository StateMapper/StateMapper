<?php
	
if (!defined('BASE_PATH'))
	die();



function kaosGetYearStats($schema, $year){
	$stats = array();
	$lastDay = min(date('Y-m-d', strtotime('+1 day', strtotime($year.'-12-31'))), date('Y-m-d', strtotime('+1 day', time())));

	// TODO: this may be all precalculated.. if too slow
	
	/*
	foreach (getCol('SELECT DISTINCT status FROM bulletins') as $status){
		$optionId = 'yearstat-'.$schema.'-'.$year.'-'.$status;
		if (!($count = getOption($optionId))){
			
			updateOption($optionId, $count);
		}
		TO CONTINUE..
	*/
	
	// count every bulletin statuses
	$res = query('SELECT status, COUNT(id) AS count FROM bulletins WHERE bulletin_schema = %s AND date >= %s AND date < %s GROUP BY status', array($schema, $year.'-01-01', $lastDay)); 
	foreach ($res as $r)
		$stats[$r['status']] = $r['count'];
	
	// count documents + sub-documents amount
	$stats['document'] = get('SELECT COUNT(u.bulletin_id) AS count FROM bulletins AS b LEFT JOIN bulletin_uses_bulletin AS u ON b.id = u.bulletin_in WHERE b.bulletin_schema = %s AND b.date >= %s AND b.date < %s AND b.external_id IS NULL', array($schema, $year.'-01-01', $lastDay)) + get('SELECT COUNT(b.id) AS count FROM bulletins AS b WHERE b.bulletin_schema = %s AND b.date >= %s AND b.date < %s AND b.external_id IS NULL', array($schema, $year.'-01-01', $lastDay)); 

	// count precepts
	$stats['precepts'] = get('SELECT COUNT(p.id) FROM bulletins AS b LEFT JOIN bulletin_uses_bulletin AS u ON b.id = u.bulletin_in LEFT JOIN bulletins AS b2 ON u.bulletin_id = b2.id LEFT JOIN precepts AS p ON b2.id = p.bulletin_id WHERE b.bulletin_schema = %s AND b.date >= %s AND b.date < %s', array($schema, $year.'-01-01', $lastDay)); 
	
	// count statuses
	$stats['statuses'] = get('SELECT COUNT(s.id) FROM bulletins AS b LEFT JOIN bulletin_uses_bulletin AS u ON b.id = u.bulletin_in LEFT JOIN bulletins AS b2 ON u.bulletin_id = b2.id LEFT JOIN precepts AS p ON b2.id = p.bulletin_id LEFT JOIN statuses AS s ON p.id = s.precept_id WHERE b.bulletin_schema = %s AND b.date >= %s AND b.date < %s', array($schema, $year.'-01-01', $lastDay)); 
	
	$stats += array(
		'not_fetched' => 0,
		'not_published' => 0,
		'error' => 0,
		'fetched' => (!empty($stats['fetched']) ? $stats['fetched'] : 0) + (!empty($stats['extracting']) ? $stats['extracting'] : 0) + (!empty($stats['extracted']) ? $stats['extracted'] : 0),
	);
	
	/*static $acc = 0;
	$begin = time();
	$acc += time() - $begin;
	echo "TIME YEAR: ".$acc.'s <br>';*/
	
	$statuses = array();
	foreach (query('SELECT date, status FROM bulletins WHERE bulletin_schema = %s AND date IS NOT NULL AND date >= %s AND date < %s AND external_id IS NULL', array($schema, $year.'-01-01', $lastDay)) as $s)
		$statuses[$s['date']] = $s['status'];
		
	for ($date = $year.'-01-01'; $date < $lastDay; $date = date('Y-m-d', strtotime('+1 day', strtotime($date)))){
		$shouldHaveBulletin = kaosIsBulletinExpected($schema, $date);
			
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

function kaosGetMapSquare($date, &$bulletinStatus, &$monthHas, &$monthTotal, $dbstats, $mode = 'fetch'){
	global $kaosCall;
	
	$shouldHaveBulletin = kaosIsBulletinExpected($kaosCall['schemaObj'], $date);
	
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
		$squareInner = '0';
	else if ($bulletin){
		
		if (in_array($bulletinStatus, array('extracting', 'extracted')))
			$squareInner = $bulletinCount = $cdbstats ? $cdbstats['precepts'] : 0;
		
		else
			$squareInner = $bulletinCount = $cdbstats ? $cdbstats['count'] : 0;
		
	} else if ($has)
		$squareInner = $mode == 'fetch' ? 1 : '';
	else
		$squareInner = '';
		
	if ($date > date('Y-m-d')) // future
		$bulletinStatus = 'not_published';

	return '<a href="'.kaosGetBulletinUrl(array(
		'schema' => $kaosCall['query']['schema'], 
		'date' => $date
	), false).'" title="'.date_i18n(_('l jS \o\f F Y'), strtotime($date)).'<br><b>Status: '.strtoupper(str_replace('_', ' ', $bulletinStatus)).'</b>'.($bulletin && $bulletin['last_error'] ? '<br><br><b>LAST ERROR:</b> '.$bulletin['last_error'] : '').'" class="kaos-api-fetched-ind kaos-api-fetched-ind-'.$bulletinStatus.'">'.$squareInner.'</a>';
	
	// TODO: could/should convert status to a human sentence (NOT_PUBLISHED => Not expected)
}
