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


function wait_for_lock($key, $timeout = 5){ // timeout in seconds
	$begin = time();
	do {
		$id = lock($key);
		if ($id !== false)
			return $id;
		usleep(500000); // half second
	} while (time() - $begin < $timeout);
	return false;
}

function lock($key){
	$id = insert('locks', array('target' => $key, 'created' => date('Y-m-d H:i:s')));
	if (!$id)
		return false;
	$lock_id = get_var('SELECT id FROM locks WHERE target = %s ORDER BY id ASC', array($key));
	return $id === intval($lock_id) ? $key : false;
}

function unlock($key){
	if (!empty($key))
		query('DELETE FROM locks WHERE target = %s', array($key));
}

add_action('clean_tables', 'clean_locks');
function clean_locks($all){
	static $lastCleaned = null;
	
	if ($all)
		query('DELETE FROM locks');
	
	else if (!$lastCleaned || $lastCleaned < time() - 60){ // clean every minute top
		$lastCleaned = time();
		query('DELETE FROM locks WHERE created < %s', array(date('Y-m-d H:i:s', time() - (max(MAX_EXECUTION_TIME, 900) + 60)))); // clean after max(MAX_EXECUTION_TIME, 15min) + 1 minute
	}
}
