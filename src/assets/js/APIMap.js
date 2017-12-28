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
	// periodic map refresh

	var refreshing = false;
	var year = $('.kaos-api-years a.kaos-year-current').data('kaos-year');
		
	if (KAOS.refreshMap)
		setInterval(function(){
			updateYear();
		}, 10000);
		
	$('#wrap').on('click', '.kaos-api-years a', function(e){
		var a = $(this).addClass('kaos-year-current');
		$('.kaos-api-years a').not(a).removeClass('kaos-year-current');
		
		year = a.data('kaos-year');
		
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
			$('.kaos-api-fetched .kaos-api-map-table').animate({opacity: 0.4});
			
		refreshing = kaosAjax('refreshMap', {year: year, extract: $('.spider-ctrl-extract-button input').is(':checked')}, function(data){
			if (data && data.success){
				var rep = $(data.html);
				$('.kaos-api-fetched').replaceWith(rep);
				kaosUpdate(rep);
			}
		}, null, function(){
			refreshing = false;
		});
	}
	
	// Spider ctrl fields
	
	$('#wrap').on('click', '.kaos-api-spider-button', function(e){
		var t = $(this);
		var turnOn = !t.filter('.kaos-spider-status-waiting, .kaos-spider-status-active').length;

		kaosAjax('spiderPower', {turnOn: turnOn, schema: t.data('kaos-schema')}, function(data, success){
			if (success && data.button){
				t.replaceWith(data.button);
				kaosUpdate();
			}
		});
	});
	
	$('#wrap').on('change', '.spider-ctrl-extract-button input', function(e){
		var t = $(this);
		var turnOn = t.is(':checked');

		kaosAjax('spiderExtract', {turnOn: turnOn, schema: $('.kaos-api-spider-button').data('kaos-schema')}, function(data, success){
			if (success)
				updateYear(true);
		});
	});
	
	var configCallI = 0;
	$('#wrap').on('click', '.kaos-spider-ctrl-field-editable', function(e){
		var t = $(this);
		var cprompt = t.data('kaos-prompt');
		if (!cprompt)
			return;
		var id = t.data('kaos-ctrl-var');
		var val = t.data('kaos-ctrl-val');
		var nval = prompt(cprompt, val);
		
		if (nval != val && nval){
			configCallI++;
			(function(i){
				t.find('.kaos-spider-ctrl-field-val').html(nval);
				kaosAjax('spiderConfig', {configVar: id, configVal: nval}, function(data, success){
					if (success && configCallI == i){
						t.data('kaos-ctrl-val', nval);
						updateYear(true);
					} 
					if (!data.success){
						t.data('kaos-ctrl-val', val);
						t.find('.kaos-spider-ctrl-field-val').html(val);
						
					} else if (data.val){
						t.data('kaos-ctrl-val', data.val);
						t.find('.kaos-spider-ctrl-field-val').html(data.val);
					}
						
				});
			})(configCallI);
		}
	});
});
