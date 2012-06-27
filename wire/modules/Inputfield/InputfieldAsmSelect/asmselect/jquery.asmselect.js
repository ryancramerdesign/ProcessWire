/*
 * Alternate Select Multiple (asmSelect) 1.2 - jQuery Plugin
 * http://www.ryancramer.com/projects/asmselect/
 * 
 * Copyright (c) 2009-2012 by Ryan Cramer - http://www.ryancramer.com
 * 
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * @TODO asmSelect would probably be more functional if a deleted item still appears in the list (with ui-state-error or the like) rather than disappearing immediately. 
 * 
 *
 */

(function($) {

	$.fn.asmSelect = function(customOptions) {

		var options = {

			listType: 'ol',						// Ordered list 'ol', or unordered list 'ul'
			sortable: false, 					// Should the list be sortable?
			highlight: false,					// Use the highlight feature? 
			animate: false,						// Animate the the adding/removing of items in the list?
			addItemTarget: 'bottom',				// Where to place new selected items in list: top or bottom
			hideWhenAdded: false,					// Hide the option when added to the list? works only in FF
			hideWhenEmpty: false,					// Hide the <select> when there are no items available to select? 
			debugMode: false,					// Debug mode keeps original select visible 
			jQueryUI: true, 

			removeLabel: '<span class="ui-icon ui-icon-trash">remove</span>', // Text used in the "remove" link
			highlightAddedLabel: 'Added: ',				// Text that precedes highlight of added item
			highlightRemovedLabel: 'Removed: ',			// Text that precedes highlight of removed item

			containerClass: 'asmContainer',				// Class for container that wraps this widget
			selectClass: 'asmSelect',				// Class for the newly created <select>
			optionDisabledClass: 'asmOptionDisabled',		// Class for items that are already selected / disabled
			listClass: 'asmList',					// Class for the list ($ol)
			listSortableClass: 'asmListSortable',			// Another class given to the list when it is sortable
			listItemClass: 'asmListItem',				// Class for the <li> list items
			listItemLabelClass: 'asmListItemLabel',			// Class for the label text that appears in list items
			listItemDescClass: 'asmListItemDesc',			// Class for optional description text, set a data-desc attribute on the <option> to use it. May contain HTML.
			listItemStatusClass: 'asmListItemStatus',		// Class for optional status text, set a data-status attribute on the <option> to use it. May contain HTML.
			removeClass: 'asmListItemRemove',			// Class given to the "remove" link
			editClass: 'asmListItemEdit',
			highlightClass: 'asmHighlight',				// Class given to the highlight <span>

			editLink: '', 						// Optional URL options can link to with tag {value} replaced by option value, i.e. /path/to/page/edit?id={$value}
			editLabel: '<span class="ui-icon ui-icon-extlink"></span>', // Text used in the "edit" link (if editLink is populated)
			editLinkOnlySelected: true, 				// When true, edit link only appears for items that were already selected
			editLinkModal: true					// Whether the edit link (if used) should be modal

			};

		$.extend(options, customOptions); 

		return this.each(function(index) {

			var $original = $(this); 				// the original select multiple
			var $container; 					// a container that is wrapped around our widget
			var $select; 						// the new select we have created
			var $ol; 						// the list that we are manipulating
			var buildingSelect = false; 				// is the new select being constructed right now?
			var ieClick = false;					// in IE, has a click event occurred? ignore if not
			var ignoreOriginalChangeEvent = false;			// originalChangeEvent bypassed when this is true

			function init() {

				// initialize the alternate select multiple

				// this loop ensures uniqueness, in case of existing asmSelects placed by ajax (1.0.3)
				while($("#" + options.containerClass + index).size() > 0) index++; 

				$select = $("<select></select>")
					.addClass(options.selectClass)
					.attr('name', options.selectClass + index)
					.attr('id', options.selectClass + index); 

				$selectRemoved = $("<select></select>"); 

				$ol = $("<" + options.listType + "></" + options.listType + ">")
					.addClass(options.listClass)
					.attr('id', options.listClass + index); 

				$container = $("<div></div>")
					.addClass(options.containerClass) 
					.attr('id', options.containerClass + index); 

				buildSelect();

				$select.change(selectChangeEvent)
					.click(selectClickEvent); 

				$original.change(originalChangeEvent)
					.wrap($container).before($select).before($ol);

				if(options.sortable) makeSortable();

				if($.browser.msie && $.browser.version < 8) $ol.css('display', 'inline-block'); // Thanks Matthew Hutton

				$original.trigger('init'); 
			}

			function makeSortable() {

				// make any items in the selected list sortable
				// requires jQuery UI sortables, draggables, droppables

				$ol.sortable({
					items: 'li.' + options.listItemClass,
					handle: '.' + options.listItemLabelClass,
					axis: 'y',
					update: function(e, ui) {

						var updatedOptionId;
						$option = $('#' + ui.item.attr('rel')); 
						updatedOptionId = $option.attr('id'); 

						$(this).children("li").each(function(n) {

							$option = $('#' + $(this).attr('rel')); 
							$original.append($option); 

							/* this doesn't seem to work in newer versions of jquery
							if($(this).is(".ui-sortable-helper")) {
								updatedOptionId = $option.attr('id'); 
								return;
							}
							*/

						}); 

						if(updatedOptionId) triggerOriginalChange(updatedOptionId, 'sort'); 
					},
					start: function(e, data) {
						if(options.jQueryUI) data.item.addClass('ui-state-highlight'); 
					},
					stop: function(e, data) {
						if(options.jQueryUI) data.item.removeClass('ui-state-highlight'); 
					}

				}).addClass(options.listSortableClass); 
			}

			function selectChangeEvent(e) {
				
				// an item has been selected on the regular select we created
				// check to make sure it's not an IE screwup, and add it to the list

				if($.browser.msie && $.browser.version < 7 && !ieClick) return;
				var id = $(this).children("option:selected").slice(0,1).attr('rel'); 
				addListItem(id); 	
				ieClick = false; 
				triggerOriginalChange(id, 'add'); // for use by user-defined callbacks
			}

			function selectClickEvent() {

				// IE6 lets you scroll around in a select without it being pulled down
				// making sure a click preceded the change() event reduces the chance
				// if unintended items being added. there may be a better solution?

				ieClick = true; 
			}

			function originalChangeEvent(e) {

				// select or option change event manually triggered
				// on the original <select multiple>, so rebuild ours

				if(ignoreOriginalChangeEvent) {
					ignoreOriginalChangeEvent = false; 
					return; 
				}

				$select.empty();
				$ol.empty();
				buildSelect();

				// opera has an issue where it needs a force redraw, otherwise
				// the items won't appear until something else forces a redraw
				if($.browser.opera) $ol.hide().fadeIn("fast");
			}

			function buildSelect() {

				// build or rebuild the new select that the user
				// will select items from

				buildingSelect = true; 

				// add a first option to be the home option / default selectLabel
				var title = $original.attr('title'); 
				// number of items that are not disabled
				var numActive = 0; 

				if(title === undefined) title = '';
				$select.prepend("<option>" + title + "</option>"); 

				$original.children("option").each(function(n) {

					var $t = $(this); 
					var id; 

					if(!$t.attr('id')) $t.attr('id', 'asm' + index + 'option' + n); 
					id = $t.attr('id'); 

					if($t.is(":selected")) {
						addListItem(id); 
						addSelectOption(id, true); 						
					} else {
						numActive++;
						addSelectOption(id); 
					}
				});

				if(!options.debugMode) $original.hide(); // IE6 requires this on every buildSelect()
				selectFirstItem();
				if(options.hideWhenEmpty) { 
					if(numActive > 0) $select.show(); else $select.hide();
				}
				buildingSelect = false; 
			}

			function addSelectOption(optionId, disabled) {

				// add an <option> to the <select>
				// used only by buildSelect()

				if(disabled == undefined) var disabled = false; 

				var $O = $('#' + optionId); 
				var $option = $("<option>" + $O.text() + "</option>")
					.val($O.val())
					.attr('rel', optionId);

				if(disabled) disableSelectOption($option); 

				$select.append($option); 
			}

			function selectFirstItem() {

				// select the firm item from the regular select that we created

				$select.children(":eq(0)").attr("selected", true); 
			}

			function disableSelectOption($option) {

				// make an option disabled, indicating that it's already been selected
				// because safari is the only browser that makes disabled items look 'disabled'
				// we apply a class that reproduces the disabled look in other browsers

				$option.addClass(options.optionDisabledClass)
					.attr("selected", false)
					.attr("disabled", true);

				if(options.hideWhenEmpty) {
					if($option.siblings('[disabled!=true]').size() < 2) $select.hide();
				}

				if(options.hideWhenAdded) $option.hide();
				if($.browser.msie) $select.hide().show(); // this forces IE to update display
			}

			function enableSelectOption($option) {

				// given an already disabled select option, enable it

				$option.removeClass(options.optionDisabledClass)
					.attr("disabled", false);

				if(options.hideWhenEmpty) $select.show(); 
				if(options.hideWhenAdded) $option.show();
				if($.browser.msie) $select.hide().show(); // this forces IE to update display
			}

			function addListItem(optionId) {

				// add a new item to the html list

				var $O = $('#' + optionId); 

				if(!$O) return; // this is the first item, selectLabel

				var $removeLink = $("<a></a>")
					.attr("href", "#")
					.addClass(options.removeClass)
					.prepend(options.removeLabel)
					.click(function() { 
						dropListItem($(this).parent('li').attr('rel')); 
						return false; 
					}); 

				var $itemLabel = $("<span></span>").addClass(options.listItemLabelClass);

				// optional container where an <option>'s data-status attribute will be displayed
				var $itemStatus = $("<span></span>").addClass(options.listItemStatusClass);
				if($O.attr('data-status')) $itemStatus.html($O.attr('data-status')); 

				// optional container where an <option>'s data-desc attribute will be displayed
				var $itemDesc = $("<span></span>").addClass(options.listItemDescClass);
				if($O.attr('data-desc')) $itemDesc.html($O.attr('data-desc')); 

				if(options.editLink.length > 0 && ($O.is(':selected') || !options.editLinkOnlySelected)) {
					var $editLink = $("<a></a>")
						.html($O.html())
						.attr('href', options.editLink.replace(/\{value\}/, $O.val()))
						.append(options.editLabel)
						.click(clickEditLink);
					$itemLabel.addClass(options.editClass).append($editLink);

				} else {
					$itemLabel.html($O.html()); 
				}


				var $item = $("<li></li>")
					.attr('rel', optionId)
					.addClass(options.listItemClass)
					.append($itemLabel)
					.append($itemDesc)
					.append($itemStatus)
					.append($removeLink)
					.hide();

				if(options.jQueryUI) {
					$item.addClass('ui-state-default')
					.hover(function() {
						$(this).addClass('ui-state-hover').removeClass('ui-state-default'); 
					}, function() {
						$(this).addClass('ui-state-default').removeClass('ui-state-hover'); 
					}); 
					if(options.sortable) $item.prepend("<span class='ui-icon ui-icon-arrowthick-2-n-s'></span>"); 
				}

				if(!buildingSelect) {
					if($O.is(":selected")) return; // already have it
					$O.attr('selected', true); 
				}

				if(options.addItemTarget == 'top' && !buildingSelect) {
					$ol.prepend($item); 
					if(options.sortable) $original.prepend($O); 
				} else {
					$ol.append($item); 
					if(options.sortable) $original.append($O); 
				}

				addListItemShow($item); 

				disableSelectOption($("[rel=" + optionId + "]", $select));

				if(!buildingSelect) {
					setHighlight($item, options.highlightAddedLabel); 
					selectFirstItem();
					if(options.sortable) $ol.sortable("refresh"); 	
				}

			}

			function addListItemShow($item) {

				// reveal the currently hidden item with optional animation
				// used only by addListItem()

				if(options.animate && !buildingSelect) {
					$item.animate({
						opacity: "show",
						height: "show"
					}, 100, "swing", function() { 
						$item.animate({
							height: "+=2px"
						}, 50, "swing", function() {
							$item.animate({
								height: "-=2px"
							}, 25, "swing"); 
						}); 
					}); 
				} else {
					$item.show();
				}
			}

			function dropListItem(optionId, highlightItem) {

				// remove an item from the html list

				if(highlightItem == undefined) var highlightItem = true; 
				var $O = $('#' + optionId); 

				$O.attr('selected', false); 
				$item = $ol.children("li[rel=" + optionId + "]");

				dropListItemHide($item); 
				enableSelectOption($("[rel=" + optionId + "]", options.removeWhenAdded ? $selectRemoved : $select));

				if(highlightItem) setHighlight($item, options.highlightRemovedLabel); 

				triggerOriginalChange(optionId, 'drop'); 
				
			}

			function dropListItemHide($item) {

				// remove the currently visible item with optional animation
				// used only by dropListItem()

				if(options.animate && !buildingSelect) {

					$prevItem = $item.prev("li");

					$item.animate({
						opacity: "hide",
						height: "hide"
					}, 100, "linear", function() {
						$prevItem.animate({
							height: "-=2px"
						}, 50, "swing", function() {
							$prevItem.animate({
								height: "+=2px"
							}, 100, "swing"); 
						}); 
						$item.remove(); 
					}); 
					
				} else {
					$item.remove(); 
				}
			}

			function setHighlight($item, label) {

				// set the contents of the highlight area that appears
				// directly after the <select> single
				// fade it in quickly, then fade it out

				if(!options.highlight) return; 

				$select.next("#" + options.highlightClass + index).remove();

				var $highlight = $("<span></span>")
					.hide()
					.addClass(options.highlightClass)
					.attr('id', options.highlightClass + index)
					.html(label + $item.children("." + options.listItemLabelClass).slice(0,1).text()); 
					
				$select.after($highlight); 

				$highlight.fadeIn("fast", function() {
					setTimeout(function() { $highlight.fadeOut("slow", function() {
						$(this).remove();
					}); }, 50); 
				}); 
			}

			function triggerOriginalChange(optionId, type) {

				// trigger a change event on the original select multiple
				// so that other scripts can pick them up

				ignoreOriginalChangeEvent = true; 
				$option = $("#" + optionId); 

				$original.trigger('change', [{
					'option': $option,
					'value': $option.val(),
					'id': optionId,
					'item': $ol.children("[rel=" + optionId + "]"),
					'type': type
				}]); 
			}

			function clickEditLink(e) {

				if(!options.editLinkModal) return true; 

				var $asmItem = $(this).parents('.' + options.listItemClass); 
				var href = $(this).attr('href'); 
				var $iframe = $('<iframe id="asmSelectDialog" frameborder="0" src="' + href + '"></iframe>');
				var windowWidth = $(window).width()-300;
				var windowHeight = $(window).height()-300;
				if(windowHeight > 800) windowHeight = 800;

				var $dialog = $iframe.dialog({
					modal: true,
					height: windowHeight,
					width: windowWidth,
					position: [150,80]
				}).width(windowWidth).height(windowHeight);

				var iframeLoad = function() {

					var $icontents = $iframe.contents();	

					var browserTitle = $icontents.find('head title').text();
					$dialog.dialog('option', 'title', browserTitle); 	
		
					var buttons = [];
					var buttonCnt = 0;

					$icontents.find('form button.ui-button').each(function(n) {

						var $button = $(this);
						var label = $button.text();
						var valid = true; 
						var secondary = $button.is('.ui-priority-secondary'); 

						for(i = 0; i < buttonCnt; i++) {
							if(label == buttons[i].text) valid = false;
						}

						if(valid) {
							buttons[buttonCnt] = { 
								text: label, 
								'class': (secondary ? 'ui-priority-secondary' : ''),
								click: function() {
									if($button.attr('type') == 'submit') {
										$button.click(); 
										$asmItem.effect('highlight', {}, 500); 
										var $asmSetStatus = $icontents.find(':input.' + options.listItemStatusClass); 
										if($asmSetStatus.size() > 0) $asmItem.find('.' + options.listItemStatusClass).html($asmSetStatus.val());
									}
									$dialog.dialog('close'); 
								}
							}; 
							buttonCnt++;
						}
						$button.hide();
					}); 
					if(buttons.length > 0) $dialog.dialog('option', 'buttons', buttons)
					$dialog.width(windowWidth).height(windowHeight); 
				};

				$iframe.load(iframeLoad);

				return false; 
			}


			init();
		});
	};

})(jQuery); 
