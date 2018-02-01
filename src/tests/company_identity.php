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


// Test suite for company names. Please visit: BASE_URL/test/company_identity as admin/logged in user.
		

add_test('Identity between company names', function(){
	$country = 'es';
	
	$answer = normalize_name('PLATANO CAÑERO', $country);
	$inputs = array(
		'¿?¡-+PLATANO---CAÑERO. ',
		'CAÑERO platano',
		'CAÑERO PLATANO PLATANO PLATANO',
		'CANERO PLATANO SL',
		'CANERO de PLATANO SL',
		'CANERO ded PLATANO SL',
		'CANERO PLATANO S.L.',
		'CANERO PLATANO España',
		'CANERO PLATANO & Coe',
		'CANERO PLATANO & a.s..ID',
		'CANERO PLATANO IDE',
		'CANERO PLATANO R&D',
	);
	
	$success = $errors = array();
	foreach ($inputs as $input){
		$n = normalize_name($input, $country);
		$s = beautify_name($input, $country);
		$slug = sanitize_title($input);
		
		if ($answer !== $n)
			$str = '<tr><td>ERROR</td>';
		else
			$str = '<tr><td>SUCCESS</td>';
		
		$str = $str.'<td>'.$input.'</td><td>'.$n.'</td><td>'.$s.'</td><td>'.$slug.'</td></tr>';
		if ($answer !== $n)
			$errors[] = $str;
		else
			$success[] = $str;
	}
	
	echo '<table border="1"><tr><th></th><th>Original</th><th>Normalized</th><th>Beautified</th><th>Slug</th></tr>'.implode('', $errors).implode('', $success).'</table>';
	return count($errors);
});
