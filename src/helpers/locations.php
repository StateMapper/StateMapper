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


function get_location_by_id($id){
	return get_var('SELECT note FROM statuses WHERE related_id = %s AND type = "location" AND action = "new" ORDER BY id DESC LIMIT 1', $id);
}


function get_location_label($loc, $context = 'status'){
	
	if (empty($loc['state']) || is_numeric($loc['state']))
		$loc['state'] = get_state_name($loc['state']);
	if (empty($loc['county']) || is_numeric($loc['county']))
		$loc['county'] = get_county_name($loc['county']);
	if (empty($loc['city']) || is_numeric($loc['city']))
		$loc['city'] = get_city_name($loc['city']);
		
	return '<span class="location-full" title="'.esc_attr('<u>Full address</u>: '.$loc['label'].'<br><br><u>Original address</u>: '.$loc['original']).'">'.$loc['postalcode'].' '.$loc['city'].', '.$loc['state'].($context != 'sheet' ? ' <img data-tippy-placement="right" title="'.esc_attr(get_country_name($loc['country'])).'" src="'.get_flag_url($loc['country']).'" />' : '').'</span>';
}

function get_state_name($id){
	if (!is_numeric($id))
		return $id;
	return get_var('SELECT name FROM location_states WHERE id = %s', $id);
}

function get_county_name($id){
	if (!is_numeric($id))
		return $id;
	return get_var('SELECT name FROM location_counties WHERE id = %s', $id);
}

function get_city_name($id){
	if (!is_numeric($id))
		return $id;
	return get_var('SELECT name FROM location_cities WHERE id = %s', $id);
}

function get_location_name($id, $type){ // type is [cities|counties|states|countries]
	return get_var('SELECT name FROM location_'.$type.' WHERE id = %s', $id);
}

function get_location_slug($id, $type){ // type is [cities|counties|states|countries]
	return get_var('SELECT slug FROM location_'.$type.' WHERE id = %s', $id);
}

function insert_location(&$loc){
	if (empty($loc['id'])){
		
		// save state
		if ($loc['state'] && !is_numeric($loc['state'])){
			if ($id = get_var('SELECT id FROM location_states WHERE country = %s AND name = %s', array($loc['country'], $loc['state'])))
				$loc['state'] = $id;
			else 
				$loc['state'] = insert('location_states', array(
					'name' => $loc['state'], 
					'country' => $loc['country'], 
					'slug' => generate_slug('location_states', 'slug', $loc['state'], 80, array(
						'country' => $loc['country']
					))
				));
		}
		
		// save county
		if ($loc['county'] && !is_numeric($loc['county'])){
			if ($id = get_var('SELECT id FROM location_counties WHERE country = %s AND state_id = %s AND name = %s', array($loc['country'], $loc['state'], $loc['county'])))
				$loc['county'] = $id;
			else 
				$loc['county'] = insert('location_counties', array(
					'name' => $loc['county'], 
					'state_id' => $loc['state'], 
					'country' => $loc['country'], 
					'slug' => generate_slug('location_counties', 'slug', $loc['county'], 80, array(
						'country' => $loc['country']
					))
				));
		}
		
		// save city
		if ($loc['city'] && !is_numeric($loc['city'])){
			if ($id = get_var('SELECT id FROM location_cities WHERE country = %s AND state_id = %s AND county_id = %s AND name = %s', array($loc['country'], $loc['state'], $loc['county'], $loc['city'])))
				$loc['city'] = $id;
			else 
				$loc['city'] = insert('location_cities', array(
					'name' => $loc['city'], 
					'county_id' => $loc['county'], 
					'state_id' => $loc['state'], 
					'country' => $loc['country'],
					'slug' => generate_slug('location_cities', 'slug', $loc['city'], 80, array(
						'country' => $loc['country']
					))
				));
		}
		
		$loc['id'] = insert('locations', $loc);
	}
	return $loc['id'];
}
	

function repair_location_slugs(){
	foreach (array('cities', 'counties', 'states') as $type)
		foreach (query('SELECT id, name, country FROM location_'.$type.' WHERE slug = ""') as $r){
			$slug = generate_slug('location_'.$type, 'slug', $r['name'], 80, array(
				'country' => $r['country']
			));
			update('location_'.$type, array(
				'slug' => $slug
			), array(
				'id' => $r['id']
			));
			echo 'UPDATED '.$type.' #'.$r['id'].' ('.htmlentities($r['name']).') with slug '.$slug.'<br>';
		}
	die("DONE");
}

function get_location_id_by_slug($slug, $type, $country){ // type is [cities|counties|states|countries]
	return get_var('SELECT id FROM location_'.$type.' WHERE country = %s AND slug = %s', array(strtoupper($country), $slug));
}

function get_locations_label($loc, $short = false){
	if (!is_array($loc))
		$loc = explode(' ', $loc);
		
	$str = array();
	foreach ($loc as $l){
		$l = explode('/', $l);
		
		if ($c = get_country_schema(array_shift($l))){
			if (!$l)
				$str[] = $c->name; // country
				
			else if ($s = get_state_name(get_location_id_by_slug(array_shift($l), 'states', $c->id))){
				
				if (!$l)
					$str[] = $short ? $s.' ('.$c->id.')' : $s.', '.$c->name;
				
				else if ($s2 = get_county_name(get_location_id_by_slug(array_shift($l), 'counties', $c->id))){
				
					if (!$l)
						$str[] = $short ? $s2.' ('.$c->id.')' : $s2.', '.$s.' ('.$c->name.')';
				
					else if ($s3 = get_city_name(get_location_id_by_slug(array_shift($l), 'cities', $c->id))){
				
						if (!$l)
							$str[] = $short ? $s3.' ('.$s2.', '.$c->id.')' : $s3.', '.$s2.' ('.$s.', '.$c->name.')';
					}
				}
			}
				
				
		}
	}
	return $str;
}

function get_location_filter_array($loc){
	
	if (!empty($loc['city'])){
		if (!($id = get_location_id_by_slug($loc['city'], 'cities', $loc['country'])))
			return false;
		return array('city' => $id);
		
	} else if (!empty($loc['county'])){
		if (!($id = get_location_id_by_slug($loc['county'], 'counties', $loc['country'])))
			return false;
		return array('county' => $id);
		
	} else if (!empty($loc['state'])){
		if (!($id = get_location_id_by_slug($loc['state'], 'states', $loc['country'])))
			return false;
		return array('state' => $id);
	} 
	
	return array('country' => $loc['country']);
}

function get_country_name($country){
	$schema = get_country_schema($country);
	return $schema ? $schema->name : $country;
}

function query_locations($args, &$left = null){
	return array();
	// @todo: add locations results
	foreach (array('countries', 'states', 'counties', 'cities') as $table)
		get_var('SELECT id FROM location_'.$type.' WHERE country = %s AND slug = %s', array(strtoupper($country), $slug));
}
