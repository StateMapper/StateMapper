<?php

if (!defined('BASE_PATH'))
	die();


global $kaosCall, $kaosPage;

if (!empty($vars))
	extract($vars);

if (!empty($kaosCall['mem']['wrapPrinted'])){
	kaosReturnPrintInner($obj);
	return;
}

$kaosCall['mem']['wrapPrinted'] = true;

$cLevel = preg_match('#^(.*?)(/([0-9]+))(/raw)?(/?)$#i', kaosCurrentURL(), $m) ? intval($m[3]) : 0;

$apiCallsOrder = array('fetch', 'lint', 'parse', 'extract');

$query = isset($kaosCall['query']) ? $kaosCall['query'] : null;

//$pastDayUrl = !empty($query['date']) ? preg_replace('#^(.*?)(/'.$query['date'].')?(/'.$kaosCall['call'].'(/.*)?)$#', '$1/'.date('Y-m-d', strtotime('-1 day', strtotime($query['date']))).'$3', kaosCurrentURL(true)) : '';

// TODO: add continent too
$country = null;
if (!empty($kaosCall['schemaObj'])){
	if (in_array($kaosCall['schemaObj']->type, array('country', 'continent')))
		$country = $kaosCall['schemaObj']->id;
	else if (!empty($kaosCall['schemaObj']->country))
		$country = $kaosCall['schemaObj']->country;
	else if (!empty($kaosCall['schemaObj']->continent))
		$country = $kaosCall['schemaObj']->continent;
	else if (!empty($kaosCall['schemaObj']->providerId)
		&& ($s = kaosGetSchema($kaosCall['schemaObj']->providerId))
		&& !empty($s->country)){
		$country = $s->country;
		if (is_object($country))
			$country = $country->id;
	} 
}

if (!empty($kaosCall['pageTitle']))
	$title = $kaosCall['pageTitle'];
else if (!empty($kaosCall['schemaObj']))
	$title = kaosGetSchemaTitle($kaosCall['schemaObj'], $kaosCall['query']);
else if ($kaosPage == 'api')
	$title = 'Bulletin providers & bulletins';
else if ($kaosPage == 'settings')
	$title = 'Settings';

$avatar = null;	
if ($kaosCall && !empty($kaosCall['schemaObj']) && in_array($kaosCall['schemaObj']->type, array('country', 'continent')) && $country){
	if ($avatarUrl = kaosGetFlagUrl($country))
		$avatar = '<a href="'.kaosGetUrl(array(
			'filter' => $country
		)).'"><img class="kaos-api-result-title-bulletin-avatar" src="'.$avatarUrl.'" /></a>';
	$country = null;

} else if (!empty($kaosCall['query']['schema'])){
	if (file_exists(SCHEMAS_PATH.'/'.$kaosCall['query']['schema'].'.png')){ 
		$avatar = '<img class="kaos-api-result-title-bulletin-avatar" src="'.BASE_URL.'schemas/'.$kaosCall['query']['schema'].'.png" />';
	}
} 

if (!isset($title))
	$title = 'Error';


