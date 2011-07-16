$(document).ready(function() {

	$(".Inputfields > li > label.ui-widget-header").addClass("InputfieldStateToggle")
		.prepend("<span class='ui-icon ui-icon-triangle-1-s'> </span>")
		.click(function() {
			var $li = $(this).parent('li'); 	
			$li.toggleClass('InputfieldStateCollapsed', 200); 
			$(this).children('span.ui-icon').toggleClass('ui-icon-triangle-1-e ui-icon-triangle-1-s'); 
			return false;
		})

	$(".Inputfields .InputfieldStateCollapsed > label.ui-widget-header span.ui-icon")
		.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e'); 

	$(".ui-button").hover(function() {
		$(this).removeClass("ui-state-default").addClass("ui-state-hover");
	}, function() {
		$(this).removeClass("ui-state-hover").addClass("ui-state-default");
	}).click(function() {
		$(this).removeClass("ui-state-default").addClass("ui-state-active");
	});

	$("#content a > button").click(function() {
		window.location = $(this).parent("a").attr('href'); 
	}); 

	if($.browser.msie && $.browser.version < 8) {
		$("#content .container").html("<h2>ProcessWire does not support IE7 and below at this time. Please try again with a newer browser.</h2>").show();
	}


}); 
