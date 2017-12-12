<?php

if (!defined('BASE_PATH'))
	die();



add_action('entity_stats_before', 'kaosEntityHeaderWikipedia');
function kaosEntityHeaderWikipedia($entity){

	$suggs = getWikipediaSuggs($entity, $img);
				
	if ($suggs){ 
		?>
		<div class="entity-sheet-detail entity-medias-suggs">
			<span class="entity-sheet-label">Wikipedia suggs: </span>
			<div class="entity-sheet-body">
				<div class="entity-medias-suggs-inner">
					<ul><?php
						if ($img) echo implode('', $img);
						?>
						<span class="entity-medias-suggs-links"><?= implode(', ', $suggs) ?></span>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}

function getWikipediaSuggs($entity, &$img){
	$suggs = array();
	$img = array();
	for ($i=0; $i<3; $i++){
		
		$q = kaosGetEntityTitle($entity, $i > 0);
		
		if ($i > 0)
			$q = preg_replace('#ayuntamiento\s*de\s*#ius', '', $q); // TODO: abstract this to schemas!!
			
		$ccountry = $i > 1 ? 'en' : strtolower($entity['country']);
		
		$wikis = @json_decode(file_get_contents('https://'.$ccountry.'.wikipedia.org/w/api.php?action=opensearch&modules=opensearch&search='.urlencode($q)));
		
		if ($wikis && is_array($wikis))
			foreach ($wikis as $wiki)
				foreach (is_array($wiki) ? $wiki : array($wiki) as $w)
					if (preg_match('#^https?://#i', $w)){
						if (!$img){
							$html = file_get_contents($w.(strpos($w, '?') === false ? '?' : '&').'action=render');

							// TODO: grep all images in infobox instead of just the first one..
							if ($html && preg_match_all('#\binfobox\b.*?(<img[^>]+>)#ius', $html, $matches)){ 
								$img = array_merge($img, $matches[1]);
							}
						}
						
						$label = strip_tags(rtrim(preg_replace('#^https?://[a-z0-9]+\.wikipedia\.org/(?:wiki/)?#ius', '', urldecode($w)), '/'));
						
						// keep first translation
						$id = str_replace(array('_', ' ', '-'), ' ', remove_accents(strtolower($label)));
						if (!isset($suggs[$id]))
							$suggs[$id] = '<a href="'.esc_attr(kaosAnonymize(strip_tags($w))).'" target="_blank">'.$ccountry.':'.$label.'</a>';
					}
		
		if (count($suggs) >= 3)
			break;
	}
	$suggs = array_unique(array_values($suggs));
	array_splice($suggs, 3);
	return $suggs;
}

