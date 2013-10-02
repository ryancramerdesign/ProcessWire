$(document).ready(function() {

	$(".ConfigurableModule").each(function() {
		if($(this).parent().is('.not_installed')) return;
		$(this).after($("<i class='icon-gear' style='float: right; margin-top: 3px;'></i>")); 
	}); 

	$(".not_installed").parent("a").css('opacity', 0.6).click(function() {

		var id = $(this).children(".not_installed").attr('id');
		var $btn = $("#install_" + id); 
		var disabled = $btn.attr('disabled'); 	
		
		if(disabled && disabled.length > 0) {
			alert("This module requires other modules to be installed first."); 
		} else {
			if(confirm("Install " + $(this).text() + "?")) $btn.click();
		}

		return false;
	});

    if($('#modules_form').size() > 0) {
        $('#modules_form').WireTabs({
            items: $(".Inputfields li.WireTab")
        });
    }

}); 
