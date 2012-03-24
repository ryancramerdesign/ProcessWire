$(document).ready(function() {

	var $p = $("#ProcessPageEdit"); 

	// remove scripts, because they've already been executed since we are manipulating the DOM below (WireTabs)
	// which would cause any scripts to get executed twice
	$p.find("script").remove();

	// prepare any InputfieldFieldsetTabOpen items for use with WireTabs
	$p.find(".InputfieldFieldsetTabOpen").each(function() {
		// give the li.InputfieldFieldsetTabOpen a title attribute that is the same as the label, and remove the label
		$(this).attr('title', $(this).children("label").remove().text()); 
		// remove the ui-widget-content div as it is extraneous when used in a tab
		$(this).children("div.ui-widget-content").remove().children("ul").appendTo($(this));
	}); 
	
	// instantiate the WireTabs
	$p.WireTabs({
		items: $("#ProcessPageEdit > .Inputfields > .InputfieldWrapper, #ProcessPageEdit > .Inputfields .InputfieldFieldsetTabOpen"),
		id: 'PageEditTabs',
		skipRememberTabIDs: ['ProcessPageEditDelete']
		});

	// WireTabs gives each tab link that it creates an ID equal to the ID on the tab content
	// except that the link ID is preceded by an underscore
	$("#_ProcessPageEditView").unbind('click').attr('href', $("#ProcessPageEditView a").attr('href')); 

	// jQuery Hotkeys plugin, bind Ctrl-S to the save button
	// $(document).bind('keydown', 'ctrl+s', function() {
	//	$("#submit_save").click();
	// }); 
	// $("#submit_save").after("<span class='hotkey_note'>(CTRL+S)</span>"); 

}); 
