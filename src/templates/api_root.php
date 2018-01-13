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
	
$url = get_api_url(get_filter_url(array(
	'loc' => 'es',
	'etype' => 'company'
), false, false));

$title = get_page_title();

print_template('parts/header');
?>
<div id="main-inner">
	<div class="main-header<?php echo ' header-avatar-'.($avatar ? 'has' : 'none'); ?>">
		<div class="main-header-inner">
			<?php print_template('parts/header_logo') ?>
			<div class="header-center-wrap">
				<div class="header-center">
					<div class="header-center-inner">
						<div class="header-center-title header-title">
							<?= $title ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="api-human-content">
		<div class="api-root">
			<?php ob_start(); ?>
			Most $tateMapper's URLs hide a JSON API endpoint that can be built this way:
			- replace "<?= BASE_URL ?>" with "<?= BASE_URL ?>api/"
			- add ".json" at the end of the URL (just before the first "?", if any)
			
			This way, <a href="<?= $url ?>"><?= $url ?></a> is one valid API URL.
			
			Please, also note that in the footer's copyright menu, there is always direct URLs to the APIs related to the current page.
			
			The public $tateMapper's API is currently rate-limited to <?= API_RATE_LIMIT ?> requests every <?= API_RATE_PERIOD ?>.
			
			Please try yourself some URLs:
			
			<?php echo nl2br(ob_get_clean()); ?>
			
			<?php 
				// calc a past date for the example
				$date = '2017-01-04';
				$query = array('schema' => 'ES/BOE', 'date' => $date);
				$queryRaw = array('country' => 'es');
				
				foreach (get_url_patterns() + array(

					uri($query, 'rewind') => 'all bulletins\' map',
					
					999 => '', // force a space
					
					uri($query, 'fetch/raw') => 'retrieve the original bulletin file',
					uri($query, 'lint/raw') => 'retrieve the linted bulletin file',
					
				) as $uri => $label){
					if (is_numeric($uri))
						echo '<br>';
					else {
						$api_uri = 'api/'.$uri.'.json';
						echo '<a target="_blank" href="'.add_lang(BASE_URL.$api_uri.'?human=1').'">'.$api_uri.'</a>: '.$label.' (<a target="_blank" href="'.add_lang(BASE_URL.$api_uri.'?human=1').'">Human JSON</a> | <a target="_blank" href="'.add_lang(BASE_URL.$api_uri).'">Raw JSON</a> | <a target="_blank" href="'.add_lang(BASE_URL.$uri).'">Browser URL</a>)<br>';
				
						/* // TODO: build a preview system with tabs: Human JSON | Raw JSON + browser URL below?
						 * 
							$json = @json_decode(file_get_contents(add_lang(BASE_URL.$api_uri)));
							echo '<div style="overflow: auto; max-height: 200px; margin: 20px 0 40px 100px; padding: 10px 20px; background: #fafafa;">';
							print_json($json);
							echo '</div>';
						*/	
					}
				}
			?>
		</div>
	</div>
</div>
<?php
print_template('parts/footer');

