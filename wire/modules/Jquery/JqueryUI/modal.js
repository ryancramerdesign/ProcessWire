/**
 * Provides a basic modal window capability
 * 
 * Use this directly (as described below) or copy and paste into your Process module's
 * javascript file and modify as needed. 
 * 
 * USE 
 * ===
 * 1. In your module call $this->modules->get('JqueryUI')->use('modal'); 
 * 2. For the <a> link or <button> you want to open a modal window, give it the class 
 *    "pw-modal". For a larger modal window, also add class "pw-modal-large". Other options
 *    are "pw-modal-small" and "pw-modal-full". Default is "pw-modal-medium".
 *    If using a <button> the "href" attribute will be pulled from a containing <a> element. 
 *    If button not wrapped with an <a>, it may also be specified in a "data-href" attribute 
 *    on the button.
 *    
 *
 * ATTRIBUTES  
 * ========== 
 * Other attributes you may specify with your a.pw-modal or button.pw-modal:
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
 * 
 */

var pwModalWindows = [];

/**
 * Dialog function that returns settings ready for population to jQuery.dialog
 * 
 * Settings include:
 * 	- position
 * 	- width
 * 	- height
 * 
 * @param name Name of modal settings to use: 'full', 'large', 'medium' or 'small'
 * @returns {{position: *[], width: number, height: number}}
 * 
 */
function pwModalWindowSettings(name) {
	
	var modal = config.modals[name];
	if(typeof modal == "undefined") modal = config.modals['medium'];
	modal = modal.split(','); 

	// options that can be customized via config.modals, with default values
	var options = {
		modal: true,
		draggable: false,
		resizable: true,
		hide: 250,
		show: 100, 
		hideOverflow: true,
		closeOnEscape: false
	}

	if(modal.length >= 4) {
		for(var n = 4; n < modal.length; n++) {
			var val = modal[n];
			if (val.indexOf('=') < 1) continue;
			val = val.split('=');
			var key = jQuery.trim(val[0]);
			val = jQuery.trim(val[1].toLowerCase());
			if (typeof options[key] == "undefined") continue;
			if (val == "true" || val == "1") {
				val = true;
			} else if (val == "false" || val == "0") {
				val = false;
			} else {
				val = parseInt(val);
			}
			options[key] = val;
		}
	}
	
	return {
		modal: options.modal,
		draggable: options.draggable,
		resizable: options.resizable,
		position: [ parseInt(modal[0]), parseInt(modal[1]) ], 
		width: $(window).width() - parseInt(modal[2]),
		height: $(window).height() - parseInt(modal[3]),
		hide: options.hide,
		show: options.show, 
		closeOnEscape: options.closeOnEscape,
		create: function(event, ui) {
			if(options.hideOverflow) {
				parent.jQuery('body').css('overflow', 'hidden');
			}
			// replace the jQuery ui close icon with a font-awesome equivalent (for hipdi support)
			var $widget = $(this).dialog("widget");
			$(".ui-dialog-titlebar-close", $widget)
				.css('padding-top', 0)
				.prepend("<i class='fa fa-times'></i>")
				.find('.ui-icon').remove();
		},
		beforeClose: function(event, ui) {
			if(options.hideOverflow) {
				//parent.jQuery('body').css('overflow', 'auto');
				parent.jQuery('body').css('overflow', '');
			}
		}
	}
};

/**
 * Open a modal window and return the iframe/dialog object
 * 
 * @param href URL to open
 * @param options Settings to provide to jQuery UI dialog (if additional or overrides)
 * @param size Specify one of: full, large, medium, small (default=medium)
 * @returns jQuery $iframe
 * 
 */
