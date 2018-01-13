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
	

function get_command_path($cmd){
	exec('whereis -b '.$cmd, $output, $ret);
	if (empty($ret) && !empty($output) && !preg_match('#^[a-z0-9_]+:$#i', trim($output[0]), $m))
		return preg_match('#^[a-z0-9_]+:\s*([a-z0-9_/]+)(\s*.*)?$#i', trim($output[0]), $m) ? $m[1] : false;
	return false;
}

function get_command_running($cmd){
	exec('ps -aux | grep "'.$cmd.'" | grep -v "grep '.$cmd.'" | tr -s " " | cut -d" " -f2', $output, $ret);
	if (empty($ret) && !empty($output))
		return $output[0];
	return false;
}
