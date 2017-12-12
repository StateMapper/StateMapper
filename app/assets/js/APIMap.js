
jQuery(document).ready(function(){
	// periodic map refresh

	var refreshing = false;
	var year = jQuery('.kaos-api-years a.kaos-year-current').data('kaos-year');
		
	if (KAOS.refreshMap)
		setInterval(function(){
			updateYear();
		}, 10000);
		
	jQuery('#wrap').on('click', '.kaos-api-years a', function(e){
		var a = jQuery(this).addClass('kaos-year-current');
		jQuery('.kaos-api-years a').not(a).removeClass('kaos-year-current');
		
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
			jQuery('.kaos-api-fetched .kaos-api-map-table').animate({opacity: 0.4});
			
		refreshing = kaosAjax('refreshMap', {year: year, extract: jQuery('.spider-ctrl-extract-button input').is(':checked')}, function(data){
			if (data && data.success){
				var rep = jQuery(data.html);
				jQuery('.kaos-api-fetched').replaceWith(rep);
				kaosUpdate(rep);
			}
		}, null, function(){
			refreshing = false;
		});
	}
	
	// Spider ctrl fields
	
	jQuery('#wrap').on('click', '.kaos-api-spider-button', function(e){
		var t = jQuery(this);
		var turnOn = !t.filter('.kaos-spider-status-waiting, .kaos-spider-status-active').length;

		kaosAjax('spiderPower', {turnOn: turnOn, schema: t.data('kaos-schema')}, function(data, success){
			if (success && data.button){
				t.replaceWith(data.button);
				kaosUpdate();
			}
		});
	});
	
	jQuery('#wrap').on('change', '.spider-ctrl-extract-button input', function(e){
		var t = jQuery(this);
		var turnOn = t.is(':checked');

		kaosAjax('spiderExtract', {turnOn: turnOn, schema: jQuery('.kaos-api-spider-button').data('kaos-schema')}, function(data, success){
			if (success)
				updateYear(true);
		});
	});
	
	var configCallI = 0;
	jQuery('#wrap').on('click', '.kaos-spider-ctrl-field-editable', function(e){
		var t = jQuery(this);
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
