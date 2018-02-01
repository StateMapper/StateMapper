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


add_filter('entity_summary', 'entity_summary_wikipedia_suggs', 0, 3);
function entity_summary_wikipedia_suggs($details, $entity, $context){
	$live = get_live('wikipedia_suggs', $entity['id'], array(
		'context' => $context
	));
	if (!$live) // no block if live returns false or empty string
		return $details;
	
	$details['wikipedia'] = array(
		'label' => 'Wikipedia',
		'html' => $live
	);
	return $details;
}

function live_wikipedia_suggs($entity_id, $opts){
	if (!($entity = get_entity_by_id($entity_id)))
		return false;
		
	$can_fetch = can_fetch_by_context($opts['context']);
	$suggs = get_wikipedia_suggs($entity, $img, $can_fetch);
	if ($suggs === false)
		return '...';
	
	if ($suggs){
		$str = '<span class="inline-links">';
		
		$i = 0;
		foreach ($suggs as $sugg){
			$url = $sugg['url'];
			
			$str .= '<a href="'.anonymize($url).'" data-tippy-placement="right" target="_blank" title="'.esc_attr(__('Visit the Wikipedia page').': '.$url).'">';
			if (!$i){
				if ($img) 
					$str .= implode('', $img).' ';
				else 
					$str .= '<i class="fa fa-wikipedia-w icon"></i> ';
			}
			$str .= strip_tags($sugg['title']).'</a>';
			$i++;
		}

		$str .= '</span>';
		return array('success' => true, 'html' => $str);
	}
	return false;
}

function get_wikipedia_suggs($entity, &$img, $allow_fetch = true, $amount = 1){
	
	if ($cache = get_cache('entity '.$entity['id'].' wikipedia')){
		$img = $cache['img'];
		return $cache['suggs'];
	}
	if (!$allow_fetch)
		return false;
	
	$opts = array(
		'allowTor' => false, 
		'countAsFetch' => false,
		'noUserAgent' => true,
		'cache' => '1 day',
	);
		
	$suggs = array();
	$img = array();
	for ($i=0; $i<3; $i++){
		
		$q = get_entity_title($entity, $i > 0);
		
		//if ($i > 0)
		//	$q = preg_replace('#ayuntamiento\s*de\s*#ius', '', $q); // TODO: abstract this to schemas!!
			
		if ($i > 1 && strtolower($entity['country']) == 'en')
			break;
			
		$ccountry = $i > 1 ? 'en' : strtolower($entity['country']);
		$api_url = 'https://'.$ccountry.'.wikipedia.org/w/api.php';
		
		$search_results = fetch_json($api_url, array(
			'action' => 'query',
			'list' => 'search',
			'srsearch' => $q,
			'utf8' => 1,
			'srinfo' => 'suggestion', //totalhits|suggestion
			'srlimit' => 10,
			'format' => 'json',
		), true, false, array(
			'type' => 'json',
		) + $opts);
		
		$cresults = array();
		if ($search_results && is_object($search_results) && !empty($search_results->query) && !empty($search_results->query->search))
			foreach ($search_results->query->search as $search_result)
				$cresults[$search_result->pageid] = $search_result;
				
		if ($cresults){
			$wikis = fetch_json($api_url, array(
				'action' => 'query',
				'prop' => 'info',
				'pageids' => implode('|', array_keys($cresults)),
				'inprop' => 'url',
				'format' => 'json',
			), true, false, array(
				'type' => 'json',
			) + $opts);
			
			if ($wikis && is_object($wikis) && !empty($wikis->query) && !empty($wikis->query->pages))
				foreach ($wikis->query->pages as $page)
					$suggs[$page->pageid] = array(
						'url' => $page->canonicalurl.'?curid='.$page->pageid,
					) + ((array) $page) + ((array) $cresults[$page->pageid]);
		}
		
		if (count($suggs) >= $amount)
			break;
	}
	$suggs = array_values($suggs);
	
	$checked = array();
	foreach ($suggs as $sugg){
		// check the entity name is in the title
		if (preg_match('#^https?://#i', $sugg['url'])
			&& preg_match('#\b'.preg_replace('#[^a-z0-9]+#iu', '[^a-z0-9]*', $entity['name']).'\b#iu', $sugg['title'])){

			// try to grab the first image
			/*
			 * @todo: do not take the full <img> tag, just it's URL. Then cache such images locally somehow.
			 * 
			if (!$checked){
				$html = fetch($sugg['url'], array(
					'action' => 'render'
				), true, false, array(
					'type' => 'html',
				) + $opts);

				// @todo: grep all images in infobox instead of just the first one..
				if ($html && preg_match_all('#\binfobox\b.*?(<img[^>]+>)#ius', $html, $matches)){ 
					
					$img = array_merge($img, $matches[1]);
				} 
			}*/

			$checked[] = $sugg;
		}
	}

	array_splice($checked, $amount);
	
	set_cache('entity '.$entity['id'].' wikipedia', array('suggs' => $checked, 'img' => $img), '1 month');
	return $checked;
}

add_filter('entity_actions', 'entity_actions_wikipedia_search', -100, 2);
function entity_actions_wikipedia_search($entity, $context){
	$country = empty($entity['country']) ? 'en' : strtolower($entity['country']);
	$entity['actions']['wikipedia_search'] = array(
		'label' => 'Search on Wikipedia',
		'icon' => 'wikipedia-w',
		'url' => anonymize('https://'.$country.'.wikipedia.org/w/index.php?search='.urlencode($entity['name'])),
		'target' => '_blank',
	);
	return $entity;
}



