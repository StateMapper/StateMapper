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



add_action('entity_stats_before', 'kaosSchemaLinks');
function kaosSchemaLinks($entity){
	if ($entity['type'] != 'institution')
		return;

	$schemas = kaosAPIGetSchemas($entity['country']);

	foreach ($schemas as $schema)
		if (($s = kaosGetSchema($schema)) && $s->name == $entity['name']){

			if (!empty($s->siteUrl)){
				?>
				<div class="entity-sheet-detail entity-medias-suggs">
					<span class="entity-sheet-label">Website: </span>
					<div class="entity-sheet-body">
						<div class="entity-medias-suggs-inner">
							<a href="<?= kaosAnonymize($s->siteUrl) ?>" target="_blank"><?= getPrintDomain($s->siteUrl) ?></a>
						</div>
					</div>
				</div>
				<?php
			}

			$done = array();
			foreach ($schemas as $schema)
				if (($ss = kaosGetSchema($schema)) && $ss->type == 'bulletin' && $ss->providerId == $s->id && !in_array($s->id, $done)){
					$done[] = $s->id;

					?>
					<div class="entity-sheet-detail entity-medias-suggs">
						<span class="entity-sheet-label">Bulletins: </span>
						<div class="entity-sheet-body">
							<div class="entity-medias-suggs-inner">
								<a href="<?= kaosGetUrl(array(
									'schema' => $ss->id,
								), 'schema') ?>"><i class="fa fa-book"></i> <?= ($ss->name.(!empty($ss->shortName) ? ' ('.$ss->shortName.')' : '')) ?></a>
								<?php
									if (!empty($ss->siteUrl)){ ?>
										<a class="revert-color" href="<?= kaosAnonymize($ss->siteUrl) ?>" target="_blank"><?= getPrintDomain($ss->siteUrl) ?></a>
									<?php
									}
									if (!empty($ss->searchUrl)){ ?>
										<a class="revert-color" href="<?= kaosAnonymize($ss->searchUrl) ?>" target="_blank">official browser</a>
									<?php
									}
								?>
							</div>
						</div>
					</div>
					<?php
				}

			break;
		}
}
