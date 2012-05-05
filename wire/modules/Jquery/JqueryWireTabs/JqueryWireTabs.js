/**
 * Wire Tabs, jQuery plugin
 *
 * Developed by Ryan Cramer for ProcessWire
 *
 */

(function($) {

	$.fn.WireTabs = function(customOptions) {

		var options = {
			rememberTabs: config.JqueryWireTabs.rememberTabs, // -1 = no, 0 = only after submit, 1 = always
			cookieName: 'WireTabs',
			items: null,
			skipRememberTabIDs: [],
			id: ''
		};
			
		var totalTabs = 0; 

		$.extend(options, customOptions);

		return this.each(function(index) {

			var $tabList = $("<ul></ul>").addClass("WireTabs nav");
			var $target = $(this); 
			var lastTabID = ''; // ID attribute of last tab that was clicked

			function init() {

				if(!options.items) return; 
				if(options.id.length) $tabList.attr('id', options.id); 
				if(options.items.size() < 2) return;

				options.items.each(addTab); 
				$target.prepend($tabList); 
		
				var $form = $target; 	
				var $rememberTab = null;
				var cookieTab = getTabCookie(); 

				if(options.rememberTabs == 0) {
					$form.submit(function() { 
						setTabCookie(lastTabID); 
						return true; 
					}); 
				}

				if(cookieTab.length > 0 && options.rememberTabs > -1) $rememberTab = $tabList.find("a#_" + cookieTab);
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
				var $a = $("<a></a>")
					.attr('href', '#' + href)
					.attr('id', '_' + href) // ID equal to tab content ID, but preceded with underscore
					.html(title)
					.click(tabClick); 
				$tabList.append($("<li></li>").append($a)); 
				$target.prepend($t.hide()); 
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

