$(document).ready(function() {

	$("a.CommentTextEdit").click(function() {
		var $textarea = $("<textarea></textarea>");
		var $parent = $(this).closest('.CommentTextEditable');
		$parent.parent('.CommentText').removeClass('CommentTextOverflow');
		$textarea.attr('name', $parent.attr('id')); 
		//$textarea.height($parent.height()); 
		$(this).remove(); // remove edit link
		$textarea.val($parent.text()); 
		$parent.after($textarea);
		$parent.remove();
		return false; 
	}); 

	$(".CommentText").click(function() {
		$(this).find('a.CommentTextEdit').click();
		return false;
	}); 

	$(".CommentItem").each(function() {
		var $item = $(this);
		var $table = $item.find(".CommentItemInfo"); 
		var height = $table.height() + 30;
		var $text = $item.find(".CommentText"); 
		if($text.height() > height) {
			$text.addClass('CommentTextOverflow'); 
		}
	});
}); 
