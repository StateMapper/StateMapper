<?php

if (!defined('BASE_PATH'))
	die();



//add_action('insert_location', 'kaosHereComConvertLocation');

function kaosHereComConvertLocation($locationStr, $country, $tryId = false){
	if (!is_object($country))
		$country = kaosGetCountrySchema($country);
	
	$force = false;
	if (!isAdmin() || empty($_GET['geoloc'])){
	
		if ($tryId && is_numeric($locationStr)){
			if ($loc = getRow('SELECT * FROM locations WHERE country = %s AND id = %s', array($country->id, $locationStr)))
				return $loc;
			return null;
		}
		
		if ($loc = getRow('SELECT * FROM locations WHERE country = %s AND original = %s', array($country->id, $locationStr)))
			return $loc;

	// debug..
	} else {
		$force = true;
		if ($tryId && is_numeric($locationStr)){
			$locationStr = get('SELECT original FROM locations WHERE country = %s AND id = %s', array($country->id, $locationStr));
		}
	}
	
	if (is_numeric($locationStr))
		return null;
		
	$coordinates = @$country->vocabulary->stateLevels->country->coordinates;
	if (empty($coordinates))
		die('no coordinates specified for '.strtoupper($country).' (at ->vocabulary->stateLevels->country->coordinates)');
		
	$url = 'https://geocoder.cit.api.here.com/6.2/geocode.json';
	$args = array(
		'app_id' => HERE_COM_APP_ID,
		'app_code' => HERE_COM_APP_SECRET,
	);
	$opts = array(
		'allowTor' => false, 
		'countAsFetch' => false,
		'noUserAgent' => true,
		'accept' => 'application/json',
	);

	$resp = kaosFetch($url, $args + array(
		'searchtext' => urlencode($locationStr),
		//'Geolocation' => urlencode('geo:'.implode(',', $coordinates)),
	), true, false, $opts);
	
	$resp = @json_decode($resp);
	if (!$resp || empty($resp->Response->View) || empty($resp->Response->View[0]->Result))
		return false;
	
	$resp = $resp->Response->View[0]->Result[0];
	$relevance = $resp->Relevance;
	$a = $resp->Location->Address;
	
	//kaosJSON($a);
	
	$country = $country->id;
	if (!empty($a->Country)){
		$country = $a->Country;
		
		$conv = array(
			'MEX' => 'MX',
			'ESP' => 'ES',
		);
		if (isset($conv[$country]))
			$country = $conv[$country];
	}
	
	// recheck
	if (!$force && ($loc = getRow('SELECT * FROM locations WHERE country = %s AND original = %s', array($country, $locationStr))))
		return $loc;
	
	$data = array();
	foreach ($a->AdditionalData as $d)
		$data[$d->key] = $d->value;
	
	//kaosJSON($a);

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

function kaosSaveLocation($loc){
	return !empty($loc['id']) ? $loc['id'] : insert('locations', $loc);
}
	
function kaosGetLocationLabel($loc){
	return '<span title="'.esc_attr('<u>Full address</u>: '.$loc['label'].'<br><br><u>Original</u>: '.$loc['original']).'">'.$loc['postalcode'].' '.$loc['city'].', '.$loc['state'].' <img src="'.kaosGetFlagUrl($loc['country']).'" /></span>';
}
