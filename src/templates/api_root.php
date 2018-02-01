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
	
$url = get_api_url(get_filter_url(array(
	'loc' => 'es',
	'etype' => 'company'
), false, false));

$title = get_page_title();
$modes = get_modes();

print_header('browser');
//print_header('page');
?>
<div class="api-human-content api-root">
	<h1>API Reference</h1>
	
	<?php ob_start(); ?>
	$tateMapper provides JSON endpoints for nearly everything. 
	On most pages, you will find the corresponding JSON endpoint in the footer's <span class="footer-menu-simulation"><i class="fa fa-plug"></i> API</span> menu.
	
	The public API is currently rate-limited to <?= API_RATE_LIMIT ?> requests <?= (API_RATE_PERIOD == '1 hour' ? 'per hour' : 'every '.API_RATE_PERIOD) ?>, and provides the following endpoints:
	
	<?php echo nl2br(ob_get_clean()); ?>
	
	<div class="api-help-calls">
		<?php 
			// calc a past date for the example
			$date = '2017-01-04';
			$query = array('schema' => 'ES/BOE', 'date' => $date);
			$queryRaw = array('country' => 'es');
			
			foreach (array(

				array('providers', null, 'all schemas'),
				array('providers', 'es', 'country providers'),
				
				array('schema', $queryRaw, 'country schema'),
				array('soldiers', $queryRaw, 'country soldiers'),
				array('ambassadors', $queryRaw, 'country ambassadors'),
				
				array('schema', $query, 'bulletin schema'),
				array('fetch/raw', $query, 'bulletin file'),
				array('lint/raw', $query, 'linted bulletin file'),
				array('redirect', $query, 'bulletin\'s original URL'),
				array('parse', $query, 'parsed bulletin'),
				array('extract', $query, 'extracted bulletin'),
				array('rewind', $query, 'all bulletins\' map'),
				array('soldiers', $query, 'bulletin soldiers'),
				
				array('search', array('q' => 'ab'), 'search'),
				
			) as $c){
				$uri = uri($c[1], $c[0]);
				$label = $c[2];
				
				$clean_mode = preg_match('#^([^/]+)/.*$#', $c[0], $m) ? $m[1] : $c[0];
				if (isset($modes[$clean_mode], $modes[$clean_mode]['api_icon']))
					$icon = $modes[$clean_mode]['api_icon'];
				else if (isset($modes[$clean_mode], $modes[$clean_mode]['icon']))
					$icon = $modes[$clean_mode]['icon'];
				else
					$icon = 'map-signs';
				
				$is_document = preg_match('#^(.*)/raw\b([\?\#].*)?$#iu', $uri, $m);
				
				$api_uri = get_api_uri(BASE_URL.($is_document ? $m[1].(!empty($m[2]) ? $m[2] : '') : $uri), $is_document ? 'document' : 'json');
				
				$original_uri = $is_document ? strstr($uri, '/raw', true) : $uri;
				?>
				<div class="api-help-call">
					<a href="<?= add_lang(BASE_URL.$original_uri) ?>" class="api-help-call-title"><i class="fa fa-<?= $icon ?>"></i> <span><?= $label ?></span></a>
					<div class="api-help-call-urls">
						<a href="<?= add_lang(BASE_URL.($is_document ? $api_uri : add_url_arg('human', 1, $api_uri))) ?>"><?= $api_uri ?></a>
						
						<span class="api-help-call-links">

							<a title="<?= esc_attr(_('Browser page')) ?>" href="<?= add_lang(BASE_URL.$original_uri) ?>"><i class="fa fa-<?= $modes['browse']['icon'] ?>"></i></a>
							
							<?php
							
							if ($is_document){
								?>
								<a title="<?= esc_attr(_('Raw document')) ?>" href="<?= add_lang(BASE_URL.$uri) ?>"><i class="fa fa-<?= $modes['document']['icon'] ?>"></i></a>
								<?php
							
							} else {
								?>
								<a title="<?= esc_attr(_('Human JSON')) ?>" href="<?= add_lang(add_url_arg('human', 1, BASE_URL.$api_uri)) ?>"><i class="fa fa-male"></i></a>
								<a title="<?= esc_attr(_('Raw JSON')) ?>" href="<?= add_lang(BASE_URL.$api_uri) ?>"><i class="fa fa-<?= $modes['api']['icon'] ?>"></i></a>
								<?php
							}
							?>
						</span>
					</div>
				</div>
				<?php
		
				/* // TODO: build a preview system with tabs: Human JSON | Raw JSON + browser URL below?
				 * 
					$json = @json_decode(file_get_contents(add_lang(BASE_URL.$api_uri)));
					echo '<div style="overflow: auto; max-height: 200px; margin: 20px 0 40px 100px; padding: 10px 20px; background: #fafafa;">';
					print_json($json);
					echo '</div>';
				*/	
			}
			
			
		?>
		<br><br>
		All API queries support the optional "lang" parameter: <?= plural(array_map(function($e){
			return convert_lang_for_url($e);
		}, get_langs(true)), SEPARATOR_OR) ?>.<br><br>
		
		Please also see the <a href="<?= anonymize(get_repository_url('blob/master/documentation/manuals/INSTALL.md#daemon-commands')) ?>" target="_blank">CLI API</a>.
	</div>
</div>
<?php
print_footer();

