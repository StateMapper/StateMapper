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
		
ob_start();
$keys = array();
foreach ($smapDebug['queries'] as $q)
	print_query_line($q, false, $keys);
$queriesHtml = ob_get_clean();

$url = get_canonical_url();
$canonical = rtrim($url, '/') == rtrim(current_url(), '/');

// print canonical indicator
$str[] = '<span class="left"><a href="'.$url.'" style="'.($canonical ? '' : 'color: red').'" title="'.($canonical ? 'The canonical URL matches the current URL' : 'The canonical and the current URL are different').'"><i class="fa fa-'.($canonical ? 'check' : 'link').'"></i> Link</a></span>';

// calc query duration
$dbduration = 0;
if (!empty($smapDebug['queries']))
	foreach ($smapDebug['queries'] as $q)
		$dbduration += $q['duration'];

if (!empty($smap['begin'])){

	// generation time
	$str[] = '<span title="'.esc_attr('This page was generated in '.time_diff($smap['begin'], null, true)).'" class="show-queries left"><i class="fa fa-clock-o"></i> '.time_diff($smap['begin'], null, true).' exec</span>';

	// fetches
	if (isset($smap['fetches'])){

		$cstr = array();
		if (!empty($smap['fetched_origins']))
			foreach ($smap['fetched_origins'] as $f => $count)
				$cstr[] = number_format($count, 0).' fetched from '.$f;

		$cprec = array();
		if (!empty($smap['fetchDuration']))
			$cprec[] = time_diff(0, $smap['fetchDuration']).' fetch';
		if (!empty($smap['fetchWaitDuration']))
			$cprec[] = time_diff(0, $smap['fetchWaitDuration']).' wait';

		$str[] = '<span title="'.esc_attr(implode("\n", $cstr)).'" class="show-queries left"><i class="fa fa-download"></i> '.number_format($smap['fetches'], 0).' fetches'.($cprec ? ' ('.implode(', ', $cprec).')' : '').'</span>';

	}
}

// queries console
if (!empty($smapDebug['queries'])){
	$keysHtml = '';
	if ($keys){
		$keysHtml = array();
		foreach ($keys as $icon => $c)
			$keysHtml[] = $c[1];
		$keysHtml = ' <span class="footer-queries-icons">'.implode(' ', $keysHtml).'</span>';
	}
	$count = count($smapDebug['queries']);
	$str[] = '<span class="show-queries left"><span title="'.esc_attr('click to show all executed queries').'"><i class="fa fa-database"></i> '.sprintf(ngettext('%s query', '%s queries', $count), number_format($count)).' ('.time_diff(0, $dbduration, true).')</span>'.$keysHtml.'</span>';
}

if (!empty($smapDebug['queries'])){

	$slow = $smapDebug['queries'];
	usort($slow, function($a, $b){
		return $a['duration'] < $b['duration'];
	});
	array_splice($slow, 10);

	$has = 0;
	foreach ($slow as $q){
		if ($q['duration'] < 1)
			continue;
		$has++;
	}

	?>
	<div class="debug-queries">
		<div class="debug-queries-inner">
			<div class="debug-queries-title">$smap global variable</div>
			<table border="0">
				<tr><td><?php 
					$smap_clone = $smap;
					unset($smap_clone['schemaObj']);
					debug($smap_clone);
				?></td></tr>
			</table>
			<?php
			
				if (!empty($smap['fetched_urls'])){
					?>
					<div class="debug-queries-title">All <?= count($smap['fetched_urls']) ?> fetched URLs:</div>
					<table border="0">
					<?php
						$i = 0;
						foreach ($smap['fetched_urls'] as $url){
							?>
							<tr><td class="debug-queries-prefix">[<?= time_diff($smap['fetchDurations'][$i], 0, true) ?>] </td><td class="query-key-td"><i class="color-<?= ($smap['fetchCodes'][$i] == 200 ? 'green' : 'red') ?> fa fa-<?= ($smap['fetchCodes'][$i] != 200 ? 'times' : ($smap['fetchTypes'][$i] ? 'download' : 'check')) ?>"></i> </td><td class="debug-queries-val"><a href="<?= $url ?>" target="_blank"><?= $url ?></a> </td><td> <span title="Status code <?= $smap['fetchCodes'][$i] ?> was returned"> <i class="fa fa-long-arrow-right"></i> <?= $smap['fetchCodes'][$i] ?></span></td></tr>
							<?php
							$i++;
						}
					?>
					</table>
					<?php
				}
				
				if ($has){
				?>
					<div class="debug-queries-title">Top 10 slow queries:</div>
					<table border="0">
					<?php
						foreach ($slow as $q)
							print_query_line($q, true);
					?>
					</table>
				<?php
			}
			?>
			<div class="debug-queries-title">All <?= number_format(count($smapDebug['queries']), 0) ?> queries:</div>
			<table border="0">
			<?php
				echo $queriesHtml;
			?>
			</table>
		</div>
	</div>
	<?php
}

