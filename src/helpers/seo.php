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
	
	
add_action('head', 'seo_head');
function seo_head(){
	
	// page title
	$title = get_page_title(true);
	$desc = get_page_description(true);
	$canonical = get_canonical_url();
	
	?>
	
	<title><?= $title ?></title>
	<meta name="description" content="<?= esc_attr($desc) ?>">
	
	<?php
	if (!IS_INSTALL){
		?>
		<link rel="canonical" href="<?= $canonical ?>" />
		<?php
	}

	if ((defined('IS_ERROR') && IS_ERROR) 
		|| (!empty($smap['call']) && in_array($smap['call'], array('parse', 'extract')))
		|| IS_INSTALL
		|| IS_API
	){
		?>
		<meta name="robots" content="noindex" />
		<?php

	} else {
		?>
		<meta property="og:type" content="website">
		<meta property="og:title" content="<?= esc_attr($title) ?>">
		<meta property="og:description" content="<?= esc_attr($desc) ?>">
		<meta property="og:url" content="<?= esc_attr($canonical) ?>">
		<meta property="og:site_name" content="$tateMapper">

		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:description" content="<?= esc_attr($desc) ?>">
		<meta name="twitter:title" content="<?= esc_attr($title) ?>">
		<?php 
	}
}

function set_page_title($title){
	global $smap;
	$smap['page_title'] = $title;
}

function get_page_title($seoTitle = false){
	global $smap;
	$title = false;
	
	if (isset($smap['page_title']) && (!empty($smap['page_title']) || !$seoTitle))
		$title = $smap['page_title'];
	
	else if (IS_INSTALL)
		$title = 'Quick installation';
		
	else if ($smap['page'] == 'lists')
		$title = 'My lists';
		
	else if ($smap['page'] == 'api')
		$title = 'API Reference';
		
	else if (!empty($smap['entity']))
		$title = get_entity_title($smap['entity'], false, true);
		
	else if ($smap['page'] == 'ambassadors')
		$title = $seoTitle ? 'Country Ambassadors for '.get_country_schema($smap['filters']['loc'])->name : get_country_schema($smap['filters']['loc'])->name;
	
	else if ($smap['page'] == 'bulletin'){
		
		$schema = !empty($smap['schemaObj']) ? $smap['schemaObj'] : get_schema($smap['filters']['loc']);
		$title = get_schema_title($schema, $smap['query'], $seoTitle && is_admin());
		
		if ($seoTitle){
			
			if ($smap['call'] == 'soldiers'){
				if (!empty($schema->adjective))	
					$title = $schema->adjective.' Schema Soldiers';
				else
					$title = 'Schema Soldiers for '.$title;
				
			} else if ($smap['call'] == 'rewind')
				$title = 'All '.$title;
			
			else if (is_dated_mode($smap['call']))
				$title .= ' from '.date_i18n('l jS \o\f F Y', strtotime($smap['query']['date']));
			
			else if ($smap['call'] == 'schema')
				$title = 'Schema for '.$title;
				
		}

	} else if ($smap['page'] == 'providers'){
		
		if (!empty($smap['filters']['loc']))
			$title = $seoTitle ? get_country_schema($smap['filters']['loc'])->adjective.' public data providers' : get_country_schema($smap['filters']['loc'])->name;
		else
			$title = 'Public data providers';
	
	} else if ($smap['page'] == 'settings')
		$title = 'Settings';
	
	else if (!empty($smap['filters']['q']))
		$title = sprintf(_('Search results for "%s"'), htmlentities($smap['filters']['q']));
		
	else if (!empty($smap['filters']['etype'])){
		$title = _('Search results');

		$etype = explode(' ', $smap['filters']['etype']);
		$types = get_entity_types();
		if (count($etype) == 1){
			$etype = explode('/', $etype[0]);
			if (isset($types[$etype[0]])){
				if (count($etype) > 1)
					$title = 'All '.get_company_label();
				else
					$title = 'All '.$types[$etype[0]]['plural'];
			}
		}
		if (!empty($smap['filters']['loc'])){
			$title .= ' in '.plural(get_locations_label($smap['filters']['loc']), '&');
		}
	
	} else if (!empty($smap['filters']['loc']))
		$title = sprintf(_('All entities in %s'), plural(get_locations_label($smap['filters']['loc']), '&'));
	
	else if (is_home())
		$title = '';
	
	else
		$title = 'Error';
		
	if ($seoTitle){
		if ($title != '')
			$title .= ' - ';
		$title .= '$tateMapper';
		if (is_api() && $smap['page'] != 'api')
			$title .= ' API';
		if (is_home() || $smap['page'] == 'api')
			$title .= ' - '.get_slogan(true);
	}

	return $title;
}

function get_slogan($translate = false){
	if ($translate)
		return __('Worldwide, collaborative, public data reviewing and monitoring tool');
	else
		return 'Worldwide, collaborative, public data reviewing and monitoring tool';
}

function get_page_description(){
	return get_page_title(false).' in $tateMapper, a worldwide, collaborative, public data reviewing and monitoring tool.';
}


add_action('html_attributes', 'seo_html_attributes');
function seo_html_attributes(){
	echo ' prefix="og: http://ogp.me/ns#"';
}
