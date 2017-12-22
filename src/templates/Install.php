<?php
/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net>
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

$error = null;
$args = array();

if (!empty($_POST['kaosInstall'])){
	// sent the install form
	
	$args = array(
		'host' => @$_POST['kaosInstall_host'],
		'user' => @$_POST['kaosInstall_user'],
		'pass' => @$_POST['kaosInstall_pass'],
		'name' => @$_POST['kaosInstall_name'],
		'base_url' => @$_POST['kaosInstall_base_url'],
	);
	
	if (empty($args['host']) || empty($args['user']) || empty($args['name']) || empty($args['base_url']))
		$error = 'You must fill all fields';
	
	else {
		
		try {
			$conn = @(new mysqli($args['host'], $args['user'], $args['pass']));
		} catch (Exception $e){
			$conn = false;
			$error = 'Database connection failed: '.$e->getMessage();
		}
		if ($conn && $conn->connect_error){
			$err = $conn->connect_error;
			$conn = false;
			$error = 'Database connection failed: '.$err;
		}
		if ($conn && !mysqli_select_db($conn, $args['name'])){
			$conn = false;
			$error = 'Database "'.htmlentities($args['name']).'" not found, please create it first!';
		}
		
		if (!$error){
			// really install
			ignore_user_abort(true);
			$content = file_get_contents(BASE_PATH.'/config.sample.php');
			
			foreach (array(
				'DATABASE_HOST' => $args['host'],
				'DATABASE_USER' => $args['user'],
				'DATABASE_PASS' => $args['pass'],
				'DATABASE_NAME' => $args['name'],
				'BASE_URL' => rtrim($args['base_url'], '/').'/',
			) as $rep => $val)
				$content = str_replace('PUT_YOUR_'.$rep.'_HERE', $val, $content);
			
			// count tables
			$res = mysqli_query($conn, 'SELECT * FROM information_schema.tables WHERE table_schema = "'.$args['name'].'"');
			$count = mysqli_num_rows($res);
			if (!$count){
				
				// if no table found, import the db structure
				exec('mysql -u'.$args['user'].(!empty($args['pass']) ? ' -p'.$args['pass'] : '').' -h '.$args['host'].' '.$args['name'].' < "'.BASE_PATH.'/database/structure.sql"', $output, $return);
				
				if (!empty($return))
					$error = 'An error occured during the database structure setup.';
					
			}

			if (!$error){
				$resNames = mysqli_query($conn, 'SELECT * FROM names LIMIT 1');
				if (!mysqli_num_rows($resNames)){
				
					// if no names found, load them
					$cerror = loadNames($conn, $args);
					if ($cerror !== true){
						$error = $cerror;
						mysqli_query($conn, 'DELETE * FROM names');
					}
				}
			}

			if (!$error){
				if (!@file_put_contents(BASE_PATH.'/config.php', $content))
					$error = BASE_PATH.'/config.php couldn\'t be written. Please make '.BASE_PATH.' writtable.';
				else {
					ignore_user_abort(false);
					mysqli_close($conn);
					
					$_SESSION['kaos_justinstalled'] = 1;
					redirect($args['base_url'].'?installed=1');
				}
			}
			ignore_user_abort(false);
			mysqli_close($conn);
		}
	}
}

