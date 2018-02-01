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



var tips = [];
function smapHookTips(elt){
	
	//for (var i=0; i<tips.length; i++)
	//	tips[i].destroyAll();
	//tips = [];
	
	if (!elt)
		elt = $('body');

	var elts = elt.find('[title]').not('.tipped').addClass('tipped');
	if (elts.length){
		var i = smapInc();
		elts.addClass('tipping-'+i);
		tips.push(tippy('.tipping-'+i, {
			arrow: true,
			arrowtransform: 'scale(0.75) translateY(-1.5px)',
			distance: 15,
			position: 'bottom'
		}));
	}
	
	// hook entity names' popups
	var elts = elt.find('.inline-entity').not('.tipped').addClass('tipped');
	if (elts.length){
		var i = smapInc();
		elts.addClass('tipping-'+i);
		tips.push(tippy('.tipping-'+i, {
			arrow: true,
			arrowtransform: 'scale(0.75) translateY(-1.5px)',
			interactive: true,
			delay: 600,
			distance: 16,
			offset: '-10, 0',
			duration: 100,
			placement: 'right-start',
			theme: 'smap-entity',
			html: '#entity-popup',
			onShow: function(){
				var c = $(this._reference).smapGetRelated();
				var inner = $(this).find('.inline-popup-inner');
				
				if (inner.is('.loaded, .loading'))
					return;
				
				var loading = c && (c.loading || c.loading === false)  ? c.loading : SMAP.loading;
				if (loading)
					inner.addClass('loading').html(loading);
				
				smapAjax('inline_popup', c, function(data, success){
					inner.removeClass('loading');
					if (success){
						inner.addClass('loaded').html(data.html);
						smapUpdate(inner);
					} else
						inner.html('Error');
					
				}); // @todo: show/alert errors if any
			},
			onShown: function(){
				smapUpdate($(this).find('.inline-popup-inner'));
			}
		}));
	}
	
	
	// hook entity names' popups
	var elts = $('.inline-entity').not('.tipped').addClass('tipped');
	if (elts.length){
		var i = smapInc();
		elts.addClass('tipping-'+i);
		tips.push(tippy('.tipping-'+i, {
			arrow: true,
			arrowtransform: 'scale(0.75) translateY(-1.5px)',
			interactive: true,
			delay: 600,
			distance: 16,
			offset: '-10, 0',
			duration: 100,
			placement: 'right-start',
			theme: 'smap-entity',
			html: '#entity-popup',
			onShow: function(){
				var c = $(this._reference).smapGetRelated();
				var inner = $(this).find('.inline-popup-inner');
				
				if (inner.is('.loaded, .loading'))
					return;
				
				var loading = c && (c.loading || c.loading === false)  ? c.loading : SMAP.loading;
				if (loading)
					inner.addClass('loading').html(loading);
				
				smapAjax('inline_popup', c, function(data, success){
					inner.removeClass('loading');
					if (success){
						inner.addClass('loaded').html(data.html);
						smapUpdate(inner);
					} else
						inner.html('Error');
					
				}); // @todo: show/alert errors if any
			},
			onShown: function(){
				smapUpdate($(this).find('.inline-popup-inner'));
			}
		}));
	}
}
