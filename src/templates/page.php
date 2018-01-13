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


if (!empty($smap['mem']['wrapPrinted'])){
	print_inner($obj);
	return;
}

$smap['mem']['wrapPrinted'] = true;

$cLevel = preg_match('#^(.*?)(/([0-9]+))(/raw)?(/?)$#i', current_url(), $m) ? intval($m[3]) : 0;

$apiCallsOrder = array('fetch', 'lint', 'parse', 'extract');

$query = isset($smap['query']) ? $smap['query'] : null;

//$pastDayUrl = !empty($query['date']) ? preg_replace('#^(.*?)(/'.$query['date'].')?(/'.$smap['call'].'(/.*)?)$#', '$1/'.date('Y-m-d', strtotime('-1 day', strtotime($query['date']))).'$3', current_url(true)) : '';

if ($smap && !empty($smap['schemaObj']))
	$schemaObj = $smap['schemaObj'];
else if (!empty($smap['filters']['loc']))
	$schemaObj = get_country_schema($smap['filters']['loc']);
else
	$schemaObj = null;

$country = null;
$avatar = null;	
$title = get_page_title();

if (!defined('IS_ERROR') || !IS_ERROR){
	if ($schemaObj){
		if (in_array($schemaObj->type, array('country', 'continent')))
			$country = $schemaObj->id;
		else if (!empty($schemaObj->country))
			$country = $schemaObj->country;
		else if (!empty($schemaObj->continent))
			$country = $schemaObj->continent;
		else if (!empty($schemaObj->providerId)
			&& ($s = get_schema($schemaObj->providerId))
			&& !empty($s->country)){
			$country = $s->country;
			if (is_object($country))
				$country = $country->id;
		} 
	} else if (!empty($smap['filters']['loc']))
		$country = strtoupper($smap['filters']['loc']); 
		
	if ($schemaObj && in_array($schemaObj->type, array('country', 'continent')) && $country){
		if ($avatarUrl = get_flag_url($country))
			$avatar = '<a data-tippy-placement="bottom" title="'.esc_attr($title).'" href="'.url(array(
				'country' => $country
			)).'"><img class="header-center-bulletin-avatar" src="'.$avatarUrl.'" /></a>';
		$country = null;

	} else if (!empty($smap['query']['schema'])){
		if (file_exists(SCHEMAS_PATH.'/'.$smap['query']['schema'].'.png')){ 
			$avatar = '<img data-tippy-placement="bottom" title="'.esc_attr($title).'" class="header-center-bulletin-avatar" src="'.BASE_URL.'schemas/'.$smap['query']['schema'].'.png" />';
		}
	}
}

print_template('parts/header');
?>

<div class="main-header<?php echo ' header-avatar-'.($avatar ? 'has' : 'none'); ?>">
	<div class="main-header-inner">
		<?php print_template('parts/header_logo') ?>
		<div class="header-center-wrap">
			<div class="header-center"><?php
				if ($avatar)
					echo $avatar;
				?>
				<div class="header-center-inner">
					<?php if ($country){ ?>
						<div class="header-center-country">
						<?php
							if ($cavatarUrl = get_flag_url($country)){
								?>
								<img data-tippy-placement="bottom" title="<?= get_schema($country)->name ?>" class="header-center-flag" src="<?= $cavatarUrl ?>" />
								<?php
							}
							?>
							<span><a data-tippy-placement="bottom" title="<?= esc_attr(get_schema($country)->type == 'continent' ? _('See continent schema') : _('See country schema')) ?>" class="clean-links" href="<?= url($country, 'schema') ?>"><?= get_schema($country)->name ?></a></span>
							<?php
							if ($schemaObj && !empty($schemaObj->providerId) && ($provider = get_schema($schemaObj->providerId))){
								echo ' <i class="fa fa-angle-right"></i> ';
								$providerName = !empty($provider->shortName) ? $provider->shortName : $provider->name;
								
								echo '<span><a data-tippy-placement="bottom" title="'.esc_attr(_('See provider schema')).'" class="clean-links" href="'.url($provider->id, 'schema').'" title="'.esc_attr($provider->name).'">';
								if (strlen($providerName) > 40)
									echo substr($providerName, 0, 35).'...';
								else
									echo $providerName;
								echo '</a></span>';
							}
						?>
						</div>
					<?php } 
					
					$hasMenu = is_admin() && $schemaObj && $schemaObj->type == 'bulletin';
					?>
					<div class="header-center-title header-title<?php if ($hasMenu) echo ' header-title-menued'; ?>">
						<?php if ($hasMenu){ ?>
							<div class="header-title-menu menu-right">
								<div class="menu-wrap">
									<div class="menu-menu">
										<ul class="menu-inner">
											<li><a href="#" class="smap-ajax" data-smap-ajax="deleteExtractedData" data-smap-confirm="Are you sure you want to DELETE ALL EXTRACTED DATA from ALL BULLETINS? This CANNOT be undone!">Delete all extracted data</a></li>
										</ul>
									</div>
								</div>
							</div>
						<?php } ?>
						<?= ($avatar ? '' : '<i class="fa fa-angle-right"></i> ').$title ?>
						<?php if ($hasMenu){ ?>
							<span class="header-title-menu-icon"><i class="fa fa-caret-down"></i></span>
						<?php } ?>
						</div>
					<div class="header-center-call"><?php
					
					$modes = get_modes();
					if (isset($smap['call'], $modes[$smap['call']])){
						$c = $modes[$smap['call']];
						echo '<i class="fa fa-'.$c['icon'].'"></i> '.(!empty($c['headerTitle']) ? $c['headerTitle'] : $c['title']);
					} else if ($smap['page'] == 'ambassadors'){
						$c = $modes[$smap['page']];
						echo '<i class="fa fa-'.$c['icon'].'"></i> '.(!empty($c['headerTitle']) ? $c['headerTitle'] : $c['title']);
					} else if ($smap['page'] == 'providers' && !empty($smap['filters']['loc'])){
						$c = $modes['providers'];
						echo '<i class="fa fa-'.$c['icon'].'"></i> '.(!empty($c['headerTitle']) ? $c['headerTitle'] : $c['title']);
					}
					?></div>
				</div>
			</div>
		</div>
		<?php print_template('parts/header_right') ?>
	</div>
