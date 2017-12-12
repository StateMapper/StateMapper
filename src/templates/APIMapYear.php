<?php
global $kaosCall;
// kaos-grid-3

$kaosCall['outputNoFilter'] = true;
$query = $kaosCall['query'];

$workersCount = 0;
$countPerYear = array();
$workersHtml = kaosWorkersStats($workersCount, null, $countPerYear);

$spider = array(
	'started' => !!$workersCount
);

ob_start();

$dateHeader = null;
$stats = array();

add_js('APIMap');

if (empty($vars))
	$vars = array();
	
$vars += getSpiderConfig($kaosCall['query']['schema']) + array(
	'currentYear' => date('Y'),
);

$currentYear = $vars['currentYear'];
$mode = $vars['extract'] ? 'extract' : 'fetch';

$dateHeader = date('Y-m', strtotime($currentYear.'-01-01'));

echo '<div class="kaos-api-years">';

$labels = kaosGetLabels('statuses');

$minYear = intval(date('Y', strtotime($vars['dateBack'])));

$yearStats = array();
$statuses = array();
for ($cyear = intval(date('Y')); true; $cyear--){
	$yearStats[$cyear] = kaosGetYearStats($kaosCall['query']['schema'], $cyear); // TODO: optimize: pass/calc useful stats
	foreach ($yearStats[$cyear] as $status => $count)
		if ($count)
			$statuses[] = $status;
	if ($cyear <= $minYear)
		break;
}
$statuses = array_unique($statuses);

