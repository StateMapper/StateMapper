<?php
	
if (!defined('BASE_PATH'))
	die();


function insertBulletin($query, $parent_id = null){
	if (empty($query['format']))
		return new KaosError('missing format in insertBulletin');
	
	if (!empty($query['id']))
		$id = get('SELECT id FROM bulletins WHERE bulletin_schema = %s AND external_id = %s AND format = %s', array($query['schema'], $query['id'], $query['format']));
	else
		$id = get('SELECT id FROM bulletins WHERE bulletin_schema = %s AND date = %s AND format = %s', array($query['schema'], $query['date'], $query['format']));
	
	if (!$id){
		$id = insert('bulletins', array(
			'bulletin_schema' => $query['schema'],
			'external_id' => !empty($query['id']) ? $query['id'] : null,
			'date' => empty($query['id']) ? $query['date'] : null,
			'fetched' => null,
			'created' => date('Y-m-d H:i:s'),
			'format' => $query['format'],
			'status' => 'waiting',
		));
	}
	if ($id && ($parent_id || !empty($query['followingParent']))){
		if (!$parent_id)
			$parent_id = get('SELECT id FROM bulletins WHERE bulletin_schema = %s AND date = %s', array($query['followingParent']['schema'], $query['followingParent']['date']));
		if (!$parent_id)
			kaosDie('no parent_id found for followingParent');
			
		$b2b_in = get('SELECT id FROM bulletin_uses_bulletin WHERE bulletin_id = %s AND bulletin_in = %s', array($id, $parent_id));
		
		if (!$b2b_in)
			insert('bulletin_uses_bulletin', array(
				'bulletin_id' => $id,
				'bulletin_in' => $parent_id,
			));
	}
	return $id;
}

function setBulletinFetched($bulletin, $query){
	if (!empty($query['id']))	
		return query('UPDATE bulletins SET status = "fetched", format = %s WHERE bulletin_schema = %s AND external_id = %s AND fetched IS NULL', array($bulletin['format'], $query['schema'], $query['id']));
	else
		return query('UPDATE bulletins SET status = "fetched", format = %s WHERE bulletin_schema = %s AND date = %s AND fetched IS NULL', array($bulletin['format'], $query['schema'], $query['date']));
}

function setBulletinParsed($bulletin, $query){
	if (!empty($query['id']))	
		return query('UPDATE bulletins SET status = "parsed", parsed = %s, format = %s WHERE bulletin_schema = %s AND external_id = %s AND parsed IS NULL', array(date('Y-m-d H:i:s'), $bulletin['format'], $query['schema'], $query['id']));
	else
		return query('UPDATE bulletins SET status = "parsed", parsed = %s, format = %s WHERE bulletin_schema = %s AND date = %s AND parsed IS NULL', array(date('Y-m-d H:i:s'), $bulletin['format'], $query['schema'], $query['date']));
}

function setBulletinNone($query){
	if (!empty($query['id']))	
		return query('UPDATE bulletins SET status = "none" WHERE bulletin_schema = %s AND external_id = %s AND parsed IS NULL', array($query['schema'], $query['id']));
	else
		return query('UPDATE bulletins SET status = "none" WHERE bulletin_schema = %s AND date = %s AND parsed IS NULL', array($query['schema'], $query['date']));
}

function setBulletinError($query, $error){
	if (!empty($query['id']))	
		return query('UPDATE bulletins SET status = "error", attempts = attempts + 1, last_error = %s WHERE bulletin_schema = %s AND external_id = %s AND parsed IS NULL', array($error, $query['schema'], $query['id']));
	else
		return query('UPDATE bulletins SET status = "error", attempts = attempts + 1, last_error = %s WHERE bulletin_schema = %s AND date = %s AND parsed IS NULL', array($error, $query['schema'], $query['date']));
}

function getBulletinStatus($schema, $date){
	return get('SELECT status FROM bulletins WHERE bulletin_schema = %s AND date = %s LIMIT 1', array($schema, $date));
}

function getBulletinAttempts($schema, $date){
	return get('SELECT attempts FROM bulletins WHERE bulletin_schema = %s AND date = %s LIMIT 1', array($schema, $date));
}

function getBulletinFixes($schema, $date){
	return get('SELECT fixes FROM bulletins WHERE bulletin_schema = %s AND date = %s LIMIT 1', array($schema, $date));
}

