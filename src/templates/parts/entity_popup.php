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
	
?>
<div class="lists-container entity-popup-content" <?= related(array('entity_id' => $entity['id'], 'in_my_lists' => get_my_lists_for_entity($entity['id']))) ?>>
	<?php
	if ($actions = get_entity_actions_html($entity, 'popup'))
		print_actions_menu($entity, $actions, 'entity-action', 'entity-popup-actions-wrap entity-actions-wrap', 'popup');
		
	if ($entity['summary']){
		echo '<div class="entity-summary">';
		
		$details = array();
		if (0) // @todo: reimplement
		foreach ($entity['summary'] as $id => $e){
			if (!empty($e['icon']))
				$e['html'] = '<span class="inline-links" title="'.esc_attr($e['label']).'" data-tippy-placement="right"><span><i class="fa fa-'.$e['icon'].' icon"></i> '.$e['html'].'</span></span>';
			$details[] = '<div class="entity-detail-icon entity-popup-detail-'.$id.'">'.$e['html'].'</div>';
		}

		echo '<div class="entity-details entity-popup-details">'.implode('', $details).'</div>';
		
		echo '</div>';
	}
	
	?>
	<div class="entity-lists-add-wrap"></div>
</div>
