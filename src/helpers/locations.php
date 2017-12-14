<?php
	
if (!defined('BASE_PATH'))
	die();


function getLocationById($id){
	return get('SELECT note FROM statuses WHERE related_id = %s AND type = "location" AND action = "new" ORDER BY id DESC LIMIT 1', $id);
}


function kaosGetLocationLabel($loc){
	
	if (is_numeric($loc['state']))
		$loc['state'] = getStateName($loc['state']);
	if (is_numeric($loc['county']))
		$loc['county'] = getCountyName($loc['county']);
	if ($loc['city'] && is_numeric($loc['city']))
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
	
