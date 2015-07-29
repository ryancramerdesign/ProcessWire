$(document).ready(function() {

	$("#select_parent_submit").hide();
	$("#select_parent_id").change(function() {
		var val = $(this).val();
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
	var $nameWrap = $("#wrap_Inputfield__pw_page_name");
	var $form = $nameInput.closest('form');
	var ajaxURL = $form.attr('data-ajax-url'); 
	var $dupNote = $("<p class='notes'>" + $form.attr('data-dup-note') + "</p>");
	var $status = $("<span id='ProcessPageAddStatus'></span>");
	
	$nameWrap.children(".InputfieldHeader").append($status.hide()); 
	$nameInput.after($dupNote.hide()); 
	
	function checkExists() {
		var parent_id = $("#Inputfield_parent_id").val();
		var name = $nameInput.val();
		if(existsName == name) return; // no change to name yet
		if(parent_id && name.length > 0) {
			existsName = name;
			$.get(ajaxURL + "exists?parent_id=" + parent_id + "&name=" + name, function(data) {
				$status.html(' ' + data).show();
				if($(data).hasClass('taken')) {
					$nameInput.addClass('ui-state-error-text'); 
					$dupNote.fadeIn('fast');
				} else {
					$nameInput.removeClass('ui-state-error-text');
					$dupNote.hide();
				}
			}); 
		}
	}
	
	$("#Inputfield_title, #Inputfield__pw_page_name").keyup(function(e) {
		if(existsTimer) clearTimeout(existsTimer);
		existsTimer = setTimeout(function() { checkExists(); }, 250); 
	}); 


});
