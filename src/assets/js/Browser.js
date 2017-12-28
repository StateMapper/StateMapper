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

$(document).ready(function(){
	var sugg = {
		input: $('#kaosSearch')
	};
	sugg.initVal = sugg.input.val();
	
	if (!$('body.browser-found').length && sugg.input.val() == '')
		sugg.input.focus();
	
	sugg.wrap = sugg.input.parent().find('.kaosSearchSugg');
	
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
			
		sugg.wrap.outerWidth(sugg.input.outerWidth()).addClass('kaosSearchSugg-loading').show();
		
		$('body').on('click.kaosSeachSugg', function(e){
			if (!$(e.target).closest('.kaosSearchSugg, #kaosSearch').length)
				closeSearch();
		});
		
		(function(query){
			kaosAjax('search', {query: query}, function(data){
				if ($('body').hasClass('search-sending'))
					return;
					
				if (sugg.last == query){
					sugg.open = true;
					sugg.active = null;
					sugg.suggs = sugg.wrap.find('.kaosSearchSugg-results-inner').html(data.results).children('div');
					
					sugg.wrap.removeClass('kaosSearchSugg-loading');
					
					var inner = sugg.wrap.find('.kaosSearchSugg-results-inner');
					var more = sugg.wrap.find('.kaosSearchSugg-results-more');
					
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
		$('body').off('click.kaosSeachSugg');
		
		if (!keepSearchVal && $('body.browser-found').length)
			sugg.input.val(sugg.initVal);
		
		sugg.open = false;
	}
	
	sugg.wrap.on('mouseover', '.kaosSearchSugg-results-inner > div > a', function(e){
		suggSelect($(this));
	});
	
	function suggUnhook(){
		$(document).off('keypress.kaosSearch keydown.kaosSearch');
	}
	
	function suggHook(){
		suggUnhook();
		$(document).on('keypress.kaosSearch keydown.kaosSearch', function(e){
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
			if (v != ''){
				url = KAOS.searchUrl.replace('%s', encodeURIComponent(v));
				
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
				sugg.active.removeClass('kaosSugg-active');
				sugg.active = null;
			}
			return;
		}
		li = li.closest('div');
		if (sugg.active){
			if (sugg.active.get(0) === li.get(0))
				return;
			sugg.active.removeClass('kaosSugg-active');
		}
		sugg.active = li.addClass('kaosSugg-active');
	}
	
	/* stats */
	$('.entity-stats').on('click', '.kaos-entity-stat', function(e){
		var t = $(this);
		var related = $(this).kaosGetRelated();
		var w = t.closest('.entity-stat-wrap');
		var h = w.find('.entity-stat-children-holder');
		if (w.hasClass('entity-stat-children-filled')){
			h.stop().toggle();
			w.toggleClass('entity-stat-children-open');
		} else {
			w.addClass('entity-stat-children-open');
			w.addClass('entity-stat-children-filled');
			w.addClass('entity-stat-children-loading');
			kaosAjax('loadStatuses', {related: related}, function(data){
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
			$(this).parent().find('.kaos-folding').first().toggle();
		}
	});
	
	// status actions
	$('body').on('click', '.status-action', function(e){
		var t = $(e.target).closest('a');
		var related = t.kaosGetRelated();
		var action = t.data('kaos-status-action');
		kaosAjax('statusAction', {status_action: action, related: related}, function(data, success){
			debugger;
		});
		return false;
	});
});

$.fn.kaosGetRelated = function(){
	var c = $(this).data('kaos-related');
	console.log(c);
	var ret = c ? c : {};
	$(this).parents('[data-kaos-related]').each(function(){
		$.extend(ret, $(this).data('kaos-related'));
	});
	return ret;
};
