<?php

if (!defined('BASE_PATH'))
	die();

global $kaosCall;
extract($vars);
$kaosCall['outputNoFilter'] = true;

ob_start();

// what this entity was adjudicated
$statuses = array();

$ids = kaosGetOtherEntities($entity['id']);

$stats = query('
	SELECT "target" AS rel, COALESCE(YEAR(b.date), YEAR(b_in.date)) AS date, s.type AS _type, s.action AS _action,
	SUM(a.originalValue) AS amount, a.originalUnit AS unit, COUNT(s.id) AS count, COUNT(s.related_id) AS related

	FROM precepts AS p
	LEFT JOIN statuses AS s ON p.id = s.precept_id
	LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
	LEFT JOIN bulletin_uses_bulletin AS bb ON p.bulletin_id = bb.bulletin_id
	LEFT JOIN bulletins AS b_in ON bb.bulletin_in = b_in.id
	LEFT JOIN amounts AS a ON s.amount = a.id

	WHERE s.type IS NOT NULL AND s.action IS NOT NULL AND s.target_id IN ( '.implode(', ', $ids).' )
	GROUP BY COALESCE(YEAR(b.date), YEAR(b_in.date)), s.type, s.action
	ORDER BY COALESCE(b.date, b_in.date) DESC, s.type = "name" ASC
');

$stats = array_merge($stats, query('
	SELECT "related" AS rel, COALESCE(YEAR(b.date), YEAR(b_in.date)) AS date, s.type AS _type, s.action AS _action,
	SUM(a.originalValue) AS amount, a.originalUnit AS unit, COUNT(s.id) AS count, COUNT(s.target_id) AS target

	FROM precepts AS p
	LEFT JOIN statuses AS s ON p.id = s.precept_id
	LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
	LEFT JOIN bulletin_uses_bulletin AS bb ON p.bulletin_id = bb.bulletin_id
	LEFT JOIN bulletins AS b_in ON bb.bulletin_in = b_in.id
	LEFT JOIN amounts AS a ON s.amount = a.id

	WHERE s.type IS NOT NULL AND s.action IS NOT NULL AND (p.issuing_id IN ( '.implode(', ', $ids).' ) OR s.related_id IN ( '.implode(', ', $ids).' ) )
	GROUP BY COALESCE(YEAR(b.date), YEAR(b_in.date)), s.type, s.action
	ORDER BY COALESCE(b.date, b_in.date) DESC, s.type = "name" ASC

'));


$location = getLocationById($entity['id']);

$locationObj = apply_filters('location_lint', null, $location, $entity['country']);
	
?>
<div>
	<div class="entity-header entity-header-entity-<?= $entity['id'] ?>" <?= kaosRelated(array('id' => $entity['id'])) ?>>
		<div class="entity-intro">
			<span class="entity-country">
			<?php
				$country = kaosGetCountrySchema($entity['country']);

				$entityCountry = $country;
				if ($locationObj)
					$entityCountry = $locationObj['country'];

				if ($avatarUrl = kaosGetFlagUrl($entityCountry))
					echo '<img class="entity-avatar" src="'.$avatarUrl.'" />';

				// country name
				if (kaosGetCountrySchema($entityCountry))
					echo kaosGetCountrySchema($entityCountry)->name;
				else if ($locationObj && !empty($locationObj['countryName']))
					echo $locationObj['countryName'];
				else
					echo $country->name;

				?></span><?php

				if ($locationObj){
					echo '<i class="entity-intro-sep fa fa-angle-right"></i><span class="clean-links entity-breadcrumb-state">'.getStateName($locationObj['state']).'</span>';
					echo '<i class="entity-intro-sep fa fa-angle-right"></i><span class="clean-links entity-breadcrumb-city">'.getCityName($locationObj['city']).'</span>';
				}

			?><i class="entity-intro-sep fa fa-angle-right"></i><span class="entity-breadcrumb-type clean-links"><?php

			switch ($entity['type']){
				case 'person':
					echo ' <a href="'.BASE_URL.'?etype=person" title="See all people">Person</a>';
					break;
				case 'institution':
					echo ' <a href="'.BASE_URL.'?etype=institution" title="See all institutions">Institution</a>';
					break;
				case 'company':
					if (!empty($entity['subtype'])){
						$c = kaosGetCountrySchema($entity['country']);
						$label = $c->vocabulary->legalEntityTypes->{$entity['subtype']}->name;
						if (!empty($c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls)){
							if (isset($c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls->{getLang()}))
								$url = $c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls->{getLang()};
							else {
								$urls = (array) $c->vocabulary->legalEntityTypes->{$entity['subtype']}->urls;
								$url = array_shift($urls);
							}
							$label = '<a href="'.esc_attr(kaosAnonymize($url)).'" target="_blank" title="'.esc_attr(sprintf('More information about %s', $label)).'">'.$label.'</a>';
						}
						echo $label;
					} else
						echo '<a href="'.BASE_URL.'?etype=company">Company (Unknown type)</a>';
					break;
			}
			?></span>
		</div>
		<div class="entity-name entity-title">
			<div class="entity-title-inner"><a class="entity-title-icon" href="<?= BASE_URL ?><?php

				$conv = array(
					'institution' => array(
						'uri' => '?etype=institution',
						'title' => 'See all institutions',
					),
					'company' => array(
						'uri' => '?etype=company',
						'title' => 'See all companies',
					),
					'person' => array(
						'uri' => '?etype=person',
						'title' => 'See all people',
					),
				);
				echo $conv[$entity['type']]['uri'];

			?>" title="<?= $conv[$entity['type']]['title'] ?>"><?= '<i class="fa fa-'.kaosGetEntityIcon($entity).'"></i>' ?></a> <?= (!empty($entity) ? kaosGetEntityTitle($entity) : '') ?><?= buggyButton('entity', 'Mark this name as buggy') ?>
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
				<?php kaosPrintEntityStats($stats, $entity, array('id' => $entity['id'])); ?>
			</div>
		</div>
	</div>
	<!--<div class="entity-info">
		<div class="entity-info-block">
			<div class="entity-info-inner">
				<?php //kaosPrintStatuses($statuses, $entity); ?>
			</div>
		</div>
	</div>-->
</div>
<?php
return ob_get_clean();

