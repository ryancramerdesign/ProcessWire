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
		// console.log(showPublish); 
		var $button = $("#submit_publish").closest('.Inputfield'); 
		if($button.size() > 0) { 
			if(showPublish) {
				$button.fadeIn();		
			} else {
				$button.fadeOut();
			}
		}
	}).change();

	var existsTimer = null;	
	var existsName = '';
	var $nameInput = $("#Inputfield__pw_page_name"); 
	
	function checkExists() {
		var parent_id = $("#Inputfield_parent_id").val();
		var name = $nameInput.val();
		if(existsName == name) return; // no change to name yet
		if(parent_id && name.length > 0) {
			existsName = name;
			$("#ProcessPageAddStatus").remove();
			$.get("./exists?parent_id=" + parent_id + "&name=" + name, function(data) {
				var $status = $("<span id='ProcessPageAddStatus'></span>").append(' ' + data).hide();
				$("#wrap_Inputfield__pw_page_name .InputfieldHeader").append($status.fadeIn('fast'))
			}); 
		}
	}
	
	$("#Inputfield_title, #Inputfield__pw_page_name").keyup(function(e) {
		if(existsTimer) clearTimeout(existsTimer);
		$("#ProcessPageAddStatus").remove();
		existsTimer = setTimeout(function() { checkExists(); }, 500); 
	}); 


});
