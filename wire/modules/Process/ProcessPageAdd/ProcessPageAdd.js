$(document).ready(function() {

	$("#select_parent_submit").hide();
	$("#select_parent_id").change(function() {
		val = $(this).val();
		if(val > 0) $("#select_parent_submit").click();
	});	

	var submitted = false;
	$("#ProcessPageAdd").submit(function() {
		if(submitted) return false;
		submitted = true;
	});
	
	$("#template").change(function() {
		var $t = $(this);
		var val = $t.val();
		var showPublish = false; 
		if($t.is("select")) {
			var $option = $t.find("option[value=" + val + "]"); 
			if($option.attr('data-publish') === '1') showPublish = true; 	
		} else {
			showPublish = $t.attr('data-publish') === '1'; 
		}
		console.log(showPublish); 
		var $button = $("#submit_publish").closest('.Inputfield'); 
		if($button.size() > 0) { 
			if(showPublish) {
				$button.fadeIn();		
			} else {
				$button.fadeOut();
			}
		}
	}).change();


});
