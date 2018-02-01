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

smap_add_action('load', function(elt){
	
	$('.lists').on('click', '.list-new-button', function(){
		var wrap = $(this).closest('.list-new-wrap');
		var user_input = prompt($(this).smap('prompt'));
		if (user_input)
			smapAjax('create_list', {user_input: user_input}, function(data, success){
				if (success)
					smapUpdate($(data.list_html).insertBefore(wrap));
			});
		return false;
	
	}).on('click', '.list-rename', function(){
		var t = $(this);
		var args = t.smapGetRelated();
		var user_input = prompt(t.smap('prompt'), args.list_title);
		if (user_input){
			args.user_input = user_input;
			smapAjax('rename_list', args, function(data, success){
				if (success)
					t.closest('.list').smapSet({list_title: data.list_title}).find('.list-title span').html(data.list_title);
			});
		}
		return false;
	
	}).on('click', '.list-delete', function(){
		var t = $(this);
		if (confirm(t.smap('prompt'))){
			smapAjax('delete_list', $(this).smapGetRelated(), function(data, success){
				if (success)
					t.closest('.list').slideUp(function(){
						$(this).remove();
					});
			});
		}
		return false;
	
	});
	
	// delete a list in an entity's sheet summary
	$('.sheet').on('click', '.listed-lists-wrap > span .delete', function(){
		var t = $(this);
		var related = t.smapGetRelated();
		smapAjax('set_entity_for_list', $.extend(true, {}, {active: false}, related), function(data, success){
			if (success)
				t.closest('span').fadeOut(function(){
					$(this).remove();
				});
		});
		
		t.closest('.summary-in-my-lists').find('.listed-add-wrap input').focus();
	});
});

function autoaction_add_to_list(a, related){
	var wrap = a.closest('.lists-container').find('.entity-lists-add-wrap');
	
	var context = null;
	if (!wrap.length){
		wrap = a.closest('.sheet').find('.listed-add-wrap');
		context = 'sheet';
	}

	var topWrap = wrap.closest('.my-lists-wrap');
	if (!topWrap.length)
		topWrap = wrap;
	
	if (wrap.is('.loaded')){

		topWrap.toggleClass('modify-opened');
		wrap.toggle();
		if (wrap.is(':visible'))
			wrap.find('input').focus();
	
	} else {

		wrap.addClass('loaded');
		var lists = $('#my-lists').clone(false).hide().removeAttr('id');
		
		var myLists = related.in_my_lists;

		lists.find('li').each(function(){
			var s = $(this).smap();
			//console.log(myLists, s.list_id);
			if (s && s.list_id && $.inArray(convert_integer(s.list_id), myLists) >= 0)
				$(this).addClass('menu-item-active').addClass('menu-item-activated');
		});
		
		var input = lists.find('.search-wrap input');
		
		input.on('keypress keydown', function(e){
			if (e.which == 13){
				var user_input = $.trim(input.val());
				if (user_input != ''){
					input.val('');
					smapAjax('autoaction', {related: $.extend(true, {}, {user_input: user_input}, related)}, function(data, success){
						if (success){
							
							if (context == 'sheet')
								$(data.list_html_sheet).appendTo(topWrap.find('.lists-list-holder'));
							
							else {

								if (data.is_new)
									var li = $(data.list_html).prependTo(topWrap.find('.lists-list-holder'));
								else
									var li = topWrap.find('li.list-'+data.list.list_id);
								li.addClass('menu-item-active').addClass('menu-item-activated');
							}
						}
					});
				}
			}
		});
		
		// save changes in list subscription
		lists.on('smap_menu_item_changed', function(e){
			var t = $(e.target);
			var active = t.hasClass('menu-item-active');
			var related = t.smapGetRelated();
			if (related.ajax_save)
				smapAjax(related.ajax_save, $.extend(true, {}, {active: active}, related), function(data, success){
					
				});
			//console.log(t, active, related);
		});

		
		if (context == 'sheet')
			lists.appendTo(wrap).show();
		else
			lists.appendTo(wrap.empty().show()).slideDown('fast');
		
		topWrap.toggleClass('modify-opened');
		topWrap.closest('.hidden').removeClass('hidden');
		input.focus();
	}
	return false;
}

