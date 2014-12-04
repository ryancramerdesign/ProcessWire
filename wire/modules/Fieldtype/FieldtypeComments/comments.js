jQuery(document).ready(function($) {
	
	$(".CommentActionReply").click(function() {
		var $this = $(this);
		var $form = $this.parent().next('form');
		if($form.length == 0) {
			$form = $("#CommentForm form").clone().removeAttr('id');
			$form.hide().find(".CommentFormParent").val($(this).attr('data-comment-id'));
			$(this).parent().after($form);
			$form.slideDown();
		} else if(!$form.is(":visible")) {
			$form.slideDown();
		} else {
			$form.slideUp();
		}
		return false;
	}); 
	
}); 