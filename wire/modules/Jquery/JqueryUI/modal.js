/**
 * Provides a basic modal window capability
 * 
 * Use this directly (as described below) or copy and paste into your Process module's
 * javascript file and modify as needed. 
 * 
 * USE 
 * ===
 * 1. In your module call $this->modules->get('JqueryUI')->use('modal'); 
 * 2. For the <a> link you want to open a modal window, give it the class "pw-modal".
 *    For a larger modal window, also add class "pw-modal-large".
 *
 * ATTRIBUTES  
 * ========== 
 * Other attributes you may specify with your a.pw-modal:
 * 
 * data-buttons: If you want to use modal window buttons, specify a jQuery selector 
 * that identifies these buttons in the modal window. The original buttons will be 
 * hidden in the modal content and moved outside to the modal window interface.
 * 
 * data-autoclose: If also using data-buttons, this option will make clicking any 
 * of those buttons automatically close the modal window, after the next page loads. 
 * This enables save actions to take place. You may also populate this property with
 * a jQuery selector, in which case autoclose will only take place if the button 
 * matches the given selector. 
 * 
 * data-close: Populate with a selector that matches the button (or buttons) that 
 * should immediately close the window (no follow-up save or anything). 
 * 
 * MODAL CONTENT
 * =============
 * The window opened in the modal may optionally create a button or link with the 
 * class 'pw-modal-cancel'. When clicked, the window will immediately close. This 
 * can also be one of your 'data-buttons' if you want it to. 
 * 
 * EVENTS
 * ======
 * When the window is closed, the a.pw-modal link receives a "pw-modal-closed" event
 * with the arguments (event, ui).
 * 
 * 
 */

$(document).ready(function() {
	
	// enable titles with HTML in ui dialog
	$.widget("ui.dialog", $.extend({}, $.ui.dialog.prototype, {
		_title: function(title) {
			if (!this.options.title ) {
				title.html("&#160;");
			} else {
				title.html(this.options.title);
			}
		}
	}));
	
	$(document).on('click', 'a.pw-modal', function() { 
		
		var $a = $(this);
		var href = $a.attr('href');
		var url = href + (href.indexOf('?') > -1 ? '&' : '?') + 'modal=1';
		var title = $a.attr('title'); 
		var $iframe = $('<iframe class="pw-modal-window" frameborder="0" src="' + url + '"></iframe>');
		var _autoclose = $a.attr('data-autoclose'); 
		var autoclose = _autoclose != null; // whether autoclose is enabled
		var autocloseSelector = autoclose && _autoclose.length > 0 ? _autoclose : ''; // autoclose only enabled if clicked button matches this selector
		var closeSelector = $a.attr('data-close'); // immediately close window (no closeOnLoad) for buttons/links matching this selector
		var closeOnLoad = false;
		
		// attribute holding selector that determines what buttons to show, example: "#content form button.ui-button[type=submit]"
		var buttonSelector = $a.attr('data-buttons'); 

		if($a.hasClass('pw-modal-large')) {
			var windowWidth = $(window).width() - 30;
			var windowHeight = $(window).height() - 145;
			var windowPosition = [15,15]; 
		} else {
			var windowWidth = $(window).width() - 100;
			var windowHeight = $(window).height() - 160;
			var windowPosition = [50,49]; 
			if(buttonSelector) windowHeight -= 70;
		}
		
		// a class of pw-modal-cancel on one of the buttons always does an immediate close
		if(closeSelector == null) closeSelector = '';
		closeSelector += (closeSelector.length > 0 ? ', ' : '') + '.pw-modal-cancel'; 
		
		var $dialog = $iframe.dialog({
			modal: true,
			height: windowHeight,
			width: windowWidth,
			title: title,
			position: windowPosition,
			show: 250, 
			hide: 250,
			close: function(event, ui) {
				$a.trigger('modal-close', { event: event, ui: ui } ); // legacy, deprecated
				$a.trigger('pw-modal-closed', { event: event, ui: ui } ); // new
			}
		}).width(windowWidth).height(windowHeight);
	
		var $spinner = $("<i class='fa fa-spin fa-spinner fa-2x ui-priority-secondary'></i>")
			.css({ 
				'position': 'absolute',
				'top': (parseInt($(window).height() / 2) - 80) + 'px', 
				'left': (parseInt($(window).width() / 2) - 20) + 'px', 
				'z-index': 9999
			}).hide();
		$("body").append($spinner.fadeIn('fast')); 
		
		$iframe.load(function() {
			
			var buttons = [];
			var $icontents = $iframe.contents();
			var n = 0;
			$spinner.fadeOut('fast', function() { $spinner.remove(); }); 
			$icontents.find('body').hide();
			
			if(closeOnLoad) {
				// this occurs when item saved and resulting page is loaded
				if($icontents.find(".NoticeError, .NoticeWarning, .ui-state-error").length == 0) {
					// if there are no error messages present, close the window
					if(typeof Notifications != "undefined") {
						var messages = [];
						$icontents.find(".NoticeMessage").each(function() {
							messages[messages.length] = $(this).text();
						});
						if(messages.length > 0) setTimeout(function() {
							for(var i = 0; i < messages.length; i++) {
								Notifications.message(messages[i]);
							}
						}, 500); 
					}
					$dialog.dialog('close'); 
					return;
				}
			}
	
			// set the dialog window title, if it isn't already set
			if(!title) {
				$dialog.dialog('option', 'title', $icontents.find('title').text());
			}
		
			// copy buttons in iframe to dialog
			if(buttonSelector) { 
				$icontents.find(buttonSelector).each(function() {
					var $button = $(this);
					var text = $button.html();
					var skip = false;
					// avoid duplicate buttons
					for(var i = 0; i < buttons.length; i++) {
						if(buttons[i].text == text || text.length < 1) skip = true;
					}
					if(!skip) {
						buttons[n] = {
							'html': text,
							'class': ($button.is('.ui-priority-secondary') ? 'ui-priority-secondary' : ''),
							'click': function(e) {
								$(e.currentTarget).fadeOut('fast');
								$button.click();
								$("body").append($spinner.fadeIn());
								if(closeSelector.length > 0 && $button.is(closeSelector)) {
									// immediately close if matches closeSelector
									$dialog.dialog('close');
								}
								if(autoclose) {
									// automatically close on next page load
									if(autocloseSelector.length > 0) {
										closeOnLoad = $button.is(autocloseSelector); // if button matches selector
									} else {
										closeOnLoad = true; // tell it to close window on the next 'load' event
									}
								}
							}
						};
						n++;
					};
					$button.hide(); // hide button that is in interface
				});
			} // .pw-modal-buttons

			/*
			// add a cancel button
			if($a.attr('data-cancel') != "undefined") {
				buttons[n] = {
					'text': 'Cancel', 
					'class': 'ui-priority-secondary', 
					'click': function() {
						 $dialog.dialog('close'); 
					} 		
				}; 
			}
			*/

			// render buttons
			if(buttons.length > 0) {
				$dialog.dialog('option', 'buttons', buttons);
				$dialog.width(windowWidth).height(windowHeight);
			}
			
			$icontents.find('body').fadeIn('fast');
	
		}); // $iframe.load
	
		return false;
	}); // click(a.pw-modal)
});

