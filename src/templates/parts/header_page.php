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
							<span><a data-tippy-placement="bottom" title="<?= esc_attr(get_schema($country)->type == 'continent' ? _('See continent providers') : _('See country providers')) ?>" class="clean-links" href="<?= get_providers_url($country) ?>"><?= get_schema($country)->name ?></a></span>
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
					
					if ($title){
						?>
						<div class="header-center-title header-title">
							<?= ($avatar ? '' : '<i class="fa fa-angle-right"></i> ').$title ?>
						</div>
						<?php
					} 
					
					$modes = get_modes();
					$str = false;
					
					if (isset($smap['call'], $modes[$smap['call']])){
						$c = $modes[$smap['call']];
						$str = '<i class="fa fa-'.$c['icon'].'"></i> '.(!empty($c['headerTitle']) ? $c['headerTitle'] : $c['title']);
						
					} else if ($smap['page'] == 'ambassadors'){
						$c = $modes[$smap['page']];
						$str = '<i class="fa fa-'.$c['icon'].'"></i> '.(!empty($c['headerTitle']) ? $c['headerTitle'] : $c['title']);
						
					} else if ($smap['page'] == 'providers' && !empty($smap['filters']['loc'])){
						$c = $modes['providers'];
						$str = '<i class="fa fa-'.$c['icon'].'"></i> '.(!empty($c['headerTitle']) ? $c['headerTitle'] : $c['title']);
					}
					
					if ($str){
						?>
						<div class="header-center-call"><?= $str ?></div>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php print_template('parts/header_right') ?>
	</div>
</div>
<?php 
print_template('parts/filters'); 
