$(document).ready(function() {
	$("a.InputfieldFileLink").fancybox();
	$(".InputfieldImage .InputfieldFileList").bind('AjaxUploadDone', function() {
		$("a.InputfieldFileLink", $(this)).fancybox(); 
	}); 
});
