$(document).ready(function() {
	$(document).on('click', 'a.pw-modal', function() { 
		
		var $a = $(this);
		var href = $a.attr('href');
		var url = href + (href.indexOf('?') > -1 ? '&' : '?') + 'modal=1';
		var title = $a.attr('title'); 
		var $iframe = $('<iframe class="modal-window" frameborder="0" src="' + url + '"></iframe>');
		var windowWidth = $(window).width()-100;
		var windowHeight = $(window).height()-160;
	
		var $dialog = $iframe.dialog({
			modal: true,
			height: windowHeight,
			width: windowWidth,
			position: [50,49],
			close: function(event, ui) {
				$a.trigger('modal-close')
			}
		}).width(windowWidth).height(windowHeight);
	
		$iframe.load(function() {
	
			var buttons = [];
			//$dialog.dialog('option', 'buttons', {}); 
			var $icontents = $iframe.contents();
			var n = 0;
			if(!title) title = $icontents.find('title').text();
	
			// set the dialog window title
			$dialog.dialog('option', 'title', title);
	
			/*
			// copy buttons in iframe to dialog
			$icontents.find("#content form button.ui-button[type=submit]").each(function() {
				var $button = $(this);
				var text = $button.text();
				var skip = false;
				// avoid duplicate buttons
				for(i = 0; i < buttons.length; i++) {
					if(buttons[i].text == text || text.length < 1) skip = true;
				}
				if(!skip) {
					buttons[n] = {
						'text': text,
						'class': ($button.is('.ui-priority-secondary') ? 'ui-priority-secondary' : ''),
						'click': function() {
							$button.click();
							if(closeOnSave) setTimeout(function() {
								ProcessListerPro.refreshLister = true;
								$dialog.dialog('close');
							}, 500);
							closeOnSave = true; // only let closeOnSave happen once
						}
					};
					n++;
				};
				$button.hide();
			});
			*/
	
			/*
			buttons[n] = {
			 'text': 'Cancel', 
			 'class': 'ui-priority-secondary', 
			 'click': function() {
			 $dialog.dialog('close'); 
			 }
			 }; 
			 if(buttons.length > 0) $dialog.dialog('option', 'buttons', buttons);
			 $dialog.width(windowWidth).height(windowHeight);
			 */
	
		});
	
		return false;
	});
}); 
