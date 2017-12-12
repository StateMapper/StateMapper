<?php

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