?><!DOCTYPE html>
	<html class="">
		<head>
			<?php head('Install'); ?>
			<style>
				.kaosError {
					border: 1px solid red;
					color: red;
					padding: 5px 10px;
					margin: 10px 0 20px;
					background: white;
				}
				#wrap {
					line-height: normal;
				}
				#wrap input[type="text"] {
					width: 100%;
					font-size: 14px;
					padding: 8px 8px;
				}
				#wrap > div {
					vertical-align: top;
					display: inline-block;
					width: 50%;
					margin-bottom: 20px;
				}
				.kaos-install-field {
					vertical-align: top;
					width: 100%;
					display: inline-block;
					margin-bottom: 20px;
				}
				#wrap > div > label {
					float: left;
					width: 180px;
					vertical-align: top;
					display: inline-block;
				}
				#wrap > div > div {
					margin-left: 200px;
					display: block;
				}
				.kaos-install-field {
					display: block;
					margin-bottom: 20px;
				}
			</style>
			<script>
			jQuery(document).ready(function(){
				var f = jQuery('#kaos-install-form');
				
				// submit the install form only once, and change the submit button's label
				f.submit(function(e){
					if (f.is('.installing')){
						e.preventDefault();
						e.stopPropagation();
						return false;
					}
					f.addClass('installing');
					var b = f.find('.install-submit');
					b.attr('value', b.data('kaos-installing'));
				});
			});
			</script>
		</head>
		<body>
			<div id="wrap">
				<form id="kaos-install-form" action="<?= kaosCurrentURL() ?>" method="POST">
					<h1>StateMapper Installation</h1>
					<?php
						if ($error)
							echo '<div class="kaosError">'.$error.'</div>';
							
						// TODO: add some system checks!!
						// function_exists('simplexml_load_file') for simplexml, for example
					?>
					<div>
						<label>Application base URL:</label>
						<div class="kaos-install-field">
							<input type="text" name="kaosInstall_base_url" value="<?= esc_attr(!empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') ?>://<?= (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') ?>/<?= preg_replace('#^(/var/www(/html)?/?)(.*?)$#i', '$3', BASE_PATH) ?>" />
						</div>
					</div>
					<div>
						<label>Database host:</label>
						<div class="kaos-install-field">
							<input name="kaosInstall_host" type="text" value="<?= (!empty($args['host']) ? esc_attr($args['host']) : 'localhost') ?>" />
						</div>
					</div>
					<div>
						<label>Database user:</label>
						<div class="kaos-install-field">
							<input name="kaosInstall_user" type="text" value="<?= (!empty($args['user']) ? esc_attr($args['user']) : 'root') ?>" />
						</div>
					</div>
					<div>
						<label>Database password:</label>
						<div class="kaos-install-field">
							<input name="kaosInstall_pass" type="password" value="" />
						</div>
					</div>
					<div>
						<label>Database name:</label>
						<div class="kaos-install-field">
							<input name="kaosInstall_name" type="text" value="<?= (!empty($args['name']) ? esc_attr($args['name']) : 'statemapper') ?>" />
						</div>
					</div>
					<!--
					<div>
						<label>Enable fetching through Tor:</label>
						<div>
							<?php if (kaosGetCommandPath('torify')){ ?>
								<div class="kaos-install-field">
									<label><input type="checkbox" checked /> Enable Tor fetching</label>
								</div>
								<div class="kaos-install-field">
									<span>Tor Controle API URL: </span><input type="text" value="127.0.0.1:9051" />
								</div>
								<div class="kaos-install-field">
									<span>Tor Socks Proxy URL: </span><input type="text" value="127.0.0.1:9050" />
								</div>
							<?php } else { ?>
								<div>Tor was not detected. You can install it through:</div>
							<?php } ?>
						</div>
					</div>
					<div>
						<label>IPFS:</label>
						<div>
							<div>
								<?php if (!($ipfsBin = kaosGetCommandPath('ipfs'))){ ?>
									<div>IPFS was not detected. You can install it through:</div>
								<?php } ?>
							</div>
							<div class="kaos-install-field"><label>IPFS bin path: <input type="text" value="<?= ($ipfsBin ? $ipfsBin : 'ipfs') ?>" /></label></div>
							<div>Enable IPFS fetching for:</div>
							<div class="kaos-install-field"><label><input type="checkbox" checked /> App updates</label></div>
							<div class="kaos-install-field"><label><input type="checkbox" checked /> Bulletin definitions (schemas)</label></div>
							<div class="kaos-install-field"><label><input type="checkbox" checked /> Bulletins</label></div>
							<div class="kaos-install-field"><label><input type="checkbox" checked /> Parsed bulletins</label></div>
						</div>
					</div>
					<div>
						<lalbel>&nbsp;</lalbel>
						<div>
							<?php if (!is_writable(DATA_PATH)){ ?>
								<div><i class="fa fa-warning"></i> <?= DATA_PATH ?> must be made writable. Please execute <code>sudo chmod -R 777 <?= DATA_PATH ?></code></div>
							<?php } else { ?>
								<div><i class="fa fa-check"></i> <?= DATA_PATH ?> is writable.</div>
							<?php } ?>
						</div>
					</div>
					-->
					<div>
						<div>
							<input class="install-submit" type="submit" name="kaosInstall" value="Install" data-kaos-installing="Installing, please be patient..." />
						</div>
					</div>
				</form>
			</div>
		</body>
	</html><?php 
exit();

