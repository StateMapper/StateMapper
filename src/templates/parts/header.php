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
	
// protect against header double-print due to errors while printing
if (defined('SMAP_HEADER_PRINTED'))
	return;
define('SMAP_HEADER_PRINTED', true);

// favicon
$fav = 'map-signs';
$modes = get_modes();
$types = get_entity_types();
if (!empty($smap['entity'])){
	$fav = $types[$smap['entity']['type']]['icon'];
} else 
	switch (!empty($smap['page']) ? $smap['page'] : 'browser'){
		
		default:
			
			if (!empty($modes[$smap['page']]))
				$fav = $modes[$smap['page']]['icon'];
			
			if (!empty($modes[$smap['call']]))
				$fav = $modes[$smap['call']]['icon'];
			
			else if (!empty($smap['filters']['etype']) && isset($types[$smap['filters']['etype']]))
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
		if (!empty($smap['is_iframe']))
			echo ' has-iframe';
		if (!empty($entity))
			echo ' has-results ';
		if (has_filter())
			echo ' filters-open';
		else if (is_home())
			echo ' home';
		if (IS_ALPHA)
			echo ' alpha';
		if (IS_API)
			echo ' api';
		
		if (defined('IS_ERROR') && IS_ERROR)
			echo ' is-error';		
		else
			echo ' call-'.(!empty($smap['call']) ? $smap['call'] : 'browser');
		echo ' page-'.(!empty($smap['page']) ? $smap['page'] : 'home');
		
		echo ' '.implode(' ', apply_filters('body_classes', array()));
	?>">
		<?php do_action('body_after'); ?>
		<div id="main">
			<div id="main-inner">
			<?php 
			// home backgrounds
			/*if (is_home()){ ?>
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
			<?php } 
			*/
			
			$header = !empty($header) ? $header : 'page';
			print_template('parts/header_'.$header);

			?>
			<div class="main-body">
				<?php
					$isError = !empty($obj) && is_array($obj) && empty($obj['success']);
					$actions = array();
	
					if (in_array($smap['page'], array('bulletin', 'bulletins', 'ambassadors', 'providers')) || $isError){ ?>
						<div class="body-intro-help<?php if ($isError) echo ' body-intro-error'; ?>"><i class="fa fa-<?= ($isError ? 'warning' : 'info-circle') ?>"></i> <?php
						

						if ($isError)
							echo 'ERROR'.(!empty($obj['error']) ? ': '.esc_string($obj['error']) : '');
							
						else if ($smap['page'] == 'ambassadors')
							echo 'Ambassadors are social collectives that host all bulletins of one country, check their integrity, and maintain translations. More information about StateMapper\'s commissions <a href="'.anonymize('https://github.com/'.SMAP_GITHUB_REPOSITORY.'#contribute').'" target="_blank">here</a>.';
								
						else if ($smap['page'] == 'providers')
							echo 'Below are shown all the currently available '.(!empty($smap['filters']['loc'])
								? get_country_schema($smap['filters']['loc'])->adjective.' bulletins'
								: 'bulletins').'.';

						else if (isset($smap['call'])){
							switch ($smap['call']){
								case 'schema':
									echo 'Schemas are definition files for each bulletin, institution, country and continents. It holds the fetching, parsing and extracting protocoles as well as languages and legal definitions.';
									break;
								case 'fetch':
									if (!empty($smap['query']['id']))
										echo 'Below is the '.get_format_label($smap['query'], 'document').' <strong>'.$smap['query']['id'].'</strong> from bulletin of <strong><a href="'.url(array(
												'date' => $smap['query']['date'],
												'schema' => $smap['query']['schema']
											), 'fetch').'">'.date_i18n('M j, Y', strtotime($smap['query']['date'])).'</a></strong>.';
									else {
										echo 'Below is the bulletin\'s '.get_format_label($smap['query'], 'document').' from <strong>'.date_i18n('M j, Y', strtotime($smap['query']['date'])).'</strong>.';
										
										$bs = array();
										foreach (query('SELECT DISTINCT external_id, status, fetched, parsed, done FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NOT NULL', array($smap['query']['schema'], $smap['query']['date'])) as $doc){
											
											$docname = preg_replace('#'.preg_quote($smap['schemaObj']->shortName, '#').'#', '', $doc['external_id']);
											$docname = preg_replace('#-+#', '-', $docname);
											$docname = preg_replace('#^-?(.*)-?$#', '$1', $docname);
											
											$bs[] = '<li><a href="'.url(array(
												'date' => $smap['query']['date'],
												'id' => $doc['external_id'],
												'schema' => $smap['query']['schema']
											), 'fetch').'" title="'.$doc['status'].'">'.(in_array($doc['status'], array('fetched')) ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>').' '.$docname.'</a></li>';
										}
										
										if ($bs)
											echo '<div class="top-help-related-documents">Related documents: <div class="top-help-related-documents-links"><ul>'.implode('', $bs).'</ul></div></div>';
									}
									
									if (in_array($smap['call'], array('fetch')) && schema_has_feature($smap['query']['schema'], 'fetch')){ 
										
										$actions[] = '<a href="'.url($smap['query'], 'fetch/raw').'" title="'.esc_attr($modes['fullscreen']['buttonTip']).'"><i class="fa fa-'.$modes['fullscreen']['icon'].'"></i><span>'.(!empty($modes['fullscreen']['shortTitle']) ? $modes['fullscreen']['shortTitle'] : $modes['fullscreen']['title']).'</span></a>';
										
										$actions[] = '<a href="'.url($smap['query'], 'download').'" title="'.esc_attr($modes['download']['buttonTip']).'"><i class="fa fa-'.$modes['download']['icon'].'"></i><span>'.(!empty($modes['download']['shortTitle']) ? $modes['download']['shortTitle'] : $modes['download']['title']).'</span></a>';
										
										$actions[] = '<a href="'.url(array('format' => get_format_by_query($smap['query'])) + $smap['query'], 'redirect').'" title="'.esc_attr($modes['redirect']['buttonTip']).'"><i class="fa fa-'.$modes['redirect']['icon'].'"></i><span>'.(!empty($modes['redirect']['shortTitle']) ? $modes['redirect']['shortTitle'] : $modes['redirect']['title']).'</span></a>';
											
									} 
									
									break;
								case 'lint':
									echo 'Linting is the action of converting binay files (like PDF) into textual content, so that parsing can be done. This step is currently only useful for PDF files.';
									
									if (in_array($smap['call'], array('fetch', 'lint')) && schema_has_feature($smap['query']['schema'], 'fetch') && show_mode('rewind')){ 

										$actions[] = '<a href="'.url($smap['query'], 'lint/raw').'" title="'.esc_attr($modes['fullscreen']['buttonTip']).'"><i class="fa fa-'.$modes['fullscreen']['icon'].'"></i><span>'.(!empty($modes['fullscreen']['shortTitle']) ? $modes['fullscreen']['shortTitle'] : $modes['fullscreen']['title']).'</span></a>';
										
										$actions[] = '<a href="'.url($smap['query'], 'download/txt').'" title="'.esc_attr($modes['download']['buttonTip']).'"><i class="fa fa-'.$modes['download']['icon'].'"></i><span>'.(!empty($modes['download']['shortTitle']) ? $modes['download']['shortTitle'] : $modes['download']['title']).'</span></a>';
										
										$actions[] = '<a href="'.url(array('format' => get_format_by_query($smap['query'])) + $smap['query'], 'redirect').'" title="'.esc_attr($modes['redirect']['buttonTip']).'"><i class="fa fa-'.$modes['redirect']['icon'].'"></i><span>'.(!empty($modes['redirect']['shortTitle']) ? $modes['redirect']['shortTitle'] : $modes['redirect']['title']).'</span></a>';
											
									}
									break;
									
								case 'parse':
									echo 'Parsing is the action of understanding the bulletin by isolating and refactoring each peace of information in it. Parsing also allows to fetch-follow (fetch documents found in the parsed object).';
									
									if (!empty($_GET['precept']))
										echo '<div class="top-alert-filter">Only showing parts about precept "'.htmlentities(get_filter()).'". <a href="'.remove_url_arg('precept').'">remove filter</a></div>';
									else if (!empty($_GET['filter']))
										echo '<div class="top-alert-filter">Only showing parts with titles containing "'.htmlentities(get_filter()).'". <a href="'.remove_url_arg('filter').'">remove filter</a></div>';
										
									break;
								case 'extract':
									echo 'Extraction is where all the useful information from the parsed object is normalized into small entities the software knows how to handle. This allows to query the information in a fast and logical manner.';
									break;
								case 'rewind':
									echo 'Rewinding is the step where you get to fetch all documents for as long as you can.';
									break;
								case 'soldiers':
									echo 'The Soldiers are the developers that implement and maintain the bulletins\' schemas. More information about StateMapper\'s commissions <a href="'.anonymize('https://github.com/'.SMAP_GITHUB_REPOSITORY.'#contribute').'" target="_blank">here</a>.';
									break;
							}
						} 

						if (IS_API)
							$actions[] = '<a href="'.get_api_url().'" title="'.esc_attr($modes['raw']['buttonTip']).'"><i class="fa fa-'.$modes['raw']['icon'].'"></i><span>'.(!empty($modes['raw']['shortTitle']) ? $modes['raw']['shortTitle'] : $modes['raw']['title']).'</span></a>';
										
						
						if ($actions)
							echo '<div class="header-intro-actions">'.implode('', $actions).'</div>';
						
						?></div>
						<?php 
					} 

				?>
				<div class="main-body-inner">
					<div id="wrap">
						<?php
						
