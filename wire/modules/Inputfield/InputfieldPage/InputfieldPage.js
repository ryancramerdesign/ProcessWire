$(document).ready(function() {
	$("p.InputfieldPageAddButton a").click(function() {
		var $input = $(this).parent('p').next('.InputfieldPageAddItems');
		if($input.is(":visible")) $input.slideUp('fast').find(":input").val('');
			else $input.slideDown('fast').parents('.ui-widget-content').slice(0,1).effect('highlight', {}, 500) 
		return false;
	}); 	

	// support for dependent selects
	$(".InputfieldPage .findPagesSelector").each(function() {
		
		var $t = $(this);
		var selector = $t.val();
		// if there is no "=page." present in the selector, then this can't be a dependent select
		if(selector.indexOf('=page.') == -1) return;
		var labelFieldName = $t.attr('data-label');
		// if it doesn't contain a dynamic request from the page, then stop now
		
		var $wrap = $t.parents(".InputfieldPage");
		var $select = $('select#' + $wrap.attr('id').replace(/^wrap_/, ''));

		if($select.size() < 1) return;

		var parts = selector.match(/(=page.[_a-zA-Z0-9]+)/g);

		for(var n = 0; n < parts.length; n++) {
			
			var part = parts[n];
			var name = part.replace('=page.', '');
			var $inputfield = $('#Inputfield_' + name); 
			if($inputfield.size() < 1) return;
		
			// monitor changes to the dependency field
			$inputfield.change(function() {
				var s = selector; 		
				var v = $inputfield.val();
				if(v == null) {
					// no values selected
					$select.children().remove();
					$select.change();
					return;
				}
				v = v.toString();
				v = v.replace(/,/g, '|'); // if multi-value field, convert commas to pipes
				s = s.replace(part, '=' + v); 
				s = s.replace(/,\s*/g, '&');
				var url = config.urls.admin + 'page/search/for?' + s + '&limit=999&get=' + labelFieldName; 
				$.getJSON(url, {}, function(data) {
					//$select.children().remove();
					$select.children().addClass('option-tbd'); // mark existing options as to-be-deleted
					for(n = 0; n < data.matches.length; n++) {
						var page = data.matches[n]; 
						// first see if we can find the existing option already present
						var $option = $select.children("[value=" + page.id + "]"); 
						// if that option isn't already there, then make a new one
						var selected = false;	
						if($option.size() > 0) selected = $option.is(":checked");  
						$option.remove();
						var $option = $("<option value='" + page.id + "'>" + page[labelFieldName] + "</option>"); 
						if(selected) $option.attr('selected', 'selected'); 
						// add the <option> to the <select>
						$select.append($option);
					}
					$select.children(".option-tbd").remove();
					$select.change();
				}); 
			});
			
		}
	});
}); 
