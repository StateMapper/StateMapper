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

global $smap;
$smap['outputNoFilter'] = true;

// what this entity was adjudicated
$statuses = array();

$ids = get_other_entities($entity['id']);

$stats = query('
	SELECT "target" AS rel, YEAR(b.date) AS date, s.type AS _type, s.action AS _action,
	SUM(a.originalValue) AS amount, a.originalUnit AS unit, COUNT(s.id) AS count, COUNT(s.related_id) AS related

	FROM precepts AS p
	LEFT JOIN statuses AS s ON p.id = s.precept_id
	LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
	LEFT JOIN amounts AS a ON s.amount = a.id

	WHERE s.target_id IN ( '.implode(', ', $ids).' )
	GROUP BY YEAR(b.date), s.type, s.action
	ORDER BY b.date DESC, s.type = "name" ASC
');

$stats = array_merge($stats, query('
	SELECT "related" AS rel, YEAR(b.date) AS date, s.type AS _type, s.action AS _action,
	SUM(a.originalValue) AS amount, a.originalUnit AS unit, COUNT(s.id) AS count, COUNT(s.target_id) AS target

	FROM precepts AS p
	LEFT JOIN statuses AS s ON p.id = s.precept_id
	LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
	LEFT JOIN amounts AS a ON s.amount = a.id

	WHERE s.related_id IN ( '.implode(', ', $ids).' )
	GROUP BY YEAR(b.date), s.type, s.action
	ORDER BY b.date DESC, s.type = "name" ASC

'));

$stats = array_merge($stats, query('
	SELECT "related" AS rel, YEAR(b.date) AS date, s.type AS _type, s.action AS _action,
	SUM(a.originalValue) AS amount, a.originalUnit AS unit, COUNT(s.id) AS count, COUNT(s.target_id) AS target

	FROM precepts AS p
	LEFT JOIN statuses AS s ON p.id = s.precept_id
	LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
	LEFT JOIN amounts AS a ON s.amount = a.id

	WHERE p.issuing_id IN ( '.implode(', ', $ids).' )
	GROUP BY YEAR(b.date), s.type, s.action
	ORDER BY b.date DESC, s.type = "name" ASC

'));


$location = get_location_by_id($entity['id']);

$locationObj = apply_filters('location_lint', null, $location, $entity['country']);
	
?>
<div>
	<div class="entity-header entity-header-entity-<?= $entity['id'] ?>" <?= related(array('id' => $entity['id'])) ?>>
		<div class="entity-intro">
			<span class="entity-country">
			<?php
				$country = get_country_schema($entity['country']);

				$entityCountry = $country;
				if ($locationObj)
					$entityCountry = $locationObj['country'];

				if ($avatarUrl = get_flag_url($entityCountry))
					echo '<img class="entity-avatar" src="'.$avatarUrl.'" />';

				// country name
				if (get_country_schema($entityCountry))
					echo get_country_schema($entityCountry)->name;
				else if ($locationObj && !empty($locationObj['countryName']))
					echo $locationObj['countryName'];
				else
					echo $country->name;

				?></span><?php

				if ($locationObj){
					if ($locationObj['state'])
						echo '<i class="entity-intro-sep fa fa-angle-right"></i><span class="clean-links entity-breadcrumb-state">'.get_state_name($locationObj['state']).'</span>';
					
					if ($locationObj['city'])
						echo '<i class="entity-intro-sep fa fa-angle-right"></i><span class="clean-links entity-breadcrumb-city">'.get_city_name($locationObj['city']).'</span>';
				}

			?><i class="entity-intro-sep fa fa-angle-right"></i><span class="entity-breadcrumb-type clean-links"><?php

			$conv = get_entity_types();

			switch ($entity['type']){
				case 'person':
				case 'institution':
					echo ' <a href="'.url(null, $conv[$entity['type']]['slug']).'" title="'.esc_attr('See all '.$conv[$entity['type']]['plural']).'">'.ucfirst($conv[$entity['type']]['singular']).'</a>';
					break;
				case 'company':
					if (!empty($entity['subtype'])){
						$c = get_country_schema($entity['country']);
						$label = $c->vocabulary->legalEntityTypes->{$entity['subtype']}->name;
						if (!empty($c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls)){
							if (isset($c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls->{get_lang()}))
								$url = $c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls->{get_lang()};
							else {
								$urls = (array) $c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls;
								$url = array_shift($urls);
							}
							$label = '<a href="'.esc_attr(anonymize($url)).'" target="_blank" title="'.esc_attr(sprintf('More information about %s', $label)).'">'.$label.'</a>';
						}
						echo $label;
					} else
						echo '<a href="'.url(null, $conv[$entity['type']]['slug']).'">Company (Unknown type)</a>';
					break;
			}
			?></span>
		</div>
		<div class="entity-name entity-title">
			<div class="entity-title-inner"><a class="entity-title-icon" href="<?php
			
				echo url(array(
					'country' => $entity['country']
				), $conv[$entity['type']]['slug']);

			?>" title="<?= esc_attr(sprintf(_('See all %s %s'), get_country_schema($entity['country'])->adjective, $conv[$entity['type']]['plural'])) ?>"><?= '<i class="fa fa-'.get_entity_icon($entity).'"></i>' ?></a> <?= (!empty($entity) ? get_entity_title($entity) : '') ?><?= get_buggy_button('entity', 'Mark this name as buggy') ?>
			</div>
		</div>
		<?php

			do_action('entity_header', $entity);

			$details = array();
			foreach ($entity['summary'] as $id => $e)
				$details[] = '<div class="entity-sheet-detail entity-sheet-detail-'.$id.' '.(!empty($e['class']) ? $e['class'] : '').'"><span class="entity-sheet-label">'.$e['title'].': </span><span class="entity-sheet-body">'.$e['html'].'</span></div>';

			ob_start();
			do_action('entity_stats_before', $entity);
			$htmlBefore = ob_get_clean();

			ob_start();
			do_action('entity_stats_after', $entity);
			$htmlAfter = ob_get_clean();

			if ($details || $htmlBefore != '' || $htmlAfter != '')
				echo '<div class="entity-sheet-details">'.$htmlBefore.implode('', $details).$htmlAfter.'</div>';
			?>
		<div class="entity-stats-wrap">
			<div class="entity-stats">
				<?php print_entity_stats($stats, $entity, array('id' => $entity['id'])); ?>
			</div>
		</div>
	</div>
	<!--<div class="entity-info">
		<div class="entity-info-block">
			<div class="entity-info-inner">
				<?php //print_statuses($statuses, $entity); ?>
			</div>
		</div>
	</div>-->
</div>
<?php

