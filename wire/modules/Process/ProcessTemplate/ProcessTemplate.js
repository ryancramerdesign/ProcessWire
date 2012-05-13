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

	// when edit checked or unchecked, update the createRoles to match since they are dependent
	var editRolesClick = function() { 

		var $editRoles = $("#roles_editor input.editRoles"); 

		$editRoles.each(function() { 
			var $t = $(this); 
			if($t.is(":disabled")) return false; 

			var $createRoles = $("input.createRoles[value=" + $t.attr('value') + "]"); 

			if($t.is(":checked")) {
				$createRoles.removeAttr('disabled'); 
			} else {
				$createRoles.removeAttr('checked').attr('disabled', 'disabled'); 
			}
		}); 
		return true; 
	}; 
	$("#roles_editor input.editRoles").click(editRolesClick); 
	editRolesClick();


	$("#wrap_redirectLogin input").click(redirectLoginClick); 

	// ----------------
	// family

	$("#wrap_noChildren input").click(function() {
		if($("#noChildren_0:checked").size() > 0) {
			$("#wrap_childTemplates").slideDown(); 
			$("#sortfield_fieldset").slideDown(); 
		} else {	
			$("#wrap_childTemplates").slideUp(); 
			$("#sortfield_fieldset").slideUp(); 
		}
	}); 
	if($("#noChildren_1:checked").size() > 0) {
		$("#wrap_childTemplates").hide();
		$("#sortfield_fieldset").hide(); 
	}

	$("#wrap_noParents input").click(function() {
		if($("#noParents_0:checked").size() > 0) {
			$("#wrap_parentTemplates").slideDown(); 
		} else {	
			$("#wrap_parentTemplates").slideUp(); 
		}
	}); 
	if($("#noParents_1:checked").size() > 0) $("#wrap_parentTemplates").hide();

	// ----------------

	var adjustCacheExpireFields = function() {

		var $t = $("#wrap_cacheExpire input:checked"); 
		var $f = $("#wrap_cacheExpirePages"); 
		var val = parseInt($t.attr('value')); 

		if(val == 3) { 
			if(!$f.is(":visible")) {
				$f.slideDown();
			}
		} else if($f.is(":visible")) {
			$f.hide();
		}
	}; 

	var adjustCacheFields = function() {

		var val = parseInt($(this).attr('value')); 		

		if(val > 0) {
			if(!$("#wrap_noCacheGetVars").is(":visible")) {
				$("#wrap_useCacheForUsers").slideDown();
				$("#wrap_noCacheGetVars").slideDown();
				$("#wrap_noCachePostVars").slideDown();
				$("#wrap_cacheExpire").slideDown();
				$("#wrap_cacheExpire input:checked").change();
			}

		} else {
			if($("#wrap_noCacheGetVars").is(":visible")) {
				$("#wrap_useCacheForUsers").hide();
				$("#wrap_noCacheGetVars").hide();
				$("#wrap_noCachePostVars").hide();
				$("#wrap_cacheExpire").hide();
				$("#wrap_cacheExpirePages").hide();
			}
		}

	}; 

	$("#wrap_cacheExpire input").change(adjustCacheExpireFields).change();

	$("#cache_time").change(adjustCacheFields).change();

	// -----------------
	// asmSelect fieldgroup indentation
	var fieldgroupFieldsChange = function() {
		$ol = $('#fieldgroup_fields').prev('ol.asmList'); 
		$ol.find('span.asmFieldsetIndent').remove();
		$ol.children('li').children('span.asmListItemLabel').children("a:contains('_END')").each(function() {
			var label = $(this).text();
			if(label.substring(label.length-4) != '_END') return; 
			label = label.substring(0, label.length-4); 
			var $li = $(this).parents('li.asmListItem'); 
			$li.addClass('asmFieldset asmFieldsetEnd'); 
			while(1) { 
				$li = $li.prev('li.asmListItem');
				if($li.size() < 1) break;
				var $span = $li.children('span.asmListItemLabel'); // .children('a');
				var label2 = $span.text();
				if(label2 == label) {
					$li.addClass('asmFieldset asmFieldsetStart'); 
					break;
				}
				$span.prepend($('<span class="asmFieldsetIndent"></span>')); 
			}
		}); 
	};
	$("#fieldgroup_fields").change(fieldgroupFieldsChange).bind('init', fieldgroupFieldsChange); 
		
	adjustAccessFields();
	redirectLoginClick();

        // instantiate the WireTabs
	var $templateEdit = $("#ProcessTemplateEdit"); 
	$templateEdit.find('script').remove();
	$templateEdit.WireTabs({
                items: $(".Inputfields li.WireTab"),
                id: 'TemplateEditTabs',
		skipRememberTabIDs: ['WireTabDelete']
                });

	

}); 
