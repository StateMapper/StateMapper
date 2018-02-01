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


function get_my_lists($fill_entities = false, $user_id = null){
	if (!is_logged())
		return array();
	$lists = query('SELECT id, name FROM lists WHERE owner_id = %s ORDER BY created DESC', $user_id ? $user_id : get_my_id());
	if ($fill_entities){
		foreach ($lists as &$l)
			$l['entities'] = get_list_entities($l['id']);
		unset($l);
	}
	return $lists;
}

function get_list_by_id($list_id, $check_perm = true){
	if (!$check_perm)
		return get_row('SELECT * FROM lists WHERE id = %s', $list_id);
		
	if (!($user_id = get_my_id()))
		return null;
		
	return get_row('SELECT * FROM lists WHERE id = %s AND owner_id = %s', array($list_id, $user_id));
}

function is_entity_in_list($entity, $list_id){
	return !!get('SELECT COUNT(*) FROM list_has_entity WHERE entity_id = %s AND list_id = %s', array($entity['id'], $list_id));
}

function insert_list($args){
	if (!($id = get_my_id()))
		return false;
	return insert('lists', $args + array(
		'owner_id' => $id,
		'created' => date('Y-m-d H:i:s'),
	));
}

add_filter('entity_popup_actions', 'entity_popup_action_add_as_list', 999);
function entity_popup_action_add_as_list($actions, $entity){ 
	$modes = get_modes();
	$actions['add_to_list'] = array(
		'html' => '<a class="autoaction" href="#" '.related(array(
			'action' => 'add_to_list',
			'type' => 'entity',
		)).' title="Add to a list" data-tippy-placement="top"><i class="fa fa-'.$modes['lists']['icon'].'"></i></a>',
	);
	return $actions;
}

add_filter('autoaction_add_to_list', 'autoaction_add_to_list');
function autoaction_add_to_list($ret, $args){
	if (empty($args['entity_id'])
		|| !is_numeric($args['entity_id'])
		|| !($e = get_entity_by_id($args['entity_id']))
		|| (trim(@$args['user_input']) == '' && (empty($args['list_id']) || !is_numeric($args['list_id'])))
	)
		die_error();
		
	$user_id = get_my_id();
	if (!$user_id)
		return array(
			'success' => true,
			'reauth' => true,
		);
		
	if (empty($args['list_id'])){
		$args['slug'] = sanitize_title($args['user_input'], 100);
		$args['list_id'] = get_var('SELECT id FROM lists WHERE owner_id = %s AND slug = %s', array($user_id, $args['slug']));
	}
	
	if (!empty($args['list_id'])){
		if (!($list = get_list_by_id($args['list_id'])))
			return false;
			
		if (!set_entity_for_list($args['entity_id'], $args['list_id'], true))
			return false;
	
		$is_new = false;
		
	} else {
		$list = array(
			'slug' => $args['slug'],
			'name' => htmlentities(trim($args['user_input'])),
		);
		if (!($list['list_id'] = insert_list($list)))
			return false;
		
		set_entity_for_list($args['entity_id'], $list['list_id'], true);
		$is_new = true;
	}
	
	ob_start();
	print_lists_menu(array($list));
	$list_html = ob_get_clean();
	
	$list_html_sheet = entity_summary_html_in_my_list_inner($list);
	
	return array('success' => true, 'list_html' => $list_html, 'is_new' => $is_new, 'list_id' => $list['list_id'], 'list_html_sheet' => $list_html_sheet);
}

function get_my_lists_for_entity($entity_id, $as_objects = false){
	if (!is_logged())
		return array();
	$q = prepare('FROM list_has_entity AS lhe LEFT JOIN lists AS l ON lhe.list_id = l.id WHERE lhe.entity_id = %s AND l.owner_id = %s GROUP BY lhe.list_id', array($entity_id, get_my_id()));
	if ($as_objects)
		return query('SELECT lhe.list_id, lhe.added, l.created, l.name '.$q);
	return get_col('SELECT lhe.list_id '.$q);
} 

function is_my_list($list_id){
	return ($id = get_my_id()) && get_var('SELECT COUNT(*) FROM lists WHERE id = %s AND owner_id = %s', array($list_id, $id));
}

add_action('footer_end', 'print_my_lists_container');
function print_my_lists_container(){
	if (@IS_INSTALL)
		return;
	?>
	<span id="my-lists" class="my-lists">
		<span class="my-lists-search search-wrap">
			<input type="text" autocomplete="off" placeholder="Lookup or create a list.." /><i class="fa fa-search search-icon"></i>
		</span>
		<ul class="menu lists-list-holder multisel full-cb-menu" <?= related(array('ajax_save' => 'set_entity_for_list')) ?>>
			<?php 
			$lists = get_my_lists();
			print_lists_menu($lists);
			?>
		</ul>
	</span>
	<?php
}

