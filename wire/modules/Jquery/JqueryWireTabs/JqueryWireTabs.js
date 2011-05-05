/**
 * Wire Tabs, jQuery plugin
 *
 * Developed by Ryan Cramer for ProcessWire
 *
 */

(function($) {

	$.fn.WireTabs = function(customOptions) {

		var options = {
			items: null,
			id: ''
		};
			
		var totalTabs = 0; 

		$.extend(options, customOptions);

		return this.each(function(index) {

			var $tabList = $("<ul></ul>").addClass("WireTabs nav");
			var $target = $(this); 

			function init() {
				if(!options.items) return; 
				if(options.id.length) $tabList.attr('id', options.id); 
				if(options.items.size() < 2) return;
				options.items.each(addTab); 
				$target.prepend($tabList); 
				$tabList.children("li:first").children("a").click();
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

				// add a target classname equal to the ID of the selected tab
				// so there is opportunity for 3rd party CSS adjustments outside this plugin
				$target.removeClass($oldTab.attr('id')); 
				$target.addClass($newTab.attr('id')); 
				return false; 
			}

			init(); 
		})
	}
})(jQuery); 

