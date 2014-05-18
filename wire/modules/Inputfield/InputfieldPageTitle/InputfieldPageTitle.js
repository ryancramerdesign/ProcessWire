﻿/**
 * Convert a title/headline to an ASCII URL name
 * 
 * 1. Convert accented characters to the ASCII equivalent. 
 * 2. Convert non -_a-z0-9. to blank. 
 * 3. Replace multiple dashes with single dash. 
 *
 */


$(document).ready(function() {

	var $nameField = $("#Inputfield__pw_page_name"); 

	// check if namefield exists, because pages like homepage don't have one and
	// no need to continue if it already has a value	
	if(!$nameField.length || $nameField.val().length) return;

	var $titleField = $(".InputfieldPageTitle input[type=text]"); 
	var active = true; 

	$(".InputfieldPageName .LanguageSupport input[type=text]").each(function() {
		// if language support enabled and any of the page names contains something
		// then prevent title from populating name fields
		if($(this).val().length > 0) active = false;
	});

	var titleKeyup = function() {
		if(!active) return; 
		// var val = $(this).val().substring(0, 128); 
		var val = $(this).val(); // @adrian
		var id = $(this).attr('id').replace(/Inputfield_title_*/, 'Inputfield__pw_page_name'); 
		$nameField = $("#" + id);  	
		if($nameField.size() > 0) $nameField.val(val).trigger('blur'); 
	}

	// $titleField.keyup(titleKeyup); 
	if(active) $titleField.bind('keyup change', titleKeyup);

	// $nameField.focus(function() {
	if(active) $('.InputfieldPageName input').focus(function() {
		// if they happen to change the name field on their own, then disable 
		if($(this).val().length) active = false;
	}); 
		
}); 
