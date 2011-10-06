jQuery(document).ready(function($) {

	function sanitizePageName(value) {
		return value.replace(/[^-_a-z0-9.]/gi, '-').toLowerCase().replace(/--+/g, '-').replace(/^-|-$/g, ''); 
	}

	$(".InputfieldPageName").find("input[type=text]").keyup(function() {
		var value = sanitizePageName($(this).val());
		$(this).parent('p').siblings(".InputfieldPageNameURL").children("strong").text((value.length > 0 ? value + '/' : ''))
	}).blur(function() {
		var value = sanitizePageName($(this).val());
		$(this).val(value); 
	}).keyup();
}); 
