jQuery(document).ready(function($) {
	$(".InputfieldPageName").find("input[type=text]").keyup(function() {
		var val = $(this).val().replace(/[^-_a-z0-9.]/gi, '-').toLowerCase();
		$(this).val(val).parent('p').siblings(".InputfieldPageNameURL").children("strong").text((val.length > 0 ? val + '/' : ''))
	}).keyup();
}); 
