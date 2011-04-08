jQuery(document).ready(function($) {


	$("input.InputfieldDatetimeDatepicker").each(function(n) {

		var $t = $(this); 
		var $hidden = $("<input type='hidden' />"); 
		var val = $t.val();
		var d = new Date(val); 

		$hidden.val(d.getTime()); 
		$t.after($hidden); 

		$hidden.datepicker({
			changeMonth: true,
			changeYear: true,
			showOn: 'button',
			buttonText: "&gt;",
			showAnim: 'fadeIn',
			dateFormat: '@',
			gotoCurrent: true,
			altField: $t,
			altFormat: 'yy-mm-dd'
			
			// buttonImage: config.urls.admin_images + 'icons/calendar.gif',
			// dateFormat: config.date_format
		});

		$t.change(function() {
			var val = $(this).val();
			if(val.length < 1) return;
			var d = new Date(val); 
			$hidden.val(d.getTime()); 
		}); 	
	}); 

}); 
