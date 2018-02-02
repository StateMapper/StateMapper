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
	
// generates a sitemap (an index with sub-sitemaps) at /sitemap-index.xml

// @todo: implement entities' index and subsitemaps (page by ID!) 
// Max sitemap size: 50.000 URLs, 10MB -> https://stackoverflow.com/questions/2887358/limitation-for-google-sitemap-xml-file-size/24382418#24382418

add_filter('page', 'sitemaps_page', 0, 2);
function sitemaps_page($page, $bits){
	global $smap;
	if (!$bits && preg_match('#^sitemap_([a-z0-9-]+)(?:_([a-z0-9-_]+))?\.xml$#', $page, $m) && is_sitemap($m[1])){
		$smap['sitemap'] = $m[1];
		$smap['sitemap_bits'] = !empty($m[2]) ? explode('_', $m[2]) : array();
		
		if ($smap['sitemap'] == 'bulletins' && count($smap['sitemap_bits']) >= 2){
			
			$smap['sitemap_schema'] = strtoupper(implode('/', array_splice($smap['sitemap_bits'], 0, 2)));
			if (!get_schema($smap['sitemap_schema']))
				die_error();
				
			if ($smap['sitemap_bits'] && is_numeric($smap['sitemap_bits'][0]))
				$smap['sitemap_year'] = array_shift($smap['sitemap_bits']);
				
		} else
			$smap['sitemap_schema'] = null;
		return 'sitemap';
	}
	return $page;
}

function is_sitemap($str){
	return in_array($str, get_sitemaps());
}

function get_sitemaps(){
	return array('pages', 'providers', 'bulletins', 'entities', 'index');
}

