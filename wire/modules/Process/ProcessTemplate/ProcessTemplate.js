$(document).ready(function() {
	$("#filters input[name=system]").click(function() {
		$(this).parents("form").submit();
	}); 	
}); 
