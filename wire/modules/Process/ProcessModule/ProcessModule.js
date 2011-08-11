$(document).ready(function() {
	$(".ConfigurableModule").each(function() {
		$(this).after($("<span class='ui-icon ui-icon-gear' style='float: right;'></span>").css('opacity', 0.5)); 
	}); 
	$(".not_installed").parent("a").css('opacity', 0.6).click(function() {
		var id = $(this).children(".not_installed").attr('id');
		if(confirm("Do you want to install " + $(this).text() + "?")) {
			$("#install_" + id).click();
		}
		return false;
	}); 
	$("#wrap_reset").parent(".Inputfields").prependTo($("#modules_form")); 
}); 
