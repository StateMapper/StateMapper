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


function get_bulletin_id($query){
	if (!empty($query['id'])){
		
		if (!empty($query['date']))
			return get_var('SELECT id FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id = %s AND format = %s', array($query['schema'], $query['date'], $query['id'], $query['format']));

		die_error('bad bulletin request');
	} 
	return get_var('SELECT id FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NULL AND format = %s', array($query['schema'], $query['date'], $query['format']));
}

function insert_bulletin($query){
	
	if (empty($query['format']))
		return new SMapError('missing format in insert_bulletin');
		
	if (!($id = get_bulletin_id($query)))
		$id = insert('bulletins', array(
			'bulletin_schema' => $query['schema'],
			'external_id' => !empty($query['id']) ? $query['id'] : null,
			'date' => !empty($query['date']) ? $query['date'] : null,
			'fetched' => null,
			'bulletin_hash' => null,
			'created' => date('Y-m-d H:i:s'),
			'format' => $query['format'],
			'status' => 'waiting',
		));
		
	return $id;
}

function set_bulletin_fetched($bulletin, $query){
	update('bulletins', array(
		'status' => 'fetched',
		'format' => $bulletin['format'],
		'bulletin_hash' => get_bulletin_hash($query),
	), array(
		'bulletin_schema' => $query['schema'],
		'date' => $query['date'],
		'external_id' => !empty($query['id']) ? $query['id'] : null,
		'fetched' => null,
	));
}

function set_bulletin_parsed($bulletin, $query){
	update('bulletins', array(
		'status' => 'parsed',
		'parsed' => date('Y-m-d H:i:s'),
		'format' => $bulletin['format'],
	), array(
		'bulletin_schema' => $query['schema'],
		'date' => $query['date'],
		'external_id' => !empty($query['id']) ? $query['id'] : null,
		'parsed' => null,
	));
}

function set_bulletin_extracting($query){
	update('bulletins', array(
		'status' => 'extracting'
	), array(
		'bulletin_schema' => $query['schema'],
		'date' => $query['date'],
		'external_id' => !empty($query['id']) ? $query['id'] : null,
	));
}

function set_bulletin_extracted($query){
	update('bulletins', array(
		'status' => 'extracted'
	), array(
		'bulletin_schema' => $query['schema'],
		'date' => $query['date'],
		'external_id' => !empty($query['id']) ? $query['id'] : null,
	));
}

function set_bulletin_none($query){
	update('bulletins', array(
		'status' => 'none'
	), array(
		'bulletin_schema' => $query['schema'],
		'date' => $query['date'],
		'external_id' => !empty($query['id']) ? $query['id'] : null,
		'parsed' => null,
	));
}

function set_bulletin_error($query, $error){
	update('bulletins', prepare('status = "error", attempts = attempts + 1, last_error = %s', $error), array(
		'bulletin_schema' => $query['schema'],
		'date' => $query['date'],
		'external_id' => !empty($query['id']) ? $query['id'] : null,
		'parsed' => null,
	));
}

function get_bulletin_status($schema, $date){
	return get_var('SELECT status FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NULL LIMIT 1', array($schema, $date));
}

function get_bulletin_attempts($schema, $date){
	return get_var('SELECT attempts FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NULL LIMIT 1', array($schema, $date));
}

function get_bulletin_fixes($schema, $date){
	return get_var('SELECT fixes FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NULL LIMIT 1', array($schema, $date));
}

