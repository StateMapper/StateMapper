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


// wordpress-style actions (hooks)

function add_action($action, $cb = null, $priority = 0){
	static $actions = array();

	if (!$cb){
		$ret_actions = array();
		if (isset($actions[$action])){
			ksort($actions[$action]);
			foreach ($actions[$action] as $cbs)
				$ret_actions = array_merge($ret_actions, $cbs);
		}
		return $ret_actions;
	}

	if (is_string($cb))
		$cb = '\\StateMapper\\'.$cb;

	$actions[$action][$priority][] = $cb;
}

function do_action($action){
	$args = (array) func_get_args();
	array_splice($args, 0, 1);
	foreach (add_action($action) as $cb)
		call_user_func_array($cb, $args);
}


function add_filter($name, $cb = null, $priority = 0, $args_count = null){
	static $cbs = array();
	if ($cb){
		if (!isset($cbs[$name]))
			$cbs[$name] = array();
			
		if (!isset($cbs[$name][$priority]))
			$cbs[$name][$priority] = array();
		
		$cbs[$name][$priority][] = array(is_string($cb) ? '\\StateMapper\\'.$cb : $cb, $args_count);
	} 
	return $cbs;
}

function apply_filters($name){
	$cbs = add_filter($name);
	$vars = func_get_args();
	array_shift($vars);
	$var = $vars[0];
	if (isset($cbs[$name])){
		ksort($cbs[$name]);
		foreach ($cbs[$name] as $priority => $ccbs){
			foreach ($ccbs as $cb){
				$cvars = $vars;
				$cvars[0] = $var;
				if ($cb[1] !== null)
					array_splice($cvars, $cb[1]);
				$var = call_user_func_array($cb[0], $cvars);
			}
		}
	}
	return $var;
}

function smap_ajax_autoaction($args){
	if (empty($args['related']))
		return 'Bad id';
	if (empty($args['related']['action']) || !preg_match('#^[a-z_]+$#i', $args['related']['action']))
		return 'Bad action';

	$ret = apply_filters('autoaction_'.$args['related']['action'], false, $args['related']);
	return $ret ? $ret : 'Bad action';
}

function print_actions_menu($entity, $actions, $action_class, $wrap_class = '', $context = 'sheet', $placement = 'bottom'){
	$str = array();
	$advanced = array();
	foreach ($actions as $id => $e){
		if (!isset($e['html'])){
			$e['html'] = !empty($e['url']) ? '<a href="'.$e['url'].'"'.(!empty($e['target']) ? ' target="'.$e['target'].'"' : '') : '<a href="#"';
			$e['html'] .= ' data-tippy-placement="'.$placement.'" title="'.esc_attr(!empty($e['title']) ? $e['title'] : $e['label']).'" class="'.$action_class.' action'.(!empty($e['url']) ? '' : ' autoaction').'" '.related(array('action' => $id)).'><i class="fa fa-'.$e['icon'].'"></i>';
			$e['html'] .= !empty($e['url']) ? '</a>' : '</a>';
		}
		
		$e['html'] = apply_filters('entity_action_print_'.$id, $e['html'], $entity, $context);
		
		if (!empty($e['advanced']))
			$advanced[] = $e['html'];
		else
			$str[] = $e['html'];
	}
	if ($advanced)
		$str[] = '<span class="menu actions-advanced"><span class="menu-button"><i class="fa fa-angle-down"></i></span></span>';
		
	echo '<div class="'.$wrap_class.' actions-wrap"><div class="actions">'.implode('', $str).'</div></div>';
}

function print_nice_alert($alert, $force_print = false){
	if (defined('SMAP_JS_PRINTED') || $force_print){
		?>
		<script>
			$(document).ready(function(){
				$.smapNiceAlert(<?= json_encode($alert) ?>);
			});
		</script>
		<?php
	} else
		add_nice_alert($alert);
}

function add_nice_alert($alert = null){
	static $alerts = array();
	if ($alert)
		$alerts[] = $alert;
	else
		return $alerts;
}

add_action('footer_end', function(){
	foreach (add_nice_alert() as $alert)
		print_nice_alert($alert, true);
}, 100);
		


function has_filter_bar(){
	return !is_home() && !has_error() && !IS_API && (is_page('browser') || !empty($smap['entity']));
}
