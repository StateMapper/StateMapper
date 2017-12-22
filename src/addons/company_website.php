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



add_action('entity_stats_before', 'kaosCompanyWebsite');
function kaosCompanyWebsite($entity){
	if ($url = getCompanyWebsite($entity)){
		?>
		<div class="entity-sheet-detail entity-medias-suggs">
			<span class="entity-sheet-label">Website sugg: </span>
			<div class="entity-sheet-body">
				<div class="entity-medias-suggs-inner">
					<a href="<?= kaosAnonymize($url) ?>" target="_blank"><?= getPrintDomain($url) ?></a>
					<a class="revert-color" href="https://www.startpage.com/do/search?q=<?= urlencode($entity['name']) ?>" target="_blank">search</a>
				</div>
			</div>
		</div>
		<?php
	}
}


function getCompanyWebsite($e){
	
	$opts = array(
		'allowTor' => false, 
		'countAsFetch' => false,
		'noUserAgent' => true,
	);

	$html = kaosFetch('https://www.startpage.com/do/search', array('q' => $e['name']), true, false, $opts);
	
	$html = preg_replace('#^.*\bweb_regular_results[^>]*>(.*)$#ius', '$1', $html);
	if (preg_match_all('#href=(["\'])((https?://[^/\?\#"\']+)([/\?\#][^"\']+?)?)\1#iu', $html, $m)){
		$links = array();
		foreach ($m[2] as $l)
			if (!in_array($l, array('https://www.startpage.com', 'https://ixquick-proxy.com')))
				$links[] = $l;
		$links = array_unique($links);
		$matches = array();
		foreach ($links as $l){
			$domain = getPrintDomain($l);
			if (preg_match('#'.preg_replace('#[^a-z0-9]+#iu', '[^a-z0-9]*', $e['name']).'#iu', $domain))
				$matches[] = array(
					'url' => $l,
					'score' => similar_text(strtolower($domain), strtolower($e['name']))
				);
		}

		//foreach ($matches as $m)
		//	echo $m['url'].': '.$m['score'].'<br>';

		$url = false;
		$max = false;
		foreach ($matches as $m)
			if (!$max || $m['score'] > $max){
				$url = $m['url'];
				$max = $m['score'];
			}
		if ($max && $max > 4)
			return $url;
	}
	return false;
}

