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

add_filter('location_lint', 'herecom_location_lint');
function herecom_location_lint($locationObj, $location, $country){
	if ($location){
		$locationObj = herecom_convert_location($location, $country, true);
		if ($locationObj)
			insert_location($locationObj);
	}
	return $locationObj;
}


function herecom_convert_location($locationStr, $country, $tryId = false){
	if (!defined('HERE_COM_APP_ID') || !HERE_COM_APP_ID
		|| !defined('HERE_COM_APP_SECRET') || !HERE_COM_APP_SECRET)
		return null;
		
	if (!is_object($country))
		$country = get_country_schema($country);
	
	$force = false;
	if (!is_admin() || empty($_GET['geoloc'])){
	
		if ($tryId && is_numeric($locationStr)){
			if ($loc = get_row('SELECT * FROM locations WHERE country = %s AND id = %s', array($country->id, $locationStr)))
				return $loc;
			return null;
		}
		
		if ($loc = get_row('SELECT * FROM locations WHERE country = %s AND original = %s', array($country->id, $locationStr)))
			return $loc;

	// debug (force geolocation again) while admin with ?geoloc=1
	} else {
		$force = true;
		if ($tryId && is_numeric($locationStr)){
			$locationStr = get_var('SELECT original FROM locations WHERE country = %s AND id = %s', array($country->id, $locationStr));
		}
	}
	
	if (is_numeric($locationStr))
		return null;
		
	$coordinates = @$country->vocabulary->stateLevels->country->coordinates;
	if (empty($coordinates))
		die_error('here.com addon: geolocation not possible, no coordinates specified in schema '.$country->ID.' (at ->vocabulary->stateLevels->country->coordinates)');
		
	$url = 'https://geocoder.cit.api.here.com/6.2/geocode.json';

	//echo "geocoding $locationStr<br>";

	$args = array(
		'app_id' => HERE_COM_APP_ID,
		'app_code' => HERE_COM_APP_SECRET,
		'searchtext' => urlencode($locationStr.', '.$country->name),
		//'Geolocation' => urlencode('geo:'.implode(',', $coordinates)),
	);
	$opts = array(
		'allowTor' => false, 
		'countAsFetch' => false,
		'noUserAgent' => true,
		'type' => 'json',
	);
	$resp = fetch($url, $args, true, false, $opts);
	
	//print_json($args);
	//print_json($resp);
	
	if (is_admin() && !empty($_GET['show_geoloc']))
		echo 'geolocating '.$locationStr.'<br>';

	$json = @json_decode($resp);
	if (!$json || empty($json->Response->View) || empty($json->Response->View[0]->Result)){
		if (is_admin() && !empty($_GET['debug'])){
			echo 'error geocoding: ';
			debug($resp);
		}
		return false;
	}
	
	$json = $json->Response->View[0]->Result[0];
	$relevance = $json->Relevance;
	$a = $json->Location->Address;
	
	//print_json($a);
	
	$country = $country->id;
	if (!empty($a->Country)){
		$country = $a->Country;
		
		// TODO: put other ISO country codes in a new attribute in country schemas.
		$conv = array(
			'MEX' => 'MX',
			'ESP' => 'ES',
		);
		if (isset($conv[$country]))
			$country = $conv[$country];
		
		// TODO: check we know this country? (exists as a country schema)
	}
	
	// recheck
	if (!$force && ($loc = get_row('SELECT * FROM locations WHERE country = %s AND original = %s', array($country, $locationStr))))
		return $loc;
	
	$data = array();
	foreach ($a->AdditionalData as $d)
		$data[$d->key] = $d->value;
	
	//print_json($a);

	return array(
		'label' => $a->Label,
		'housenumber' => !empty($a->HouseNumber) ? $a->HouseNumber : null,
		'postalcode' => !empty($a->PostalCode) ? $a->PostalCode : null,
		'city' => $a->City,
		'county' => !empty($a->County) ? $a->County : null,
		'state' => !empty($a->State) ? $a->State : null,
		'country' => $country,
		//'countryName' => !empty($data['CountryName']) ? $data['CountryName'] : strtoupper($country),

		'original' => $locationStr,
		'relevance' => $relevance * 100,
		'updated' => date('Y-m-d H:i:s'),
	);
}

