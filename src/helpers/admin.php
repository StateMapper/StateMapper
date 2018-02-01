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

// CLI-only functions
if (!IS_CLI)
	die_error('This command is only for CLI purpose');

function admin(){
	global $smap;
	
	$cmd = !empty($smap['cli_args']) && count($smap['cli_args']) > 1 ? $smap['cli_args'][1] : null;
		
	switch ($cmd){
		case 'clear':
			$input = readline('Are you SURE you want to CLEAR ALL extracted data? [y/N] ');
			
			if ($input === 'y'){
				admin_clear();
				
			} else
				echo 'Operation aborted'.PHP_EOL;
			exit(0);

		case 'reset':
			break;
			
			// @todo: implement factory reset
			$input = readline('You are about to reset the whole installation to a fresh one. Are you SURE you want to RESET the whole installation? [y/N] ');
			
			if ($input === 'y'){
				echo 'Resetting the whole installation to a fresh one...'.PHP_EOL;
				
			} else
				echo 'Operation aborted'.PHP_EOL;
			exit(0);
			
		case 'create_user':
		case 'update_user_pass':
		case 'update_user_status':
		
			$user_login = readline('Please enter the new user_login: ');
			
			if (empty($user_login) || !preg_match('#^[a-z0-9_]+$#i', trim($user_login)))
				echo 'user_login can only contains latin letters, numbers and underscores.'.PHP_EOL.'Operation aborted.'.PHP_EOL;
			
			else {
				$user_login = trim($user_login);
				
				if (in_array($cmd, array('create_user', 'update_user_pass')))
					$user_pass = readline_silent('Please enter the new user_pass: ');
				else
					$user_pass = true;
				
				if ($user_pass !== true && (empty($user_pass) || strlen(trim($user_pass)) < 8))
					echo 'user_pass must be at least 8 characters.'.PHP_EOL.'Operation aborted.'.PHP_EOL;
					
				else {
				
					$user_pass_confirm = $user_pass === true ? true : readline_silent('Please enter the same user_pass again: ');
					
					if ($user_pass_confirm !== $user_pass)
						echo 'the two entered user_pass are different.'.PHP_EOL.'Operation aborted.'.PHP_EOL;
					
					else {
					
						if ($user_pass !== true)
							$user_pass = hash('sha256', trim($user_pass));
						
						if (in_array($cmd, array('create_user', 'update_user_status')))
							$status = readline('Please enter the new user\'s status [active|pending|disabled]: ');
						else
							$status = true;
						
						if ($status !== true && !in_array($status, array('active', 'pending', 'disabled')))
							echo 'bad status entered.'.PHP_EOL.'Operation aborted.'.PHP_EOL;
						
						else {
							$id = get_var('SELECT id FROM users WHERE user_login = %s', $user_login);
							
							if ($cmd != 'create_user'){
								// updating user
								if (!$id)
									echo 'user_login not found.'.PHP_EOL.'Operation aborted.'.PHP_EOL;
								else {
									$update = $cmd == 'update_user_pass' ? array(
										'user_pass' => $user_pass,
									) : array(
										'status' => $status,
									);
									update('users', $update, array(
										'id' => $id
									));
									echo 'user #'.$id.' updated'.PHP_EOL;
									exit(1);
								}
								
							} else if ($id)
								echo 'the user_login already exists.'.PHP_EOL.'Operation aborted.'.PHP_EOL;
							
							else {
								
								// creating user
								if (!($user = create_user(array(
									'user_login' => $user_login,
									'user_pass' => $user_pass,
									'status' => $status,
								))))
									echo 'Internal error. The user couldn\'t be created.'.PHP_EOL;
								else {
									echo 'user created with ID #'.$user['id'].PHP_EOL;
									exit(0);
								}
							}
						}
					}
				}
			}
			exit(1);
	}
	
	echo 'missing command'.PHP_EOL;
	exit(1);
}


function admin_clear(){
		
	$tables = array(
		'entities', 
		'precepts', 
		'statuses', 
		'status_has_service', 
		'amounts', 
		'lists',
		'list_has_entity',
		'locations',
		'location_states',
		'location_counties',
		'location_cities',
		
		/* TODO: implement several hard-reset button
		'bulletins',
		'spiders',
		'workers',
		* */
	);
	$errors = 0;
	foreach ($tables as $table)
		if (!query('TRUNCATE '.$table))
			$errors++;
	
	clean_tables(true);
	query('UPDATE bulletins SET status = "fetched" WHERE status IN ( "extracting", "extracted" )');
		
	echo 'Tables '.implode(', ', $tables).' were '.($errors ? 'emptied with '.$errors.' errors' : 'successfuly emptied').PHP_EOL;
	return $errors;
}

// Convert all tables to TokuDB engine with "?setNewEngine=TokuDB" - for development purpose only
function convert_db_engine($newEngine){
	if (!in_array($newEngine, array('TokuDB')))
		die('bad engine');
		
	$sql = '
		SELECT TABLE_NAME, ENGINE FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = "'.DB_NAME.'"
    ';

    foreach (query($sql) as $t){
		$alter = strcasecmp($t['ENGINE'], $newEngine);
		if ($alter){
			echo 'Altering table "'.$t['TABLE_NAME'].'" engine from '.$t['ENGINE'].' to '.$newEngine.'<br>';
			query('ALTER TABLE '.$t['TABLE_NAME'].' ENGINE="'.$newEngine.'"');
		} else 
			echo 'Leaving table "'.$t['TABLE_NAME'].'" with engine '.$t['ENGINE'].'<br>';
		
		//query('ALTER SCHEMA '.DB_NAME.' DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_general_ci');
		//query('ALTER TABLE '.$t['TABLE_NAME'].' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    }
    die('done');
}
// convert_db_engine('TokuDB');



// from https://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
function readline_silent($prompt) {
	if (preg_match('/^win/i', PHP_OS)) {
		
		$vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
		
		file_put_contents(
			$vbscript, 'wscript.echo(InputBox("'
			. addslashes($prompt)
			. '", "", "password here"))');
		
		$command = "cscript //nologo " . escapeshellarg($vbscript);
		$password = rtrim(shell_exec($command));
		unlink($vbscript);
		return $password;
		
	} else {
		
		$command = "/usr/bin/env bash -c 'echo OK'";
		
		if (rtrim(shell_exec($command)) !== 'OK') {
			trigger_error("Can't invoke bash");
			return;
		}
		
		$command = "/usr/bin/env bash -c 'read -s -p \""
			. addslashes($prompt)
			. "\" mypassword && echo \$mypassword'";
			
		$password = rtrim(shell_exec($command));
		echo "\n";
		return $password;
	}
}
