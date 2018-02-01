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

function searx_get_nodes($no_cache = false){
	if (!$no_cache && ($cache = get_cache('searx nodes')))
		return $cache;
	
	// grab node list from Github
	$content = fetch('https://raw.githubusercontent.com/wiki/asciimoo/searx/Searx-instances.md');
	
	$nodes = array();
	if (preg_match('#Alive\s*and\s*running\s*:?\s*(.*?)\#{3}#ius', $content, $m)){
		if (preg_match_all('#^\s*\*\s*\[\s*\*\*(.*?)\*\*\s*\](\(\s*(.*?)\))?#ium', $m[1], $urls, PREG_SET_ORDER)){
			foreach ($urls as $url)
				$nodes[] = rtrim(!empty($url[3]) ? $url[3] : $url[1], '/');
		} 
	} 
	set_cache('searx nodes', $nodes, '1 week');
	return $nodes;
}

function searx_query($str, $lang = null, $sum_nodes = 1, $attempts_per_node = 3, $no_cache = false){
	$cache_key = 'searx results lang:'.($lang ? $lang : '*').' nodes:'.$sum_nodes.' '.base64_encode($str);
	
	if (!$no_cache && ($cache = get_cache($cache_key)))
		return $cache;
	
	$nodes = searx_get_nodes();
	shuffle($nodes); // use nodes randomly
	
	$i = $node_count = 0;
	$unavailable = $results = array();
	$attempts = $attempts_per_node * $sum_nodes;
	
	// @todo: fork to fetch several URLs at the same time, then wait for children
	foreach ($nodes as $node){
		if (get_cache('searx node unavailable '.$node) === 1)
			continue;
		
		$content = fetch_json($node.'/search', array(
			'q' => $str,
			'format' => 'json'
		) + ($lang ? array('lang' => $lang) : array()), true, false, array(
			'type' => 'json',
			'timeout' => 5,
			'retries' => 1,
			'countAsFetch' => false,
		));
		if ($content && !empty($content->results)){
			
			$results = array_merge($results, $content->results);
			$node_count++;
			if ($node_count >= $sum_nodes)
				break;

		} else {
			$unavailable[] = $node;
			if ($i > $attempts)
				break;

			$i++;
		}
	}
	if ($results){
		set_cache($cache_key, $results, '1 day');
		
		// avoid calling bad nodes again
		if ($unavailable)
			foreach ($unavailable as $cnode)
				set_cache('searx node unavailable '.$cnode, 1, '1 day');
	}
	return $results ? $results : null;
}

