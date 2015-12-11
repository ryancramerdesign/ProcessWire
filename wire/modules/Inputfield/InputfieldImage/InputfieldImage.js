
$(document).ready(function() {

	var magnificOptions = {
		type: 'image', 
		closeOnContentClick: true, 
		closeBtnInside: true,
		/*
		image: {
			titleSrc: function(item) {
				return item.el.find('img').attr('alt'); 
			}
		},
		*/
		callbacks: {
			open: function() {
				// for firefox, which launches Magnific after a sort
				if($(".InputfieldFileJustSorted").size() > 0) this.close();
			}
		}
	}; 

	//$("a.InputfieldFileLink").magnificPopup(magnificOptions);
	
	function addImageListToggle($target) {
		var $listToggle = $("<a class='InputfieldImageListToggle HideIfEmpty' href='#'></a>")
			.append("<i class='fa fa-th'></i>");
		if($target.hasClass('InputfieldImage')) {
			$target.find('.InputfieldHeader').append($listToggle);
		} else {
			$(".InputfieldImage .InputfieldHeader", $target).append($listToggle);
		}
	}
	
	$(document).on('reloaded', '.InputfieldImage', function() {
		var $t = $(this);
		// $t.find("a.InputfieldFileLink").magnificPopup(magnificOptions);
		addImageListToggle($t);
		if($t.find(".InputfieldImageDefaultGrid")) {
			unsetGridMode($t);
			setGridMode($t);
		}
	}); 
	
	$(document).on('click', '.InputfieldImage .InputfieldFileLink', function() {
		var $a = $(this);
		var options = magnificOptions;
		options['items'] = { 
			src: $a.attr('href'), 
		};
		$.magnificPopup.open(options, 0);
		return false;
	});

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
	
	function setGridModeItem($a) {
		var $img = $a.children("img");
		var gridSize = $img.attr('data-gridsize'); 
		$a.css('background', '#000 url(' + $img.attr('src') + ') center center no-repeat');
		$a.css('width', gridSize + 'px').css('height', gridSize + 'px'); 
		$img.css('height', gridSize + 'px'); 
		if($img.width() > (gridSize-1) && $img.height() > (gridSize-1)) $a.css('background-size', 'cover'); 
	}

	function setGridMode($parent) {
		$parent.find("i.fa-th").replaceWith($("<i class='fa fa-list'></i>")); 
		$parent.find(".InputfieldFileLink").each(function() {
			setGridModeItem($(this));
		}); 
		$parent.addClass('InputfieldImageGrid'); 
	}
	
	function unsetGridModeItem($a) {
		$a.css({
			background: 'none',
			width: '',
			height: ''
		}).find('img').css('height', ''); 
	}

	function unsetGridMode($parent) {
		$parent.removeClass('InputfieldImageGrid'); 
		$parent.find(".InputfieldFileLink").each(function() {
			unsetGridModeItem($(this));
		}); 
		$parent.find("i.fa-list").replaceWith($("<i class='fa fa-th'></i>")); 
	}

	addImageListToggle($('.InputfieldForm'));
	
	$(document).on('click', '.InputfieldImageListToggle', function() {
		var $parent = $(this).parents(".InputfieldImage"); 
		if($parent.hasClass('InputfieldImageGrid')) unsetGridMode($parent);
			else setGridMode($parent);
		return false; 
	}); 

	/*
	$(document).on('dblclick', '.InputfieldImage.InputfieldRenderValue .InputfieldContent', function(e) {
		$(this).closest('.Inputfield').find('.InputfieldImageListToggle').click();
		return false;
	});
	*/

	$(".InputfieldImage").find(".InputfieldImageDefaultGrid").each(function() {
		setGridMode($(this).parents(".InputfieldImage")); 
	}); 

	$(document).on('AjaxUploadDone', '.InputfieldImage .InputfieldFileList', function() {
		$("a.InputfieldFileLink", $(this)).magnificPopup(magnificOptions); 
		var $parent = $(this).parents('.InputfieldImage'); 
		if($parent.is(".InputfieldImageGrid")) {
			unsetGridMode($parent);
			setGridMode($parent);
		}
	}); 

});
