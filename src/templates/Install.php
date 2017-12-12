<?php

if (!defined('BASE_PATH'))
	die();

?><!DOCTYPE html>
	<html class="">
		<head>
			<?php apiHead('Install'); ?>
			
			<script type="text/javascript">
				jQuery(document).ready(function(){
				});
			</script>
			<style>
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
		</head>
		<body>
			<div id="wrap">
				<h1>Kaos Installation</h1>
				<div>
					<label>Application base URL:</label>
					<div class="kaos-install-field">
						<input type="text" value="http//localhost/kaos155-php/application" />
					</div>
				</div>
				<div>
					<label>Application base URI:</label>
					<div class="kaos-install-field">
						<?= preg_replace('#^(/var/www(/html)?)(.*?)$#i', '$1', BASE_PATH) ?>/<input type="text" value="<?= preg_replace('#^(/var/www(/html)?/?)(.*?)$#i', '$3', BASE_PATH) ?>" />
					</div>
				</div>
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
				<div>
					<div>
						<input type="submit" value="Install" />
					</div>
				</div>
			</div>
		</body>
	</html><?php 
exit();

