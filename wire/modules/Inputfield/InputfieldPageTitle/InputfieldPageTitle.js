/**
 * Convert a title/headline to an ASCII URL name
 * 
 * 1. Convert accented characters to the ASCII equivalent. 
 * 2. Convert non -_a-z0-9. to blank. 
 * 3. Replace multiple dashes with single dash. 
 *
 */
function titleToUrlName(name) {

	// replace leading and trailing whitespace 
	name = jQuery.trim(name).toLowerCase();  
  
	// utf8 accented to ascii translation
	var str1 = ":,àáäâèéëêìíïîòóöôùúüûñçčćďĺľńňŕřšťýž";
	var str2 = "--aaaaeeeeiiiioooouuuuncccdllnnrrstyz";

	// change common accented characters to ascii equivalent
	for(var cnt=0, n=str1.length; cnt<n; cnt++) {
		var re = new RegExp(str1.charAt(cnt), 'g'); 
		name = name.replace(re, str2.charAt(cnt));
	}

	// replace invalid with blank
	name = name.replace(/[^-a-z0-9. ]/g, '');

	// convert whitespace to dash
	name = name.replace(/\s+/g, '-') 

	// convert multiple dashes to single
	name = name.replace(/--+/g, '-'); 

	// remove leading or trailing dashes
	name = name.replace(/(^-|-$)/g, ''); 

	return name;
}

$(document).ready(function() {

	var $nameField = $("#Inputfield_name"); 

	// check if namefield exists, because pages like homepage don't have one and
	// no need to continue if it already has a value	
	if(!$nameField.length || $nameField.val().length) return;

	var $titleField = $(".InputfieldPageTitle input[type=text]"); 
	var active = true; 

	var titleKeyup = function() {
		if(!active) return; 
		var val = $titleField.val().substring(0, 70); 
		$nameField.val(titleToUrlName(val)).trigger('keyup'); 
		
	}

	$titleField.keyup(titleKeyup); 

	$nameField.focus(function() {
		// if they happen to change the name field on their own, then disable 
		if($(this).val().length) active = false;
	}); 
		
}); 