function repair_bulletin($schema, $date){
	$bulletin = get_row('SELECT * FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NULL LIMIT 1', array($schema, $date));
	
	$ids = query('SELECT id, external_id, format FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NOT NULL', array($schema, $date));
	
	foreach ($ids as $b){
		
		$fetcher = get_format_fetcher($b['format']);
		
		if (is_error($fetcher)){
			if (IS_CLI)
				print_log('error fixing '.$b['format'], array('color' => 'red'));
			continue;
		}
		
		$filePath = get_bulletin_path(array(
			'date' => $date,
			'schema' => $schema,
		) + $b);
		
		$filePath = $fetcher->get_content_path($filePath, false);
		
		if (file_exists($filePath)){
			// this only work for one level depth :S
			query('DELETE FROM bulletins WHERE id = %s', array($b['id'])); 
			@unlink($filePath);

			if (IS_CLI)
				print_log('document '.$filePath.' deleted from local disk and database', array('color' => 'red'));
		}
	}
	
	
	$fetcher = get_format_fetcher($bulletin['format']);
	
	if (is_error($fetcher)){
		if (IS_CLI)
			print_log('error fixing '.$bulletin['format'], array('color' => 'red'));
		return false;
	}
	
	$filePath = get_bulletin_path(array(
		'date' => $date,
		'schema' => $schema,
		'format' => $bulletin['format'],
	));
	
	$filePath = $fetcher->get_content_path($filePath, false); // TODO: should plan extensions too!!!
	
	if (file_exists($filePath)){
		// this only works for one level depth :S
		@unlink($filePath);

		if (IS_CLI)
			print_log('document '.$filePath.' deleted from local disk', array('color' => 'red'));
	}
	
	// keep track of how many fixes this bulletin had
	query('UPDATE bulletins SET fixes = fixes + 1 WHERE bulletin_schema = %s AND date = %s LIMIT 1', array($schema, $date));
}

function is_bulletin_expected($schemaObj, $date){
	if ($date > date('Y-m-d'))
		return false;
		
	if (is_string($schemaObj))
		$schemaObj = get_schema($schemaObj);
	$weekDays = !empty($schemaObj->frequency) && !empty($schemaObj->frequency->weekDays) ? $schemaObj->frequency->weekDays : null;
	$baseDays = array("MO", "TU", "WE", "TH", "FR", "SA", "SU");
	return !$weekDays || in_array($baseDays[intval(date('N', strtotime($date)))-1], $weekDays); 
}

function get_format_label($queryOrFormat, $default = null){
	$f = get_format_fetcher(is_array($queryOrFormat) ? get_format_by_query($queryOrFormat) : $queryOrFormat);
	return $f && method_exists($f, 'get_format_label') ? $f->get_format_label() : ($default ? $default : strtoupper($format));
}

function get_format_by_query($query){
	if (!empty($query['id']))
		return get_var('SELECT format FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id = %s', array($query['schema'], $query['date'], $query['id']));
	else
		return get_var('SELECT format FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NULL', array($query['schema'], $query['date']));
}

function get_bulletins_count($include_documents = false){
	return get_var('SELECT COUNT(*) FROM bulletins WHERE status IN ( "fetched", "parsed", "extracting", "extracting" )'.($include_documents ? '' : ' AND external_id IS NULL'));
}

function get_providers_count(){
	return count(get_col('SELECT bulletin_schema FROM bulletins WHERE status IN ( "fetched", "parsed", "extracting", "extracting" ) GROUP BY bulletin_schema'));
}

function get_bulletin_hash($query){
	return ($path = get_bulletin_path($query)) && file_exists($path) ? hash('sha256', $path) : null;
}

function get_bulletin_path($query){
	return ($uri = get_bulletin_uri($query)) ? DATA_PATH.'/'.$uri : null;
}

// file formats:
// 2017/01/01.xml
// 2017/01/01/id_document.xml
function get_bulletin_uri($query, $relative_to_bulletin_root = false){
	if (empty($query['date']) || empty($query['format']))
		return false;
		
	$file_uri = str_replace('-', '/', $query['date']); 
	if (!empty($query['id']))
		$file_uri .= '/'.$query['id'];

	$file_uri .= '.'.strtolower($query['format']);
	if (!$relative_to_bulletin_root)
		return $query['schema'].'/'.$file_uri;
	return $file_uri;
}
