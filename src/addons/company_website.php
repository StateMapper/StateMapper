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
	
/*
 * @todo: convert to a "searx" search!!
 * see https://github.com/asciimoo/searx/wiki/Searx-instances
 * 
 * take (first) all domains that don't have the entity name as a URI (and better if in domain name).
 * then all selected domains through clearbit.com and calculate matching with the entity name
 * (collect other infos from clearbit)
 * 
 * clearbit: 600 requests per minute to each API
 * see https://clearbit.com/docs
 * 
 */

// guess a company's website from its name, subtype and country, looking up in startpage.com

add_filter('entity_summary', 'company_website_entity_suggs', 0, 3);
function company_website_entity_suggs($details, $entity, $context){

	$live = get_live('company_website', $entity['id'], array(
		'context' => $context
	));
	if (!$live) // no block if live returns false or empty string
		return $details;
	
	$details['company_website'] = array(
		'label' => 'Web search',
		'html' => $live,
	);
	return $details;
}

function live_company_website($entity_id, $opts){
	if (!($entity = get_entity_by_id($entity_id)))
		return false;
		
	$opts += array(
		'context' => 'sheet',
	);
		
	$can_fetch = can_fetch_by_context($opts['context']);
	$url = get_entity_website($entity, $can_fetch);
	if ($url === null)
		return '...';
	
	$str = '';
	if ($url){
		$str .= '<a href="'.anonymize($url).'" data-tippy-placement="right" target="_blank" title="'.esc_attr(__('Visit the autodetected entity website').': '.$url).'"><i class="fa fa-globe icon"></i> '.get_domain($url, true).'</a>';

	} else if (!$url)
		return false;
		
	return array('success' => true, 'html' => '<div class="inline-links">'.$str.'</div>');
}

function get_entity_websearches($e){
	$searches = array();
	$name = $e['name'];
	
	if ($e['type'] == 'person'){
		$name = strtoupper($name);
		if (!empty($e['first_name']))
			$name = $e['first_name'].' '.$e['name'];
	}

	if (!empty($e['subtype']))
		$subtype = get_subtype_prop($e['country'], $e['type'].'/'.$e['country'].'/'.$e['subtype'], 'name');
			
	if (!empty($e['country'])){
		if (!empty($e['subtype']))
			$searches[] = array(
				'q' => $name.', '.$subtype,
				'country' => $e['country'],
			);
		
		$searches[] = array(
			'q' => $name,
			'country' => $e['country'],
		);
	}
		
	if (!empty($e['subtype']))
		$searches[] = array(
			'q' => $name.', '.$subtype,
		);
		
	$searches[] = array(
		'q' => $name
	);
	
	return $searches;
}

function get_entity_websearch_urls($e, $allow_fetch = false, $limit = 20){
	
	$cache = get_cache('entity '.$e['id'].' urls');
	if ($cache !== null)
		return $cache;
		
	if (!$allow_fetch)
		return null;
	
	$opts = array(
		'allowTor' => false, 
		'countAsFetch' => false,
		'noUserAgent' => true,
		'type' => 'html',
	);
	
	$urls = array();
	foreach (get_entity_websearches($e) as $search){
		
		$args = array(
			'q' => $search['q'],
		);
		
		if (!empty($search['country']))
			$args['with_region'] = 'country'.strtoupper($search['country']);
		
		$html = fetch('https://www.startpage.com/do/search', $args, true, false, $opts);
		
		$html = preg_replace('#^.*?<body(.*)$#ius', '$1', $html);
		
		if (preg_match_all('#href=(["\'])(https?://.*?)\1#iu', $html, $m)){
			$links = array();
			foreach ($m[2] as $url){
				$domain = get_domain($url, true);
				if (!in_array($domain, array(
						
						// list of ignored links (root domains)
						'startpage.com', 
						'ixquick-proxy.com', 
						'startpage.com', 
						'twitter.com', 
						'facebook.com',
						'startmail.com',
						'wikipedia.org',
						'wikimedia.org',
						'linkedin.com',
					)))
					$urls[] = array(
						'url' => $url,
						'domain' => $domain,
					);
			}
		}
		if (count($urls) > $limit)
			break;
	}

	set_cache('entity '.$e['id'].' urls', $urls, '1 month');
	return $urls;
}


function get_entity_website($e, $allow_fetch = false){
	
	$cache = get_cache('entity '.$e['id'].' website');
	if ($cache !== null)
		return $cache;
		
	if (!$allow_fetch)
		return null;
	
	$schema = get_schema($e['country']);

	$url = false;
	$matches = array();

	foreach (get_entity_websearch_urls($e, $allow_fetch) as $url){
			
		$bits = explode('.', $url['domain']);
		$base_domain = array_shift($bits); // the domain name without extension
		$ext = $bits[0];
		
		if (isset($matches[$base_domain]))
			continue;
				
		// check the name is inside the URL
		$inside_url = preg_match('#'.preg_replace('#[^a-z0-9]+#iu', '[^a-z0-9]*', beautify_name($e['name'], $schema)).'#iu', $base_domain);

		// check the name is inside the title (only possible with searx)
		//$inside_title = preg_match('#'.preg_replace('#[^a-z0-9]+#iu', '[^a-z0-9]*', $e['name']).'#iu', $domain);
		
		if ($inside_url){
			
			$k1 = preg_replace('#[-_ ]#', '', strtolower($base_domain));
			$k2 = preg_replace('#[-_ ]#', '', strtolower($e['name']));
			similar_text($k1, $k2, $percent);
			
			if ($ext == $schema->topLevelDomain) // increase score if URL is from the right country
				$percent = $percent * 2;
			else if (!in_array($ext, array('org', 'com', 'net', 'tv', 'io', 'eco', 'earth', 'club')))  // decrease score if URL is from a wrong country
				$percent = $percent / 2;
			
			$matches[$base_domain] = $url + array(
				'base_domain' => $base_domain,
				'score' => $percent,
			);
		}
	}
	
	$max = false;
	foreach ($matches as $m)
		if (!$max || $m['score'] > $max){
			$url = $m['url'];
			$max = $m['score'];
		}

	if (!$max || $max < 40)
		$url = false;

	set_cache('entity '.$e['id'].' website', $url, '1 month');
	return $url;
}

add_filter('entity_actions', 'entity_actions_website_search', -1000, 2);
function entity_actions_website_search($entity, $context){
	$entity['actions']['website_search'] = array(
		'label' => 'Search on the web',
		'icon' => 'search',
		'url' => anonymize('https://www.startpage.com/do/search?q='.urlencode($entity['name'])),
		'target' => '_blank',
	);
	return $entity;
}



