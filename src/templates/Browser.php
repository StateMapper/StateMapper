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

global $kaosCall, $kaosPage;
$kaosCall['mem']['wrapPrinted'] = true;

if (!empty($vars))
	extract($vars);

$country = null;

$title = false;
if (!empty($entity))
	$title = kaosGetEntityTitle($entity, false, true);
else if (!empty($_GET['q']))
	$title = sprintf(_('Results for "%s"'), htmlentities($_GET['q']));
else if (!empty($_GET['etype'])){
	$title = _('Search results');

	$etype = explode(' ', $_GET['etype']);
	$types = getEntityTypes();
	if (count($etype) == 1 && isset($types[$etype[0]]))
		$title = $types[$etype[0]]['title'];
}

add_js('Browser');

ob_start();
?>
<div class="kaos-browser-title-title">
	<form action="<?= BASE_URL ?>" method="GET">
		<span class="kaos-search-icon"><i class="fa fa-search"></i></span>
		<input type="text" autocomplete="off" name="q" id="kaosSearch" placeholder="<?php
			if (!isHome(true))
				echo esc_attr(_('Lookup for a company, a person..'));
			?>" value="<?= esc_attr($kaosCall['currentQuery']) ?>" />
		<div class="kaosSearchSugg">
			<div class="kaosSearchSugg-inner">
				<div class="kaosSearchSugg-loading-msg">
					<div><i class="fa fa-circle-o-notch fa-spin"></i> <?= _('Loading') ?>..</div>
				</div>
				<div class="kaosSearchSugg-results">
					<div class="kaosSearchSugg-results-inner"></div>
					<div class="kaosSearchSugg-results-more"></div>
				</div>
			</div>
		</div>
	</form>
</div>
<?php
$searchInput = ob_get_clean();


