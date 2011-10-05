jQuery(document).ready(function($) {
	var lastVal = '';
	$(".InputfieldPageName").find("input[type=text]").keyup(function() {
		var value = $(this).val();
		var val = value.replace(/[^-_a-z0-9.]/gi, '-').toLowerCase().replace(/--+/g, '-').replace(/^-|-$/g, ''); 
		if(val != value) $(this).val(val);
		if(val != lastVal) $(this).parent('p').siblings(".InputfieldPageNameURL").children("strong").text((val.length > 0 ? val + '/' : ''))
		lastVal = val;
	}).keyup();
}); 
