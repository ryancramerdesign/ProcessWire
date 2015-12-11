function initPageEditForm() {

	// instantiate the WireTabs
	$('#ProcessPageEdit:not(.ProcessPageEditSingleField)').WireTabs({
		items: $("#ProcessPageEdit > .Inputfields > .InputfieldWrapper"), 
		id: 'PageEditTabs',
		skipRememberTabIDs: ['ProcessPageEditDelete']
	});
	
	// WireTabs gives each tab link that it creates an ID equal to the ID on the tab content
	// except that the link ID is preceded by an underscore

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

	// prevent Firefox from sending two requests for same click
	$(document).on('click', '#AddPageBtn', function() {
		return false;
	});
	
}
