$(document).ready(function() {

	$("a.InputfieldFileLink").fancybox();

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

	function setGridMode($parent) {
		$parent.find("i.icon-th").replaceWith($("<i class='icon-list'></i>")); 
		$parent.find(".InputfieldFileLink").each(function() {
			var $a = $(this);
			var $img = $a.children("img"); 
			$a.css('background', '#000 url(' + $img.attr('src') + ') center center no-repeat'); 
			if($img.width() > 99 && $img.height() > 99) $a.css('background-size', 'cover'); 
		}); 
		$parent.addClass('InputfieldImageGrid'); 
	}

	function unsetGridMode($parent) {
		$parent.removeClass('InputfieldImageGrid'); 
		$parent.find(".InputfieldFileLink").css('background', 'none'); 
		$parent.find("i.icon-list").replaceWith($("<i class='icon-th'></i>")); 
	}

	var $listToggle = $("<a class='InputfieldImageListToggle' href='#'></a>").append("<i class='icon-th'></i>"); 
	$(".InputfieldImage .InputfieldHeader").append($listToggle); 
	$listToggle.click(function() {
		var $parent = $(this).parents(".InputfieldImage"); 
		if($parent.hasClass('InputfieldImageGrid')) unsetGridMode($parent);
			else setGridMode($parent);
		return false; 
	}); 

	$(".InputfieldImage").find(".InputfieldImageDefaultGrid").each(function() {
		setGridMode($(this).parents(".InputfieldImage")); 
	}); 

	$(document).on('AjaxUploadDone', '.InputfieldImage .InputfieldFileList', function() {
		$("a.InputfieldFileLink", $(this)).fancybox(); 
		var $parent = $(this).parents('.InputfieldImage'); 
		if($parent.is(".InputfieldImageGrid")) setGridMode($parent);
	}); 

});
