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


function kaosConvertCurrency($value, $currency, $destCurrency = 'USD'){
	$ret = array(
		'value' => $value,
		'unit' => strtoupper($currency)
	);
	
	if (!empty($currency)){

		do {
			
			$config = null;
			foreach (kaosGetCurrencies() as $cCurrency => $cConfig)
				if (in_array($ret['unit'], $cConfig['labels'])){
					$config = $cConfig;
					if (!isset($ret['originalUnit'])){
						$ret['originalUnit'] = $cCurrency;
						$ret['originalValue'] = $ret['value'];
					}
					break;
				}
				
			if (!$config || empty($config['rate']) || $ret['unit'] == $destCurrency)
				return $ret;
			
			// convert from one currency to another + force grabbing real currencies (not anything after the amount) -> gen a list of currencies
			
			foreach ($config['rate'] as $cdestCurrency => $destRate){
				if (!isset($config['rate'][$destCurrency]) || $destCurrency == $cdestCurrency){
					$ret['value'] = $destRate * $ret['value'];
					$ret['unit'] = $cdestCurrency;
					break;
				}
			}
		
		} while (true);
		
		return $ret;
	}
	return $value;
}

function kaosGetCurrencies(){
	return array(

		'EUR' => array(
			'singular' => 'Euro',
			'plural' => 'Euros',
			'labels' => array('EUROS', 'EURO', 'EUR', 'E', '€'),
			'rate' => array('USD' => 1.16)
		),
		
		'ESP' => array(
			'singular' => 'Peseta',
			'plural' => 'Pesetas',
			'labels' => array('PESETAS', 'PESETA', 'PTS', 'PTAS', 'PTA', '€'),
			'rate' => array('EUR' => 166.386)
		),
		
		'USD' => array(
			'singular' => 'US Dollar',
			'plural' => 'US Dollars',
			'labels' => array('US DOLLARS', 'US DOLLAR', 'DOLLARS', 'DOLLAR', 'USD', 'US$', '$'),
			'rate' => array(
				'EUR' => 1/1.16,
			),
		),

	);
}



	 
function kaosConvertAmount($amount, $schema){
/*
	if ($amount['type'] == 'currency'){
		print_r($amount);
	}
*/
	if (!isset($amount['amount']) || !isset($amount['unit'])){
		if (KAOS_IS_CLI)
			kaosPrintLog('bad formed amount given: '.print_r($amount, true), array('color' => 'red')); // TODO: should log this somewhere visible
		return null;
	}
		
	$v = $amount['amount'];
		
	$ret = array(
		'originalValue' => $v * 100,
		'originalUnit' 	=> $amount['unit'],
		'value' 		=> $v * 100,
		'unit' 			=> $amount['unit'],
	);
	switch ($amount['type']){
		case 'currency':
			$ret = kaosConvertCurrency($v * 100, $amount['unit']) + $ret;
			break;
	}
/*
	if ($amount['type'] == 'currency'){
		print_r($ret);
		die();
	}
*/
	return $ret;
}


// money amount pattern
function kaosGetPatternNumber($currency = true){
	$labels = array();
	foreach (kaosGetCurrencies() as $c)
		$labels = array_merge($labels, $c['labels']);
	return '((?:[0-9\s]{1,3})(?:[,\.\s]?[0-9\s]{3})*(?:[\.,][0-9\s]+)?)'.($currency ? '(\s*(?:'.implode('|', $labels).'))?' : '');
}
