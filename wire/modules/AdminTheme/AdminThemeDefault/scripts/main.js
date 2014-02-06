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
		// fix annoying fouc with this particular button
		var $button = $("#head_button > button.dropdown-toggle").hide();

		this.setupCloneButton();
		this.setupButtonStates();
		this.setupFieldFocus();
		this.setupTooltips();
		this.setupSearch();
		this.setupDropdowns();
		this.setupMobile();
		// this.sizeTitle();
		if($("body").hasClass('hasWireTabs') && $("ul.WireTabs").size() == 0) $("body").removeClass('hasWireTabs'); 
		$('#content').removeClass('fouc_fix'); // FOUC fix
		this.browserCheck();

		if($button.size() > 0) $button.show();
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
		// no head_button in modal view
		if($("body").is(".modal")) return;

		// if there are buttons in the format "a button" without ID attributes, copy them into the masthead
		// or buttons in the format button.head_button_clone with an ID attribute.
		// var $buttons = $("#content a[id=] button[id=], #content button.head_button_clone[id!=]"); 
		var $buttons = $("#content a:not([id]) button:not([id]), #content button.head_button_clone[id!=]"); 

		// don't continue if no buttons here or if we're in IE
		if($buttons.size() == 0 || $.browser.msie) return;

		var $head = $("<div id='head_button'></div>").prependTo("#breadcrumbs .container").show();
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
			$(this).removeClass("ui-state-default").addClass("ui-state-active"); // .effect('highlight', {}, 100); 
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
			open: function(event, ui) {
				$("#topnav").hide();
			},
			close: function(event, ui) {
				$("#topnav").show();
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
		}).focus(function() {
			$(this).siblings('label').find('i').hide(); // hide icon
		}).blur(function() {
			$status.text('');	
			$(this).siblings('label').find('i').show(); // show icon
		});
		
	},

	// whether or not dropdown positions are currently being monitored
	dropdownPositionsMonitored: false,

	setupDropdowns: function() {

		$("ul.dropdown-menu").each(function() {
			var $ul = $(this).hide();
			var $a = $ul.siblings(".dropdown-toggle"); 

			if($a.is("button")) {
				$a.button();
			} else {
				$ul.css({ 'border-top-right-radius': 0 }); 
			}

			// hide nav when an item is selected to avoid the whole nav getting selected
			$ul.find('a').click(function() {
				$ul.hide();
				return true; 
			});

			var lastOffset = null; 

			$a.mouseenter(function() {
				var offset = $a.offset();	
				if(lastOffset != null) {
					if(offset.top != lastOffset.top || offset.left != lastOffset.left) {
						// dropdown-toggle has moved, destroy and re-create
						$ul.menu('destroy').removeClass('dropdown-ready');
					}
				}	
				if(!$ul.hasClass('dropdown-ready')) {
					$ul.css('position', 'absolute'); 
					$ul.prependTo($('body')).addClass('dropdown-ready').menu();
					var position = { my: 'right top', at: 'right bottom', of: $a };
					var my = $ul.attr('data-my'); 
					var at = $ul.attr('data-at'); 
					if(my) position.my = my; 
					if(at) position.at = at; 
					$ul.position(position).css('z-index', 200);
				}
				$a.addClass('hover'); 
				$ul.show();
				lastOffset = offset; 

			}).mouseleave(function() {
				setTimeout(function() {
					if($ul.is(":hover")) return;
					$ul.find('ul').hide();
					$ul.hide();
					$a.removeClass('hover');
				}, 50); 
			}); 

			$ul.mouseleave(function() {
				if($a.is(":hover")) return;
				$ul.hide();
				$a.removeClass('hover'); 
			}); 

		});

		// ajax loading of fields and templates
		$(document).on('hover', 'ul.dropdown-menu a.has-ajax-items:not(.ajax-items-loaded)', function() {
			var $a = $(this); 
			$a.addClass('ajax-items-loaded'); 	
			var url = $a.attr('href');
			var $ul = $a.siblings('ul'); 

			$.getJSON(url, function(data) {

				// now add new event to monitor menu positions
				if(!ProcessWireAdminTheme.dropdownPositionsMonitored && data.length > 10) {
					ProcessWireAdminTheme.dropdownPositionsMonitored = true; 
					var dropdownHover = function() {
						var fromAttr = $a.attr('data-from'); 
						if(!fromAttr) return;
						var $from = $('#' + $a.attr('data-from')); 
						setTimeout(function() { 
							var fromLeft = $from.offset().left-1;
							var $ul = $a.parent('li').parent('ul'); 
							var thisLeft = $ul.offset().left;
							if(thisLeft != fromLeft) $ul.css('left', fromLeft); 
						}, 500); 
					};
					$(document).on('hover', 'ul.dropdown-menu a', dropdownHover); 
				}

				// populate the retrieved items
				$.each(data, function(n) {
					var item = this;
					var $li = $("<li class='ui-menu-item'><a href='" + url + "edit?id=" + item.id + "'>" + item.name + "</a></li>"); 
					$ul.append($li); 
				}); 

				// trigger the first call
				dropdownHover();

			}); 
		}); 


	}, 	

	setupMobile: function() {
		// collapse or expand the topnav menu according to whether it is wrapping to multiple lines
		var collapsedTopnavAtBodyWidth = 0;
		var collapsedTabsAtBodyWidth = 0;

		var windowResize = function() {

			// top navigation
			var $topnav = $("#topnav"); 
			var $body = $("body"); 
			var height = $topnav.height();

			if(height > 50) {
				// topnav has wordwrapped
				if(!$body.hasClass('collapse-topnav')) {
					$body.addClass('collapse-topnav'); 
					collapsedTopnavAtBodyWidth = $body.width();
				}
			} else if(collapsedTopnavAtBodyWidth > 0) {
				// topnav is on 1 line
				var width = $body.width();
				if($body.hasClass('collapse-topnav') && width > collapsedTopnavAtBodyWidth) {
					$body.removeClass('collapse-topnav'); 
					collapsedTopnavAtBodyWidth = 0;
				}
			}

			$topnav.children('.collapse-topnav-menu').children('a').click(function() {
				if($(this).is(".hover")) {
					// already open? close it. 
					$(this).mouseleave();
				} else {
					// open it again
					$(this).mouseenter();
				}
				return false;
			}); 

			// wiretabs
			var $wiretabs = $(".WireTabs"); 
			if($wiretabs.size < 1) return;

			$wiretabs.each(function() {
				var $tabs = $(this);
				var height = $tabs.height();
				if(height > 65) {
					if(!$body.hasClass('collapse-wiretabs')) {
						$body.addClass('collapse-wiretabs'); 
						collapsedTabsAtBodyWidth = $body.width();
						// console.log('collapse wiretabs'); 
					}
				} else if(collapsedTabsAtBodyWidth > 0) {
					var width = $body.width();
					if($body.hasClass('collapse-wiretabs') && width > collapsedTabsAtBodyWidth) {
						$body.removeClass('collapse-wiretabs'); 
						collapsedTabsAtBodyWidth = 0;
						// console.log('un-collapse wiretabs'); 
					}
				}
			}); 
		};

		windowResize();
		$(window).resize(windowResize);

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

	$("#notices a.notice-remove").click(function() {
		$("#notices").slideUp('fast', function() { $(this).remove(); }); 
	}); 
}); 
