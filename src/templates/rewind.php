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

global $smap;
// grid-3

$smap['outputNoFilter'] = true;
$query = $smap['query'];

$workersCount = 0;
$countPerYear = array();
$workersHtml = is_admin() ? get_workers_stats($workersCount, null, $countPerYear) : '';

$spider = array(
	'started' => !!$workersCount
);

ob_start();

$dateHeader = null;
$stats = array();

add_js('rewind');

if (empty($vars))
	$vars = array();
	
$vars += get_spider_config($smap['query']['schema']) + array(
	'currentYear' => date('Y'),
);

$currentYear = $vars['currentYear'];
$mode = $vars['extract'] ? 'extract' : 'fetch';

$dateHeader = date('Y-m', strtotime($currentYear.'-01-01'));

echo '<div class="map-years">';

$labels = get_bulletin_statuses();

$minYear = intval(date('Y', strtotime($vars['dateBack'])));

$yearStats = array();
$statuses = array();
for ($cyear = intval(date('Y')); true; $cyear--){
	$yearStats[$cyear] = get_map_year_stats($smap['query']['schema'], $cyear); // TODO: optimize: pass/calc useful stats
	foreach ($yearStats[$cyear] as $status => $count)
		if ($count)
			$statuses[] = $status;
	if ($cyear <= $minYear)
		break;
}
$statuses = array_unique($statuses);

?><div class="map-year-header">
	<span class="map-year-inner">
		<span class="map-year-label">&nbsp;</span>
		<span class="map-year-stats map-year-stats-labels">
			<?php 
				foreach ($labels as $status => $c){
					if ((empty($c['force']) && !in_array($status, $statuses)))// || (!empty($c['context']) && !in_array($mode, is_array($c['context']) ? $c['context'] : array($c['context']))))
						continue;
		
					?><span class="map-fetched-legend-state map-fetched-legend-state-<?= $status ?><?php
					
						if (!empty($c['spaceBelow']))
							echo ' map-year-stat-space-below';
							
					?>"><span><?php 
						if (!empty($c['icon']))
							echo '<i class="fa fa-'.$c['icon'].'"></i>';
					?></span> <?= str_pad($c['label'].' ', 25, '.') ?> </span><?php
				}
			?>
		</span>
	</span>
</div>
<?php

for ($cyear = intval(date('Y')); true; $cyear--){
	$stats = $yearStats[$cyear];

	$workersInd = '';
	if (!empty($countPerYear[$cyear]))
		$workersInd = '<span class="map-year-workers-ind" title="'.esc_attr($countPerYear[$cyear].' active workers').'"><span><i class="fa fa-bug fa-rotate-90"></i>'.($countPerYear[$cyear] > 1 ? '<span>'.$countPerYear[$cyear].'</span>' : '').'</span></span>';
	
	?><div class="map-year">
		<a href="#" data-smap-year="<?= $cyear ?>" class="map-year-inner <?php if ($cyear == $currentYear) echo 'smap-year-current'; ?>">
			<?= $workersInd ?>
			<span class="map-year-label"><?= $cyear ?></span>
			<span class="map-year-stats">
				<?php 
					foreach ($labels as $status => $c){
						if ((empty($c['force']) && !in_array($status, $statuses)))// || (!empty($c['context']) && !in_array($mode, is_array($c['context']) ? $c['context'] : array($c['context']))))
							continue;
		
						$count = isset($stats[$status]) ? $stats[$status] : 0;
						$formattedCount = is_numeric($count) ? format_bytes($count, 0, '') : $count;
						?>
						<span title="<?= esc_attr($c['label'].': '.($count ? $formattedCount : 'none')) ?>" class="<?php
							if ($count || !empty($c['noBackground']))
								echo 'map-year-stat-bg-'.$status;
							else
								echo 'map-year-stat-bg-ph';
								
							if (!empty($c['spaceBelow']))
								echo ' map-year-stat-space-below';
								
						?>"><?= ($count ? $formattedCount : '&nbsp;') ?></span>
						<?php
					}
				?>
			</span>
		</a>
	</div><?php
	
	if ($cyear <= $minYear)
		break;
}

echo '</div>';

$trs = array();

$finalDate = min(strtotime(($currentYear+1).'-01-01'), strtotime(date('Y-m-d')));
$currentMonth = date('Y-m');
$i = 0;

$dateHeaders = array();