?><div class="kaos-api-year-header">
	<span class="kaos-api-year-inner">
		<span class="kaos-api-years-label">&nbsp;</span>
		<span class="kaos-api-years-stats kaos-api-years-stats-labels">
			<?php 
				foreach ($labels as $status => $c){
					if ((empty($c['force']) && !in_array($status, $statuses)))// || (!empty($c['context']) && !in_array($mode, is_array($c['context']) ? $c['context'] : array($c['context']))))
						continue;
		
					?><span class="kaos-api-fetched-legend-state kaos-api-fetched-legend-state-<?= $status ?><?php
					
						if (!empty($c['spaceBelow']))
							echo ' kaos-year-stat-space-below';
							
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
		$workersInd = '<span class="kaos-api-year-workers-ind" title="'.esc_attr($countPerYear[$cyear].' active workers').'"><span><i class="fa fa-bug fa-rotate-90"></i>'.($countPerYear[$cyear] > 1 ? '<span>'.$countPerYear[$cyear].'</span>' : '').'</span></span>';
	
	?><div class="kaos-api-year">
		<a href="#" data-kaos-year="<?= $cyear ?>" class="kaos-api-year-inner <?php if ($cyear == $currentYear) echo 'kaos-year-current'; ?>">
			<?= $workersInd ?>
			<span class="kaos-api-years-label"><?= $cyear ?></span>
			<span class="kaos-api-years-stats">
				<?php 
					foreach ($labels as $status => $c){
						if ((empty($c['force']) && !in_array($status, $statuses)))// || (!empty($c['context']) && !in_array($mode, is_array($c['context']) ? $c['context'] : array($c['context']))))
							continue;
		
						$count = isset($stats[$status]) ? $stats[$status] : 0;
						$formattedCount = is_numeric($count) ? kaosFormatBytes($count, 0, '') : $count;
						?>
						<span title="<?= esc_attr($c['label'].': '.($count ? $formattedCount : 'none')) ?>" class="<?php
							if ($count || !empty($c['noBackground']))
								echo 'kaos-api-status-bg-'.$status;
							else
								echo 'kaos-api-status-bg-ph';
								
							if (!empty($c['spaceBelow']))
								echo ' kaos-year-stat-space-below';
								
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
	LEFT JOIN bulletin_uses_bulletin AS bb ON b.id = bb.bulletin_in
	
	LEFT JOIN precepts AS p ON (bb.bulletin_id = p.bulletin_id OR bb.bulletin_in = p.bulletin_id) AND p.id IS NOT NULL
	
	WHERE b.bulletin_schema = %s AND b.date IS NOT NULL AND b.date >= %s AND b.date < %s
	GROUP BY b.date
	ORDER BY b.date ASC
', array($kaosCall['query']['schema'], $currentYear.'-01-01', date('Y-m-d', $finalDate))) as $c)
	$dbstats[$c['date']] = $c;
	
$workers = array();
foreach (query('SELECT w.pid, w.date FROM workers AS w LEFT JOIN spiders AS s ON w.spider_id = s.id WHERE s.bulletin_schema = %s AND w.date >= %s AND w.date < %s', array($kaosCall['query']['schema'], $currentYear.'-01-01', date('Y-m-d', $finalDate))) as $w)
	if (isActivePid($w['pid'])){
		$d = date('Y-m', strtotime($w['date']));
		if (!isset($workers[$d]))
			$workers[$d] = 1;
		else
			$workers[$d]++;
	}

for ($date = strtotime($currentYear.'-01-01'); $date < $finalDate; $date = kaosAddMonth($date)){
	$monthHas = $monthTotal = 0;

	$month = date('Y-m', $date);
	$endDate = kaosAddMonth($date);
	$squares = array();
	
	for ($day = strtotime($month.'-01'); $day < $endDate; $day = strtotime('+1 day', $day)){
		$squares[] = kaosGetMapSquare(date('Y-m-d', $day), $bulletinStatus, $monthHas, $monthTotal, $dbstats, $mode);
		$stats[$bulletinStatus] = (isset($stats[$bulletinStatus]) ? $stats[$bulletinStatus] : 0) + 1;
		
		$dayNum = intval(date('d', $day));
		if (!isset($dateHeaders[$dayNum]))
			$dateHeaders[$dayNum] = '<span class="kaos-api-fetched-ind"><span>'.$dayNum.'</span></span>';
	}
	
	$tr = '<tr class="kaos-api-fetched-map-block"><td class="kaos-api-fetched-year">'.($i != 11 && $month != $currentMonth ? '' : date('Y', $date)).'</td><td class="kaos-api-fetched-header">'.date_i18n('m. F', $date).'</td><td class="kao-api-fetched-map">';
	
	$tr .= implode('', $squares);

	$tr .= '</td><td><span class="kaos-api-fetched-map-total">'.($monthHas ? ($monthHas == $monthTotal ? '<i class="fa fa-check"></i>' : ceil($monthHas * 100 / $monthTotal).'%') : '-');
	
	if (!empty($workers[$month]))
		$tr .= '<span class="kaos-api-month-workers-ind" title="'.esc_attr($workers[$month].' active workers').'"><span><i class="fa fa-bug fa-rotate-180"></i>'.($workers[$month] > 1 ? '<span>'.$workers[$month].'</span>' : '').'</span></span>';
	
	$tr .= '</span></td></tr>';
	
//	echo '<tr colspan="3" class="kaos-api-fetched-map-space"></tr>';

	$tr .= '</td></tr>';
	
	$trs[] = $tr;
	$i++;
}

//echo '<h3>'.sprintf(_('Year %s'), $currentYear).'</h3>';
?>
<div class="kaos-api-map-table-wrap">
	<div class="kaos-api-map-table-inner">
		<table border="0" cellspacing="0" cellpadding="0" class="kaos-api-map-table">
			<tr class="kaos-api-fetched-map-block kaos-api-fetch-day-header">
				<td class="kaos-api-fetched-year">&nbsp;</td>
				<td class="kaos-api-fetched-header"><span>Day <i class="fa fa-long-arrow-right"></i></span></td>
				<td class="kaos-api-fetched-map"><?= implode('', $dateHeaders) ?></td>
			</tr>
			<?= implode('', array_reverse($trs)) ?>
		</table>
	</div>
</div>
<?php
	
$maps = ob_get_clean();

ob_start();	
?>
<div class="kaos-api-fetched">
	<div class="spider-ctrl">
		<div class="spider-ctrl">
			<?php if (isAdmin()){ ?>
				<div class="spider-ctrl-right">
					<div class="spider-ctrl-button"><?= kaosSpiderPowerButton($kaosCall['query']['schema']) ?></div>
					<div class="spider-ctrl-extract-button"><label><input type="checkbox" name="spider_extract" <?php if (!empty($vars['extract'])) echo 'checked '; ?>/> Extract</label></div>
				</div>
			<?php } ?>
			<?php echo $workersHtml; ?>
		</div>
	</div>
	<?= $maps ?>
</div>
<?php 

return ob_get_clean();
