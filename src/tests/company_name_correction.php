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
		

add_test('Company name correction', function(){
	if (!function_exists('searx_fix_name')){
		echo 'addon searx not loaded';
		return 1;
	}
	$lang = 'es_ES';
	$errors = 0;
	$trs = array();
	
	$names = array(
		'voddafon e',
		'voddafon ee',
		'voddafon es',
		'sam ur',
		'gene r al m o t o r s',
		'ib er d ro l a',
		'tel e f o n ic a',
		'tel e f o n ic a S.L.',
		'tel e f o n ic aa',
		'coca...co ll a',
		'coca...co lll a',
		'coca...co llll a',
		'cocacoya',
	);
	if (1)
		$names = get_col('SELECT name FROM entities WHERE type = "company" LIMIT 100');
	
	foreach ($names as $original_name){
		$name = searx_fix_name($original_name, $lang);
		
		if ($name == $original_name)
			$name = searx_fix_name($original_name, $lang, 5, 3);
		
		if ($name == $original_name)
			$errors++;
		$trs[] = '<tr><td><strong>'.$original_name.'</strong></td><td>'.$name.'</td></tr>';
	}
	echo '<table border="1">'.implode('', $trs).'</table>';
	return $errors;
});
