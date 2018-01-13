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
	// periodic map refresh

	var refreshing = false;
	var year = $('.map-years a.smap-year-current').data('smap-year');
		
	if (SMAP.refreshMap)
		setInterval(function(){
			updateYear();
		}, 10000);
		
	$('#wrap').on('click', '.map-years a', function(e){
		var a = $(this).addClass('smap-year-current');
		$('.map-years a').not(a).removeClass('smap-year-current');
		
		year = a.data('smap-year');
		
		updateYear(true);
		return false;
	});
	
	function updateYear(manual){
		
		if (refreshing){
			if (manual)
				refreshing.abort();
			else
				return;
		}
		
		if (manual)
			$('.map-fetched .map-table').animate({opacity: 0.4});
			
		refreshing = smapAjax('refreshMap', {year: year, extract: $('.spider-ctrl-extract-button input').is(':checked')}, function(data){
			if (data && data.success){
				var rep = $(data.html);
				$('.map-fetched').replaceWith(rep);
				smapUpdate(rep);
			}
		}, null, function(){
			refreshing = false;
		});
	}
	
	// Spider ctrl fields
	
	$('#wrap').on('click', '.spider-button', function(e){
		var t = $(this);
		var turnOn = !t.filter('.spider-status-waiting, .spider-status-active').length;

		smapAjax('spiderPower', {turnOn: turnOn, schema: t.data('schema')}, function(data, success){
			if (success && data.button){
				t.replaceWith(data.button);
				smapUpdate();
			}
		});
	});
	
	$('#wrap').on('change', '.spider-ctrl-extract-button input', function(e){
		var t = $(this);
		var turnOn = t.is(':checked');

		smapAjax('spiderExtract', {turnOn: turnOn, schema: $('.spider-button').data('schema')}, function(data, success){
			if (success)
				updateYear(true);
		});
	});
	
	var configCallI = 0;
	$('#wrap').on('click', '.spider-ctrl-field-editable', function(e){
		var t = $(this);
		var cprompt = t.data('smap-prompt');
		if (!cprompt)
			return;
		var id = t.data('smap-ctrl-var');
		var val = t.data('smap-ctrl-val');
		var nval = prompt(cprompt, val);
		
		if (nval != val && nval){
			configCallI++;
			(function(i){
				t.find('.spider-ctrl-field-val').html(nval);
				smapAjax('spiderConfig', {configVar: id, configVal: nval}, function(data, success){
					if (success && configCallI == i){
						t.data('smap-ctrl-val', nval);
						updateYear(true);
					} 
					if (!data.success){
						t.data('smap-ctrl-val', val);
						t.find('.spider-ctrl-field-val').html(val);
						
					} else if (data.val){
						t.data('smap-ctrl-val', data.val);
						t.find('.spider-ctrl-field-val').html(data.val);
					}
						
				});
			})(configCallI);
		}
	});
});
