$(document).ready(function() {

	var $pageView = $("#Inputfield_permissions_36"); 

	if(!$pageView.is(":checked")) $pageView.attr('checked', 'checked'); 
	
	$pageView.click(function() {
		if(!$(this).is(":checked")) {
			$(this).attr('checked', 'checked');
			alert('page-view is a required permission'); 
		}
	});

	var section = '';
	var lastSection = 'none';
	var lastWasGroup = false;
	var group = '';
	var $section = null;
	var $group = null;
	var $inputfield = $("#wrap_Inputfield_permissions");
	
	$inputfield.find("input[type=checkbox]").each(function() {
		
		var $label = $(this).closest('label');
		var name = $label.text();
		var pos = name.indexOf(' ');
		
		if(pos > 0) name = name.substring(0, pos);
		
		pos = name.indexOf('-');
		section = pos > 0 ? name.substring(0, pos) : '';
		
		if(group.length && name.indexOf(group) == 0) {
			$label.prepend('<i class="fa fa-fw"></i>');
			$label.find('.pw-no-select').addClass('detail');
			$group.addClass('AdminDataListSeparator'); //.find('.pw-no-select').wrap("<strong></strong>");
			var isGroup = true;
		} else {
			group = name;
			$group = $(this).closest('tr');
			isGroup = false;
		}
		
		if(section != lastSection || (lastWasGroup && !isGroup)) {
			$group.addClass('AdminDataListSeparator');
		}
	
		lastWasGroup = isGroup;
		lastSection = section;
	});
	$inputfield.find('thead').children('tr').addClass('AdminDataListSeparator');

}); 
