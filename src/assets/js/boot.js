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
	$(document).on('smap_nice_alert_show', function(){
		smap_check_nice_alerts();
	});
	smap_check_nice_alerts();
	
	smapLazyloadCSS(resize); // resize after all CSS are loaded

	// footer positioning
	function resize(){
		$('#main-inner').css({'padding-bottom': $('.footer').outerHeight()});
	}
	$(window).on('resize', function(){ 
		resize();
	});
	
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
			
	// top filters
	$('span.header-filter-ind').click(function(){
		$('body').toggleClass('filters-open');
		$('.header-filters').stop().slideToggle();
	});
	$('#top-filter-advanced .menu-button').click(function(){
		$('.header-filters-advanced').slideToggle();
	});
	
	// init beautiful tooltips
	smapUpdate();
	
	// front warnings
	$('.front-warning span').click(function(){
		$(this).fadeOut('slow');
	});
	
	// keep bulletin iframes at just the right height to show only its scrollbar (and not the window's)
	var iframe_to = null;
	update_iframe();
	$(window).on('resize', function(){ 
		update_iframe();
	});
	
	function update_iframe(){
		if (iframe_to)
			clearTimeout(iframe_to);
		iframe_to = setTimeout(function(){
			var f = $('.footer');
			var winh = $(window).height();
			var diff = f.outerHeight(true);// + f[0].style.bottom;
			$('.bulletin-iframe').each(function(){
				var t = $(this).css({height: 1});
				t.css({height: winh - t.parent().offset().top - diff});
			});
		}, 300);
	}
	
});

function smapUpdate(elt){
	smapHookTips(elt);
}

// nice alerts
$.smapNiceAlert = function(opts){
	var a = $('<div class="nice-alert nice-alert-id-'+opts.id+(opts.class ? ' '+opts.class : '')+'"><i class="fa fa-'+opts.icon+'"></i> '+opts.label+'</div>').prependTo('#main-inner');
	
	var w = a.width();
	a.css({
		right: -w
	});
	a.animate({
		right: 0
	}, 400);
	
	if (opts.timeout)
		setTimeout(function(){
			close_alert();
		}, opts.timeout);
		
	a.click(function(){
		close_alert();
	});
		
	function close_alert(){
		a.not('.closing').addClass('closing').animate({
			right: -w
		}, 400, function(){
			a.remove();
		});
	}
};


function smap_check_nice_alerts(){
	var a = null;
	while (a = SMAP.nice_alerts.shift())
		$.smapNiceAlert(a);
}
