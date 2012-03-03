$(document).ready(function() {
	$("p.InputfieldPageAddButton a").click(function() {
		var $input = $(this).parent('p').next('.InputfieldPageAddItems');
		if($input.is(":visible")) $input.slideUp('fast').find(":input").val('');
			else $input.slideDown('fast').parents('.ui-widget-content').slice(0,1).effect('highlight', {}, 500) 
		return false;
	}); 	
}); 
