/**
  * jQuery Tabs for ProcessWire
  *
  * ProcessWire 2.x
  * Copyright (C) 2014 by Ryan Cramer
  * Licensed under GNU/GPL v2, see LICENSE.TXT
  *
  * http://processwire.com
  *
  */
(function($) {

	$.fn.WireTabs = function(customOptions) {

		var options = {
			rememberTabs: 0, // -1 = no, 0 = only after submit, 1 = always
			cookieName: 'WireTabs',
			items: null,
			skipRememberTabIDs: [],
			itemsParent: null,
			id: '' // id for tabList. if already exists, existing tabList will be used
		};
		
		if(config.JqueryWireTabs.rememberTabs != "undefined") {
			options.rememberTabs = config.JqueryWireTabs.rememberTabs;
		}
		var totalTabs = 0; 

		$.extend(options, customOptions);

		return this.each(function(index) {

			var $tabList = null;
			var $target = $(this); 
			var lastTabID = ''; // ID attribute of last tab that was clicked
			var generate = true; // generate markup/manipulate DOM?

			function init() {

				if(!options.items) return;
				if(options.items.size() < 2) return;
				
				if(options.id.length) {
					$tabList = $("#" + options.id);
					if($tabList.length) generate = false;
						else $tabList = null;
				}
				if(!$tabList) {
					$tabList = $("<ul></ul>").addClass("WireTabs nav");
					if(options.id.length) $tabList.attr('id', options.id); 
				}
				
				options.items.each(addTab); 
				if(generate) $target.prepend($tabList); // DOM manipulation
		
				var $form = $target; 	
				var $rememberTab = null;
				var cookieTab = getTabCookie(); 

				if(options.rememberTabs == 0) {
					$form.submit(function() { 
						setTabCookie(lastTabID); 
						return true; 
					}); 
				}

				var hash = document.location.hash.replace("#",""); // thanks to @da-fecto
				if(hash.length) {
					$rememberTab = $tabList.find("a#_" + hash); 
					if($rememberTab.length == 0) {
						$rememberTab = null;
					} else {
						document.location.hash = '';
					}
				}
				if($rememberTab == null && cookieTab.length > 0 && options.rememberTabs > -1) $rememberTab = $tabList.find("a#_" + cookieTab);
				if($rememberTab && $rememberTab.size() > 0) {
					$rememberTab.click();
					if(options.rememberTabs == 0) setTabCookie(''); // don't clear cookie when rememberTabs=1, so it continues
				} else {
					$tabList.children("li:first").children("a").click();
				}
			}

			function addTab() {
				totalTabs++;
				var $t = $(this);
				if(!$t.attr('id')) $t.attr('id', "WireTab" + totalTabs); 
				var title = $t.attr('title') || $t.attr('id'); 
				$t.removeAttr('title');
				var href = $t.attr('id'); 
				var $a = $('a#_' + href); // does it already exist?
				if($a.length > 0) {
					$a.click(tabClick); 
				} else {
					var $a = $("<a></a>")
						.attr('href', '#' + href)
						.attr('id', '_' + href) // ID equal to tab content ID, but preceded with underscore
						.html(title)
						.click(tabClick); 
					$tabList.append($("<li></li>").append($a)); 
				}
				$t.hide();
				// the following removed to prevent DOM manipulation if the tab content:
				// if(options.itemsParent === null) options.itemsParent = $t.parent(); 
				//if($t.parent() != options.itemsParent) options.itemsParent.prepend($t);
				//$target.prepend($t.hide()); 
			}

			function tabClick() {
				var $oldTab = $($tabList.find("a.on").removeClass("on").attr('href')).hide(); 
				var $newTab = $($(this).addClass('on').attr('href')).show(); 
				var newTabID = $newTab.attr('id'); 
				var oldTabID = $oldTab.attr('id'); 

				// add a target classname equal to the ID of the selected tab
				// so there is opportunity for 3rd party CSS adjustments outside this plugin
				if(oldTabID) $target.removeClass($oldTab.attr('id')); 
				$target.addClass(newTabID); 
				if(options.rememberTabs > -1) {
					if(jQuery.inArray(newTabID, options.skipRememberTabIDs) != -1) newTabID = '';
					if(options.rememberTabs == 1) setTabCookie(newTabID); 
					lastTabID = newTabID; 
				}
				$(document).trigger('wiretabclick', [ $newTab, $oldTab ]); 
				return false; 
			}

			function setTabCookie(value) {
				document.cookie = options.cookieName + '=' + escape(value);
			}
	
			function getTabCookie() {
				var regex = new RegExp('(?:^|;)\\s?' + options.cookieName + '=(.*?)(?:;|$)','i');
				var match = document.cookie.match(regex);	
				match = match ? match[1] : '';
				return match;
			}

			init(); 
		})
	}
})(jQuery); 

