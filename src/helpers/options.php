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



function update_option($name, $value){
	$id = add_option($name, $value);
	query('DELETE FROM options WHERE name = %s AND id != %s ORDER BY id ASC', array($name, $id));
}

function add_option($name, $value){
	return insert('options', array('name' => $name, 'value' => is_object($value) || is_array($value) || is_bool($value) ? serialize($value) : $value));
}


function get_option($name, $default = null){
	$value = get_var('SELECT value FROM options WHERE name = %s ORDER BY id DESC LIMIT 1', array($name));
	if ($value === null)
		return $default;

	try {
		$unserialized = @unserialize($value);
	} catch (Exception $e){
		return $value;
	}
	return $unserialized === false ? $value : $unserialized;
}

function delete_option($name){
	return query('DELETE FROM options WHERE name = %s', $name);
}

function addget_option($name, $val){
	$id = get_var('SELECT id FROM options WHERE name = %s AND value = %s LIMIT 1', array($name, $val));
	if ($id === null)
		$id = insert('options', array(
			'name' => $name,
			'value' => $val,
		));
	return $id;
}

