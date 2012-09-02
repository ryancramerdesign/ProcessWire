jQuery(document).ready(function($) {

	$("input.InputfieldDatetimeDatepicker").each(function(n) {

		var $t = $(this); 
		var pickerVisible = $t.is(".InputfieldDatetimeDatepicker2");
		var ts = parseInt($t.attr('data-ts')); 
		var tsDate = null;
		var dateFormat = $t.attr('data-dateformat'); 
		var timeFormat = $t.attr('data-timeformat');
		var hasTimePicker = timeFormat.length > 0 && !pickerVisible;
		var showOn = $t.is(".InputfieldDatetimeDatepicker3") ? 'focus' : 'button';
		var ampm = parseInt($t.attr('data-ampm')) > 0; 
		var yearRange = $t.attr('data-yearrange'); 

		if(ts > 1) tsDate = new Date(ts); 

		if(pickerVisible) {
			// datepicker always visible (inline)
			var $datepicker = $("<div></div>"); 
			$t.parent('p').after($datepicker); 
		} else {
			// datepicker doesn't appear till requested
			var $datepicker = $t; 
		}

		var options = {
			changeMonth: true,
			changeYear: true,
			showOn: showOn,
			buttonText: "&gt;",
			showAnim: 'fadeIn',
			dateFormat: dateFormat,
			gotoCurrent: true,
			defaultDate: tsDate
			// buttonImage: config.urls.admin_images + 'icons/calendar.gif',
			// dateFormat: config.date_format
		}; 

		if(yearRange && yearRange.length) options.yearRange = yearRange; 

		if(hasTimePicker) { 
			options.ampm = ampm; 
			options.timeFormat = timeFormat; 
			if(timeFormat.indexOf('ss') > -1) options.showSecond = true; 
			if(timeFormat.indexOf('m') == -1) options.showMinute = false;
			$datepicker.datetimepicker(options); 
		} else {
			$datepicker.datepicker(options); 
		}

		if(pickerVisible) {
			$datepicker.change(function(e) {
				var d = $datepicker.datepicker('getDate');
				var str = $.datepicker.formatDate(dateFormat, d); 
				$t.val(str); 
			}); 
		}
			

	}); 

}); 
