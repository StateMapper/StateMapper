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

// favicon
$fav = 'map-signs';
$modes = get_modes();
$types = get_entity_types();
if (!empty($smap['entity'])){
	$fav = $types[$smap['entity']['type']]['icon'];
} else 
	switch (!empty($smap['page']) ? $smap['page'] : 'browser'){
		
		case 'providers':
			$fav = $modes['providers']['icon'];
			break;

		case 'ambassadors':
			$fav = $modes['ambassadors']['icon'];
			break;
			
		case 'bulletin':
		case 'bulletins':
			$fav = $modes[$smap['call']]['icon'];
			break;
			
		default:
			if (!empty($smap['filters']['etype']) && isset($types[$smap['filters']['etype']]))
				$fav = $types[$smap['filters']['etype']]['icon'];
	}


?><!DOCTYPE html>
<html<?php do_action('html_attributes') ?> class="<?php
	echo 'smap-call-type-'.(!empty($smap['call']) ? $smap['call'] : 'none');
	echo ' smap-call-schema-'.(!empty($smap['query']) && !empty($smap['query']['schema']) ? $smap['query']['schema'] : 'none');
?>">
	<head>
<?= get_disclaimer('html') ?>

		<link rel="icon" href="<?= APP_URL.'/addons/fontawesome_favicons/'.$fav.'.ico' ?>" type="image/x-icon" />
		<?php
		
		/* to test?..
		<link rel="EditURI" type="application/rsd+xml" title="RSD" href="https://statemapper.net/xmlrpc.php?rsd">
		<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="https://statemapper.net/wp-includes/wlwmanifest.xml"> 

		<link rel="shortlink" href="https://statemapper.net/">
		<link rel="alternate" type="application/json+oembed" href="">
		<link rel="alternate" type="text/xml+oembed" href="">
		
		... <link rel="alternate" type="text/calendar" title="%s" href="%s" />
		*/
		
		do_action('head'); 
		
		?>
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php
		
		// allow extra head tags (do not put addons/extra_head.php on github!!)
		if (is_file(APP_PATH.'/addons/extra_head.php'))
			include(APP_PATH.'/addons/extra_head.php');

		?>
	</head>
	<body class="<?php
		if (!empty($body_class))
			echo $body_class;
		if (!empty($smap['isIframe']))
			echo ' has-iframe';
		if (!empty($entity))
			echo ' has-results ';
		if (has_filter())
			echo ' filters-open';
		else if (is_home(true))
			echo ' root';
		if (IS_ALPHA)
			echo ' alpha';
		if (IS_API)
			echo ' api';
	?>">
		<div id="main">
			<?php 
			// home backgrounds
			if (is_home(true)){ ?>
				<div class="bg-diag-left">
					<div class="bg-triangle"></div>
					<div class="bg-triangle-bg"></div>
				</div>
				<div class="bg-stripes-wrap">
					<div class="bg-stripes bg-stripes-left"></div>
					<div class="bg-stripes bg-stripes-right"></div>
				</div>
				<div class="bg-diag-right">
					<div class="bg-triangle"></div>
					<div class="bg-triangle-bg"></div>
				</div>
			<?php } ?>
