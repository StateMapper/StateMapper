<?php
	
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
	return array(
		'capital' => array(
			'new' => array(
				'icon' => 'plus',
				'own' => 'Founded with a capital of [amount]',
				'issuing' => '[related] was founded with a capital of [amount]',
				'stats' => '[count] Companies funded with a total capital of [amount]',
			),
			'increase' => array(
				'icon' => 'money',
				'own' => 'Capital increase by [amount]',
				'issuing' => '[related] had a capital increase of [amount]',
				'stats' => '[count] Capital increases for a total [amount]',
			),
		),
		'fund' => array(
			'new' => array(
				'icon' => 'credit-card',
				'own' => 'Funded with [amount] through [issuing]',
				'issuing' => array(
					'icon' => 'credit-card-alt', 
					'label' => '[related] funded with [amount]',
				),
				'stats' => '[count] Fundings for a total [amount]',
			),
		),
		'owner' => array(
			'update' => array(
				'icon' => 'user-circle-o',
				'own' => array(
					'icon' => 'user-o',
					'label' => 'Now owning [related]',
				),
				'issuing' => '[target] was made owner of [related]',
				'related' => '[target] was made owner',
				'stats' => '[count] Now owning',
			),
		),
		'administrator' => array(
			'start' => array(
				'icon' => 'user-plus',
				'own' => 'Now administrating [related]',
				'issuing' => '[target] was made administrator of [related]',
				'related' => '[target] was made administrator',
				'stats' => '[count] Now administrating',
			),
			'keep' => array(
				'icon' => 'user',
				'own' => 'Reelected administrator of [related]',
				'issuing' => '[target] was reelected administrator of [related]',
				'related' => '[target] was reelected administrator',
				'stats' => '[count] Administrators reelected',
			),
			'end' => array(
				'icon' => 'user-times',
				'own' => array(
					'icon' => 'user-o',
					'label' => 'No longer administrator of [related]',
				),
				'issuing' => '[target] is no longer administrator of [related]',
				'related' => '[target] is no longer administrator',
				'stats' => '[count] Administrators ceased',
			),
		),
		'president' => array(
			'new' => array(
				'icon' => 'user-plus',
				'own' => 'Now president of [related]',
				'issuing' => '[target] was made president of [related]',
				'related' => '[target] was made president',
				'stats' => '[count] Now president of',
			),
		),
		'counselor' => array(
			'new' => array(
				'icon' => 'user-plus',
				'own' => 'Now counselor of [related]',
				'issuing' => '[target] was made counselor of [related]',
				'related' => '[target] was made counselor',
				'stats' => '[count] New counselors',
			),
		),
		'object' => array(
			'new' => array(
				'icon' => 'file-o',
				'note' => 'Object',
				'own' => 'Declared its object as [note]',
				'issuing' => '[related] declared its object as [note]',
				'stats' => '[count] Object declarations',
			),
		),
		'location' => array(
			'new' => array(
				'icon' => 'map-marker',
				'note' => 'Location',
				'own' => 'Declared its location as [note]',
				'issuing' => '[related] declared its location as [note]',
				'stats' => '[count] Location declarations',
			),
		),
		'name' => array(
			'update' => array(
				'icon' => 'exchange',
				'name' => 'Name changes',
				'related' => 'Was renamed to [target]',
				'own' => 'Is the new name of [related]',
				'issuing' => '[related] was renamed to [target]',
				'stats' => '[count] Name changes',
			),
			'end' => array(
				'icon' => 'times',
				'name' => 'Company dissolutions',
				'own' => 'Company was dissolved',
				'issuing' => '[related] was dissolved',
				'stats' => '[count] Company dissolutions',
			),
		),
		'absorb' => array(
			'new' => array(
				'icon' => 'shopping-cart',
				'related' => 'Absorbed [target]',
				'own' => 'Absorbed by [related]',
				'issuing' => '[related] absorbed [target]',
				'stats' => '[count] Company absorptions',
			),
		),
	);
}
