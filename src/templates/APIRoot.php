<?php

if (!defined('BASE_PATH'))
	die();

global $kaosCall;

ob_start();
?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.kaos-schema-table-actions').find('button, input[type="button"]').click(function(e){
			var action = jQuery(this).data('kaos');
			var schema = jQuery(this).closest('tr').data('kaos').toLowerCase();
			var date = jQuery.trim(jQuery('.kaos-input-date').val());
			
			var url = '<?= BASE_URL ?>api/'+schema+'/'+(jQuery(this).hasClass('kaos-action-no-date') || date == '' ? '' : date)+(action != 'filter' ? '/'+action : '');
			
			if (e.ctrlKey)
				window.open(url);
			else
				location.href = url;
		});
	});
</script>

	<div class="kaos-input-date-wrap">										
		<?php if ($kaosCall['filter']){ ?>
			<span class="kaos-schemas-filter"><span class="kaos-schemas-filter-label"><span class="fa fa-filter"></span> <?= _('Filter') ?>: </span><?= kaosGetCountrySchema($kaosCall['filter'])->originalName ?> <a href="<?= BASE_URL.'api' ?>"><i class="fa fa-times"></i></a></span>
		<?php } ?>
	</div>
	<table border="0" cellpadding="0" cellspacing="0" class="kaos-table kaos-table-bulletins">
		<?php 
		$last = null;
		$country = null;
		foreach ($kaosCall['schemas'] as $path){ 
			$schema = kaosGetSchema($path);
			if ($schema->type == 'country')
				$country = $schema;

			$level = preg_match_all('#/#', $path) + 1;
			if ($schema->type != 'continent' && $country)
				$level++;
				
			$innerLevel = 0;
			
			if ($schema->type == 'bulletin' 
				&& $last 
				&& !empty($schema->providerId) 
				&& $schema->providerId == $last->id){
				
				$level++;
				$innerLevel++;
			}
			?>
			<tr data-kaos="<?= $path ?>" class="kaos-schema-type-<?= htmlentities($schema->type) ?> kaos-schema-level-<?= $level ?>">
				<td><?php
				if (empty($schema))
					echo '<i class="fa fa-warning" style="color: red"></i>';
				else if ($schema->type == 'bulletin'){
					if (!empty($schema->onProgress))
						echo '<i class="fa fa-warning" style="color: orange"></i>';
					else
						echo '<i class="fa fa-check" style="color: #09d91b"></i>';
				}
				?></td>
				<td>
					<div class="kaos-schema-title">
						<div class="kaos-schema-picture">
							<?php 
							if (in_array($schema->type, array('continent', 'country'))){ 
								if (file_exists(ASSETS_PATH.'/images/flags/'.$schema->id.'.png')){
									?>
									<img src="<?= ASSETS_URL.'/images/flags/'.$schema->id.'.png' ?>" />
									<?php
								}
								
							} else if (file_exists(SCHEMAS_PATH.'/'.$path.'.png')){ 
								?>
								<img src="<?= BASE_URL.'schemas/'.$path.'.png' ?>" />
								<?php
							} 
							?>
						</div>
						<div class="kaos-schema-intro"><?= ucfirst($schema->type) ?></div>
						<div class="kaos-schema-name">
							<?php 
							$name = $schema->name;
							if (!empty($schema->shortName) && !preg_match('#\b'.preg_quote($schema->shortName, '#').'\b#ius', $schema->name))
								$name .= ' ('.$schema->shortName.')';
							if (!empty($schema->searchUrl))
								echo '<a title="'.esc_attr('Go to the bulletin\'s original search form: '.$schema->searchUrl).'" href="'.kaosAnonymize($schema->searchUrl).'" target="blank">'.$name.'</a>';
							else if (!empty($schema->siteUrl))
								echo '<a title="'.esc_attr('Go to the provider\'s website: '.$schema->siteUrl).'" href="'.kaosAnonymize($schema->siteUrl).'" target="blank">'.$name.'</a>';
							else if (empty($kaosCall['rootSchema']) && preg_match('#^([A-Z]+)$#', $schema->id))
								echo '<a title="'.esc_attr('Only show entries from '.$schema->name).'" href="'.BASE_URL.'api/'.strtolower($schema->id).'">'.$name.'</a>';
							else
								echo $name;
							?>
						</div>
					</div>
				</td>
				<td class="kaos-schema-table-actions-td">
					<div class="kaos-schema-table-actions">
						<a href="<?= kaosGetUrl($schema->id, 'schema') ?>"title="Show the bulletin's definition <br>(<?= $path ?>)" class="kaos-action-no-date"><i class="fa fa-book"></i><span>Schema</span></a>
						
						<?php if ($schema->type == 'bulletin'){ ?>
							<a href="<?= kaosGetUrl($schema->id, 'fetch') ?>" title="Download and display bulletins" class="<?php if (empty($schema->fetchProtocoles)) echo ' kaos-top-action-disabled'; ?>"><i class="fa fa-cloud-download"></i><span>Fetch</span></a>
							
							<a href="<?= kaosGetUrl($schema->id, 'parse') ?>" title="Display parsed information from the bulletin" class="<?php if (empty($schema->parsingProtocoles)) echo ' kaos-top-action-disabled'; ?>"><i class="fa fa-tree"></i><span>Parse</span></a>
							
							<a href="<?= kaosGetUrl($schema->id, 'extract') ?>" title="Extract important parts from the parsed bulletin" class="<?php if (empty($schema->extractProtocoles)) echo ' kaos-top-action-disabled'; ?>"><i class="fa fa-magic"></i><span>Extract</span></a>
								
							<a href="<?= kaosGetUrl($schema->id, 'rewind') ?>" title="Fetch, parse and extract many years from a single page" class="<?php if (empty($schema->fetchProtocoles)) echo ' kaos-top-action-disabled'; ?>"><i class="fa fa-backward"></i><span>Rewind</span></a>
							
							<?php
							if (isAdmin()){
								$status = getSpiderStatus($schema->id, 'stopped');
								if ($status != 'stopped'){
									$count = 0;
									kaosWorkersStats($count, $schema->id);
									?>
									<a href="<?= kaosGetUrl($schema->id, 'rewind') ?>" class="kaos-bulletin-spider-ind" title="<?= $schema->id ?> spider is active with <?= $count ?> workers"><i class="fa fa-bug"></i><span><?= $count ?></span></a>
									<?php
								}
							}
							?>
							
						<?php } else if (in_array($schema->type, array('continent', 'country')) && $kaosCall['filter'] != $schema->id){ ?>
								<a href="<?= kaosGetUrl($schema->id) ?>" title="Show only entries for this <?= $schema->type ?>"><i class="fa fa-filter"></i><span>Filter</span></a>
						<?php } ?>
					</div>
				</td>
				<td>
					<div class="kaos-schema-table-actions">
					<?php if (in_array($schema->type, array('institution', 'bulletin'))){ ?>
						<a href="<?= kaosGetUrl($schema->id, 'soldiers') ?>" title="" class="<?php if (empty($schema->soldiers)) echo ' kaos-top-action-disabled'; ?>"><i class="fa fa-fire"></i><span>Soldiers</span></a>
					<?php } else { ?>
						<span>&nbsp;</span>
					<?php } ?>
					<?php if (in_array($schema->type, array('country', 'europe'))){ ?>
						<a href="<?= kaosGetUrl($schema->id, 'ambassadors') ?>" title="" class="<?php if (empty($schema->ambassadors)) echo ' kaos-top-action-disabled'; ?>"><i class="fa fa-globe"></i><span>Ambass.</span></a>
					<?php } else { ?>
						<span>&nbsp;</span>
					<?php } ?>
					</div>
				</td>
							
				<?php /*
				<!--
				<td class="kaos-schema-table-files">
					<?php
					$str = array();
					if (in_array($schema->type, array('bulletin', 'country'))){ 
						$output = array();
						
						// print amount of files
						$output = array();
						//exec('find "'.BASE_PATH.'/data/'.$path.'" -type f | grep "/byDate/" | grep -v ".pdf."  | grep -v ".xml." | wc -l', $output, $returnVar);
						if (0 && empty($returnVar) && $output && is_numeric($output[0])){
							$cstr = '<i class="fa fa-files-o"></i> '.number_format(intval($output[0]));
							/*
							$output = array();
							exec('find "'.BASE_PATH.'/data/'.$path.'" -type f | grep -v ".pdf."  | grep -v ".xml." | wc -l', $output, $returnVar);
							if (empty($returnVar) && $output && is_numeric($output[0])){
								$cstr .= ' <span class="kaos-schema-sub-bulletins">('.number_format(intval($output[0])).')</span>';
							}
							$str[] = $cstr.$rewind;
							$rewind = '';
							*
							// print folder size on disk
							if (0){
								$output = array();
								exec('du -hs "'.BASE_PATH.'/data/'.$path.'"', $output, $returnVar);
								if (empty($returnVar) && $output)
									$str[] = '<i class="fa fa-hdd-o"></i> '.preg_replace('#^(\S+)(.*?)$#', '$1', $output[0]);
							}
						} 
					} 
					if ($str) 
						echo implode('</td><td class="kaos-schema-table-size">', $str);
					if (count($str) < 2)
						echo '</td><td class="kaos-schema-table-size">';
					?>
				</td>
				*/ ?>
			</tr>
			<?php 
			if (!$innerLevel)
				$last = $schema;
		} ?>
	</table>
<?php 

return ob_get_clean();

