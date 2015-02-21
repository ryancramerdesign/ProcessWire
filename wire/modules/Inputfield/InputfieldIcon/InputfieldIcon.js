$(document).ready(function() {
	
	$(".InputfieldIcon select").change(function() {
		
		var $select = $(this); 
		var val = $select.val();
	
		if(val.length > 0) {
			$select.parents(".InputfieldIcon").find(".InputfieldHeader > i.fa:first-child")
				.attr('class', 'fa ' + val) 
				.parent().effect('highlight', 500);
			var $all = $select.siblings(".InputfieldIconAll");
			if($all.is(":visible")) {
				var $icon = $all.find("." + val);
				if(!$icon.hasClass('on')) $icon.click();
			}
		}
		
		$select.removeClass('on'); 
	});
	
	$(".InputfieldIconAll").hide();
	
	$("a.InputfieldIconShowAll").click(function() {
	
		var $link = $(this);
		var $all = $link.siblings(".InputfieldIconAll");
		var $select = $link.siblings("select"); 
		
		if($all.is(":visible")) {
			$all.slideUp('fast', function() {
				$all.html(''); 
			}); 
			return false;
		}
		
		$select.children("option").each(function() {
			var val = $(this).attr('value'); 
			if(val.length == 0) return;
			var $icon = $("<i class='fa fa-fw'></i>")
				.addClass(val)
				.attr('data-name', val)
				.css('margin-right', '2px')
				.css('cursor', 'pointer')
			$all.append($icon); 
		}); 
		
		$all.slideDown('fast', function() {
			
			$all.children().click(function() {
				$(this).siblings('.on').removeClass('on').mouseout();
				$(this).addClass('on').mouseover();
				if(!$select.hasClass('on')) $select.val($(this).attr('data-name')).change();

			}).mouseover(function() {
				$(this).addClass('ui-state-highlight');

			}).mouseout(function() {
				if(!$(this).hasClass('on')) {
					$(this).removeClass('ui-state-highlight');
				}
			});

			var val = $select.val();	
			if(val.length > 0) $all.children("." + val).click();
		}); 
	
		return false;
	}); 
	
});