function kaosFixBulletin($schema, $date){
	$bulletin = getRow('SELECT * FROM bulletins WHERE bulletin_schema = %s AND date = %s LIMIT 1', array($schema, $date));
	
	$ids = query('SELECT b.id, b.external_id, b.format FROM bulletin_uses_bulletin AS bb LEFT JOIN bulletins AS b ON bb.bulletin_id = b.id WHERE bb.bulletin_in = %s', array($bulletin['id']));
	
	foreach ($ids as $b){
		
		$stillUsed = get('SELECT COUNT(bulletin_in) FROM bulletin_uses_bulletin WHERE bulletin_id = %s AND bulletin_in != %s', array($b['id'], $bulletin['id']));
		
		if ($stillUsed) // this bulletin is still used, leave it as it is
			continue;
		
		$fetcher = kaosGetFormatFetcher($b['format']);
		
		if (kaosIsError($fetcher)){
			if (KAOS_IS_CLI)
				kaosPrintLog('error fixing '.$b['format'], array('color' => 'red'));
			continue;
		}
		
		$filePath = DATA_PATH.'/'.$schema.'/byId/'.$b['external_id'].'.'.strtolower($b['format']);
		
		$filePath = $fetcher->getContentFilePath($filePath, false);
		
		if (file_exists($filePath)){
			// this only work for one level depth :S
			query('DELETE FROM bulletin_uses_bulletin WHERE bulletin_id = %s', array($b['id']));
			query('DELETE FROM bulletins WHERE id = %s', array($b['id'])); 
			@unlink($filePath);

			if (KAOS_IS_CLI)
				kaosPrintLog('document '.$filePath.' deleted from local disk and database', array('color' => 'red'));
		}
	}
	
	
	$fetcher = kaosGetFormatFetcher($bulletin['format']);
	
	if (kaosIsError($fetcher)){
		if (KAOS_IS_CLI)
			kaosPrintLog('error fixing '.$bulletin['format'], array('color' => 'red'));
		return false;
	}
	
	$filePath = DATA_PATH.'/'.$schema.'/byDate/'.$date.'.'.strtolower($bulletin['format']);
	$filePath = $fetcher->getContentFilePath($filePath, false); // TODO: should plan extensions too!!!
	
	if (file_exists($filePath)){
		// this only work for one level depth :S
		query('DELETE FROM bulletin_uses_bulletin WHERE bulletin_id = %s', array($bulletin['id']));
//		query('DELETE FROM bulletins WHERE id = %s', array($bulletin['id']));  // leave database row for fixes count
		@unlink($filePath);

		if (KAOS_IS_CLI)
			kaosPrintLog('document '.$filePath.' deleted from local disk', array('color' => 'red'));
	}

	query('UPDATE bulletins SET fixes = fixes + 1 WHERE bulletin_schema = %s AND date = %s LIMIT 1', array($schema, $date));
}


function kaosGetBulletinUrl($query, $raw = false){
	if (!isset($query['schema']))
		return 'no schema for bulletin_url';
	if (!isset($query['date']))
		return 'no date for bulletin_url';
	$url = BASE_URL.'api/'.strtolower($query['schema']).'/'.$query['date'];
	if (!empty($query['id']))
		$url .= '/'.$query['id'];
	$url .= '/fetch';
	if ($raw)
		$url .= '/raw';
	return $url;
}


function kaosIsBulletinExpected($schemaObj, $date){
	if ($date > date('Y-m-d'))
		return false;
		
	if (is_string($schemaObj))
		$schemaObj = kaosGetSchema($schemaObj);
	$weekDays = !empty($schemaObj->frequency) && !empty($schemaObj->frequency->weekDays) ? $schemaObj->frequency->weekDays : null;
	$baseDays = array("MO", "TU", "WE", "TH", "FR", "SA", "SU");
	return !$weekDays || in_array($baseDays[intval(date('N', strtotime($date)))-1], $weekDays); 
}

function kaosGetFormatLabel($queryOrFormat, $default = null){
	$f = kaosGetFormatFetcher(is_array($queryOrFormat) ? kaosGetFormatByQuery($queryOrFormat) : $queryOrFormat);
	return $f && method_exists($f, 'getFormatLabel') ? $f->getFormatLabel() : ($default ? $default : strtoupper($format));
}

function kaosGetFormatByQuery($query){
	if (!empty($query['id']))
		return get('SELECT format FROM bulletins WHERE bulletin_schema = %s AND external_id = %s', array($query['schema'], $query['id']));
	else
		return get('SELECT format FROM bulletins WHERE bulletin_schema = %s AND date = %s', array($query['schema'], $query['date']));
}
