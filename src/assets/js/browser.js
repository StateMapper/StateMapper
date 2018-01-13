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
	var sugg = {
		input: $('#search-input')
	};
	sugg.initVal = sugg.input.val();
	
	if (!$('body.has-results').length && sugg.input.val() == '' && jQuery(window).scrollTop() < 50)
		sugg.input.focus();
	
	sugg.wrap = sugg.input.parent().find('.search-suggs');
	
	$('.browser-big-submit-button').click(function(e){
		searchSend(e.ctrlKey);
	});
		
	sugg.input.on('keypress keyup change focus', function(e){
		
		if ($.inArray(e.type, ['keypress', 'keyup']) >= 0){
			switch (e.which){
				case 13: // enter
					if (e.type == 'keypress')
						searchSend(e.ctrlKey);
					return false;
				case 27: // escape
					sugg.input.blur();
					return false;
			}
		}
		
		if (!sugg.to)
			sugg.to = setTimeout(function(){
				sugg.to = null;
				suggUpdate();
			}, 500);
	});
	
	sugg.input.on('focus', function(){
		sugg.input.select();
	});
	
	function suggUpdate(){
		if (!sugg.input.is(':focus'))
			closeSearch();
		else {
			var nval = $.trim(sugg.input.val());
			if (!sugg.last || sugg.last != nval || !sugg.open){
				
				if (nval != '')
					openSearch(nval);
				else
					openSearchIntro();
				
				sugg.last = nval;
			}
		}
	}
	
	function openSearch(nval){
		if ($('body').hasClass('search-sending'))
			return;
			
		sugg.wrap.outerWidth(sugg.input.outerWidth()).addClass('search-suggs-loading').show();
		
		$('body').on('click.smapSeachSugg', function(e){
			if (!$(e.target).closest('.search-suggs, #search-input').length)
				closeSearch();
		});
		
		(function(query){
			smapAjax('search', {query: query}, function(data){
				if ($('body').hasClass('search-sending'))
					return;
					
				if (sugg.last == query){
					sugg.open = true;
					sugg.active = null;
					sugg.suggs = sugg.wrap.find('.search-suggs-results-inner').html(data.results).children('div');
					
					sugg.wrap.removeClass('search-suggs-loading');
					
					var inner = sugg.wrap.find('.search-suggs-results-inner');
					var more = sugg.wrap.find('.search-suggs-results-more');
					
					if (data.resultsMore)
						more.html(data.resultsMore).show();
					else
						more.hide();
					
					inner.css({'max-height': $(window).height() - sugg.wrap.offset().top - more.height() - 30});
					
					suggHook();
				}
			});
		})(nval);
	}
	
	function openSearchIntro(){
		// TODO: add a search tip on the homepage! (because centering doesn't allow input placeholder!)
		sugg.wrap.hide();
	}
	
	function closeSearch(keepSearchVal){
		suggUnhook();
		sugg.wrap.hide();
		$('body').off('click.smapSeachSugg');
		
		if (!keepSearchVal && $('body.has-results').length)
			sugg.input.val(sugg.initVal);
		
		sugg.open = false;
	}
	
	sugg.wrap.on('mouseover', '.search-suggs-results-inner > div > a', function(e){
		suggSelect($(this));
	});
	
	function suggUnhook(){
		$(document).off('keypress.search-input keydown.search-input');
	}
	
	function suggHook(){
		suggUnhook();
		$(document).on('keypress.search-input keydown.search-input', function(e){
			switch (e.which){
				case 38: // up
				case 40: // down
					var down = e.which == 40;
					if (sugg.suggs)
						suggSelect(sugg.active ? sugg.active[down ? 'next' : 'prev']() : sugg.suggs[down ? 'first' : 'last']());
					return false;
					
				case 13: // enter
					searchSend(e.ctrlKey);
					return false;
					
				case 9: // tab
				case 27: // escape
					closeSearch();
					sugg.input.blur();
					return;
			}
		});
	}
	
	function searchSend(new_tab){
		var url = null;
		$('body').addClass('search-sending');
		if (sugg.active){
			url = sugg.active.find('a')[0].href;
		} else {
			closeSearch(true);
			var v = $.trim(sugg.input.val());
			if (v != '' || !$('body').hasClass('root')){ // do not send empty queries from homepage
				url = SMAP.searchUrl.replace('%s', encodeURIComponent(v));
				
				// replace or add &q=
				url = url.replace(/(([&\?])[^&]+=)(&(.*))?$/, '$2$4');
				url = url.replace(/[&\?]?[&\?]$/, '');
			} else
				sugg.input.focus();
		}
		if (url){
			if (new_tab)
				window.open(url);
			else
				window.location = url;
		}
	}

	function suggSelect(li){
		if (!li.length){
			if (sugg.active){
				sugg.active.removeClass('sugg-active-active');
				sugg.active = null;
			}
			return;
		}
		li = li.closest('div');
		if (sugg.active){
			if (sugg.active.get(0) === li.get(0))
				return;
			sugg.active.removeClass('sugg-active-active');
		}
		sugg.active = li.addClass('sugg-active-active');
	}
	
	/* stats */
	$('.entity-stats').on('click', '.entity-stat', function(e){
		var t = $(this);
		var related = $(this).smapGetRelated();
		var w = t.closest('.entity-stat-wrap');
		var h = w.find('.entity-stat-children-holder');
		if (w.hasClass('entity-stat-children-filled')){
			h.stop().toggle();
			w.toggleClass('entity-stat-children-open');
		} else {
			w.addClass('entity-stat-children-open');
			w.addClass('entity-stat-children-filled');
			w.addClass('entity-stat-children-loading');
			smapAjax('loadStatuses', {related: related}, function(data){
				if (data.success){
					$(data.html).appendTo(h);
					h.stop().show();
				}
			}, function(){
				// error
				w.removeClass('entity-stat-children-open');
				w.removeClass('entity-stat-children-filled');
			}, function(){
				// complete
				w.removeClass('entity-stat-children-loading');
			});
		}
	});
	
	/* extracts from title click */
	$('.entity-stats').on('click', '.status-title', function(e){
		if (!$(e.target).closest('a').length){
			$(this).parent().find('.folding').first().toggle();
		}
	});
	
	// status actions
	$('body').on('click', '.status-action', function(e){
		var t = $(e.target).closest('a');
		var related = t.smapGetRelated();
		var action = t.data('status-action');
		smapAjax('statusAction', {status_action: action, related: related}, function(data, success){
			debugger;
		});
		return false;
	});
	
	// infinite loading
	var l = $('.infinite-loader');
	if (l.length){
		var lto = null;
		$(window).on('scroll.smap-scroll', function(){
			infinite_check();
		});
		infinite_check();
		
		function infinite_check(){
			if (lto !== null)
				return;
				
			lto = setTimeout(function(){
				lto = true;
				var win_top = $(window).scrollTop();
				var win_h = $(window).height();
				var loading = 0;
				var done = false;
				l.each(function(){
					var t = $(this);
					var diff = win_top + win_h - t.offset().top + Math.min(win_h / 2, 450);
					if (diff > 0){
						
						// load another page
						loading++;
						smapAjax('loadMoreResults', $(this).smap(), function(data, success){
							loading--;
							if (success){
								var wrap = t.parent();
								t.remove();
								wrap.children().removeClass('last');
								wrap.append(data.results);
								wrap.closest('.search-wrap').find('.search-results-intro').html(data.resultsLabel);
								
								// update scroll detection
								l = $('.infinite-loader');
								if (!l.length)
									$('body').off('scroll.smap-scroll');
							}
							if (!loading && done)
								lto = null;
						});
						
					}
				});
				if (!loading)
					lto = null;
				done = true;
			}, 100);
		}
	}
	
	$('.search-results').on('click', '.results-outro a', function(){
		sugg.input.focus();
		return false;
	});
});

$.fn.smap = function(key){
	return this.data('smap-'+(key ? key : 'related'));
};

$.fn.smapGetRelated = function(){
	var c = $(this).smap();
	console.log(c);
	var ret = c ? c : {};
	$(this).parents('[data-smap-related]').each(function(){
		$.extend(ret, $(this).data('smap-related'));
	});
	return ret;
};