$dbstats = array();
foreach (query('
	SELECT b.id, b.date, b.status, COUNT(bb.id) + 1 AS count, b.last_error, COUNT(p.id) AS precepts
	FROM bulletins AS b 
	LEFT JOIN bulletins AS bb ON b.bulletin_schema = bb.bulletin_schema AND b.date = bb.date AND bb.external_id IS NOT NULL
	
	LEFT JOIN precepts AS p ON b.id = p.bulletin_id AND p.id IS NOT NULL
	
	WHERE b.bulletin_schema = %s AND b.date IS NOT NULL AND b.date >= %s AND b.date < %s
	GROUP BY b.date
	ORDER BY b.date ASC
', array($smap['query']['schema'], $currentYear.'-01-01', date('Y-m-d', $finalDate))) as $c)
	$dbstats[$c['date']] = $c;
	
$workers = array();
foreach (query('SELECT w.pid, w.date FROM workers AS w LEFT JOIN spiders AS s ON w.spider_id = s.id WHERE s.bulletin_schema = %s AND w.date >= %s AND w.date < %s', array($smap['query']['schema'], $currentYear.'-01-01', date('Y-m-d', $finalDate))) as $w)
	if (is_active_pid($w['pid'])){
		$d = date('Y-m', strtotime($w['date']));
		if (!isset($workers[$d]))
			$workers[$d] = 1;
		else
			$workers[$d]++;
	}

for ($date = strtotime($currentYear.'-01-01'); $date < $finalDate; $date = add_month($date)){
	$monthHas = $monthTotal = 0;

	$month = date('Y-m', $date);
	$endDate = add_month($date); 
	$squares = array();
	
	for ($day = strtotime($month.'-01'); $day < $endDate; $day = strtotime('+1 day', $day)){
		$squares[] = get_map_square(date('Y-m-d', $day), $bulletinStatus, $monthHas, $monthTotal, $dbstats, $mode);
		$stats[$bulletinStatus] = (isset($stats[$bulletinStatus]) ? $stats[$bulletinStatus] : 0) + 1;
		
		$dayNum = intval(date('d', $day));
		if (!isset($dateHeaders[$dayNum]))
			$dateHeaders[$dayNum] = '<span class="map-fetched-ind"><span>'.$dayNum.'</span></span>';
	}
	
	$tr = '<tr class="map-fetched-map-block"><td class="map-fetched-year">'.($i != 11 && $month != $currentMonth ? '' : date('Y', $date)).'</td><td class="map-fetched-header">'.date_i18n('m. F', $date).'</td><td class="kao-api-fetched-map">';
	
	$tr .= implode('', $squares);

	$tr .= '</td><td><span class="map-fetched-map-total">'.($monthHas ? ($monthHas == $monthTotal ? '<i class="fa fa-check"></i>' : ceil($monthHas * 100 / $monthTotal).'%') : '-');
	
	if (!empty($workers[$month]))
		$tr .= '<span class="map-month-workers-ind" title="'.esc_attr($workers[$month].' active workers').'"><span><i class="fa fa-bug fa-rotate-180"></i>'.($workers[$month] > 1 ? '<span>'.$workers[$month].'</span>' : '').'</span></span>';
	
	$tr .= '</span></td></tr>';
	
//	echo '<tr colspan="3" class="map-fetched-map-space"></tr>';

	$tr .= '</td></tr>';
	
	$trs[] = $tr;
	$i++;
}

//echo '<h3>'.sprintf(_('Year %s'), $currentYear).'</h3>';
?>
<div class="map-table-wrap">
	<div class="map-table-inner">
		<table class="map-table">
			<tr class="map-fetched-map-block map-header-days">
				<td class="map-fetched-year">&nbsp;</td>
				<td class="map-fetched-header"><span>Day <i class="fa fa-long-arrow-right"></i></span></td>
				<td class="map-fetched-map"><?= implode('', $dateHeaders) ?></td>
			</tr>
			<?= implode('', array_reverse($trs)) ?>
		</table>
	</div>
</div>
<?php
	
$maps = ob_get_clean();

?>
<div class="map-fetched">
	<div class="spider-ctrl">
		<div class="spider-ctrl">
			<?php if (is_admin()){ ?>
				<div class="spider-ctrl-right">
					<div class="spider-ctrl-button"><?= get_spider_button($smap['query']['schema']) ?></div>
					<div class="spider-ctrl-extract-button"><label><input type="checkbox" name="spider_extract" <?php if (!empty($vars['extract'])) echo 'checked '; ?>/> Extract</label></div>
				</div>
			<?php } ?>
			<?php echo $workersHtml; ?>
		</div>
	</div>
	<?= $maps ?>
</div>
<?php 

