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

			$("input.viewRoles").attr('checked', 'checked'); 	

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
			$("#wrap_redirectLogin").hide();
			$("#wrap_guestSearchable").hide();
			$("#useRolesYes").slideDown();
			$("#wrap_useRoles > label").click();
			$("input.viewRoles").attr('checked', 'checked'); 
		} else {
			$("#useRolesYes").slideUp(); 
		}
	});


	if($("#useRoles_0:checked").size() > 0) $("#useRolesYes").hide();

	$("#roles_37").click(adjustAccessFields);
	$("input.viewRoles:not(#roles_37)").click(function() {
		// prevent unchecking 'view' for other roles when 'guest' role is checked
		var $t = $(this);
		if($("#roles_37").is(":checked")) return false;
		return true; 
	}); 

	$("#wrap_redirectLogin input").click(redirectLoginClick); 

	// ----------------
	// family

	$("#wrap_noChildren input").click(function() {
		if($("#noChildren_0:checked").size() > 0) {
			$("#wrap_childTemplates").slideDown(); 
		} else {	
			$("#wrap_childTemplates").slideUp(); 
		}
	}); 
	if($("#noChildren_1:checked").size() > 0) $("#wrap_childTemplates").hide();

	$("#wrap_noParents input").click(function() {
		if($("#noParents_0:checked").size() > 0) {
			$("#wrap_parentTemplates").slideDown(); 
		} else {	
			$("#wrap_parentTemplates").slideUp(); 
		}
	}); 
	if($("#noParents_1:checked").size() > 0) $("#wrap_parentTemplates").hide();

	// ----------------

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
	var $templateEdit = $("#ProcessTemplateEdit"); 
	$templateEdit.find('script').remove();
	$templateEdit.WireTabs({
                items: $(".Inputfields li.WireTab"),
                id: 'TemplateEditTabs'
                });

	

}); 
