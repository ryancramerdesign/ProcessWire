
/**
 * Set a cookie value
 *
 * @param string name
 * @param string value
 * @param int days
 *
 */
function CommentFormSetCookie(name, value, days) {
	var today = new Date();
	var expire = new Date();
	if(days == null || days == 0) days = 1;
	expire.setTime(today.getTime() + 3600000 * 24 * days);
	document.cookie = name + "=" + escape(value) + ";path=/;expires=" + expire.toGMTString();
}

/**
 * Get a cookie value
 *
 * @param string name
 * @return string
 *
 */
function CommentFormGetCookie(name) {
	var regex = new RegExp('[; ]' + name + '=([^\\s;]*)');
	var match = (' ' + document.cookie).match(regex);
	if(name && match) return unescape(match[1]);
	return '';
}

/**
 * Initialize comments form 
 * 
 */
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

	// remember values when comment form submitted
	$(".CommentFormSubmit button").on('click', function() {
		var $this = $(this);
		var $form = $this.closest('form.CommentForm')
		var cite = $form.find(".CommentFormCite input").val();
		var email = $form.find(".CommentFormEmail input").val();
		var $website = $form.find(".CommentFormWebsite input");
		var website = $website.length > 0 ? $website.val() : '';
		var $notify = $form.find(".CommentFormNotify :input");
		var notify = $notify.length > 0 ? $notify.val() : '';
		if(cite.indexOf('|') > -1) cite = '';
		if(email.indexOf('|') > -1) email = '';
		if(website.indexOf('|') > -1) website = '';
		var cookieValue = cite + '|' + email + '|' + website + '|' + notify;
		CommentFormSetCookie('CommentForm', cookieValue, 30);
	});

	// populate comment form values if they exist in cookie
	var cookieValue = CommentFormGetCookie('CommentForm');
	if(cookieValue.length > 0) {
		var values = cookieValue.split('|');
		var $form = $("form.CommentForm");
		$form.find(".CommentFormCite input").val(values[0]);
		$form.find(".CommentFormEmail input").val(values[1]);
		$form.find(".CommentFormWebsite input").val(values[2]);
		$form.find(".CommentFormNotify :input").val(values[3]);
	}

	// upvoting and downvoting
	var voting = false;
	$(".CommentActionUpvote, .CommentActionDownvote").on('click', function() {
		if(voting) return false;
		voting = true; 
		var $a = $(this); 
		$.getJSON($a.attr('data-url'), function(data) {
			//console.log(data); 
			if('success' in data) {
				if(data.success) {
					var $votes = $a.closest('.CommentVotes'); 
					$votes.find('.CommentUpvoteCnt').text(data.upvotes);
					$votes.find('.CommentDownvoteCnt').text(data.downvotes); 
					$a.addClass('CommentVoted'); 
				} else if(data.message.length) {
					alert(data.message); 
				}
			} else {
				// let the link passthru to handle via regular pageload rather than ajax
				voting = false;
				return true; 
			}
			voting = false;
		}); 
		return false; 
	}); 
}); 