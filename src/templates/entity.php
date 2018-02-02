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

//$location = get_location_by_id($entity['id']);

/*
$locationObj = apply_filters('location_lint', null, $location, $entity['country']);

// fixing.....
if (!is_numeric($location) && $locationObj && !empty($locationObj['id']))
	update('statuses', array(
		'note' => $locationObj['id']
	), array(
		'note' => $location
	));*/
$locationObj = null;

print_header('browser');

$in_my_lists = array();
foreach ($entity['summary']['in_my_lists'] as $cur)
	$in_my_lists[] = intval($cur['list_id']);
?>
<div class="sheet entity-sheet entity-header-entity-<?= $entity['id'] ?>" <?= related(array('entity_id' => $entity['id'], 'in_my_lists' => $in_my_lists)) ?>>
	<div class="sheet-header">
		<div class="entity-intro">
			<span class="entity-country">
			<?php
				$country = get_schema($entity['country']);

				$entityCountry = $country;
				if ($locationObj)
					$entityCountry = $locationObj['country'];
				
				echo '<a href="'.url(array(
					'country' => $entity['country'],
				)).'" title="See all '.get_schema($entity['country'])->adjective.' entities" class="clean-links">';
					
				if ($avatarUrl = get_flag_url($entityCountry))
					echo '<img class="entity-avatar" src="'.$avatarUrl.'" />';

				// country name
				if (get_schema($entityCountry))
					echo get_schema($entityCountry)->name;
				else if ($locationObj && !empty($locationObj['countryName']))
					echo $locationObj['countryName'];
				else
					echo $country->name;
					
				echo '</a>';

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
					echo ' <a href="'.url(array(
						'country' => $entity['country']
					), $conv[$entity['type']]['slug']).'" title="'.esc_attr('See all '.get_schema($entity['country'])->adjective.' '.$conv[$entity['type']]['plural']).'">'.ucfirst($conv[$entity['type']]['singular']).'</a>';
					break;
				case 'company':
					if (!empty($entity['subtype'])){
						$c = get_schema($entity['country']);
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
						echo '<a href="'.url(array(
							'country' => $entity['country']
						), $conv[$entity['type']]['slug']).'">Company (Unknown type)</a>';
					break;
			}
			?></span>
		</div>
		<div class="entity-name entity-title">
			<?php
				if ($actions = get_entity_actions_html($entity, 'sheet'))
					print_actions_menu($entity, $actions, 'entity-action', 'entity-sheet-actions-wrap entity-actions-wrap', 'sheet');
			?>
			<div class="entity-title-inner">
				<a class="icon" href="<?php
			
				echo url(array(
					'country' => $entity['country']
				), $conv[$entity['type']]['slug']);

				?>" title="<?= esc_attr(sprintf(_('See all %s %s'), get_schema($entity['country'])->adjective, $conv[$entity['type']]['plural'])) ?>"><?= '<i class="fa fa-'.get_entity_icon($entity).'"></i>' ?></a><?php 
				
					if (!empty($entity))
						echo ' <h1 class="seemless">'.get_entity_title($entity).'</h1>';
				?>
			</div>
		</div>
	</div>
	<div class="sheet-body">
		<div class="entity-summary">
			<?php
			$labels = get_status_labels();
			$lines = array();
			
			if ($founded = @$entity['summary']['founded']){
				$amount = $founded['amount'] ? print_amount($founded['amount'], $founded['unit']) : null;
				
				if ($founded['date'])
					$html = '<b>'.date_i18n('Y, M jS', strtotime($founded['date'])).'</b> - Aged '.time_diff($founded['date']).($amount ? ' - Inicial capital: <b>'.$amount.'</b>' : '');
				else if ($amount)
					$html = 'Initial capital: <b>'.$amount.'</b>';
				else
					$html = null;
				
				if ($html)
					$lines[] = array(
						'icon' => 'birthday-cake',
						'label' => 'Founded',
						'html' => $html,
					);
			}
			foreach ($entity['summary'] as $type => $summary){
				if ($type == 'founded')
					continue;
					
				if ($line = apply_filters('entity_summary_html_'.$type, false, $summary, $entity))
					$lines[] = $line;
				
				else
					foreach ($summary as $rel => $s){
							
						if (@$labels->{$type}->start->summary)
							$label = $labels->{$type}->start->summary;
						else if (@$labels->{$type}->new->summary)
							$label = $labels->{$type}->new->summary;
						else if (@$labels->{$type}->increase->summary)
							$label = $labels->{$type}->increase->summary;
						else if (@$labels->{$type}->update->summary)
							$label = $labels->{$type}->update->summary;
						else
							continue;
							
						if (@$labels->{$type}->increase->icon)
							$icon = $labels->{$type}->increase->icon;
						else if (@$labels->{$type}->start->icon)
							$icon = $labels->{$type}->start->icon;
						else if (@$labels->{$type}->new->icon)
							$icon = $labels->{$type}->new->icon;
						else if (@$labels->{$type}->update->icon)
							$icon = $labels->{$type}->update->icon;
						else
							$icon = null;
						
						if (is_object($label)){
							if (!empty($label->{$rel}))
								$label = $label->{$rel};
							else {
								$label = (array) $label;
								$label = array_shift($label);
							}
						}
						
						$str = array();
						if (!empty($s['current'])){
							if (in_array($type, array('location', 'object')))
								$str[] = '<i>'.$s['current']['note'].'</i>';
							else if (!empty($s['current']['amount']))
								$str[] = print_amount($s['current']['amount'], $s['current']['unit']);
							else
								foreach ($s['current'] as $e)
									$str[] = get_entity_title_html($e, array('icon' => true));
						}
						if (!empty($s['past'])){
							if (in_array($type, array('location', 'object')))
								$str[] = '<i>'.$s['past']['note'].'</i>';
							else if (!empty($s['past']['amount']))
								$str[] = print_amount($s['past']['amount'], $s['past']['unit']);
							else
								foreach ($s['past'] as $e)
									$str[] = get_entity_title_html($e, array('icon' => true));
						}
						$lines[] = array(
							'icon' => $icon,
							'label' => $label,
							'html' => plural($str),
						);
					}
					
			}
			if ($lines){
				echo '<table class="sheet-summary-table">';
				foreach ($lines as $c){
					$class = '';
					if (!empty($c['hidden']))
						$class = 'hidden';
						
					echo '<tr class="'.$class.'"><td class="sheet-summary-label">'.($c['icon'] ? '<i class="fa fa-'.$c['icon'].'"></i> ' : '').$c['label'].': </td><td class="sheet-summary-value">'.$c['html'].'</td></tr>';
				}
				echo '</table>';
			}
			?>
		</div>
		<div class="sheet-content">
			<div class="entity-statuses-wrap">
				<div class="entity-statuses">
					<?php print_entity_statuses($entity['activity'], $entity, array('id' => $entity['id'])); ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php

print_footer();
