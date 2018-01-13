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


function export(){
	if (!is_writable(BASE_PATH.'/database'))
		die_error(BASE_PATH.'/database must be made writable');
		
	$dest = BASE_PATH.'/database/structure';
	@unlink($dest.'.exporting.sql');

	echo 'exporting database structure to '.strip_root($dest.'.sql').'...'.PHP_EOL; 
		
	$cmd = 'mysqldump --no-data --no-create-db --skip-add-drop-table -u "'.DB_USER.'"'.(DB_PASS != '' ? ' -p "'.DB_PASS.'"' : '').' -h "'.DB_HOST.'" "'.DB_NAME.'" > "'.$dest.'.exporting.sql"';

	exec($cmd, $output, $returnVar);
	if (!empty($returnVar) || $output){
		@unlink($dest.'.exporting.sql');
		die_error('an error occurred: '.$output[0]);
	}
	if (!rename($dest.'.exporting.sql', $dest.'.sql'))
		die_error('an error occurred: '.$output[0]);
	echo 'done'.PHP_EOL; 
}