if ($str)
	echo implode('', $str);



function print_query_line($q, $onlySlow = false, &$keys = array()){
	$class = '';
	if ($q['duration'] < 1){
		if ($onlySlow)
			return;
	} else
		$class .= 'debug-queries-slow';
		
	$str = array();
	if (!empty($q['explain']))
		foreach ($q['explain'] as $l){
			$type = strtolower($l['type']);
			
			// see https://www.sitepoint.com/using-explain-to-write-better-mysql-queries/ to optimize queries..
			// and https://stackoverflow.com/questions/1157970/understanding-mysql-explain
			
			$icon = 'key';
			$suffix = '';
			
			if (in_array($type, array('system', 'const', 'eq_ref'))){
				// already optimized
				$status = 'best-optimized';
				$statusPluralStr = 'are GREATLY OPTIMIZED';
				$statusStr = 'Query is GREATLY OPTIMIZED';
				$icon = 'star';
			
			} else {
				// to be optimized
				$suffix = '<span>'.$l['key'].'</span>';
				$is_ok = in_array($type, array('ref', 'range'));
				
				if ($is_ok && !empty($l['possible_keys']) && !empty($l['keys']) && $l['possible_keys'] == $l['keys']){
					$status = 'best-optimized';
					$statusPluralStr = 'are GREATLY OPTIMIZED';
					$statusStr = 'Query is GREATLY OPTIMIZED';
					$icon = 'star';

				} else if ($is_ok && $l['rows'] < 2000){
					$status = 'optimized';
					$statusPluralStr = 'COULD BE OPTIMIZED (though few matches)';
					$statusStr = 'Query COULD BE OPTIMIZED (though few matches)';

				} else if (!empty($l['possible_keys'])){
					$status = 'optimizable';
					$statusPluralStr = 'COULD BE OPTIMIZED';
					$statusStr = 'Query COULD BE OPTIMIZED';

				} else {
					$status = 'not-optimized';
					$statusPluralStr = 'are NOT OPTIMIZED';
					$statusStr = 'Query is NOT OPTIMIZED';
				}
			}
			
			$iclass = 'query-key-icon query-key-icon-'.$type.' query-key-status-'.$status;
			$str[] = '<span class="'.$iclass.'" title="'.esc_attr('<div style="text-align: left"><strong>'.$statusStr.':</strong><br>'.debug($l, false).'</div>').'"><i class="fa fa-'.$icon.'"></i>'.$suffix.'</span>';
			
			
			if (!in_array($status, array('best-optimized'))){
				if (!isset($keys[$status]))
					$keys[$status] = array(0, '');
				$keys[$status][0]++;
				$keys[$status][1] = '<span class="query-key-icon query-key-icon-'.$type.' query-key-status-'.$status.'" title="'.esc_attr($keys[$status][0].' queries '.$statusPluralStr).'"><i class="fa fa-'.$icon.'"></i> '.$keys[$status][0].'</span>';
			}
		}
	echo '<tr class="'.$class.'"><td class="debug-queries-prefix">'.str_pad('['.time_diff(0, $q['duration'], true).']', 5, ' ').' </td><td class="query-key-td">'.implode('', $str).'</td><td class="debug-queries-val">'.smap_replace(array(
		'#\b(SELECT|FROM|GROUP\s+BY|WHERE|ORDER\s+BY|(?:(?:LEFT|INNER|RIGHT|OUTER)\s+)?JOIN|ON|LIMIT|OFFSET)\b#i' => '<strong>$1</strong>'
	), $q['query']).'</td></tr>';
}
