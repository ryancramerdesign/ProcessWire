$(document).ready(function() {

	var magnificOptions = {
		type: 'image', 
		closeOnContentClick: true, 
		closeBtnInside: true,
		image: {
			titleSrc: function(item) {
				return item.el.find('img').attr('alt'); 
			}
		},
		callbacks: {
			open: function() {
				// for firefox, which launches Magnific after a sort
				if($(".InputfieldFileJustSorted").size() > 0) this.close();
			}
		}
	}; 

	$("a.InputfieldFileLink").magnificPopup(magnificOptions);

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
		$parent.find("i.fa-th").replaceWith($("<i class='fa fa-list'></i>")); 
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
		$parent.find("i.fa-list").replaceWith($("<i class='fa fa-th'></i>")); 
	}

	var $listToggle = $("<a class='InputfieldImageListToggle HideIfSingle HideIfEmpty' href='#'></a>")
		.append("<i class='fa fa-th'></i>"); 
	$(".InputfieldImage .InputfieldHeader").append($listToggle); 
	$(document).on('click', '.InputfieldImageListToggle', function() {
		var $parent = $(this).parents(".InputfieldImage"); 
		if($parent.hasClass('InputfieldImageGrid')) unsetGridMode($parent);
			else setGridMode($parent);
		return false; 
	}); 

	$(".InputfieldImage").find(".InputfieldImageDefaultGrid").each(function() {
		setGridMode($(this).parents(".InputfieldImage")); 
	}); 

	$(document).on('AjaxUploadDone', '.InputfieldImage .InputfieldFileList', function() {
		$("a.InputfieldFileLink", $(this)).magnificPopup(magnificOptions); 
		var $parent = $(this).parents('.InputfieldImage'); 
		if($parent.is(".InputfieldImageGrid")) setGridMode($parent);
	}); 

});
