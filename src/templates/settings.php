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
	
global $smap;

print_header('page');
?>
<?php if (!function_exists('mb_detect_encoding') || !function_exists('iconv')){ ?>
	<div class="block">
		<?php
		if (!function_exists('mb_detect_encoding')){ ?>
			<div><i class="fa fa-warning"></i> php mb_* functions missing, please install it</div>
			<?php 
		}
		if (!function_exists('iconv')){ ?>
			<div><i class="fa fa-warning"></i> php iconv function missing, please install it</div>
			<?php
		}
		?>
	</div>
	<?php
}
?>

<h3><i class="fa fa-hdd-o"></i> Disk space</h3>
<div class="block block-diskspace">
	<?php
		// print folder sizes on disk
		echo '<table border="0">';
		
		$loader = get_loading(false);

		$size = get_option('disksize_schemas');
		echo '<tr><td class="smap-icon-td"><i class="fa fa-book"></i> </td><td>'.str_pad('Schemas folder: ', 25, '.').' </td><td><span class="smap-disksize smap-disksize-schemas '.($size ? '' : 'smap-disksize-fetchnow').'" data-smap-disksize="schemas">'.($size ? $size : 'fetching..').'</span>'.$loader.'</td></tr>';

		$size = get_option('disksize_data');
		echo '<tr><td class="smap-icon-td"><i class="fa fa-cloud-download"></i> </td><td>'.str_pad('Bulletins folder: ', 25, '.').' </td><td><span class="smap-disksize smap-disksize-data '.($size ? '' : 'smap-disksize-fetchnow').'" data-smap-disksize="data">'.($size ? $size : 'fetching..').'</span>'.$loader.'</td></tr>';

		$size = get_option('disksize_free');
		$total = get_option('disksize_total');
		echo '<tr><td class="smap-icon-td"><i class="fa fa-hdd-o"></i> </td><td>'.str_pad('Free disk space: ', 25, '.').' <td>'
			.'<span class="smap-disksize smap-disksize-free '.($size ? '' : 'smap-disksize-fetchnow').'" data-smap-disksize="free">'.($size ? $size : 'fetching..').'</span> / '
			.'<span class="smap-disksize smap-disksize-total '.($total ? '' : 'smap-disksize-fetchnow').'" data-smap-disksize="total">'.($total ? $total : 'fetching..').'</span> ('
			.'<span class="smap-disksize smap-disksize-freepct '.($total ? '' : 'smap-disksize-fetchnow').'" data-smap-disksize="freepct">'.($total ? get_disk_free_pct(true) : 'fetching..').'</span>)'
		.$loader.'</td></tr>';
		
		echo '</table>';
		
	?>
</div>

<h3><i class="fa fa-globe"></i> InterPlanetary File System</h3>
<div class="block block-ipfs">
	<div class="block-intro-help">
		<div><a href="<?= anonymize('https://ipfs.io/') ?>" target="_blank">IPFS</a> is a P2P filesystem that allows to share bulletins and schemas with no central authority.</div>
		<br/>
		<table border="0">
			
			<tr><td><?= str_pad('Installed version: ', 28, '.') ?> </td><td><?php
				if (!get_command_path('ipfs'))
					echo 'not installed, please <a href="'.anonymize('https://ipfs.io/docs/install/').'" target="_blank">install it</a>.';
				else {
					$configEnabled = in_array('ipfs', explode(',', BULLETIN_CACHES_READ));
					echo preg_replace('#^ipfs\s*version\s*(.*)$#i', '$1', shell_exec('ipfs version'));
				}
			?>
			</td></tr>
			<?php 
			
			$ipfsRunning = get_command_running('ipfs daemon');
			echo '<tr><td>'.str_pad('Daemon: ', 28, '.').' </td><td>'.(
				$ipfsRunning 
				? '<i class="fa fa-check"></i> running (#'.$ipfsRunning.')'
				: '<i class="fa fa-times"></i> inactive'
			).'</td></tr>';
			
			if (in_array('ipfs', explode(',', BULLETIN_CACHES_READ))){
				global $smapConfig;
				if (!empty($smapConfig['IPFS'])){
					echo '<div><i class="fa fa-check"></i> IPFS enabled with API url '.IPFS_API_URL.'. Kaos nodes: <ul>';
					if (!empty($smapConfig['IPFS']['fetchFrom']))
						foreach ($smapConfig['IPFS']['fetchFrom'] as $nodeUrl => $nodeConfig)
							echo '<li><i class="fa fa-download"></i> Receiving from: "'.$nodeConfig['name'].'" on <a href="'.(defined('IPFS_WEB_URL') ? IPFS_WEB_URL : IPFS_API_URL.'/api/v0/ls?arg=').$nodeUrl.'">'.$nodeUrl.'</a></li>';
					if (!empty($smapConfig['IPFS']['uploadTo']))
						foreach ($smapConfig['IPFS']['uploadTo'] as $nodeUrl => $nodeConfig)
							echo '<li><i class="fa fa-upload"></i> Uploading to: "'.$nodeConfig['name'].'" on <a href="'.(defined('IPFS_WEB_URL') ? IPFS_WEB_URL : IPFS_API_URL.'/api/v0/ls?arg=').$nodeUrl.'">'.$nodeUrl.'</a></li>';
					echo '</ul></div>';
				} else
					echo '<div><i class="fa fa-warning"></i> IPFS enabled on gateway '.IPFS_API_URL.', but no Kaos nodes configured</div>';
			
			} else
				echo '<tr><td>'.str_pad('Status: ', 28, '.').' </td><td><i class="fa fa-times"></i> disabled</td></tr>';
				
			?>
		</table>
	</div>
</div>

<h3><i class="fa fa-connectdevelop"></i> Tor</h3>
<div class="block block-tor">
	<?php if (TOR_ENABLED){ ?>
		<i class="fa fa-globe"></i> TOR proxy enabled via <?= TOR_PROXY_URL ?>
	<?php } else { ?>
		<i class="fa fa-times"></i> TOR proxy disabled. To install and enable Tor, please follow step 5 of <a href="<?= anonymize(get_repository_url('blob/master/documentation/guides/INSTALL.md#installation')) ?>" target="_blank">these installation instructions</a>.
	<?php } ?>
</div>

<?php

print_footer();
