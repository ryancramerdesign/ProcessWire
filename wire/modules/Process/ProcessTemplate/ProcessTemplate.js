$(document).ready(function() {


	$("#wrap_filter_system input").click(function() {
		$(this).parents("form").submit();
	}); 	

	$("#filter_field").change(function() {
		$(this).parents("form").submit();
	}); 

	var redirectLoginClick = function() {
		if($("#redirectLogin_-1:checked").size() > 0) $("#wrap_redirectLoginURL").slideDown();
			else $("#wrap_redirectLoginURL").hide();
	}

	var adjustAccessFields = function() {

		var items = [ '#wrap_redirectLogin', '#wrap_guestSearchable' ]; 

		if($("#roles_37").is(":checked")) {
			$("#wrap_redirectLoginURL").hide();

			$(items).each(function(key, value) {
				var $item = $(value);
				if($item.is(".InputfieldStateCollapsed")) {
					$item.hide();
				} else {
					$item.slideUp();
				}
			}); 

		} else {

			$(items).each(function(key, value) {
				var $item = $(value); 
				if($item.is(":visible")) return;
				$item.slideDown("fast", function() {
					if(!$item.is(".InputfieldStateCollapsed")) return; 
					$item.find(".InputfieldStateToggle").click();
				}); 
			}); 
			redirectLoginClick();
		}
	}; 

	$("#wrap_useRoles input").click(function() {
		if($("#useRoles_1:checked").size() > 0) {
			$("#useRolesYes").slideDown();
			$("#wrap_useRoles > label").click();
		} else {
			$("#useRolesYes").slideUp(); 
		}
	
	});
	if($("#useRoles_0:checked").size() > 0) $("#useRolesYes").hide();

	$("#roles_37").click(adjustAccessFields); 

	$("#wrap_redirectLogin input").click(redirectLoginClick); 

	var adjustCacheFields = function() {
		var val = parseInt($(this).attr('value')); 		
		if(val > 0) {
			if(!$("#wrap_noCacheGetVars").is(":visible")) {
				$("#wrap_useCacheForUsers").slideDown();
				$("#wrap_noCacheGetVars").slideDown();
				$("#wrap_noCachePostVars").slideDown();
			}

		} else {
			if($("#wrap_noCacheGetVars").is(":visible")) {
				$("#wrap_useCacheForUsers").hide();
				$("#wrap_noCacheGetVars").hide();
				$("#wrap_noCachePostVars").hide();
			}
		}
	}; 

	$("#cache_time").change(adjustCacheFields).change();
		
	adjustAccessFields();
	redirectLoginClick();

        // instantiate the WireTabs
	$("#ProcessTemplateEdit").WireTabs({
                items: $(".Inputfields li.WireTab"),
                id: 'TemplateEditTabs'
                });

	

}); 
