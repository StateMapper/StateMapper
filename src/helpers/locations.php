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


function getLocationById($id){
	return get('SELECT note FROM statuses WHERE related_id = %s AND type = "location" AND action = "new" ORDER BY id DESC LIMIT 1', $id);
}


function kaosGetLocationLabel($loc){
	
	if (empty($loc['state']) || is_numeric($loc['state']))
		$loc['state'] = getStateName($loc['state']);
	if (empty($loc['county']) || is_numeric($loc['county']))
		$loc['county'] = getCountyName($loc['county']);
	if (empty($loc['city']) || is_numeric($loc['city']))
		$loc['city'] = getCityName($loc['city']);
		
	return '<span class="location-full" title="'.esc_attr('<u>Full address</u>: '.$loc['label'].'<br><br><u>Original</u>: '.$loc['original']).'">'.$loc['postalcode'].' '.$loc['city'].', '.$loc['state'].' <img src="'.kaosGetFlagUrl($loc['country']).'" /></span>';
}

function getStateName($id){
	if (!is_numeric($id))
		return $id;
	return get('SELECT name FROM location_states WHERE id = %s', $id);
}

function getCountyName($id){
	if (!is_numeric($id))
		return $id;
	return get('SELECT name FROM location_counties WHERE id = %s', $id);
}

function getCityName($id){
	if (!is_numeric($id))
		return $id;
	return get('SELECT name FROM location_cities WHERE id = %s', $id);
}
	
