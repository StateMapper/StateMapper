/*
 * StateMapper: worldwide, collaborative, public data reviewing and monitoring tool.
 * Copyright (C) 2017  StateMapper.net <statemapper@riseup.net>
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


function kaosAjax(action, actionData, successCb, errorCb, completeCb){
	
	return jQuery.ajax({
		type: 'POST',
		url: KAOS.ajaxUrl,
		data: jQuery.extend(true, {}, {
			action: action,
			session: KAOS.session
		}, actionData ? actionData : {}),
		complete: function(data){
			if (completeCb)
				completeCb(data);
		},
		success: function(data){
			if (data.error)
				alert(data.error);
			if (data.msg)
				alert(data.msg);
			if (successCb)
				successCb(data, data && data.success);
		},
		error: function(data){
			//alert('an error ocurred');
			if (errorCb)
				errorCb(data);
		},
		dataType: 'json'
	});
}

jQuery(document).ready(function(){
	
	// menus
	jQuery('body').on('click', '.menu-button', function(e){
		var m = jQuery(e.target).closest('.menu');
		if (!m.hasClass('menu-on')){
			
			m.addClass('menu-on');
			
			setTimeout(function(){
				jQuery('body').on('click.kaosMenu', function(e){
					if (jQuery(e.target).closest('.menu').get(0) !== m.get(0)){
						closeMenu(m);
						e.stopPropagation();
						e.preventDefault();
					}
				});
			}, 300);
			
		} else
			closeMenu(m);
		
	});
	
	function closeMenu(m){
		if (m.hasClass('menu-changed') && m.find('.multisel').length)
			m.find('.multisel a').first().trigger('kaos_enter');
		else {
			jQuery('body').off('click.kaosMenu');
			m.removeClass('menu-on');
		}
	}

	// live disksizes
	
	var updating = false;
	
	if (jQuery('.kaos-disksize-fetchnow').length)
		disksizeUpdate();
	
	if (jQuery('.kaos-disksize').length){
		setInterval(function(){
			disksizeUpdate();
		}, 60000);
	}
	
	function disksizeUpdate(){
		if (updating)
			return;
		
		var sizes = [];
		jQuery('.kaos-disksize').each(function(){
			jQuery(this).parent().find('.kaos-disksize-loader').stop().animate({opacity: 1}, 'slow');
			sizes.push(jQuery(this).data('kaos-disksize'));
		});
		
		if (sizes.length){
			updating = true;
			kaosAjax('disksize', {sizes: sizes}, function(data){
				if (data.success)
					for (var i in data.sizes)
						if (data.sizes[i])
							jQuery('.kaos-disksize-'+i).removeClass('kaos-disksize-fetchnow').html(data.sizes[i]);
			}, null, function(){
				jQuery('.kaos-disksize-loader').stop().animate({opacity: 0}, 'fast');
				updating = false;
			});
		}
	}
	
	// header title menu
	jQuery('.header-title-menued').on('click', function(){
		jQuery('body').off('click.header-title-menu');
		if (jQuery('.header-title-menu .menu-wrap').toggleClass('menu-on').hasClass('menu-on'))
			setTimeout(function(){
				jQuery('body').off('click.header-title-menu').on('click.header-title-menu', function(e){
					if (!jQuery(e.target).closest('.header-title').length){
						jQuery('.header-title-menu .menu-wrap').removeClass('menu-on');
						jQuery('body').off('click.header-title-menu');
					}
				});
		}, 100);
	});
	
	// top date menu
	var dateInput = jQuery('.kaos-top-calendar input');
	dateInput.on('keypress keydown blur', function(e){
		if (e.type == 'blur'){
			if (!jQuery(e.target).closest('.kaos-top-calendar').length)
				dateReset();
		} else if (e.which == 13) // enter
			dateChange();
	});
	jQuery('.kaos-top-calendar button').click(function(){
		dateChange();
	});
	
	function dateChange(){
		var date = dateInput.val();
		if (kaosDateIsValid(date)){
			window.location = dateInput.data('kaos-url').replace('%s', date);
			return true;
		}
		return false;
	}
	function dateReset(){
		dateInput.val(dateInput.data('kaos-oval'));
	}
	
	jQuery('.kaos-top-date').on('click', function(){
		jQuery('body').off('click.header-top-date');
		if (jQuery('body').toggleClass('kaos-top-date-menu-on').hasClass('kaos-top-date-menu-on'))
			setTimeout(function(){
				jQuery('body').off('click.header-top-date').on('click.header-top-date', function(e){
					if (!jQuery(e.target).closest('.kaos-top-date-menu').length){
						
						dateReset();
						jQuery('body').removeClass('kaos-top-date-menu-on');
						jQuery('body').off('click.header-top-date');
					}
				});
				dateInput.focus();
		}, 100);
		return false;
	});
	
	// auto (dropin) ajax
	jQuery('body').on('click', 'a.kaos-ajax', function(){
		var c = jQuery(this).data('kaos-confirm');
		if (!c || confirm(c)){
			var data = jQuery(this).data('kaos-ajax-data');
			kaosAjax(jQuery(this).data('kaos-ajax'), data ? data : {});
		}
		return false;
	});
		
	// queries debug
	jQuery('.kaos-show-queries').on('click', function(){
		jQuery('.kaos-api-queries').stop().slideToggle('fast');
	});
	
	// top filters
	jQuery('span.kaos-top-filter-ind').click(function(){
		jQuery('body').toggleClass('kaos-filters-open');
		jQuery('.header-filters').stop().slideToggle();
	});
	
	// init beautiful tooltips
	kaosUpdate();
	
	// menu items multi-selection
	
	jQuery('body').on('click', '.multisel-cb', function(e){
		e.stopPropagation();
		e.preventDefault();
		
		var item = jQuery(this).closest('a').blur().parent('li');
		var active = item.toggleClass('menu-item-active').hasClass('menu-item-active');
		if (active)
			item.addClass('menu-item-activated');

		var m = item.closest('.menu').addClass('menu-changed');
		m.find('.menu-item-blank')[m.find('.menu-item-active').length ? 'removeClass' : 'addClass']('menu-item-active');

		return false;
	});
	jQuery('body').on('click kaos_enter', '.multisel a', function(e){
		var m = jQuery(this).closest('.menu-wrap.multisel');
		var active = m.find('.menu-item-active:not(.menu-item-blank) a');
		var isBlank = e.type != 'kaos_enter' && jQuery(this).closest('.menu-item-blank').length;

		if ((e.type != 'click' || active.length > 1) && (e.type != 'click' || (!e.ctrlKey && !e.shiftKey)) && !isBlank){
			if (e.type != 'kaos_enter'){
				var t = jQuery(this).closest('a').parent();
				if (!t.hasClass('menu-item-active') || !t.hasClass('menu-item-activated')){
					t.toggleClass('menu-item-active');
					active = jQuery(this).closest('.menu-wrap.multisel').find('.menu-item-active:not(.menu-item-blank) a');
				}
			}
			if (!active.length)
				active = jQuery(this).closest('.menu-wrap.multisel').find('.menu-item-active a');
			
			// factorize URL parameters from active checkboxes
			var hrefs = [];
			var base = null;
			active.each(function(){
				if (!base)
					base = this.href.substr(0, this.href.indexOf('?'));
				hrefs.push(argsToObject(this.href.substr(this.href.indexOf('?')+1)));
			});
			var args = {};
			for (var i=0; i<hrefs.length; i++)
				for (var k in hrefs[i]){
					if (typeof args[k] == 'undefined')
						args[k] = [];
					if (jQuery.inArray(hrefs[i][k], args[k]) < 0)
						args[k].push(encodeURIComponent(hrefs[i][k]));
				}
			var url = [];
			for (var k in args)
				url.push(k+'='+args[k].join('+'));
				
			window.location = base+'?'+url.join('&');
			return false;
		}
	});
	jQuery(document).on('keypress keyup', function(e){
		if (e.which == 13 && jQuery('.menu-on .multisel').length){ // enter
			e.stopPropagation();
			jQuery('.menu-on .multisel a').first().trigger('kaos_enter');
			return false;
		}
	});
	
	jQuery('#top-filter-advanced .menu-button').click(function(){
		jQuery('.header-filters-advanced').slideToggle();
	});
	
	jQuery('.multiblock-bar a').click(function(){
		closeMenu(jQuery(this).closest('.menu'));
		var b = jQuery(this).parent().find('.multiblock-blank');
		b = b.clone(true).removeClass('multiblock-blank').addClass('multiblock-item');
		b.appendTo(jQuery(this).closest('.multiblock').find('.multiblock-items'));
	});
	jQuery('.multiblock').on('click', '.multiblock-delete', function(e){
		jQuery(e.target).closest('.multiblock-item').fadeOut(function(){
			jQuery(this).remove();
		});
	});
	
	// front warnings
	jQuery('.front-warning span').click(function(){
		jQuery(this).fadeOut('slow');
	});
});

function argsToObject(search){
	return search ? JSON.parse('{"' + search.replace(/&/g, '","').replace(/=/g,'":"') + '"}', function(key, value){ 
		return key==="" ? value : decodeURIComponent(value);
	}) : {};
}

var tips = [];
function kaosUpdate(elt){
	for (var i=0; i<tips.length; i++)
		tips[i].destroyAll();
	tips = [];
	tips.push(tippy('[title]', {
		arrow: true,
		arrowtransform: 'scale(0.75) translateY(-1.5px)'
	}));
}

function kaosDateIsValid(input){ // mysql format
	var bits = input.split('-');
	var d = new Date(bits[0], bits[1] - 1, bits[2]);
	return d.getFullYear() == bits[0] && (d.getMonth() + 1) == bits[1] && d.getDate() == Number(bits[2]);
}

// load bad iframe in Chrome
function kaosGetChromeVersion () {     
	var raw = navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./);
	return raw ? parseInt(raw[2], 10) : false;
}

function kaosRedrawElement(el,delay){
	console.log("chrome44-bug-512827 workaround");
	var dispOrigValue = el.style.display;
	el.style.display = 'none';
	setTimeout(function(){
		var te = document.createTextNode(' ');
		el.appendChild(te);
		el.style.display = dispOrigValue;
		te.parentElement.removeChild(te);
	},delay);
}    
