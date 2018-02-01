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


// live
smap_add_action('loaded', function(){
	
	var lives = [];
	var init_i = smapInc();
	var live_i = init_i;
	$('.live').each(function(){
		lives.push($(this).smap());
		$(this).addClass('live-i-'+live_i);
		live_i = smapInc();
	});
	if (lives.length)
		smapAjax('live', {lives: lives}, function(data, success){
			if (success){
				var update = $();
				for (var i=0; i<data.result.length; i++){
					var cl = 'live-i-'+(i+init_i);
					var live = $('.'+cl).removeClass(cl);
					update = update.add(live);

					if (!data.result[i]){
						var wrap = live.closest('.live-wrap');
						(wrap.length ? wrap : live).hide();
					} else
						live.replaceWith($(data.result[i]));
				}
				if (update.length)
					smapUpdate(update);
			}
		});
		
		
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
});
