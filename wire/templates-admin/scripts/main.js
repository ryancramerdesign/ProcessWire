$(document).ready(function() {

	// setup the toggles for Inputfields and the animations that occur between opening and closing
	$(".Inputfields > li > label.ui-widget-header").addClass("InputfieldStateToggle")
		.prepend("<span class='ui-icon ui-icon-triangle-1-s'></span>")
		.click(function() {
			var $li = $(this).parent('li'); 	
			$li.toggleClass('InputfieldStateCollapsed', 100);
			$(this).children('span.ui-icon').toggleClass('ui-icon-triangle-1-e ui-icon-triangle-1-s'); 
			$li.children('.ui-widget-header').effect('highlight', {}, 300); 
			return false;
		})

	// use different icon for open and closed
	$(".Inputfields .InputfieldStateCollapsed > label.ui-widget-header span.ui-icon")
		.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e'); 


	// if there are buttons in the format "a button" without ID attributes, copy them into the masthead
	// or buttons in the format button.head_button_clone with an ID attribute.
	var $buttons = $("#content a[id=] button[id=], #content button.head_button_clone[id!=]"); 
	if($buttons.size() > 0 && !$.browser.msie) {
		var $head = $("<div id='head_button'></div>").appendTo("#masthead .container").show();
		$buttons.each(function() {
			var $t = $(this);
			var $a = $t.parent('a'); 
			if($a.size()) { 
				$button = $t.parent('a').clone();
				$head.append($button);
			} else if($t.is('.head_button_clone')) {
				$button = $t.clone();
				$button.attr('data-from_id', $t.attr('id')).attr('id', $t.attr('id') + '_copy');
				$a = $("<a></a>").attr('href', '#');
				$button.click(function() {
					$("#" + $(this).attr('data-from_id')).click().parents('form').submit();
					return false;
				});
				$head.append($a.append($button));	
			}
		}); 
	}

	// jQuery UI button states
	$(".ui-button").hover(function() {
		$(this).removeClass("ui-state-default").addClass("ui-state-hover");
	}, function() {
		$(this).removeClass("ui-state-hover").addClass("ui-state-default");
	}).click(function() {
		$(this).removeClass("ui-state-default").addClass("ui-state-active").effect('highlight', {}, 500); 
	});


	// make buttons with <a> tags click to the href of the <a>
	$("a > button").click(function() {
		window.location = $(this).parent("a").attr('href'); 
	}); 

	// we don't even want to go there
	if($.browser.msie && $.browser.version < 8) {
		$("#content .container").html("<h2>ProcessWire does not support IE7 and below at this time. Please try again with a newer browser.</h2>").show();
	}

	// add focus to the first text input, where applicable
	jQuery('#content input[type=text]:visible:enabled:first').each(function() {
		var $t = $(this); 
		if(!$t.val() && !$t.is(".no_focus")) $t.focus();	
	});

}); 
