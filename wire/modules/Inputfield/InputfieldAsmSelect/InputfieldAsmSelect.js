$(document).ready(function() {
	$(".InputfieldAsmSelect select[multiple=multiple]").each(function() {
		var $t = $(this); 
		var options = config[$t.attr('id')]; 
		$t.asmSelect(options); 
	}); 
}); 
