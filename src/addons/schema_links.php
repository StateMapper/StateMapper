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

add_filter('entity_stats_before', 'print_schema_links', 0, 2);
function print_schema_links($entity, $context){
	if ($entity['type'] != 'institution')
		return $entity;

	$schemas = get_schemas($entity['country']);

	foreach ($schemas as $schema)
		if (($s = get_schema($schema)) && $s->name == $entity['name']){
			
			// print a link to siteUrl
			if (!empty($s->siteUrl))
				$entity['summary']['website'] = array(
					'label' => 'Website',
					'html' => '<a href="'.anonymize($s->siteUrl).'" target="_blank">'.get_domain($s->siteUrl).'</a>',
				);

			$html = $done = array();
			foreach ($schemas as $schema)
				if (($ss = get_schema($schema)) && $ss->type == 'bulletin' && $ss->providerId == $s->id && !in_array($s->id, $done)){
					// print bulletins from the current provider
					
					$done[] = $s->id;
					
					ob_start();
					?>
					<div>
						<a href="<?= url(array(
							'schema' => $ss->id,
						), 'schema') ?>"><i class="fa fa-book"></i> <?= ($ss->name.(!empty($ss->shortName) ? ' ('.$ss->shortName.')' : '')) ?></a>
						<?php
							
						if (!empty($ss->siteUrl)){ ?>
							<a class="revert-color" href="<?= anonymize($ss->siteUrl) ?>" target="_blank"><?= get_domain($ss->siteUrl) ?></a>
						<?php
						}
						if (!empty($ss->searchUrl)){ ?>
							<a class="revert-color" href="<?= anonymize($ss->searchUrl) ?>" target="_blank">official browser</a>
						<?php
						}
						?>
					</div>
					<?php
					$html[] = ob_get_clean();
					
				}
			
			if ($html)
				$entity['summary']['bulletins'] = array(
					'label' => count($html) > 1 ? 'Bulletins' : 'Bulletin',
					'html' => implode("\n", $html),
				);
			break;
		}
		
	return $entity;
}
