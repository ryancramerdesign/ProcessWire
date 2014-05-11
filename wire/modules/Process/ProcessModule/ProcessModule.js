$(document).ready(function() {

	/*
	$(".ConfigurableModule").each(function() {
		if($(this).parent().is('.not_installed')) return;
		$(this).after($("<i class='fa fa-cog' style='float: right; margin-top: 3px;'></i>")); 
	}); 
	*/

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

	$("button.ProcessModuleSettings").click(function() {
		console.log('click'); 
		var $a = $(this).parents('tr').find('.ConfigurableModule').parent('a');
		window.location.href = $a.attr('href'); 
	}); 

    if($('#modules_form').size() > 0) {
        $('#modules_form').WireTabs({
            items: $(".Inputfields li.WireTab"),
			rememberTabs: true
        });
    }
	
	$("select.modules_section_select").change(function() {
		var section = $(this).val();
		var $sections = $(this).parent('p').siblings('.modules_section')
		if(section == '') {
			$sections.show();
		} else {
			$sections.hide();
			$sections.filter('.modules_' + section).show();
		}
		document.cookie = $(this).attr('name') + '=' + section;
		return true; 
	}).change();


	$(document).on('click', '#head_button a', function() { 
		// when check for new modules is pressed, make sure the next screen goes to the 'New' tab
		document.cookie = 'WireTabs=tab_new_modules';
		return true; 
	});

}); 