?><!DOCTYPE html>
	<html class="<?php
		echo 'kaos-call-type-'.(!empty($kaosCall['call']) ? $kaosCall['call'] : 'none');
		
		echo ' kaos-call-schema-'.(!empty($kaosCall['query']) && !empty($kaosCall['query']['schema']) ? $kaosCall['query']['schema'] : 'none');
	?>">
		<head>
			<?php head($title); ?>
		</head>
		<body class="<?php
			if (!empty($kaosCall['isIframe']))
				echo 'kaos-api-is-iframe';
		?>">
			<div id="main">
				<div class="kaos-api-result-header<?php echo ' kaos-header-avatar-'.($avatar ? 'has' : 'none'); ?>">
					<div class="kaos-api-result-intro">
						<?php logo() ?>
						<div class="kaos-api-result-intro-left">
							<div class="kaos-api-result-title"><?php
								if ($avatar)
									echo $avatar;
								?>
								<div class="kaos-api-result-title-inner">
									<?php if ($country){ ?>
										<div class="kaos-api-result-title-country">
										<?php
											if (file_exists(APP_PATH.'/assets/images/flags/'.$country.'.png')){
												?>
												<img class="kaos-api-result-title-flag" src="<?= ASSETS_URL.'/images/flags/'.$country.'.png' ?>" />
												<?php
											}
											?>
											<span><?= kaosGetSchema($country)->name ?></span>
											<?php
											if (!empty($kaosCall['schemaObj']) && !empty($kaosCall['schemaObj']->providerId) && ($provider = kaosGetSchema($kaosCall['schemaObj']->providerId))){
												echo ' <i class="fa fa-angle-right"></i> ';
												$providerName = !empty($provider->shortName) ? $provider->shortName : $provider->name;
												
												echo '<span title="'.esc_attr($provider->name).'">';
												if (strlen($providerName) > 40)
													echo substr($providerName, 0, 35).'...';
												else
													echo $providerName;
												echo '</span>';
											}
										?>
										</div>
									<?php } 
									
									$hasMenu = isAdmin() && !empty($kaosCall['schemaObj']) && $kaosCall['schemaObj']->type == 'bulletin';
									?>
									<div class="kaos-api-result-title-title header-title<?php if ($hasMenu) echo ' header-title-menued'; ?>">
										<?php if ($hasMenu){ ?>
											<div class="header-title-menu menu-right">
												<div class="menu-wrap">
													<div class="menu-menu">
														<ul class="menu-inner">
															<li><a href="#" class="kaos-ajax" data-kaos-ajax="deleteExtractedData" data-kaos-confirm="Are you sure you want to DELETE ALL EXTRACTED DATA from ALL BULLETINS? This CANNOT be undone!">Delete all extracted data</a></li>
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
									<div class="kaos-api-result-title-call"><?php
									if (isset($kaosCall['call']))
										switch ($kaosCall['call']){
											case 'schema':
												echo '<i class="fa fa-book"></i> Schema';
												break;
											case 'fetch':
												echo '<i class="fa fa-cloud-download"></i> Fetching';
												break;
											case 'lint':
												echo '<i class="fa fa-font"></i> Lint';
												break;
											case 'parse':
												echo '<i class="fa fa-tree"></i> Parsing';
												break;
											case 'extract':
												echo '<i class="fa fa-magic"></i> Extraction';
												break;
											case 'rewind':
												echo '<i class="fa fa-backward"></i> Rewind map';
												break;
											case 'soldiers':
												echo '<i class="fa fa-fire"></i> Schema Soldiers';
												break;
											case 'ambassadors':
												echo '<i class="fa fa-globe"></i> Country Ambassadors';
												break;
										}
									?></div>
								</div>
							</div>
						</div>
						<?= headerRight() ?>
					</div>
				</div>
				<?php include(APP_PATH.'/templates/filters.php'); ?>
				<div class="kaos-api-result-body kaos-api-result-body-type-<?= (!empty($kaosCall['call']) ? $kaosCall['call'] : 'home') ?>">
					<?php 
					$isError = $obj && is_array($obj) && empty($obj['success']);
					
					if ($kaosPage == 'api' || $isError){ ?>
						<div class="kaos-body-intro-help<?php if ($isError) echo ' kaos-body-intro-error'; ?>"><i class="fa fa-<?= ($isError ? 'warning' : 'info-circle') ?>"></i> <?php
						if ($isError)
							echo 'ERROR'.(!empty($obj['error']) ? ': '.kaosEscapeString($obj['error']) : '');
						else if (isset($kaosCall['call']))
							switch ($kaosCall['call']){
								case 'schema':
									echo 'Schemas are definition files for each bulletin, institution, country and continents. It holds the fetching, parsing and extracting protocoles as well as languages and legal definitions.';
									break;
								case 'fetch':
									echo 'Fetching is the action of downloading and archiving a bulletin for later use (parsing and extracting). ';
									if (!empty($kaosCall['query']['id']))
										echo 'Below is the '.kaosGetFormatLabel($kaosCall['query'], 'document').' <strong>'.$kaosCall['query']['id'].'</strong> from bulletin of <strong><a href="'.kaosGetUrl(array(
												'date' => $kaosCall['query']['date'],
												'schema' => $kaosCall['query']['schema']
											), 'fetch').'">'.date_i18n('M j, Y', strtotime($kaosCall['query']['date'])).'</a></strong>.';
									else {
										echo 'Below is the bulletin\'s '.kaosGetFormatLabel($kaosCall['query'], 'document').' from <strong>'.date_i18n('M j, Y', strtotime($kaosCall['query']['date'])).'</strong>.';
										
										$bs = array();
										foreach (query('SELECT DISTINCT b_id.external_id, b_id.status, b_id.fetched, b_id.parsed, b_id.done FROM bulletins AS b LEFT JOIN bulletin_uses_bulletin AS bb ON b.id = bb.bulletin_in LEFT JOIN bulletins AS b_id ON bb.bulletin_id = b_id.id WHERE b.bulletin_schema = %s AND b.date = %s AND b_id.external_id IS NOT NULL', array($kaosCall['query']['schema'], $kaosCall['query']['date'])) as $doc){
											
											$docname = preg_replace('#'.preg_quote($kaosCall['schemaObj']->shortName, '#').'#', '', $doc['external_id']);
											$docname = preg_replace('#-+#', '-', $docname);
											$docname = preg_replace('#^-?(.*)-?$#', '$1', $docname);
											
											$bs[] = '<li><a href="'.kaosGetUrl(array(
												'date' => $kaosCall['query']['date'],
												'id' => $doc['external_id'],
												'schema' => $kaosCall['query']['schema']
											), 'fetch').'" title="'.$doc['status'].'">'.(in_array($doc['status'], array('fetched')) ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>').' '.$docname.'</a></li>';
										}
										
										if ($bs)
											echo '<div class="top-help-related-documents">Related documents: <div class="top-help-related-documents-links"><ul>'.implode('', $bs).'</ul></div></div>';
									}
									break;
								case 'lint':
									echo 'Linting is the action of converting binay files (like PDF) into textual content, so that parsing can be done. This step is currently only useful for PDF files.';
									break;
								case 'parse':
									echo 'Parsing is the action of understanding the bulletin by isolating and refactoring each peace of information in it. Parsing also allows to fetch-follow (fetch documents found in the parsed object).';
									
									if (!empty($_GET['precept']))
										echo '<div class="top-alert-filter">Only showing parts about precept "'.htmlentities(kaosGetFilter()).'". <a href="'.remove_url_arg('precept').'">remove filter</a></div>';
									else if (!empty($_GET['filter']))
										echo '<div class="top-alert-filter">Only showing parts with titles containing "'.htmlentities(kaosGetFilter()).'". <a href="'.remove_url_arg('filter').'">remove filter</a></div>';
										
									break;
								case 'extract':
									echo 'Extraction is where all the useful information from the parsed object is normalized into small entities the software knows how to handle. This allows to query the information in a fast and logical manner.';
									break;
								case 'rewind':
									echo 'Rewinding is the step where you get to fetch all documents for as long as you can.';
									break;
								case 'soldiers':
									echo 'The Soldiers are the developers that implement and maintain the bulletins\' schemas. More information about StateMapper\'s commissions <a href="'.kaosAnonymize('https://github.com/'.KAOS_GITHUB_REPOSITORY.'#contribute').'" target="_blank">here</a>.';
									break;
								case 'ambassadors':
									echo 'Ambassadors are social collectives that host all bulletins of one country, check their integrity, and maintain translations. More information about StateMapper\'s commissions <a href="'.kaosAnonymize('https://github.com/'.KAOS_GITHUB_REPOSITORY.'#contribute').'" target="_blank">here</a>.';
									break;
							}
						else
							echo 'Below are shown all the currently available bulletins.';
						?></div>
						<?php 
					} 
					?>
					<div class="kaos-api-result-body-inner">
						<?php kaosReturnPrintInner($obj); ?>
					</div>
					<?php
						// ?rewind=1 mode (jumping backward, day after day, in extract mode)
						if (!empty($kaosCall['call']) && $kaosCall['call'] == 'extract' && !empty($_GET['rewind'])){
							$args = array('date' => date('Y-m-d', strtotime('-1 day', strtotime($kaosCall['query']['date'])))) + $kaosCall['query'];
							?>
							<script>
								setTimeout(function(){
									window.location = "<?= kaosGetUrl($args, 'extract') ?>";
								}, 2000); // 2s
							</script>
							<?php
						}
					?>
				</div>
				<?php footer() ?>
			</div>
		</body>
	</html><?php 