?><!DOCTYPE html>
	<html class="">
		<head>
			<?php head($title); ?>
		</head>
		<body class="<?php
			if (!empty($entity))
				echo 'browser-found ';
			if (hasFilter())
				echo ' kaos-filters-open';
			else if (isHome(true))
				echo ' kaos-root';
		?>">
			<div id="main">
				<?php if (isHome(true)){ ?>
					<div class="bg-diag-left"></div>
					<div class="bg-stripes-wrap">
						<div class="bg-stripes bg-stripes-left"></div>
						<div class="bg-stripes bg-stripes-right"></div>
					</div>
					<div class="bg-diag-right"></div>
				<?php } ?>
				<div id="main-inner">
					<div class="kaos-api-result-header">
						<div class="kaos-api-result-intro">
							<?php logo() ?>
							<div class="kaos-api-result-intro-left">
								<div class="kaos-api-result-title"><?php
									if (!empty($kaosCall['query']['schema'])){
										if (file_exists(SCHEMAS_PATH.'/'.$kaosCall['query']['schema'].'.png')){
											?>
											<img class="kaos-api-result-title-bulletin-avatar" src="<?= BASE_URL.'schemas/'.$kaosCall['query']['schema'].'.png' ?>" />
											<?php
										}
									}

									?>
									<div class="kaos-browser-title-inner">
										<?php
										if (!isHome(true))
											echo $searchInput;
										if (!hasFilter())
											echo '<span class="kaos-top-filter-ind" title="'.esc_attr(_('Add a filter')).'">+ '._('Filters').'</span>';
										else
											echo '<a title="'.esc_attr(_('Remove all filters')).'" class="kaos-top-filter-ind" href="'.BASE_URL.(!empty($_GET['q']) ? '?q='.$_GET['q'] : '').'">- '._('Filters').'</a>';
										?>
									</div>
								</div>
							</div>
							<?= headerRight() ?>
						</div>
					</div>
					<?php include(APP_PATH.'/templates/filters.php'); ?>
					<div class="kaos-api-result-body kaos-api-result-body-type-<?= (!empty($kaosCall['call']) ? $kaosCall['call'] : 'home') ?>">
						<div class="kaos-api-result-body-inner">
							<div id="wrap">
								<?php
								
								if ($kaosCall['currentQuery'] != '' || (hasFilter() && empty($kaosCall['entity']))){

									ob_start();
									if (!empty($kaosCall['results'])){
										?>
										<div class="search-results-intro search-results-intro-has"><?php
											$c = count($kaosCall['results']);
											$total = number_format($kaosCall['resultsCount'], 0);
											if (!empty($kaosCall['currentQuery'])){
												
												if ($kaosCall['resultsCount'] == $c)
													echo sprintf(_('%s results for query "%s"'), number_format($c, 0), htmlentities($kaosCall['currentQuery'])).':';
												else
													echo sprintf(_('%s results out of %s for query "%s"'), number_format($c, 0), $total, htmlentities($kaosCall['currentQuery'])).':';
													
											} else if (!empty($_GET['misc']) && $_GET['misc'] == 'buggy'){
												
												if ($c != $total)
													echo sprintf(_('%s %s out of %s that may be buggy'), number_format($c, 0), kaosGetCompanyFilter(), $total).':';
												else
													echo sprintf(_('%s %s that may be buggy'), number_format($c, 0), kaosGetCompanyFilter()).':';
											
											} else {
												
												if ($c != $total)
													echo sprintf(_('%s %s out of %s'), number_format($c, 0), kaosGetCompanyFilter(), $total).':';
												else
													echo sprintf(_('%s %s'), number_format($c, 0), kaosGetCompanyFilter()).':';
											}
										?></div>
										<div class="search-results">
											<?php
												$count = count($kaosCall['results']);
												$i=0;
												foreach ($kaosCall['results'] as $r){
													$i++;
													?>
													<div class="<?= ($i == $count ? 'last' : '') ?>">
														<a href="<?= kaosGetEntityUrl($r) ?>">
															<span><i class="result-icon fa fa-<?= kaosGetEntityIcon($r) ?>"></i><span><?= kaosGetEntityTitle($r) ?></span></span>

															<?php

																// TODO: factorize with template/Entity.php stats! (try to grab everything at once, or precache to entity table)

																$details = array();

																$date = get('SELECT COALESCE(b.date, b_in.date) AS date
																	FROM statuses AS s
																	LEFT JOIN precepts AS p ON s.precept_id = p.id
																	LEFT JOIN bulletins AS b ON p.bulletin_id = b.id
																	LEFT JOIN bulletin_uses_bulletin AS bb ON p.bulletin_id = bb.bulletin_id
																	LEFT JOIN bulletins AS b_in ON bb.bulletin_in = b_in.id
																	WHERE related_id = %s AND type = "capital" AND action IN ( "new" )
																', $r['id']);

																if ($date)
																	$details[] = '<span class="entity-line-detail"><span class="entity-line-label">'._('Founded').': </span><span class="entity-line-body">'.date_i18n('Y', strtotime($date)).'</span></span>';

																$object = get('SELECT note FROM statuses WHERE related_id = %s AND type = "object" AND action = "new" ORDER BY id DESC LIMIT 1', $r['id']);

																if ($object)
																	$details[] = '<span class="entity-line-detail"><span class="entity-line-label">'._('Object').': </span><span class="entity-line-body"><i>"'.$object.'"</i></span></span>';

																if ($details)
																	echo '<span class="entity-line-details">'.implode(' / ', $details).'</span>';
															?>
														</a>
													</div>
													<?php
												}
											?>
										</div>
										<?php
									} else {
										?>
										<div class="search-results-intro search-results-intro-none"><?= sprintf(_('No results found for "%s".'), htmlentities($kaosCall['currentQuery'])) ?><?php

											if (hasFilter())
												echo ' '._('Check your query or try removing some filters...');

										?></div>
										<?php
									}

								} else if (!empty($entity)){
									?>
									<div class="browser-inner">
										<?php
											if (!empty($entity))
												echo kaosGetTemplate('Entity', array('entity' => $entity));
										?>
									</div>
									<?php
								} else {
									?>
									<div class="browser-center-msg">
										<div>
											<div class="logo-root-big-wrap"><a data-tippy-placement="bottom" title="<?= esc_attr(_('Start investigating..')) ?>" href="#" onclick="jQuery('#kaosSearch').focus().select(); return false;"><img src="<?= ASSETS_URL.'/images/logo/logo-square-transparent.png?v='.KAOS_ASSETS_INC ?>" class="logo-root-big" /></a></div>
											<?php
												if (!empty($_GET['installed']) && !empty($_SESSION['kaos_justinstalled']))
													echo '<div class="front-warning front-warning-success"><span>'._('The installation was completed successfully!').'</span></div>';
												else if (defined('KAOS_FRONTPAGE_MESSAGE') && KAOS_FRONTPAGE_MESSAGE)
													echo '<div class="front-warning front-warning-custom"><span>'.KAOS_FRONTPAGE_MESSAGE.'</span></div>';
											?>
											<div class="browser-directories"><div><?php 
												foreach (getEntityTypes() as $type => $c){
													?>
													<a href="<?= BASE_URL ?>?etype=<?= $type ?>"><i class="fa fa-<?= kaosGetEntityIcon($type) ?>"></i> <?= number_format(get('SELECT COUNT(*) FROM entities WHERE type = %s', $type), 0) ?> <?= $c['title'] ?></a>
													<?php
												}
											?></div></div>
											<div class="search-root-big-wrap"><?= $searchInput ?></div>
											<div class="browser-big-submit">
												<input type="button" class="browser-big-submit-button" value="$tate Search" />
											</div>
											<?php
												if (KAOS_DEBUG)
													echo '<div class="front-warning front-warning-debug"><span>Debug mode enabled</span></div>';
											?>
											<div class="root-slogan"><?= _('Worldwide, collaborative, public data reviewing and monitoring tool.') ?></div>
										</div>
									</div>
									<?php
								} ?>
							</div>
						</div>
					</div>
				</div>
				<?php footer() ?>
			</div>
		</body>
	</html><?php

return '';
