$(document).ready(function() {
	$(".InputfieldAsmSelect select[multiple=multiple]").each(function() {
		var $t = $(this); 

		if(typeof config === 'undefined') {
			var options = { sortable: true };
		} else {
			var options = config[$t.attr('id')]; 
		}
		$t.asmSelect(options); 
	}); 
}); 
