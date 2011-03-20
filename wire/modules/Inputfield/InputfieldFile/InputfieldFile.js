$(document).ready(function() {

	$(".InputfieldFileSort").hide();

	$(".InputfieldFileList").each(function() {

		$(this).find(".InputfieldFileDelete input").css({
			position: 'absolute', 
			top: '0', 
			right: '0', 
			zIndex: '-1'
		}).change(function() {
			if($(this).is(":checked")) {
				// not an error, but we want to highlight it in the same manner
				$(this).parents(".InputfieldFileInfo").addClass("ui-state-error")
					.siblings(".InputfieldFileData").slideUp();

			} else {
				$(this).parents(".InputfieldFileInfo").removeClass("ui-state-error")
					.siblings(".InputfieldFileData").slideDown();
			}
		}); 

		// if we're dealing with a single item list, then don't continue with making it sortable
		if($(this).children("li").size() < 2) return; 

		$(this).sortable({
			axis: 'y', 
			start: function(e, ui) {
				ui.item.children(".InputfieldFileInfo").addClass("ui-state-highlight"); 
			}, 
			stop: function(e, ui) {
				$(this).children("li").each(function(n) {
					$(this).find(".InputfieldFileSort").val(n); 
				}); 
				ui.item.children(".InputfieldFileInfo").removeClass("ui-state-highlight"); 
			}
		}); 
	}).find(".ui-widget-header").hover(function() {
		$(this).addClass('ui-state-hover'); 
	}, function() {
		$(this).removeClass('ui-state-hover'); 
	}); 

	$(".InputfieldFileUpload input[type=file]").live('change', function() {
		var $t = $(this); 
		if($t.next("input.InputfieldFile").size()) return; // not the last one
		var maxFiles = parseInt($t.siblings('.InputfieldFileMaxFiles').val()); 
		var numFiles = $t.parent('.InputfieldFileUpload').siblings('.InputfieldFileList').children('li').size() + $t.siblings('input[type=file]').size() + 1; 
		if(maxFiles > 0 && numFiles >= maxFiles) return; 
		if($t.siblings('input[type=file]:empty').length > 0) return;
		var $i = $t.clone().hide().val(''); 
		$t.after($i); 	
		$i.slideDown(); 
	}); 
	
}); 
