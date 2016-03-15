function initPageEditForm() {

	// instantiate the WireTabs
	$('#ProcessPageEdit:not(.ProcessPageEditSingleField)').WireTabs({
		items: $("#ProcessPageEdit > .Inputfields > .InputfieldWrapper"), 
		id: 'PageEditTabs',
		skipRememberTabIDs: ['ProcessPageEditDelete']
	});
	
	// WireTabs gives each tab link that it creates an ID equal to the ID on the tab content
	// except that the link ID is preceded by an underscore

	// trigger a submit_delete submission. this is necessary because when submit_delete is an <input type='submit'> then 
	// some browsers call it (rather than submit_save) when the enter key is pressed in a text field. This solution
	// by passes that undesirable behavior. 
	$("#submit_delete").click(function() {
		if(!$("#delete_page").is(":checked")) {
			$("#wrap_delete_page label").effect('highlight', {}, 500); 
			return;
		}
		$(this).before("<input type='hidden' name='submit_delete' value='1' />"); 
		$("#ProcessPageEdit").submit();
	}); 

	// prevent Firefox from sending two requests for same click
	$(document).on('click', '#AddPageBtn', function() {
		return false;
	});
	
	/***********************************************/

	function pwButtonDropdownClick() {

		var $a = $(this);
		var $dropdown = $a.closest('.pw-button-dropdown');
		if(!$dropdown.length) return true;

		if($a.hasClass('pw-button-dropdown-default')) {
			// just click the button
		} else {
			var value = $a.attr('data-dropdown-value');
			if(!value) return true;
			
			var selector = $dropdown.attr('data-dropdown-input');
			if(!selector) return true;

			var $input = $(selector);
			if(!$input.length) return true;
			$input.val(value);
		}

		var $button = $dropdown.data('button');
		if(!$button) return true;
		$(":input:focus").blur();
		$button.click();

		return false;
	}

	var dropdownCnt = 0;

	if($('body').hasClass('touch-device') || $('body').hasClass('modal')) {
		$("ul.pw-button-dropdown").hide();	
	} else {
		$("button[type=submit]").each(function() {

			var $button = $(this);
			var name = $button.attr('name');

			if(name.indexOf('submit') == -1) return;
			if(name.indexOf('_save') == -1 && name.indexOf('_publish') == -1) return;

			var $dropdownTemplate = $("ul.pw-button-dropdown:not(.pw-button-dropdown-init)")
			var $dropdown = $dropdownTemplate.clone();

			dropdownCnt++;
			var dropdownCntClass = 'pw-button-dropdown-' + dropdownCnt;

			$dropdownTemplate.hide();
			$dropdown.addClass('dropdown-menu pw-dropdown-menu shortcuts pw-button-dropdown-init ' + dropdownCntClass);
			$dropdown.data('button', $button);

			var $buttonText = $button.find('.ui-button-text');
			var labelText = $.trim($buttonText.text());
			var labelHTML = $buttonText.html();

			$dropdown.find('a').each(function() {
				var $a = $(this);
				$a.html($a.html().replace('%s', labelText));
				$a.click(pwButtonDropdownClick);
			});

			/*
			 // add first item to be same as default button action
			 var $li = $('<li></li>');
			 var $a = $('<a></a>').attr('href', '#default').append(labelHTML).addClass('pw-button-dropdown-default');
			 $a.click(pwButtonDropdownClick);
			 var $icon = $a.find('i');

			 if(!$icon.length) {
			 $icon = "<i class='fa fa-fw fa-check-square'></i>&nbsp;";
			 $a.prepend($icon);
			 } else {
			 $icon.addClass('fa-fw');
			 }
			 $dropdown.prepend($li.append($a));
			 */

			$button.after($dropdown)
				.addClass('dropdown-toggle pw-dropdown-toggle dropdown-toggle-delay pw-dropdown-toggle-delay')
				.attr('data-dropdown', '.' + dropdownCntClass);

			$button.click(function() {
				$(this).addClass('pw-button-clicked');
				$dropdown.remove();
			});
		});
	}

	
}