function searx_fix_name($original_name, $lang = null, $nodes_count = 5, $threshold = 2, $debug = false){

	if ($results = searx_query(preg_replace('#[^\pL0-9]#', '', collapse_to_double(mb_strtoupper($original_name))), $lang, $nodes_count)){
		$appearances = array();
		foreach ($results as $r){
			foreach (array('title', 'content') as $k)
				if (!empty($r->{$k}) && mb_strlen($r->{$k}) > 2 && preg_match_all('#(?:\b[\pL0-9]+\b)#ius', mb_strtolower($r->{$k}), $words))
					foreach ($words[0] as $w){
						if (!isset($appearances[$w]))
							$appearances[$w] = 1;
						else
							$appearances[$w]++;
					}
		}
		if ($appearances){
			$ordered = $appearances;
			arsort($appearances);

			$linted = collapse_to_double(preg_replace('#[^\pL0-9]#', '', mb_strtolower(remove_accents($original_name))));
			
			foreach ($appearances as $w => $appearance){
				if ($appearance < $nodes_count * 5) // skip words appearing less than 3 times per node
					continue;
					
				$clinted = collapse_to_double(preg_replace('#[^\pL0-9]#', '', mb_strtolower(remove_accents($w))));
				
				if (strlen($clinted) < 3) // skip words with less than 3 letters/numbers
					continue;
					
				foreach ($ordered as $other_w => $other_appearance){
					$other_linted = collapse_to_double(preg_replace('#[^\pL0-9]#', '', mb_strtolower(remove_accents($other_w))));
					if (strlen($other_linted) < 3)
						continue;
					
					foreach (array(collapse_to_double($other_w.' '.$w), collapse_to_double($other_w.' '.$w)) as $new_word){
						
						$new_linted = collapse_to_double(preg_replace('#[^\pL0-9]#', '', mb_strtolower(remove_accents($new_word))));
						$other_leven = levenshtein($linted, $new_linted);
						
						if ($other_leven < 3){
							
							$new_appearance = $appearance + $other_appearance;
							$appearances[$new_word] = $new_appearance;
							if ($debug)
								echo 'COMPOSED!: '.$new_word.' (LEVEL: '.$other_leven.' / APPEARANCE: '.$new_appearance.' / LINTED: '.$clinted.')<br>';
						}
					}
				}
			}
			
			// only keep words that are in the original linted string
			$included = array();
			
			//$original_struct = preg_replace('#[^bcdfghjklmnpqrstvwxz0-9]#', '', $linted);
			//$original_struct = preg_replace('#((.)\2)#', '$2', $original_struct);
			if ($debug)
				echo 'LINTED: '.$linted.' <br>';
			
			foreach ($appearances as $w => $appearance){
				if ($appearance < $nodes_count * 5) // skip words appearing less than 3 times per node
					continue;
					
				$clinted = collapse_to_double(preg_replace('#[^\pL0-9]#', '', mb_strtolower(remove_accents($w))));
				
				if (strlen($clinted) < $threshold + 1) // skip words with less than 3 letters/numbers
					continue;
					
				$leven = levenshtein($linted, $clinted);
				if (strpos($linted, $clinted) !== false){
					$included[$w] = $appearance;
				
				} else if ($leven < 3){
					$included[$w] = $appearance;
					
				} else {
					
					continue;
					
				}
				if ($debug)
					echo 'IN: '.$w.' (LEVEN: '.$leven.' / APPEARANCE: '.$appearance.' / LINTED: '.$clinted.')<br>';
			}
			
			$sum = array_sum(array_values($included));
			$most = array();
			foreach ($included as $w => $appearance)
				if ($appearance > 0.20 * $sum)
					$most[$w] = $appearance;//strpos($linted, $w);
			
			arsort($most);
			
			$name = null;
			$has = array();
			foreach ($most as $cword => $appearance){
				$clinted = collapse_to_double(preg_replace('#[^\pL0-9]#', '', mb_strtolower(remove_accents($cword))));
				$clinted2 = collapse_to_double(minimize_spaces(preg_replace('#[^\pL0-9\s]#', '', mb_strtolower(remove_accents($cword)))));
				if (levenshtein($clinted, $linted) < $threshold){
					
					foreach ($results as $r){
						foreach (array('title', 'content') as $k)
							if (!empty($r->{$k}) && mb_strlen($r->{$k}) > 2){
								$text = mb_strtolower(remove_accents($r->{$k}));
								$text = collapse_to_double(minimize_spaces(preg_replace('#[^\pL0-9\s]#', '', $text)));
								
								if (preg_match_all('#'.preg_quote($clinted2, '#').'#s', $text, $matches, PREG_SET_ORDER)){
									if (!isset($has[$cword]))
										$has[$cword] = 0;
									$has[$cword] += count($matches);
								}
							}
					}
					break;
				}
			}
			if ($has){
				$name = array_keys($has);
				$name = array_shift($name);
				return beautify_name(mb_strtolower($name), substr($lang, -2));
			}
			
		}
	}
	return $original_name;
}

function collapse_to_double($str){
	//echo $str;
	//return $str;
	$str = preg_replace('{(.)\1+}', '$1$1', $str);
	//echo ' => '.$str.'<br>';
	return $str;
}
