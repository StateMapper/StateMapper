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
	
function getTempFolder(){
	return APP_PATH.'/assets/tmp'; // set to false for no caching
}
	
function getTempUrl(){
	return APP_URL.'/assets/tmp'; // set to false for no caching
}

function print_scss_tags(){
	$scss_ids = array('reset', 'font', 'main', 'home'); // scss to include
	
	require(APP_PATH.'/assets/lib/scssphp/scss.inc.php');
	
	$formatter = KAOS_DEBUG ? 'Expanded' : 'Compressed';
	
	$scss = new \Leafo\ScssPhp\Compiler(APP_PATH.'/assets/scss');
	$scss->setFormatter('Leafo\\ScssPhp\\Formatter\\'.$formatter);

	$path = array();
	foreach ($scss_ids as $scss_id)
		$path[] = APP_PATH.'/assets/scss/_'.$scss_id.'.scss';
		
	$date = null;
	foreach ($path as $p)
		$date = $date ? max(filemtime($p), $date) : filemtime($p);
	
	$scssName = 'kaos-'.implode('+', $scss_ids).'-'.$date.'-'.strtolower($formatter).'.css';
	$dest = getTempFolder().'/'.$scssName;

	// generate css if dest missing or dest's modification time is earlier than max of scss's modification times.
	if (!file_exists($dest)){
		$str = '';
		foreach ($path as $p)
			$str .= file_get_contents($p).' ';
			
		file_put_contents(getTempFolder().'/'.$scssName, $scss->compile($str));
	} 
	?>
	<link rel="stylesheet" type="text/css" href="<?= getTempUrl().'/'.$scssName ?>" />
	<?php
}

add_action('head', function(){
	
	add_js('helpers');

	global $kaosCall, $kaosPage;
	$session = array(
		'query' => isset($kaosCall['query']) ? $kaosCall['query'] : array(),
	);
	
	?>

	<link rel="stylesheet" type="text/css" href="<?= ASSETS_URL ?>/lib/font-awesome-4.7.0/css/font-awesome.min.css" />
	<?php print_scss_tags() ?>

	<script type="text/javascript" src="<?= ASSETS_URL ?>/lib/jquery-3.2.1/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="<?= ASSETS_URL ?>/lib/tippyjs-2.0.0-beta.2/dist/tippy.all.min.js"></script>

	<script type="text/javascript">
		var KAOS = {
			ajaxUrl: '<?= BASE_URL ?>',
			session: <?= json_encode($session) ?>,
			refreshMap: <?= (!empty($_GET['stop']) ? '0' : '1') ?>,
			searchUrl: '<?= add_url_arg('q', '%s', isHome() ? null : BASE_URL, false) ?>'
		};
	</script>
	<?php
	foreach (add_js() as $js)
		echo '<script type="text/javascript" src="'.ASSETS_URL.'/js/'.$js.'.js?v='.KAOS_ASSETS_INC.'"></script>';
});

function add_js($js = null){
	static $jss = array();
	if ($js)
		$jss[] = $js;
	return $jss;
}
