$(document).ready(function() {
	$("a.InputfieldFileLink").fancybox();
	$(".InputfieldImage .InputfieldFileList").live('AjaxUploadDone', function() {
		$("a.InputfieldFileLink", $(this)).fancybox(); 
	}); 
});
