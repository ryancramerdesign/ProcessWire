/**
 * ProcessWire Admin Theme jQuery/Javascript
 *
 * Copyright 2012 by Ryan Cramer
 * 
 */

var ProcessWireAdminTheme = {

	/**
	 * Initialize the default ProcessWire admin theme
	 *
	 */
	init: function() {
		this.setupCloneButton();
		this.setupButtonStates();
		this.setupFieldFocus();
		this.setupTooltips();
		this.setupSearch();
		this.sizeTitle();
		$('#content').removeClass('fouc_fix'); // FOUC fix
		this.browserCheck();
	},

	/**
	 * Enable jQuery UI tooltips
	 *
	 */
	setupTooltips: function() {
		$("a.tooltip").tooltip({ 
			position: {
				my: "center bottom-20",
				at: "center top",
				using: function(position, feedback) {
					$(this).css(position);
					$("<div>")
						.addClass("arrow")
						.addClass(feedback.vertical)
						.addClass(feedback.horizontal)
						.appendTo(this);
				}
			}
		}).hover(function() {
			$(this).addClass('ui-state-hover');
		}, function() {
			$(this).removeClass('ui-state-hover');
		}); 
	},

	/**
	 * Clone a button at the bottom to the top 
	 *
	 */
	setupCloneButton: function() {
		// if there are buttons in the format "a button" without ID attributes, copy them into the masthead
		// or buttons in the format button.head_button_clone with an ID attribute.
		// var $buttons = $("#content a[id=] button[id=], #content button.head_button_clone[id!=]"); 
		var $buttons = $("#content a:not([id]) button:not([id]), #content button.head_button_clone[id!=]"); 

		// don't continue if no buttons here or if we're in IE
		if($buttons.size() == 0 || $.browser.msie) return;

		var $head = $("<div id='head_button'></div>").appendTo("#masthead .container").show();
		$buttons.each(function() {
			var $t = $(this);
			var $a = $t.parent('a'); 
			if($a.size()) { 
				$button = $t.parent('a').clone();
				$head.append($button);
			} else if($t.is('.head_button_clone')) {
				$button = $t.clone();
				$button.attr('data-from_id', $t.attr('id')).attr('id', $t.attr('id') + '_copy');
				$a = $("<a></a>").attr('href', '#');
				$button.click(function() {
					$("#" + $(this).attr('data-from_id')).click(); // .parents('form').submit();
					return false;
				});
				$head.append($a.append($button));	
			}
		}); 
	},

	/**
	 * Make buttons utilize the jQuery button state classes
	 *	
 	 */
	setupButtonStates: function() {
		// jQuery UI button states
		$(".ui-button").hover(function() {
			$(this).removeClass("ui-state-default").addClass("ui-state-hover");
		}, function() {
			$(this).removeClass("ui-state-hover").addClass("ui-state-default");
		}).click(function() {
			$(this).removeClass("ui-state-default").addClass("ui-state-active").effect('highlight', {}, 500); 
		});

		// make buttons with <a> tags click to the href of the <a>
		$("a > button").click(function() {
			window.location = $(this).parent("a").attr('href'); 
		}); 
	},

	/**
	 * Make the first field in any forum have focus, if it is a text field
	 *
	 */
	setupFieldFocus: function() {
		// add focus to the first text input, where applicable
		jQuery('#content input[type=text]:visible:enabled:first:not(.hasDatepicker)').each(function() {
			var $t = $(this); 
			if(!$t.val() && !$t.is(".no_focus")) window.setTimeout(function() { $t.focus(); }, 1);
		});

	},

	/**
	 * Adjust the font-size of the #title to fit within the screen's width
	 *
	 * If we get below a certain size, then we introduce line wrap
	 *
	 */
	sizeTitle: function() {
		// adjust the font-size of #title to fit within the screen's width
		var $title = $("#title"); 

		// don't bother continuing if the title isn't a consideration
		if($title.size() == 0 || $title.text().length < 35) return;

		var titleSizePx = $title.css('font-size'); // original/starting size (likely 37px)
		var titleSize = parseInt(titleSizePx); // size integer without 'px'
		var fitTitle = function() {
			// determine size of possible #head_button so that we don't overlap with it
			var buttonWidth = 0;
			var $button = $("#head_button button"); 
			if($button.size() > 0) buttonWidth = $button.width()+20; // 20=padding

			// maxTitleWidth is the width of #title's parent minus the buttonWidth
			maxTitleWidth = $title.parent().width() - buttonWidth; 
			
			// our default CSS settings when no resizing is needed
			$title.css({ whiteSpace: 'nowrap', marginTop: '0', paddingRight: '0' }); 

			// keep reducing the font-size of title until it fits
			while($title.width() > maxTitleWidth) {
				if(--titleSize < 22) {
					// if we get below 22px, lets wordwrap instead, and then get out
					$title.css({ marginTop: '-0.75em', whiteSpace: 'normal', paddingRight: buttonWidth + 'px' })
					break;
				}
				$title.css('font-size', titleSize + 'px');
			}
		}

		// when the window is resized, update the title size
		$(window).resize(function() {
			$title.css('font-size', titleSizePx);
			titleSize = parseInt(titleSizePx);
			fitTitle();
		});

		fitTitle();
	},

	/**
	 * Make the site search use autocomplete
	 * 
	 */
	setupSearch: function() {

		$.widget( "custom.adminsearchautocomplete", $.ui.autocomplete, {
			_renderMenu: function(ul, items) {
				var that = this;
				var currentType = "";
				$.each(items, function(index, item) {
					if (item.type != currentType) {
						ul.append("<li class='ui-widget-header'><a>" + item.type + "</a></li>" );
						currentType = item.type;
					}
					ul.attr('id', 'ProcessPageSearchAutocomplete'); 
					that._renderItemData(ul, item);
				});
			},
			_renderItemData: function(ul, item) {
				if(item.label == item.template) item.template = '';
				ul.append("<li><a href='" + item.edit_url + "'>" + item.label + " <small>" + item.template + "</small></a></li>"); 
			}
		});
		
		var $input = $("#ProcessPageSearchQuery"); 
		var $status = $("#ProcessPageSearchStatus"); 
		
		$input.adminsearchautocomplete({
			minLength: 2,
			position: { my : "right top", at: "right bottom" },
			search: function(event, ui) {
				$status.html("<img src='" + config.urls.modules + "Process/ProcessPageList/images/loading.gif'>");
			},
			source: function(request, response) {
				var url = $input.parents('form').attr('action') + 'for?get=template_label,title&include=all&admin_search=' + request.term;
				$.getJSON(url, function(data) {
					var len = data.matches.length; 
					if(len < data.total) $status.text(data.matches.length + '/' + data.total); 
						else $status.text(len); 
					response($.map(data.matches, function(item) {
						return {
							label: item.title,
							value: item.title,
							page_id: item.id,
							template: item.template_label ? item.template_label : '',
							edit_url: item.editUrl,
							type: item.type
						}
					}));
				});
			},
			select: function(event, ui) { }
		}).blur(function() {
			$status.text('');	
		});
		
	},

	/**
	 * Give a notice to IE versions we don't support
	 *
	 */
	browserCheck: function() {
		if($.browser.msie && $.browser.version < 8) 
			$("#content .container").html("<h2>ProcessWire does not support IE7 and below at this time. Please try again with a newer browser.</h2>").show();
	}

};

$(document).ready(function() {
	ProcessWireAdminTheme.init();
}); 
