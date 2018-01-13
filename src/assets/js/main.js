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


// ajax method
function smapAjax(action, actionData, successCb, errorCb, completeCb){
	
	return $.ajax({
		type: 'POST',
		url: SMAP.ajaxUrl,
		data: $.extend(true, {}, {
			action: action,
			session: SMAP.session,
			lang: SMAP.lang
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
	
	function closeMenu(m){
		if (m.hasClass('menu-changed') && m.find('.multisel').length)
			m.find('.multisel a').first().trigger('smap_enter');
		else {
			$('body').off('click.smapMenu');
			m.removeClass('menu-on');
		}
	}

	// live disksizes
	
	var updating = false;
	
	if ($('.smap-disksize-fetchnow').length)
		disksizeUpdate();
	
	if ($('.smap-disksize').length){
		setInterval(function(){

			disksizeUpdate();
		}, 60000);
	}
	
	function disksizeUpdate(){
		if (updating)
			return;
		
		var sizes = [];
		$('.smap-disksize').each(function(){
			$(this).parent().find('.smap-disksize-loader').stop().animate({opacity: 1}, 'slow');
			sizes.push($(this).data('smap-disksize'));
		});
		
		if (sizes.length){
			updating = true;
			smapAjax('disksize', {sizes: sizes}, function(data){
				if (data.success)
					for (var i in data.sizes)
						if (data.sizes[i])
							$('.smap-disksize-'+i).removeClass('smap-disksize-fetchnow').html(data.sizes[i]);
			}, null, function(){
				$('.smap-disksize-loader').stop().animate({opacity: 0}, 'fast');
				updating = false;
			});
		}
	}
	
	// header title menu
	$('.header-title-menued').on('click', function(){
		$('body').off('click.header-title-menu');
		if ($('.header-title-menu .menu-wrap').toggleClass('menu-on').hasClass('menu-on'))
			setTimeout(function(){
				$('body').off('click.header-title-menu').on('click.header-title-menu', function(e){
					if (!$(e.target).closest('.header-title').length){
						$('.header-title-menu .menu-wrap').removeClass('menu-on');
						$('body').off('click.header-title-menu');
					}
				});
		}, 100);
	});
	
	// top date menu
	var dateInput = $('.header-calendar input');
	dateInput.on('keypress keydown blur', function(e){
		if (e.type == 'blur'){
			if (!$(e.target).closest('.header-calendar').length)
				dateReset();
		} else if (e.which == 13) // enter
			dateChange();
	});
	$('.header-calendar button').click(function(){
		dateChange();
	});
	
	function dateChange(){
		var date = dateInput.val();
		if (smapDateIsValid(date)){
			window.location = dateInput.data('smap-url').replace('%s', date);
			return true;
		}
		return false;
	}
	function dateReset(){
		dateInput.val(dateInput.data('smap-oval'));
	}
	
	$('.header-date').on('click', function(){
		$('body').off('click.header-top-date');
		if ($('body').toggleClass('header-date-menu-on').hasClass('header-date-menu-on'))
			setTimeout(function(){
				$('body').off('click.header-top-date').on('click.header-top-date', function(e){
					if (!$(e.target).closest('.header-date-menu').length){
						
						dateReset();
						$('body').removeClass('header-date-menu-on');
						$('body').off('click.header-top-date');
					}
				});
				dateInput.focus();
		}, 100);
		return false;
	});
	
	// auto (dropin) ajax
	$('body').on('click', 'a.smap-ajax', function(){
		var c = $(this).data('smap-confirm');
		if (!c || confirm(c)){
			var data = $(this).data('smap-ajax-data');
			smapAjax($(this).data('smap-ajax'), data ? data : {});
		}
		return false;
	});
		
	// queries debug
	$('.show-queries').on('click', function(){
		$('.debug-queries').stop().slideToggle('fast');
	});
	$('.debug-queries').on('contextmenu', function(e){
		$('.debug-queries').stop().slideUp('fast');
		e.preventDefault();
		return false;
	});
		
	
	// top filters
	$('span.header-filter-ind').click(function(){
		$('body').toggleClass('filters-open');
		$('.header-filters').stop().slideToggle();
	});
	
	// init beautiful tooltips
	smapUpdate();
	
	// menu items multi-selection
	
	$('body').on('click', '.multisel-cb', function(e){
		e.stopPropagation();
		e.preventDefault();
		
		var item = $(this).closest('a').blur().parent('li');
		var active = item.toggleClass('menu-item-active').hasClass('menu-item-active');
		if (active)
			item.addClass('menu-item-activated');

		var m = item.closest('.menu').addClass('menu-changed');
		m.find('.menu-item-blank')[m.find('.menu-item-active').length ? 'removeClass' : 'addClass']('menu-item-active');

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
	
	$('#top-filter-advanced .menu-button').click(function(){
		$('.header-filters-advanced').slideToggle();
	});
	
	$('.multiblock-bar a').click(function(){
		closeMenu($(this).closest('.menu'));
		var b = $(this).parent().find('.multiblock-blank');
		b = b.clone(true).removeClass('multiblock-blank').addClass('multiblock-item');
		b.appendTo($(this).closest('.multiblock').find('.multiblock-items'));
	});
	$('.multiblock').on('click', '.multiblock-delete', function(e){
		$(e.target).closest('.multiblock-item').fadeOut(function(){
			$(this).remove();
		});
	});
	
	// front warnings
	$('.front-warning span').click(function(){
		$(this).fadeOut('slow');
	});
	
	// main-iframe
	var iframe_to = null;
	update_iframe();
	jQuery(window).on('resize', function(){ update_iframe() });
	
	function update_iframe(){
		if (iframe_to)
			clearTimeout(iframe_to);
		iframe_to = setTimeout(function(){
			var f = jQuery('.footer');
			var winh = jQuery(window).height();
			var diff = f.outerHeight(true);// + f[0].style.bottom;
			jQuery('.bulletin-iframe').each(function(){
				var t = jQuery(this).css({height: 1});
				t.css({height: winh - t.parent().offset().top - diff});
			});
		}, 300);
	}
});

function argsToObject(search){
    var params = decodeURIComponent(search).split('&'),
        sParameterName;
	
	var obj = {};
    for (var i = 0; i < params.length; i++){
        sParameterName = params[i].split('=');
		obj[sParameterName[0]] = sParameterName[1];
    }
    return obj;
}

var tips = [];
function smapUpdate(elt){
	for (var i=0; i<tips.length; i++)
		tips[i].destroyAll();
	tips = [];
	tips.push(tippy('[title]', {
		arrow: true,
		arrowtransform: 'scale(0.75) translateY(-1.5px)'
	}));
}

function smapDateIsValid(input){ // mysql format
	var bits = input.split('-');
	var d = new Date(bits[0], bits[1] - 1, bits[2]);
	return d.getFullYear() == bits[0] && (d.getMonth() + 1) == bits[1] && d.getDate() == Number(bits[2]);
}

// load bad iframe in Chrome
function smapGetChromeVersion(){
	var raw = navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./);
	return raw ? parseInt(raw[2], 10) : false;
}

function smapRedrawElement(el,delay){
	// console.log("chrome44-bug-512827 workaround");
	var dispOrigValue = el.style.display;
	el.style.display = 'none';
	setTimeout(function(){
		var te = document.createTextNode(' ');
		el.appendChild(te);
		el.style.display = dispOrigValue;
		te.parentElement.removeChild(te);
	},delay);
}    

// live
var live_i = 0;
$(document).ready(function(){
	var lives = [];
	$('.live').each(function(){
		lives.push($(this).smap());
		$(this).addClass('live-i-'+live_i);
		live_i++;
	});
	if (lives.length)
		smapAjax('live', {lives: lives}, function(data, success){
			if (success)
				for (var i=0; i<data.result.length; i++){
					var live = $('.live-i-'+i);
					if (!data.result[i])
						live.closest('.live-wrap').slideUp();
					else
						live.replaceWith($(data.result[i]));
				}
		});
});