add_filter('page_sitemap', 'sitemaps_sitemap', 0, 2);
function sitemaps_sitemap($ret, $bits){
	global $smap;
	$sitemaps = get_sitemaps();

	$top = '<?xml version="1.0" encoding="UTF-8"?>';
	
	$sitemap_lastmods = array();
	$where_and = 'status IN ( "fetched", "parsed", "extracting", "extracted" )';

	foreach ($sitemaps as $sitemap){
		$items = array();
		$header = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$footer = '</urlset>';
		$tag = 'url';
		$is_index = false;
		
		switch ($sitemap){
			case 'pages':
			
				// home
				$items[] = array(	
					'uri' => uri(), 
					'changefreq' => 'daily', 
					'priority' => 1, 
					'lastmod' => strtotime('today 00:00:01'),
				);
				
				// API root
				$items[] = array(	
					'uri' => uri(null, 'api'), 
					'changefreq' => 'weekly', 
					'priority' => 0.6, 
					'lastmod' => strtotime('last monday 00:00:01'),
				);
				
				break;
			
			case 'providers':
				if ($schemas = get_col('SELECT bulletin_schema FROM bulletins WHERE '.$where_and.' GROUP BY bulletin_schema')){

					// providers' root
					$items[] = array(
						'uri' => get_providers_uri(), 
						'changefreq' => 'daily', 
						'priority' => 0.8, 
						'lastmod' => filemtime(BASE_PATH.'/schemas'),
					);
					
					// country providers' pages
					foreach (get_schema_countries() as $file){
						$cschemas = array();
						foreach ($schemas as $schema)
							if (strpos($schema, $file) === 0)
								$cschemas[] = $schema;
						if ($cschemas){
							$items[] = array(
								'uri' => get_providers_uri(strtolower($file)), 
								'changefreq' => 'daily', 
								'priority' => 0.8, 
								'lastmod' => filemtime(BASE_PATH.'/schemas/'.$file),
							);
							foreach ($cschemas as $schema){
								$query = array(
									'schema' => $schema, 
									'country' => strtolower($file),
								);
								$items[] = array(
									'uri' => uri($query, 'schema'), 
									'changefreq' => 'weekly', 
									'priority' => 0.4, 
									'lastmod' => filemtime(BASE_PATH.'/schemas/'.$schema.'.json'),
								);
								$items[] = array(
									'uri' => uri($query, 'rewind'), 
									'changefreq' => 'daily', 
									'priority' => 0.4, 
									'lastmod' => get_var('SELECT MAX(date) FROM bulletins WHERE bulletin_schema = %s AND '.$where_and, $schema),
								);
							}
						}
					}
				}
				break;
			
			case 'bulletins':
				if (empty($smap['sitemap_schema'])){ // print a sitemap of all bulletin sitemaps
					$is_index = true;
					
					foreach (get_schema_countries() as $file){
						$cschemas = array();
						foreach ($schemas as $schema)
							if (strpos($schema, $file) === 0)
								$cschemas[] = $schema;
						
						if ($cschemas){
							foreach ($cschemas as $schema)
								$items[] = array(
									'uri' => 'sitemap_bulletins_'.strtolower(str_replace('/', '_', $schema)).'.xml', 
									'lastmod' => get_var('SELECT MAX(date) FROM bulletins WHERE bulletin_schema = %s AND '.$where_and, $schema),
								);
						}
					}
					
				} else {
					if (empty($smap['sitemap_year'])){ // print a sitemap of all years
						
						$is_index = true;
						
						if ($years = get_col('SELECT YEAR(date) FROM bulletins WHERE bulletin_schema = %s AND '.$where_and.' GROUP BY YEAR(date) ORDER BY date DESC', $smap['sitemap_schema'])){
							foreach ($years as $year){
								$year = (string) $year;

								$items[] = array(
									'uri' => 'sitemap_bulletins_'.strtolower(str_replace('/', '_', $smap['sitemap_schema'])).'_'.$year.'.xml', 
									'lastmod' => $year == date('Y') 
										? get_var('SELECT MAX(date) FROM bulletins WHERE bulletin_schema = %s AND '.$where_and, $smap['sitemap_schema']) 
										: $year.'-12-31',
								);
							}
						}
					
					} else { // print all bulletins fetched this year
						
						if ($dates = get_col('SELECT date FROM bulletins WHERE bulletin_schema = %s AND '.$where_and.' AND YEAR(date) = %s GROUP BY date ORDER BY date DESC', array($smap['sitemap_schema'], $smap['sitemap_year']))){
							foreach ($dates as $date){
								$items[] = array(
									'uri' => uri(array(
										'schema' => $smap['sitemap_schema'],
										'date' => $date,
									), 'fetch'),
									'lastmod' => $date,
								);
								$items[] = array(
									'uri' => uri(array(
										'schema' => $smap['sitemap_schema'],
										'date' => $date,
									), 'fetch/raw'),
									'lastmod' => $date,
								);
							}
						}
					}
				}
				break;
			
			case 'entities':
				// @todo: implement entities' index and subsitemaps (page by ID!)
				break;
			
			case 'index':
				$is_index = true;
						
				foreach ($sitemaps as $csitemap)
					if (isset($sitemap_lastmods[$csitemap]))
						$items[] = array(
							'uri' => 'sitemap_'.$csitemap.'.xml',
							'lastmod' => $sitemap_lastmods[$csitemap],
						);
				break;
		}
		
		if (!$items)
			continue;
		
		// save min lastmod for $sitemap
		if ($sitemap != 'index'){
			$max = null;
			foreach ($items as $item)
				if (isset($item['lastmod']))
					$max = $max ? max($max, $item['lastmod']) : $item['lastmod'];
			$sitemap_lastmods[$sitemap] = $max;
		}
		
		if ($is_index){
			$header = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			$footer = '</sitemapindex>';
			$tag = 'sitemap';
		}
		
		$str = $top."\n".$header."\n";
		foreach ($items as $item){
			$item = array(
				'loc' => BASE_URL.$item['uri'],
				'lastmod' => date('Y-m-d\ZH:i:s', isset($item['lastmod']) ? (is_numeric($item['lastmod']) ? $item['lastmod'] : strtotime($item['lastmod'])) : strtotime('today 00:00:01')),
				'changefreq' => isset($item['changefreq']) ? $item['changefreq'] : null,
				'priority' => isset($item['priority']) ? $item['priority'] : null,
			);
			$str .= '<'.$tag.'>'."\n";
			foreach ($item as $k => $v)
				if ($v !== null)
					$str .= '<'.$k.'>'.$v.'</'.$k.'>'."\n";
			$str .= '</'.$tag.'>'."\n";
		}
		$str .= $footer;
		
		//$path = $sitemap == 'index' ? 'sitemap_index.xml' : 'sitemaps/sitemap-'.$sitemap.'.xml';
		if ($smap['sitemap'] == $sitemap){
			header('Content-type: application/xml; charset=UTF-8');
			echo $str;
			exit;
		} 
//		echo $sitemap.':<br>'.nl2br(htmlentities($str)).'<br><br>';
	}
	die_error();
}

// not used yet, ping Google about sitemap_index.xml
function sitemap_ping(){
	
	$ping_return = fetch('http://www.google.com/webmasters/sitemaps/ping?sitemap='.urlencode(BASE_URL.'sitemap_index.xml'), array(), true, false, array(
		'countAsFetch' => false, 
		'allowTor' => false,
		'noUserAgent' => true,
		'timeout' => 10,
		'retries' => 1,
	));
	
}
