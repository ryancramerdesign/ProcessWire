function initPageEditForm() {

	var $p = $("#ProcessPageEdit"); 

	// remove scripts, because they've already been executed since we are manipulating the DOM below (WireTabs)
	// which would cause any scripts to get executed twice
	$p.find("script").remove();

	// prepare any InputfieldFieldsetTabOpen items for use with WireTabs
	$p.find(".InputfieldFieldsetTabOpen").each(function() {
		// give the li.InputfieldFieldsetTabOpen a title attribute that is the same as the label, and remove the label
		$(this).attr('title', $(this).children("label").remove().text()); 
		// remove the ui-widget-content div as it is extraneous when used in a tab
		$(this).children(".InputfieldContent").remove().children("ul").appendTo($(this));
	}); 
	// instantiate the WireTabs
	$('#ProcessPageEdit').WireTabs({
		items: $("#ProcessPageEdit > .Inputfields > .InputfieldWrapper, #ProcessPageEdit > .Inputfields .InputfieldFieldsetTabOpen"),
		id: 'PageEditTabs',
		skipRememberTabIDs: ['ProcessPageEditDelete']
	});
	
	// WireTabs gives each tab link that it creates an ID equal to the ID on the tab content
	// except that the link ID is preceded by an underscore
	$("#_ProcessPageEditView").unbind('click').attr('href', $("#ProcessPageEditView a").attr('href')); 

	// trigger a submit_delete submission. this is necessary because when submit_delete is an <input type='submit'> then 
	// some browsers call it (rather than submit_save) when the enter key is pressed in a text field. This solution
	// by passes that undesirable behavior. 
	$("#submit_delete").click(function() {
		if(!$("#delete_page").is(":checked")) {
			$("#wrap_delete_page label").effect('highlight', {}, 500); 
			return;
		}
		$(this).before("<input type='hidden' name='submit_delete' value='1' />"); 
		$("#ProcessPageEdit").submit();
	}); 

}
