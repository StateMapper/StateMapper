<?php
	
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
	if (!isset($amount['amount']) || !isset($amount['unit']))
		die('bad formed amount given: '.print_r($amount, true));
		
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
