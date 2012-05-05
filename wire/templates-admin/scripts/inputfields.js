/**
 * JS specific to behavior of ProcessWire inputfields
 *
 */

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

}); 
