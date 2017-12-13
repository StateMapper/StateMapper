<?php

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