function pwModalWindow(href, options, size) {
	
	// destory any existing pw-modals that aren't currently open
	for(var n = 0; n <= pwModalWindows.length; n++) {
		var $iframe = pwModalWindows[n]; 	
		if($iframe == null) continue; 
		if($iframe.dialog('isOpen')) continue; 
		$iframe.dialog('destroy').remove();
		pwModalWindows[n] = null;
	}

	if(href.indexOf('modal=') > 0) {
		var url = href; 
	} else {
		var url = href + (href.indexOf('?') > -1 ? '&' : '?') + 'modal=1';
	}
	var $iframe = $('<iframe class="pw-modal-window" frameborder="0" src="' + url + '"></iframe>');
	$iframe.attr('id', 'pw-modal-window-' + (pwModalWindows.length+1));
	
	if(typeof size == "undefined" || size.length == 0) var size = 'large';
	var settings = pwModalWindowSettings(size);
	
	if(settings == null) {
		alert("Unknown modal setting: " + size);
		return $iframe;
	}
	
	if(typeof options != "undefined") $.extend(settings, options);
	
	$iframe.on('dialogopen', function(event, ui) {
		$(document).trigger('pw-modal-opened', { event: event, ui: ui });
	});
	$iframe.on('dialogclose', function(event, ui) {
		$(document).trigger('pw-modal-closed', { event: event, ui: ui });
	});
	
	$iframe.dialog(settings);
	$iframe.data('settings', settings);
	$iframe.load(function() {
		if(typeof settings.title == "undefined" || !settings.title) {
			$iframe.dialog('option', 'title', $iframe.contents().find('title').text());
		}
		$iframe.contents().find('form').css('-webkit-backface-visibility', 'hidden'); // to prevent jumping
	}); 
	
	var lastWidth = 0;
	var lastHeight = 0;
	
	function updateWindowSize() {
		var width = $(window).width();
		var height = $(window).height();
		if(width == lastWidth && height == lastHeight) return;
		var _size = size;
		if(width <= 960 && size != 'full' && size != 'large') _size = 'large';
		if(width <= 700 && size != 'full') _size = 'full';
		var _settings = pwModalWindowSettings(_size);
		var $dialog = $iframe.closest('.ui-dialog');
		if($dialog.length > 0) {
			var subtractHeight = $dialog.find(".ui-dialog-buttonpane").outerHeight() + $dialog.find(".ui-dialog-titlebar").outerHeight();
			_settings.height -= subtractHeight;
		}
		$iframe.dialog('option', 'width', _settings.width);
		$iframe.dialog('option', 'height', _settings.height);
		$iframe.dialog('option', 'position', _settings.position);
		$iframe.width(_settings.width).height(_settings.height);
		lastWidth = width;
		lastHeight = height; 
	}
	updateWindowSize();
	
	$(window).resize(updateWindowSize);
	
	$iframe.refresh = function() {
		lastWidth = 0; // force update
		lastHeight = 0;
		updateWindowSize();
	};
	
	$iframe.setButtons = function(buttons) {
		$iframe.dialog('option', 'buttons', buttons);
		$iframe.refresh();
	};
	$iframe.setTitle = function(title) {
		$iframe.dialog('option', 'title', title); 
	}; 

	return $iframe; 
}

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
	
	$(document).on('click', 'a.pw-modal, button.pw-modal', function() { 
		
		var $a = $(this);
		var _autoclose = $a.attr('data-autoclose'); 
		var autoclose = _autoclose != null; // whether autoclose is enabled
		var autocloseSelector = autoclose && _autoclose.length > 1 ? _autoclose : ''; // autoclose only enabled if clicked button matches this selector
		var closeSelector = $a.attr('data-close'); // immediately close window (no closeOnLoad) for buttons/links matching this selector
		var closeOnLoad = false;
		var modalSize = 'medium';
		if($a.hasClass('pw-modal-large')) modalSize = 'large';
			else if($a.hasClass('pw-modal-small')) modalSize = 'small';
			else if($a.hasClass('pw-modal-full')) modalSize = 'full';
	
		var settings = {
			title: $a.attr('title'),
			close: function(event, ui) {
				$a.trigger('modal-close', {event: event, ui: ui}); // legacy, deprecated
				$a.trigger('pw-modal-closed', { event: event, ui: ui }); // new
				$(document).trigger('pw-modal-closed', { event: event, ui: ui }); 
				$spinner.remove();
			}
		};
			
		// attribute holding selector that determines what buttons to show, example: "#content form button.ui-button[type=submit]"
		var buttonSelector = $a.attr('data-buttons'); 

		// a class of pw-modal-cancel on one of the buttons always does an immediate close
		if(closeSelector == null) closeSelector = '';
		closeSelector += (closeSelector.length > 0 ? ', ' : '') + '.pw-modal-cancel';
		
		var $spinner = $("<i class='fa fa-spin fa-spinner fa-2x ui-priority-secondary'></i>")
			.css({
				'position': 'absolute',
				'top': (parseInt($(window).height() / 2) - 80) + 'px',
				'left': (parseInt($(window).width() / 2) - 20) + 'px',
				'z-index': 9999
			}).hide();
		
		if($a.is('button')) {
			var $aparent = $a.closest('a');
			var href = $aparent.length ? $aparent.attr('href') : $a.attr('data-href');
			if(!href) href = $a.find('a').attr('href');
			if(!href) {
				alert("Unable to find href attribute for: " + $a.text());
				return;
			}
		} else {
			var href = $a.attr('href');
		}
		
		var $iframe = pwModalWindow(href, settings, modalSize);
	
		$("body").append($spinner.fadeIn('fast')); 
		
		$iframe.load(function() {
			
			var buttons = [];
			var $icontents = $iframe.contents();
			var n = 0;
			
			$spinner.fadeOut('fast', function() { $spinner.remove(); }); 
			
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
					$iframe.dialog('close'); 
					return;
				}
			}
			
			var $body = $icontents.find('body'); 
			$body.hide();
	
			// copy buttons in iframe to dialog
			if(buttonSelector) { 
				$icontents.find(buttonSelector).each(function() {
					var $button = $(this);
					$button.find(".ui-button-text").removeClass("ui-button-text"); // prevent doubled
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
								if(closeSelector.length > 0 && $button.is(closeSelector)) {
									// immediately close if matches closeSelector
									$iframe.dialog('close');
								}
								if(autoclose) {
									// automatically close on next page load
									$("body").append($spinner.fadeIn());
									if(autocloseSelector.length > 1) {
										closeOnLoad = $button.is(autocloseSelector); // if button matches selector
									} else {
										closeOnLoad = true; // tell it to close window on the next 'load' event
									}
								}
							}
						};
						n++;
					};
					if(!$button.hasClass('pw-modal-button-visible')) $button.hide(); // hide button that is in interface
				});
			} // .pw-modal-buttons

			// render buttons
			if(buttons.length > 0) $iframe.setButtons(buttons);
			
			$body.fadeIn('fast', function() {
				$body.show(); // for Firefox, which ignores the fadeIn()
			}); 
	
		}); // $iframe.load
	
		return false;
	}); // click(a.pw-modal)
});

