$(document).ready(function() {

	$(".ConfigurableModule").each(function() {
		$(this).after($("<span class='ui-icon ui-icon-gear' style='float: right;'></span>").css('opacity', 0.5)); 
	}); 

	$(".not_installed").parent("a").css('opacity', 0.6).click(function() {

		var id = $(this).children(".not_installed").attr('id');
		var $btn = $("#install_" + id); 
		var disabled = $btn.attr('disabled'); 	
		
		if(disabled && disabled.length > 0) {
			alert("This module requires other modules to be installed first."); 
		} else {
			if(confirm("Do you want to install " + $(this).text() + "?")) $btn.click();
		}

		return false;
	}); 

}); 
