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
 

if (!defined('BASE_PATH'))
	die();



add_action('entity_stats_before', 'company_website_entity_suggs');
function company_website_entity_suggs($entity){
	$live = get_live('company_website', $entity['id']);
	if (!$live) // no block if live returns false or empty string
		return;
	
	?>
	<div class="entity-sheet-detail entity-medias-suggs live-wrap">
		<span class="entity-sheet-label">Website sugg: </span>
		<div class="entity-sheet-body">
			<div class="entity-medias-suggs-inner">
				<?php echo $live ?>
			</div>
		</div>
	</div>
	<?php
}

function live_company_website($entity_id){
	if (!($entity = get_entity_by_id($entity_id)))
		return false;
		
	$url = get_company_website($entity, IS_AJAX);
	if ($url === null)
		return '...';
	
	ob_start();
	if ($url){
		?>
		<a href="<?= anonymize($url) ?>" target="_blank"><?= get_domain($url) ?></a>
		<?php
	}
	?>
	<a class="revert-color" href="<?= anonymize('https://www.startpage.com/do/search?q='.urlencode($entity['name'])) ?>" target="_blank">search</a>
	<?php
	return array('success' => true, 'html' => ob_get_clean());
}


function get_company_website($e, $allowFetch = false){
	
	$cache = get_cache('entity '.$e['id'].' website');
	if ($cache !== null)
		return $cache;
		
	if (!$allowFetch)
		return null;
	
	$opts = array(
		'allowTor' => false, 
		'countAsFetch' => false,
		'noUserAgent' => true,
		'type' => 'html',
	);
	
	$url = false;
	for ($i=0; $i<4; $i++){ 
		$add_particule = $i < 2 && !empty($e['subtype']); // try first with subtype particule, otherwise without it
		$limit_to_country = $i == 0 || $i == 2; // try first with country limitation, otherwise without it (alternate with particule)
		
		$html = fetch('https://www.startpage.com/do/search', array('q' => $e['name'].($add_particule ? ' '.$e['subtype'] : '')) + ($limit_to_country ? array('with_region' => 'country'.strtoupper($e['country'])) : array()), true, false, $opts);
		
		$html = preg_replace('#^.*?<body(.*)$#ius', '$1', $html);
		//echo htmlentities($html); die();
		
		if (preg_match_all('#href=(["\'])(https?://.*?)\1#iu', $html, $m)){
			$links = array();
			foreach ($m[2] as $link)
				if (!in_array(get_domain($link, true), array(
						
						// list of ignored links (domains)
						'startpage.com', 
						'ixquick-proxy.com', 
						'startpage.com', 
						'twitter.com', 
						'facebook.com',
						'startmail.com',
						'wikipedia.org',
						'wikimedia.org',
					)))
					$links[] = $link;

			$links = array_unique($links);
			
			$schema = get_country_schema($e['country']);
			
			$matches = array();
			foreach ($links as $l){
				$domain = get_domain($l, true);
				$bits = explode('.', $domain);
				$base_domain = array_shift($bits);
				$ext = $bits[0];
				
				if (preg_match('#'.preg_replace('#[^a-z0-9]+#iu', '[^a-z0-9]*', $e['name']).'#iu', $domain)){ // check the name is inside the URL
					
					$k1 = preg_replace('#[-_ ]#', '', strtolower($base_domain));
					$k2 = preg_replace('#[-_ ]#', '', strtolower($e['name']));
					similar_text($k1, $k2, $percent);
					
					if ($ext == $schema->topLevelDomain) // increase score if URL is from the right country
						$percent = $percent * 2;
					else if (!in_array($ext, array('org', 'com', 'net', 'tv', 'io')))  // decrease score if URL is from a wrong country
						$percent = $percent / 2;
					
					$matches[] = array(
						'url' => $l,
						'domain' => $domain,
						'base_domain' => $base_domain,
						'score' => $percent,
					);
				}
			}

			//foreach ($matches as $m)
			//	echo $m['url'].': '.$m['score'].'<br>';

			$max = false;
			foreach ($matches as $m)
				if (!$max || $m['score'] > $max){
					$url = $m['url'];
					$max = $m['score'];
				}
			if ($max && $max > 40)
				break;
		}
	}

	set_cache('entity '.$e['id'].' website', $url, '1 month');
	return $url;
}

