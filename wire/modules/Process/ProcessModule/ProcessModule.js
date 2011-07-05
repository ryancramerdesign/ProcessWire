$(document).ready(function() {
	$(".ConfigurableModule").each(function() {
		$(this).after($("<span class='ui-icon ui-icon-gear' style='float: right;'></span>").css('opacity', 0.5)); 
	}); 
}); 
