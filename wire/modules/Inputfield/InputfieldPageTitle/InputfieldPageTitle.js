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
  
	// multi-character replacements
	var srch = ['ä',  'ö',  'ü'];
	var repl = ['ae', 'oe', 'ue']; 

	// change single characters that translate to multi-char 
	for(var cnt = 0; cnt < srch.length; cnt++) {
		var c = srch[cnt];
		if(name.indexOf(c) > -1) {
			var re = new RegExp(c, 'g'); 
			name = name.replace(re, repl[cnt]); 
		}	
	}

	// single character, utf8 accented to ascii translation
	var str1 = ":,àáâèéëêìíïîòóôùúûñçčćďĺľńňŕřšťýž";
	var str2 = "--aaaeeeeiiiiooouuuncccdllnnrrstyz";

	// change common accented characters to ascii equivalent
	for(var cnt = 0, n = str1.length; cnt < n; cnt++) {
		var c = str1.charAt(cnt); 
		if(name.indexOf(c) > -1) { 
			var re = new RegExp(str1.charAt(cnt), 'g'); 
			name = name.replace(re, str2.charAt(cnt));
		}
	}

	// replace invalid with blank
	name = name.replace(/[^-a-z0-9. ]/g, '');

	// convert whitespace to dash
	name = name.replace(/\s+/g, '-') 

	// convert multiple dashes or dots to single
	name = name.replace(/--+/g, '-'); 

	// convert multiple dots to single
	name = name.replace(/\.\.+/g, '.'); 

	// remove ugly combinations next to each other
	name = name.replace(/(\.-|-\.)/g, '-'); 

	// remove leading or trailing dashes, underscores and dots
	name = name.replace(/(^[-_.]+|[-_.]+$)/g, ''); 

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
