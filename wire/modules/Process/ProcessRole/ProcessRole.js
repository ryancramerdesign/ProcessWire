$(document).ready(function() {

	var $pageView = $("#Inputfield_permissions_36"); 

	if(!$pageView.is(":checked")) $pageView.attr('checked', 'checked'); 
	
	$pageView.click(function() {
		if(!$(this).is(":checked")) {
			$(this).attr('checked', 'checked');
			alert('page-view is a required permission'); 
		}
	});

}); 
