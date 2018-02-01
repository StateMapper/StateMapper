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


$(document).ready(function(){
	
	// menus
	
	$('body').on('click', '.menu-button', function(e){
		var m = $(e.target).closest('.menu');
		if (!m.hasClass('menu-on')){
			
			m.addClass('menu-on');
			
			setTimeout(function(){
				$('body').on('click.smapMenu', function(e){
					if ($(e.target).closest('.menu').get(0) !== m.get(0) || $(e.target).hasClass('menu-wrap')){
						closeMenu(m);
						e.stopPropagation();
						e.preventDefault();
					}
				});
			}, 300);
			
		} else
			closeMenu(m);
	});

	// menu items multi-selection
	
	$('body').on('click', '.multisel-cb, .full-cb-menu li', function(e){
		e.stopPropagation();
		e.preventDefault();
		
		var item = $(this).closest('li');
		$(this).closest(':focus').blur();
		
		var active = item.toggleClass('menu-item-active').hasClass('menu-item-active');
		if (active)
			item.addClass('menu-item-activated');

		var m = item.closest('.menu').addClass('menu-changed');
		m.find('.menu-item-blank')[m.find('.menu-item-active').length ? 'removeClass' : 'addClass']('menu-item-active');
		item.trigger('smap_menu_item_changed');

		return false;
	});
	
	$('body').on('click smap_enter', '.multisel a', function(e){
		var m = $(this).closest('.menu-wrap.multisel');
		var active = m.find('.menu-item-active:not(.menu-item-blank) a');
		var isBlank = e.type != 'smap_enter' && $(this).closest('.menu-item-blank').length;

		if ((e.type != 'click' || active.length > 1) && (e.type != 'click' || (!e.ctrlKey && !e.shiftKey)) && !isBlank){
			if (e.type != 'smap_enter'){
				var t = $(this).closest('a').parent();
				if (!t.hasClass('menu-item-active') || !t.hasClass('menu-item-activated')){
					t.toggleClass('menu-item-active');
					active = $(this).closest('.menu-wrap.multisel').find('.menu-item-active:not(.menu-item-blank) a');
				}
			}
			if (!active.length)
				active = $(this).closest('.menu-wrap.multisel').find('.menu-item-active a');
			
			// factorize URL parameters from active checkboxes
			var hrefs = [];
			var base = null;
			active.each(function(){
				if (!base)
					base = this.href.substr(0, this.href.indexOf('?'));
				hrefs.push(argsToObject(this.href.substr(this.href.indexOf('?')+1)));
			});

			var args = {};
			var val = null;
			for (var i=0; i<hrefs.length; i++)
				for (var k in hrefs[i]){
					if (typeof args[k] == 'undefined')
						args[k] = [];
					val = decodeURIComponent(hrefs[i][k]);
					if ($.inArray(val, args[k]) < 0)
						args[k].push(val);
				}
			var url = [];
			for (var k in args)
				url.push(k+'='+encodeURIComponent($.unique(args[k]).join(' ')));
				
			window.location = base+'?'+url.join('&');
			return false;
		}
	});
	
	$(document).on('keypress keyup', function(e){
		if (e.which == 13 && $('.menu-on .multisel').length){ // enter
			e.stopPropagation();
			$('.menu-on .multisel a').first().trigger('smap_enter');
			return false;
		}
	});

	function closeMenu(m){
		if (m.hasClass('menu-changed') && m.find('.multisel').length)
			m.find('.multisel a').first().trigger('smap_enter');
		else {
			$('body').off('click.smapMenu');
			m.removeClass('menu-on');
		}
	}
});
