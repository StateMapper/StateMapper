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

$smap['outputNoFilter'] = true;

$error = null;
$args = array();

if (!empty($_POST['smap_install'])){
	// sent the install form
	
	$args = array(
		'host' => @$_POST['smap_install_host'],
		'user' => @$_POST['smap_install_user'],
		'pass' => @$_POST['smap_install_pass'],
		'name' => @$_POST['smap_install_name'],
		'base_url' => @$_POST['smap_install_base_url'],
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
				'BASE_URL' => trailingslashit(trim($args['base_url'])),
			) as $rep => $val)
				$content = str_replace('PUT_YOUR_'.$rep.'_HERE', $val, $content);
			
			// count tables
			$res = mysqli_query($conn, 'SELECT * FROM information_schema.tables WHERE table_schema = "'.$args['name'].'"');
			$count = mysqli_num_rows($res);
			if (!$count){
				
				// if no table found, import the db structure
				exec('mysql -u'.$args['user'].(!empty($args['pass']) ? ' -p'.$args['pass'] : '').' -h '.$args['host'].' '.$args['name'].' < "'.BASE_PATH.'/database/structure.sql"', $output, $return);
				
				if (!empty($return))
					$error = 'An error occurred during the database structure setup ('.$return.'): '."\n".implode("\n", $output);
			}

			if (!$error){
				$resNames = mysqli_query($conn, 'SELECT * FROM names LIMIT 1');
				if (!mysqli_num_rows($resNames)){
				
					// if no names found, load them
					$cerror = install_load_names($conn, $args);
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
					
					// insert version
					if (!$count)
						mysqli_query($conn, 'INSERT INTO options ( name, value ) VALUES ( "v", "'.SMAP_VERSION.'" )');
					
					mysqli_close($conn);
					ignore_user_abort(false);
					
					// redirect to success page
					$_SESSION['smap_installed'] = 1;
					redirect($args['base_url']);
				}
			}
			ignore_user_abort(false);
			mysqli_close($conn);
		}
	}
}

?>
<style>
	.smap-error {
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
	.smap-install-field {
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
	.smap-install-field {
		display: block;
		margin-bottom: 20px;
	}
</style>
<script>
jQuery(document).ready(function(){
	var f = jQuery('#smap-install-form');
	
	// submit the install form only once, and change the submit button's label
	f.submit(function(e){
		if (f.is('.installing')){
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
		f.addClass('installing');
		var b = f.find('.install-submit');
		b.attr('value', b.data('smap-installing'));
	});
});
</script>
<div id="wrap">
	<form id="smap-install-form" action="<?= current_url() ?>" method="POST">
		<?php
			if ($error)
				echo '<div class="smap-error">'.$error.'</div>';
				
			if (!empty($_POST['smap_install_base_url']))
				$base_url = $_POST['smap_install_base_url'];
			
			else {
				$base_url = (!empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http').'://';
				
				if (!empty($_SERVER['HTTP_HOST']))
					$base_url .= $_SERVER['HTTP_HOST'];
				else
					$base_url .= 'localhost';
					
				$base_url .= '/'.preg_replace('#^(/var/www(/html)?/?)(.*?)$#i', '$3', BASE_PATH);
				
				$base_url = trailingslashit($base_url);
			}
			
			// TODO: add some system checks!!
			// function_exists('simplexml_load_file') for simplexml, for example
		?>
		<div>
			<label>Application base URL:</label>
			<div class="smap-install-field">
				<input type="text" name="smap_install_base_url" value="<?= esc_attr($base_url) ?>" />
			</div>
		</div>
		<div>
			<label>Database host:</label>
			<div class="smap-install-field">
				<input name="smap_install_host" type="text" value="<?= (!empty($args['host']) ? esc_attr($args['host']) : 'localhost') ?>" />
			</div>
		</div>
		<div>
			<label>Database user:</label>
			<div class="smap-install-field">
				<input name="smap_install_user" type="text" value="<?= (!empty($args['user']) ? esc_attr($args['user']) : 'root') ?>" />
			</div>
		</div>
		<div>
			<label>Database password:</label>
			<div class="smap-install-field">
				<input name="smap_install_pass" type="password" value="" />
			</div>
		</div>
		<div>
			<label>Database name:</label>
			<div class="smap-install-field">
				<input name="smap_install_name" type="text" value="<?= (!empty($args['name']) ? esc_attr($args['name']) : 'statemapper') ?>" />
			</div>
		</div>
		<!--
		<div>
			<label>Enable fetching through Tor:</label>
			<div>
				<?php if (get_command_path('torify')){ ?>
					<div class="smap-install-field">
						<label><input type="checkbox" checked /> Enable Tor fetching</label>
					</div>
					<div class="smap-install-field">
						<span>Tor Controle API URL: </span><input type="text" value="127.0.0.1:9051" />
					</div>
					<div class="smap-install-field">
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
					<?php if (!($ipfsBin = get_command_path('ipfs'))){ ?>
						<div>IPFS was not detected. You can install it through:</div>
					<?php } ?>
				</div>
				<div class="smap-install-field"><label>IPFS bin path: <input type="text" value="<?= ($ipfsBin ? $ipfsBin : 'ipfs') ?>" /></label></div>
				<div>Enable IPFS fetching for:</div>
				<div class="smap-install-field"><label><input type="checkbox" checked /> App updates</label></div>
				<div class="smap-install-field"><label><input type="checkbox" checked /> Bulletin definitions (schemas)</label></div>
				<div class="smap-install-field"><label><input type="checkbox" checked /> Bulletins</label></div>
				<div class="smap-install-field"><label><input type="checkbox" checked /> Parsed bulletins</label></div>
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
				<input class="install-submit" type="submit" name="smap_install" value="Install" data-smap-installing="Installing, please be patient..." />
			</div>
		</div>
	</form>
</div>
<?php


