$(document).ready(function() {
	$("a.InputfieldFileLink").fancybox();
	// $(".InputfieldImage .InputfieldFileList").live('AjaxUploadDone', function() {
	$(document).on('AjaxUploadDone', '.InputfieldImage .InputfieldFileList', function() {
		$("a.InputfieldFileLink", $(this)).fancybox(); 
	}); 

	// $(".InputfieldImage .InputfieldFileMove").live('click', function() {
	$(document).on('click', '.InputfieldImage .InputfieldFileMove', function() {

		var $li = $(this).parent('p').parent('li'); 
		var $ul = $li.parent();

		if($(this).is('.InputfieldFileMoveTop')) $ul.prepend($li); 
			else $ul.append($li); 

		$ul.children('li').each(function(n) {
			$(this).find('.InputfieldFileSort').val(n); 	
		}); 

		return false;
	}); 
});
