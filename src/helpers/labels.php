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

function get_bulletin_statuses(){
	return array(
		'progress' => array(
			'label' => 'Progress',
			'context' => 'fetch',
		),
		'not_published' => array(
			'label' => 'Not expected',
			'context' => 'fetch',
		),
		'not_fetched' => array(
			'label' => 'To be fetched',
			'context' => array('fetch', 'extract'),
		),
		'waiting' => array(
			'label' => 'Fetching',
			'context' => array('fetch', 'extract'),
		),
		'fetched' => array(
			'label' => 'Fetched',
			'context' => array('fetch', 'extract'),
		),
		'error' => array(
			'label' => 'Error fetching',
			'context' => array('fetch', 'extract'),
		),
		'document' => array(
			'label' => 'Documents',
			'context' => 'fetch',
			'noBackground' => true,
			'spaceBelow' => true,
			'icon' => 'file-text-o',
		),
		'extracting' => array(
			'label' => 'Extracting',
			'context' => array('extract'),
		),
		'extracted' => array(
			'label' => 'Extracted',
			'context' => array('extract'),
			'force' => true,
		),
		'precepts' => array(
			'label' => 'Precepts',
			'context' => array('extract'),
			'force' => true,
			'noBackground' => true,
			'icon' => 'font',
		),
		'statuses' => array(
			'label' => 'Status',
			'context' => array('extract'),
			'force' => true,
			'noBackground' => true,
			'icon' => 'tasks',
		),
	);
}

function get_modes(){
	$dev = is_admin();
	return array(
		'browse' => array(
			'icon' => 'search',
			'title' => _('Browse'),
			'headerTip' => _('Browse extracted public data'),
		),
		'schema' => array(
			'show' => $dev,
			'icon' => 'book',
			'title' => _('Schema'),
			'headerTitle' => _('Schema'),
			'headerTip' => _('See the definition schema'),
		),
		'fetch' => array(
			'icon' => $dev ? 'cloud-download' : 'book',
			'title' => $dev ? _('Fetch') : _('Browse'),
			'headerTitle' => _('Bulletin browser'),
			'headerTip' => _('Browse the bulletins'),
		),
		'fullscreen' => array(
			'icon' => 'arrows-alt',
			'title' => _('Fullscreen'),
			'buttonTip' => _('Only show the bulletin file'),
		),
		'download' => array(
			'icon' => 'download',
			'title' => _('Download'),
			'buttonTip' => _('Download this bulletin'),
		),
		'lint' => array(
			'icon' => 'file-text-o',
			'title' => _('Lint'),
			'headerTitle' => _('Linted bulletin'),
			'headerTip' => _('Browse the bulletins in linted version'),
		),
		'redirect' => array(
			'icon' => 'external-link-square',
			'title' => _('Redirect'),
			'buttonTip' => _('Go to the original bulletin\'s URL'),
		),
		'parse' => array(
			'show' => $dev,
			'icon' => 'pagelines',
			'title' => _('Parse'),
			'headerTitle' => _('Parsed bulletin'),
			'headerTip' => _('See the parsed objects'),
		),
		'extract' => array(
			'show' => $dev,
			'icon' => 'magic',
			'title' => _('Extract'),
			'headerTitle' => _('Extracted status'),
			'headerTip' => _('See extracted status'),
		),
		'rewind' => array(
			'icon' => $dev ? 'backward' : 'map-o',
			'title' => $dev ? _('Rewind') : _('Map'),
			'headerTitle' => $dev ? _('Rewind map') : _('Bulletins map'),
			'headerTip' => _('Browse all bulletins through a map'),
		),
		'soldiers' => array(
			'show' => $dev,
			'icon' => 'fire',
			'title' => _('Soldiers'),
			'headerTitle' => _('Schema Soldiers'),
			'headerTip' => _('See who\'s maintaining this schema'),
		),
		'ambassadors' => array(
			'show' => $dev,
			'icon' => 'globe',
			'title' => _('Ambassadors'),
			'shortTitle' => _('Ambass.'),
			'headerTitle' => _('Country Ambassadors'),
			'headerTip' => _('See who\'s in charge of this area'),
		),
		'providers' => array(
			'icon' => 'podcast',
			'title' => __('Providers'),
			'shortTitle' => __('Providers', 'short title'),
			'headerTitle' => _('Public data providers'),
			'headerTip' => _('See all public data providers we use'),
		),
	);
}

function get_entity_types(){
	return array(
		'institution' => array(
			'slug' => 'institutions',
			'plural' => _('institutions'),
			'singular' => _('institution'),
			'icon' => 'university',
		),
		'company' => array(
			'slug' => 'companies',
			'plural' => _('companies'),
			'singular' => _('company'),
			'icon' => 'industry',
		),
		'person' => array(
			'slug' => 'people',
			'plural' => _('people'),
			'singular' => _('person'),
			'icon' => 'user-circle',
		),
	);
}

function get_status_labels(){
	static $cache = null;
	if ($cache !== null)
		return $cache;

	if (!($schema = file_get_contents(SCHEMAS_PATH.'/status.json')))
		die_error('could not read status.json');

	if (!($ccache = parse_schema($schema, $linted)))
		die_error('bad status.json');
	
	$cache = $ccache;	
	return $cache;
}
