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

smap_add_action('load', function(){
	
	// infinite loading
	var l = $('.infinite-loader.infinite-autoload');
	var loading = 0;
	var lto = null;
	
	if (l.length)
		infinite_check();

	$(window).off('scroll.smap-scroll').on('scroll.smap-scroll', function(){
		infinite_check();
	});
	
	function infinite_check(){
		if (lto !== null)
			return;
			
		lto = setTimeout(function(){
			lto = true;
			var win_top = $(window).scrollTop();
			var win_h = $(window).height();
			l.each(function(){
				var t = $(this);
				var diff = win_top + win_h - t.offset().top + Math.min(win_h / 2, 450);
				if (diff > 0){
					load(t);
				}
			});
			if (!loading)
				lto = null;
			done = true;
		}, 100);
	}
	
	$('a.infinite-loader').off('click.smap_infinite').on('click.smap_infinite', function(){
		var opts = $(this).hide().smap();
		var t = $($(this).data('smap-loading')).insertAfter($(this));
		$(this).remove();
		load(t, opts);
		return false;
	});
	
	function load(t, opts){
		var done = false;
		var wrap = t.parent();

		if (wrap.filter('.loading').length)
			return;
		wrap.addClass('loading');
		

		// load another page
		loading++;
		smapAjax('loadMoreResults', opts ? opts : t.smap(), function(data, success){
			loading--;
			if (success){
				wrap.removeClass('loading');
				t.remove();
				wrap.children().removeClass('last');
				
				var update = $(data.results).appendTo(wrap);
				update = update.add(wrap.closest('.search-wrap').find('.search-results-intro').html(data.resultsLabel));
				
				smapUpdate(update);
				
				// update scroll detection
				l = $('.infinite-loader.infinite-autoload');
				if (!l.length)
					$('body').off('scroll.smap-scroll');
			}
			if (!loading && done){
				lto = null;
				infinite_check();
			}
		});
	}
});
