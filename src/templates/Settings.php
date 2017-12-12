<?php

if (!defined('BASE_PATH'))
	die();
	
global $kaosCall;
$kaosCall['outputNoFilter'] = true;


ob_start();

?>
<?php if (!function_exists('mb_detect_encoding') || !function_exists('iconv')){ ?>
	<div class="kaos-block">
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
<div class="kaos-block kaos-block-diskspace">
	<?php
		// print folder sizes on disk
		echo '<table border="0">';
		
		$loader = '<i class="kaos-disksize-loader fa fa-circle-o-notch fa-spin"></i>';

		$size = getOption('disksize_schemas');
		echo '<tr><td class="kaos-icon-td"><i class="fa fa-book"></i> </td><td>'.str_pad('Schemas folder: ', 25, '.').' </td><td><span class="kaos-disksize kaos-disksize-schemas '.($size ? '' : 'kaos-disksize-fetchnow').'" data-kaos-disksize="schemas">'.($size ? $size : 'fetching..').'</span>'.$loader.'</td></tr>';

		$size = getOption('disksize_data');
		echo '<tr><td class="kaos-icon-td"><i class="fa fa-cloud-download"></i> </td><td>'.str_pad('Bulletins folder: ', 25, '.').' </td><td><span class="kaos-disksize kaos-disksize-data '.($size ? '' : 'kaos-disksize-fetchnow').'" data-kaos-disksize="data">'.($size ? $size : 'fetching..').'</span>'.$loader.'</td></tr>';

		$size = getOption('disksize_free');
		$total = getOption('disksize_total');
		echo '<tr><td class="kaos-icon-td"><i class="fa fa-hdd-o"></i> </td><td>'.str_pad('Free disk space: ', 25, '.').' <td>'
			.'<span class="kaos-disksize kaos-disksize-free '.($size ? '' : 'kaos-disksize-fetchnow').'" data-kaos-disksize="free">'.($size ? $size : 'fetching..').'</span> / '
			.'<span class="kaos-disksize kaos-disksize-total '.($total ? '' : 'kaos-disksize-fetchnow').'" data-kaos-disksize="total">'.($total ? $total : 'fetching..').'</span> ('
			.'<span class="kaos-disksize kaos-disksize-freepct '.($total ? '' : 'kaos-disksize-fetchnow').'" data-kaos-disksize="freepct">'.($total ? kaosGetDiskfreepct(true) : 'fetching..').'</span>)'
		.$loader.'</td></tr>';
		
		echo '</table>';
		
	?>
</div>

<h3><i class="fa fa-globe"></i> InterPlanetary File System</h3>
<div class="kaos-block kaos-block-ipfs">
	<div class="kaos-block-intro-help">
		<div><a href="<?= kaosAnonymize('https://ipfs.io/') ?>" target="_blank">IPFS</a> is a P2P filesystem that allows to share bulletins and schemas with no central authority.</div>
		<br/>
		<table border="0">
			
			<tr><td><?= str_pad('Installed version: ', 28, '.') ?> </td><td><?php
				if (!kaosGetCommandPath('ipfs'))
					echo 'not installed, please <a href="'.kaosAnonymize('https://ipfs.io/docs/install/').'" target="_blank">install it</a>.';
				else {
					$configEnabled = in_array('ipfs', explode(',', BULLETIN_CACHES_READ));
					echo preg_replace('#^ipfs\s*version\s*(.*)$#i', '$1', shell_exec('ipfs version'));
				}
			?>
			</td></tr>
			<?php 
			
			$ipfsRunning = kaosGetCommandRunning('ipfs daemon');
			echo '<tr><td>'.str_pad('Daemon: ', 28, '.').' </td><td>'.(
				$ipfsRunning 
				? '<i class="fa fa-check"></i> running (#'.$ipfsRunning.')'
				: '<i class="fa fa-times"></i> inactive'
			).'</td></tr>';
			
			if (in_array('ipfs', explode(',', BULLETIN_CACHES_READ))){
				global $kaosConfig;
				if (!empty($kaosConfig['IPFS'])){
					echo '<div><i class="fa fa-check"></i> IPFS enabled with API url '.IPFS_API_URL.'. Kaos nodes: <ul>';
					if (!empty($kaosConfig['IPFS']['fetchFrom']))
						foreach ($kaosConfig['IPFS']['fetchFrom'] as $nodeUrl => $nodeConfig)
							echo '<li><i class="fa fa-download"></i> Receiving from: "'.$nodeConfig['name'].'" on <a href="'.(defined('IPFS_WEB_URL') ? IPFS_WEB_URL : IPFS_API_URL.'/api/v0/ls?arg=').$nodeUrl.'">'.$nodeUrl.'</a></li>';
					if (!empty($kaosConfig['IPFS']['uploadTo']))
						foreach ($kaosConfig['IPFS']['uploadTo'] as $nodeUrl => $nodeConfig)
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
<div class="kaos-block kaos-block-tor">
	<?php if (TOR_ENABLED){ ?>
		<i class="fa fa-globe"></i> TOR proxy enabled via <?= TOR_PROXY_URL ?>
	<?php } else { ?>
		<i class="fa fa-times"></i> TOR proxy disabled. To install and enable Tor, please follow step 5 of <a href="<?= kaosAnonymize(getRepositoryUrl('blob/master/documentation/guides/INSTALL.md#installation')) ?>" target="_blank">these installation instructions</a>.
	<?php } ?>
</div>

<?php


kaosAPIReturn(ob_get_clean(), 'Settings');
exit();
