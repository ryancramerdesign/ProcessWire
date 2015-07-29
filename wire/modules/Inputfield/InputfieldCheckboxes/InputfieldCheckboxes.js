jQuery(document).ready(function($) {
	// @awt2542 PR #867
	var lastChecked = null;
	$(document).on('click', '.InputfieldCheckboxes ul input[type=checkbox]', function(e) {
		var $checkboxes = $(this).closest('ul').find('input[type=checkbox]');
		if(!lastChecked) {
			lastChecked = this;
			return;
		}
		if(e.shiftKey) {
			var start = $checkboxes.index(this);
			var end = $checkboxes.index(lastChecked);
			$checkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).attr('checked', lastChecked.checked);
		}
		lastChecked = this;
	});
}); 