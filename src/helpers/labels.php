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




function kaosGetLabels($type){
	switch ($type){
		case 'statuses':
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
	return array();
}


function kaosGetStatusLabels(){
	static $cache = null;
	if ($cache !== null)
		return $cache;
	if (!($schema = file_get_contents(SCHEMAS_PATH.'/status.json')))
		return false;

	if (!($cache = lintSchema($schema, $linted)))
		kaosDie('bad status.json');
	
	return $cache;
}