function print_lists_menu($lists){
	$cb = get_multisel_cbs();
	foreach ($lists as $l){
		if (!isset($l['list_id']))
			$l['list_id'] = $l['id'];
		echo '<li class="list-'.$l['list_id'].'" '.related(array('list_id' => $l['list_id'])).'>'.$cb.' '.$l['name'].'</li>';
		// <span class="menu-item-right"><a href="#" class="menu-item-open"><i class="fa fa-external-link"></i></a></span>
	}
}

function smap_ajax_set_entity_for_list($args){
	if (!is_logged() 
		|| empty($args['list_id']) || !is_numeric($args['list_id'])
		|| !($l = get_list_by_id($args['list_id']))
		|| empty($args['entity_id'])
		|| !($e = get_entities_by_id($args['entity_id']))
		|| !set_entity_for_list($args['entity_id'], $l['id'], !empty($args['active']) && $args['active'] != 'false')
	)
		return false;
	return array('success' => true);
}

// @todo: not working :S
function set_entity_for_list($entity_id, $list_id, $activate){
	$id = get_var('SELECT id FROM list_has_entity WHERE list_id = %s AND entity_id = %s', array($list_id, $entity_id));

	if ($activate){
		if ($id)
			return true;
		return insert('list_has_entity', array(
			'list_id' => $list_id,
			'entity_id' => $entity_id,
			'added' => date('Y-m-d H:i:s'),
		));
	}
	if (!$id)
		return true;
	return query('DELETE FROM list_has_entity WHERE list_id = %s AND entity_id = %s', array($list_id, $entity_id));
}


add_filter('entity_summary', 'entity_summary_lists_in', 30, 3);
function entity_summary_lists_in($details, $entity, $context){
	if (is_api())
		return $details;

	$details['in_my_lists'] = get_my_lists_for_entity($entity['id'], true);
	return $details;
}

add_filter('entity_summary_html_in_my_lists', 'entity_summary_html_in_my_lists', 0, 3);
function entity_summary_html_in_my_lists($line, $in_my_lists, $entity){
	$modes = get_modes();
	$links = array();
	if ($in_my_lists)
		foreach ($in_my_lists as $list)
			$links[] = entity_summary_html_in_my_list_inner($list);
			
	return array(
		'icon' => $modes['lists']['icon'],
		'label' => 'Listed in',
		'hidden' => !$in_my_lists,
		'html' => '<span class="summary-in-my-lists my-lists-wrap"><span class="listed-lists-wrap lists-list-holder">'.implode(', ', $links).'</span><span class="listed-add-wrap"></span><a href="#" class="listed-modify modify autoaction" '.related(array('action' => 'add_to_list')).'><i class="fa fa-pencil"></i><i class="fa fa-check modify-close"></i></span></a>'
	);
}

function entity_summary_html_in_my_list_inner($list){
	return '<span '.related(array('list_id' => $list['list_id'])).'>'.$list['name'].' <i class="fa fa-times delete"></i></span>';
		
}

add_filter('entity_actions', 'entity_actions_add_to_list', 500, 2);
function entity_actions_add_to_list($entity, $context){
	$modes = get_modes();
	$entity['actions']['add_to_list'] = array(
		'label' => 'Add to list',
		'icon' => $modes['lists']['icon'],
	);
	return $entity;
}

/*
add_filter('entity_action_print_add_to_list', 'entity_action_print_add_to_list', 500, 3);
function entity_action_print_add_to_list($html, $entity, $context){
	if ($context != 'sheet')
		return $html;
	
	ob_start();
	?>
	<span class="menu"><?= $html ?>x</span>
	<?php
	return ob_get_clean();
}
*/
	

function get_list_entities($list_id){
	if (!is_my_list($list_id))
		return false;
	
	return query('SELECT e.* FROM list_has_entity AS lhe LEFT JOIN entities AS e ON lhe.entity_id = e.id WHERE lhe.list_id = %s', $list_id);
}

function smap_ajax_create_list($args){
	$input = minimize_spaces(@$args['user_input']);
	if (!$input)
		return 'You must enter a name';
	
	$list = array(
		'slug' => sanitize_title($input, 100),
		'name' => htmlentities($input),
	);
	if (!($list['id'] = insert_list($list)))
		return false;
		
	$list['entities'] = array();
	
	return array('success' => true, 'list_html' => get_template('parts/list', array('list' => $list)));
}

function smap_ajax_rename_list($args){
	$input = minimize_spaces(@$args['user_input']);
	if (!$input)
		return 'You must enter a name';
		
	if (!($user_id = get_my_id()))
		return 'Please log in';
	
	$name = htmlentities($input);
	if (!update('lists', array(
		'slug' => sanitize_title($input, 100),
		'name' => $name,
	), array(
		'owner_id' => $user_id,
		'id' => $args['list_id'],
	)))
		return false;
	
	return array('success' => true, 'list_title' => $name);
}

function smap_ajax_delete_list($args){
	if (!($user_id = get_my_id()))
		return 'Please log in';
	
	if (!query('DELETE FROM lists WHERE owner_id = %s AND id = %s', array($user_id, $args['list_id'])))
		return false;
	
	return array('success' => true);
}
