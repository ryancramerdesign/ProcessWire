$(document).ready(function() {
	var filterInputfieldPermissions = function() {
		var pageEdit = $("#Inputfield_permissions_32").is(":checked"); 
		$("#wrap_Inputfield_permissions input").each(function() {
			var val = $(this).val();
			if(val == 36) return; // page-view
			if(val == 32) return; // page-edit
			if(val > 100) return; // non-system permission
			var $li = $(this).parent('label').parent('li');
			if(pageEdit) $li.show();
				else $li.hide();
		}); 
	}; 

	filterInputfieldPermissions();
	$("#wrap_Inputfield_permissions input").click(filterInputfieldPermissions); 

	$("#Inputfield_permissions_36").click(function() {
		if(!$(this).is(":checked")) {
			$(this).attr('checked', 'checked');
			alert('page-view is a required permission'); 
		}
	});

}); 
