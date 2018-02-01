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

	SMAP.acMinHeightSuggs = 3;
	
	var sugg = {
		input: $('#search-input'),
		cache: {}
	};
	sugg.input_opts = sugg.input.smap();
	sugg.initVal = sugg.input.val();
	
	smap_add_action('loaded', function(){
		if (!$('body.has-results').length && sugg.initVal == '' && $(window).scrollTop() < 50)
			sugg.input.focus();
	});
	
	sugg.wrap = sugg.input.parent().find('.search-suggs');
	
	// homepage's submit button
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
	
	var suggResizeTo = null;
	$(window).on('resize', function(){
		if (suggResizeTo)
			clearTimeout(suggResizeTo);
		suggResizeTo = setTimeout(function(){
			suggResize();
		}, 100);
	});
	
	function suggResize(){
		// adjust sugg wrap's width
		sugg.wrap.outerWidth(sugg.input.outerWidth());
		
		// adjust sugg wrap's height
		if (sugg.inner && sugg.open){
			sugg.wrap.show();
			
			var bottom = sugg.wrap.offset().top + (sugg.more ? sugg.more.height() : 0);

			SMAP.acMinHeight = 1;
			var suggs = sugg.inner.find('.sugg-none').add(sugg.suggs);
			for (var i=0; i<SMAP.acMinHeightSuggs; i++)
				if (i < suggs.length)
					SMAP.acMinHeight += suggs.eq(i).height();

			sugg.wrap.hide();
			var maxh = $('body').height();//$(window).height() + $(window).scrollTop();
			sugg.inner.css({'max-height': Math.max(SMAP.acMinHeight, maxh - bottom - 30)});
			sugg.wrap.show();
		}
	}
	
	function openSearch(nval){
		if ($('body').hasClass('search-sending'))
			return;
		var loading = sugg.input_opts && (sugg.input_opts.loading || sugg.input_opts.loading === false) && sugg.suggs && sugg.suggs.length ? sugg.input_opts.loading : SMAP.loading;

		if (loading){
			sugg.wrap.addClass('search-suggs-loading');
			sugg.wrap.show();
			suggResize();
		} 
			
		$('body').off('click.smapSeachSugg').on('click.smapSeachSugg', function(e){
			if (!$(e.target).closest('.search-suggs, #search-input').length)
				closeSearch();
		});
		
		if (sugg.cache[nval])
			appendResults(sugg.cache[nval]);
		else {
			(function(query){
				smapAjax('search', {query: query}, function(data){
					sugg.cache[query] = data;
					
					if ($('body').hasClass('search-sending'))
						return;
						
					if (sugg.last == query)
						appendResults(data);
				});
			})(nval);
		}
		
		function appendResults(data){
			sugg.open = true;
			sugg.active = null;

			sugg.inner = sugg.wrap.find('.search-suggs-results-inner').html(data.results);
			sugg.suggs = sugg.inner.find('.sugg');
			sugg.more = sugg.wrap.find('.search-suggs-results-more');

			sugg.wrap.removeClass('search-suggs-loading').show();
			smapUpdate(sugg.inner);
			
			if (data.resultsMore){
				sugg.more.html(data.resultsMore).show();
				smapUpdate(sugg.more);
			} else
				sugg.more.hide();
			
			suggHook();
			sugg.scrollbar = new SimpleBar(sugg.inner[0]);
			suggResize();
		}
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
	
	sugg.wrap.on('mouseover', '.sugg > a', function(e){
		if (!sugg.keymoving)
			suggSelect($(this), e);
	});
	
	$('body').on('mousemove', function(){
		sugg.keymoving = false;
	});
	
	function suggUnhook(){
		$(document).off('keypress.search-input keydown.search-input');
	}
	
	function suggHook(){
		suggUnhook();
		$(document).on('keypress.search-input keydown.search-input', function(e){
			
			sugg.keymoving = true; // lock mouveover while using keys
					
			$('body').off('mousemove.smap_suggs').on('mousemove.smap_suggs', function(){
				$('body').off('mousemove.smap_suggs');
				sugg.keymoving = false;
			});
			
			var count = 1;
			switch (e.which){
				/*
				 * keep home/end for the input
				case 36: // home
				case 35: // end
					count = 0;
					*/
					
				case 33: // page up
				case 34: // page down
					if ($.inArray(e.which, [33, 34]) >= 0)
						count = 10;
				
				case 38: // up
				case 40: // down
					var down = $.inArray(e.which, [34, 35, 40]) >= 0;
					if (sugg.suggs){
						var next = sugg.active;
						if (count){
							var cnext = null;
							for (var i=0; i<count; i++){
								cnext = next ? next[down ? 'next' : 'prev']() : sugg.suggs[down ? 'first' : 'last']();
								if (!cnext.length){
									if (count == 1)
										next = cnext;
									break;
								}
								next = cnext;
							}
						} else
							next = sugg.suggs[down ? 'last' : 'first']();
						suggSelect(next, e);
					}
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

	function suggSelect(li, e){
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
		
		if (e && e.type == 'mouseover')
			return;
		
		// scroll to li
		var h = sugg.active.height();
		var top = sugg.active.position(sugg.inner).top;
		var scrollElt = $(sugg.scrollbar.getScrollElement());
		
		if (top < 0)
			scrollElt.stop().animate({scrollTop: scrollElt.scrollTop() + top - 30}, 'fast');
		else if (top + h > sugg.inner.height())
			scrollElt.stop().animate({scrollTop: scrollElt.scrollTop() + top + h - sugg.inner.height() + 30}, 'fast');
	}
});
