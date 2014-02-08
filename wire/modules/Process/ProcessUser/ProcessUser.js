$(document).ready(function() {
	if($("#Inputfield_id").val() == 40) {
		// guest user
		// hide fields that aren't necessary ehre
		$("#wrap_Inputfield_pass").hide(); 	
		$("#wrap_Inputfield_email").hide();	
		$("#wrap_Inputfield_roles input").attr('disabled', 'disabled');
		//$("#wrap_submit_save").remove();
	}

	var $guestRole = $("#Inputfield_roles_37"); 
	if($guestRole.size() > 0 && !$guestRole.is(":checked")) {
		$guestRole.attr('checked', 'checked'); 
	}
}); 
