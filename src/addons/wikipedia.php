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



add_action('entity_stats_before', 'wikipedia_entity_suggs');
function wikipedia_entity_suggs($entity){
	$live = get_live('wikipedia_suggs', $entity['id']);
	if (!$live) // no block if live returns false or empty string
		return;
	?>
	<div class="entity-sheet-detail entity-medias-suggs live-wrap">
		<span class="entity-sheet-label">Wikipedia suggs: </span>
		<div class="entity-sheet-body">
			<div class="entity-medias-suggs-inner">
				<?php echo $live ?>
			</div>
		</div>
	</div>
	<?php
}

function live_wikipedia_suggs($entity_id){
	if (!($entity = get_entity_by_id($entity_id)))
		return false;
		
	$suggs = get_wikipedia_suggs($entity, $img, IS_AJAX);
	if ($suggs === false)
		return '...';
	
	if ($suggs){
		ob_start();
		?>
		<ul><?php
			if ($img) echo implode('', $img);
			?>
			<span class="entity-medias-suggs-links"><?= implode(', ', $suggs) ?></span>
		</ul>
		<?php
		return array('success' => true, 'html' => ob_get_clean());
	}
	return false;
}

function get_wikipedia_suggs($entity, &$img, $allowFetch = true){
	
	if ($cache = get_cache('entity '.$entity['id'].' wikipedia')){
		$img = $cache['img'];
		return $cache['suggs'];
	}
	if (!$allowFetch)
		return false;
	
	$opts = array(
		'allowTor' => false, 
		'countAsFetch' => false,
		'noUserAgent' => true,
		'cache' => '1 day',
		'type' => 'html',
	);
		
	$suggs = array();
	$img = array();
	for ($i=0; $i<3; $i++){
		
		$q = get_entity_title($entity, $i > 0);
		
		if ($i > 0)
			$q = preg_replace('#ayuntamiento\s*de\s*#ius', '', $q); // TODO: abstract this to schemas!!
			
		if ($i > 1 && strtolower($entity['country']) == 'en')
			break;
			
		$ccountry = $i > 1 ? 'en' : strtolower($entity['country']);
		
		$wikis = fetch('https://'.$ccountry.'.wikipedia.org/w/api.php', array('action' => 'opensearch', 'modules' => 'opensearch', 'search' => $q), true, false, $opts);
		
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
						if (!isset($suggs[$id]) && preg_match('#\b'.preg_replace('#[^a-z0-9]+#iu', '[^a-z0-9]*', $entity['name']).'\b#iu', $label))
							$suggs[$id] = '<a href="'.esc_attr(anonymize(strip_tags($w))).'" target="_blank">'.$ccountry.':'.$label.'</a>';
					}
		
		if (count($suggs) >= 3)
			break;
	}
	$suggs = array_unique(array_values($suggs));
	array_splice($suggs, 3);
	
	set_cache('entity '.$entity['id'].' wikipedia', array('suggs' => $suggs, 'img' => $img), '1 month');
	return $suggs;
}