</div>
<?php print_template('parts/filters'); ?>
<div class="main-body main-body-type-<?= (!empty($smap['call']) ? $smap['call'] : 'home') ?>">
	<?php 
	$isError = $obj && is_array($obj) && empty($obj['success']);
	
	if (in_array($smap['page'], array('bulletin', 'bulletins', 'ambassadors', 'providers')) || $isError){ ?>
		<div class="body-intro-help<?php if ($isError) echo ' body-intro-error'; ?>"><i class="fa fa-<?= ($isError ? 'warning' : 'info-circle') ?>"></i> <?php

		if ($isError)
			echo 'ERROR'.(!empty($obj['error']) ? ': '.esc_string($obj['error']) : '');
			
		else if ($smap['page'] == 'ambassadors')
			echo 'Ambassadors are social collectives that host all bulletins of one country, check their integrity, and maintain translations. More information about StateMapper\'s commissions <a href="'.anonymize('https://github.com/'.SMAP_GITHUB_REPOSITORY.'#contribute').'" target="_blank">here</a>.';
				
		else if ($smap['page'] == 'providers')
			echo 'Below are shown all the currently available '.(!empty($smap['filters']['loc'])
				? get_country_schema($smap['filters']['loc'])->adjective.' bulletins'
				: 'bulletins').'.';

		else if (isset($smap['call'])){
			switch ($smap['call']){
				case 'schema':
					echo 'Schemas are definition files for each bulletin, institution, country and continents. It holds the fetching, parsing and extracting protocoles as well as languages and legal definitions.';
					break;
				case 'fetch':
					echo 'Fetching is the action of downloading and archiving a bulletin for later use (parsing and extracting). ';
					if (!empty($smap['query']['id']))
						echo 'Below is the '.get_format_label($smap['query'], 'document').' <strong>'.$smap['query']['id'].'</strong> from bulletin of <strong><a href="'.url(array(
								'date' => $smap['query']['date'],
								'schema' => $smap['query']['schema']
							), 'fetch').'">'.date_i18n('M j, Y', strtotime($smap['query']['date'])).'</a></strong>.';
					else {
						echo 'Below is the bulletin\'s '.get_format_label($smap['query'], 'document').' from <strong>'.date_i18n('M j, Y', strtotime($smap['query']['date'])).'</strong>.';
						
						$bs = array();
						foreach (query('SELECT DISTINCT external_id, status, fetched, parsed, done FROM bulletins WHERE bulletin_schema = %s AND date = %s AND external_id IS NOT NULL', array($smap['query']['schema'], $smap['query']['date'])) as $doc){
							
							$docname = preg_replace('#'.preg_quote($schemaObj->shortName, '#').'#', '', $doc['external_id']);
							$docname = preg_replace('#-+#', '-', $docname);
							$docname = preg_replace('#^-?(.*)-?$#', '$1', $docname);
							
							$bs[] = '<li><a href="'.url(array(
								'date' => $smap['query']['date'],
								'id' => $doc['external_id'],
								'schema' => $smap['query']['schema']
							), 'fetch').'" title="'.$doc['status'].'">'.(in_array($doc['status'], array('fetched')) ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>').' '.$docname.'</a></li>';
						}
						
						if ($bs)
							echo '<div class="top-help-related-documents">Related documents: <div class="top-help-related-documents-links"><ul>'.implode('', $bs).'</ul></div></div>';
					}
					
					if (in_array($smap['call'], array('fetch')) && schema_has_feature($smap['query']['schema'], 'fetch')){ ?>
						<div class="header-intro-actions">
							<a href="<?= url($smap['query'], 'fetch/raw') ?>" title="<?= esc_attr($modes['fullscreen']['buttonTip']) ?>" class="<?= $class ?>"><i class="fa fa-<?= $modes['fullscreen']['icon'] ?>"></i><span><?= (!empty($modes['fullscreen']['shortTitle']) ? $modes['fullscreen']['shortTitle'] : $modes['fullscreen']['title']) ?></span></a>
							
							<a href="<?= url($smap['query'], 'download') ?>" title="<?= esc_attr($modes['download']['buttonTip']) ?>"><i class="fa fa-<?= $modes['download']['icon'] ?>"></i><span><?= (!empty($modes['download']['shortTitle']) ? $modes['download']['shortTitle'] : $modes['download']['title']) ?></span></a>
							
							<a href="<?= url(array('format' => get_format_by_query($smap['query'])) + $smap['query'], 'redirect') ?>" title="<?= esc_attr($modes['redirect']['buttonTip']) ?>" target="_blank"><i class="fa fa-<?= $modes['redirect']['icon'] ?>"></i><span><?= (!empty($modes['redirect']['shortTitle']) ? $modes['redirect']['shortTitle'] : $modes['redirect']['title']) ?></span></a>
						</div>
					<?php } 
					
					break;
				case 'lint':
					echo 'Linting is the action of converting binay files (like PDF) into textual content, so that parsing can be done. This step is currently only useful for PDF files.';
					
					if (in_array($smap['call'], array('fetch', 'lint')) && schema_has_feature($smap['query']['schema'], 'fetch') && show_mode('rewind')){ ?>
						<div class="header-intro-actions">

							<a href="<?= url($smap['query'], 'lint/raw') ?>" title="<?= esc_attr($modes['fullscreen']['buttonTip']) ?>"><i class="fa fa-<?= $modes['fullscreen']['icon'] ?>"></i><span><?= (!empty($modes['fullscreen']['shortTitle']) ? $modes['fullscreen']['shortTitle'] : $modes['fullscreen']['title']) ?></span></a>
							
							<a href="<?= url($smap['query'], 'download/txt') ?>" title="<?= esc_attr($modes['download']['buttonTip']) ?>"><i class="fa fa-<?= $modes['download']['icon'] ?>"></i><span><?= (!empty($modes['download']['shortTitle']) ? $modes['download']['shortTitle'] : $modes['download']['title']) ?></span></a>
							
							<a href="<?= url(array('format' => get_format_by_query($smap['query'])) + $smap['query'], 'redirect') ?>" title="<?= esc_attr($modes['redirect']['buttonTip']) ?>" target="_blank"><i class="fa fa-<?= $modes['redirect']['icon'] ?>"></i><span><?= (!empty($modes['redirect']['shortTitle']) ? $modes['redirect']['shortTitle'] : $modes['redirect']['title']) ?></span></a>
						</div>
						<?php
					}
					break;
					
				case 'parse':
					echo 'Parsing is the action of understanding the bulletin by isolating and refactoring each peace of information in it. Parsing also allows to fetch-follow (fetch documents found in the parsed object).';
					
					if (!empty($_GET['precept']))
						echo '<div class="top-alert-filter">Only showing parts about precept "'.htmlentities(get_filter()).'". <a href="'.remove_url_arg('precept').'">remove filter</a></div>';
					else if (!empty($_GET['filter']))
						echo '<div class="top-alert-filter">Only showing parts with titles containing "'.htmlentities(get_filter()).'". <a href="'.remove_url_arg('filter').'">remove filter</a></div>';
						
					break;
				case 'extract':
					echo 'Extraction is where all the useful information from the parsed object is normalized into small entities the software knows how to handle. This allows to query the information in a fast and logical manner.';
					break;
				case 'rewind':
					echo 'Rewinding is the step where you get to fetch all documents for as long as you can.';
					break;
				case 'soldiers':
					echo 'The Soldiers are the developers that implement and maintain the bulletins\' schemas. More information about StateMapper\'s commissions <a href="'.anonymize('https://github.com/'.SMAP_GITHUB_REPOSITORY.'#contribute').'" target="_blank">here</a>.';
					break;
			}
			
		} 
		?></div>
		<?php 
	} 
	?>
	<div class="main-body-inner">
		<?php print_inner($obj); ?>
	</div>
	<?php
		// ?rewind=1 mode (jumping backward, day after day, in extract mode)
		if (!empty($smap['call']) && $smap['call'] == 'extract' && !empty($_GET['rewind'])){
			$args = array('date' => date('Y-m-d', strtotime('-1 day', strtotime($smap['query']['date'])))) + $smap['query'];
			?>
			<script>
				setTimeout(function(){
					window.location = "<?= url($args, 'extract') ?>";
				}, 2000); // 2s
			</script>
			<?php
		}
	?>
</div>
<?php 

print_template('parts/footer');

return '';
