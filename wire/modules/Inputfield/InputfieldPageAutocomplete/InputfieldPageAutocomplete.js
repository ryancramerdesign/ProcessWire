/**
 * ProcessWire Page Auto Completion select widget
 *
 * This Inputfield connects the jQuery UI Autocomplete widget with the ProcessWire ProcessPageSearch AJAX API.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
var InputfieldPageAutocomplete = {

	/**
	 * Initialize the given InputfieldPageListSelectMultiple OL by making it sortable
	 *
	 */
	init: function(id, url, labelField, searchField, operator) {

		var $ol = $('#' + id + '_items'); 
		var $input = $('#' + id + '_input'); 
		var $icon = $input.parent().find(".InputfieldPageAutocompleteStatus");
		var $note = $input.parent().find(".InputfieldPageAutocompleteNote"); 
		var numAdded = 0; // counter that keeps track of quantity items added
		var numFound = 0; // indicating number of pages matching during last ajax request
		var disableChars = $input.attr('data-disablechars'); 
	
		var iconHeight = $icon.height();
		if(iconHeight) {
			var pHeight = $icon.parent().height();
			var iconTop = ((pHeight - iconHeight) / 2);
			$icon.css('top', iconTop + 'px');
			$icon.css('left', (iconTop / 2) + 'px');
		} else {
			// icon is not visible (in a tab or collapsed field), we'll leave it alone
		}	

		$icon.click(function() { $input.focus(); });
		$icon.attr('data-class', $icon.attr('class')); 

		function isAddAllowed() {
			return $('#_' + $ol.attr('data-name') + '_add_items').size() > 0; 
		}

		$input.autocomplete({
			minLength: 2,
			source: function(request, response) {
				
				if(disableChars && disableChars.length) {
					var disable = false;
					var term = request.term;
					for(var n = 0; n < disableChars.length; n++) {
						if(term.indexOf(disableChars[n]) > -1) {
							disable = true;
							break;
						}
					}
					if(disable) {
						response([]); 
						return;
					}
				}
				
				$icon.attr('class', 'fa fa-fw fa-spin fa-spinner'); 
				
				var term = request.term;
				if($input.hasClass('and_words') && term.indexOf(' ') > 0) {
					// AND words mode
					term = term.replace(/\s+/, ',');
				}
				var ajaxURL = url + '&' + searchField + operator + term; 

				$.getJSON(ajaxURL, function(data) { 

					$icon.attr('class', $icon.attr('data-class')); 
					numFound = data.total;

					if(data.total > 0) {
						$icon.attr('class', 'fa fa-fw fa-angle-double-down'); 

					} else if(isAddAllowed()) {
						$icon.attr('class', 'fa fa-fw fa-plus-circle'); 
						$note.show();

					} else {
						$icon.attr('class', 'fa fa-fw fa-frown-o'); 
					}

					response($.map(data.matches, function(item) {
						return {
							label: item[labelField], 
							value: item[labelField],
							page_id: item.id
						}
					})); 
				}); 
			},
			select: function(event, ui) {
				if(!ui.item) return;
				if($(this).hasClass('no_list')) {
					$(this).val(ui.item.label).change();
					$(this).blur();
					return false;
				} else {
					InputfieldPageAutocomplete.pageSelected($ol, ui.item);
					$(this).val('').focus();
					return false;
				}
			}

		}).blur(function() {
			if(!$(this).val().length) $input.val('');
			$icon.attr('class', $icon.attr('data-class')); 
			$note.hide();

		}).keyup(function() {
			$icon.attr('class', $icon.attr('data-class')); 

		}).keydown(function(event) {
			if(event.keyCode == 13) {
				// prevents enter from submitting the form
				event.preventDefault();
				// instead we add the text entered as a new item
				// if there is an .InputfieldPageAdd sibling, which indicates support for this
				if(isAddAllowed()) { 
					if($.trim($input.val()).length < 1) {
						$input.blur();
						return false;
					}
					numAdded++;
					// new items have a negative page_id
					var page = { page_id: (-1 * numAdded), label: $input.val() }; 
					// add it to the list
					InputfieldPageAutocomplete.pageSelected($ol, page); 
					$input.val('').blur().focus();
					$note.hide();
				} else {
					$(this).blur();
				}
				return false;
			}
		}); 

		var makeSortable = function($ol) { 
			$ol.sortable({
				// items: '.InputfieldPageListSelectMultiple ol > li',
				axis: 'y',
				update: function(e, data) {
					InputfieldPageAutocomplete.rebuildInput($(this)); 
				},
				start: function(e, data) {
					data.item.addClass('ui-state-highlight');
				},
				stop: function(e, data) {
					data.item.removeClass('ui-state-highlight');
				}
			}); 
			$ol.addClass('InputfieldPageAutocompleteSortable'); 
		};

		// $('#' + $ol.attr('id') + '>li').live('mouseover', function() {
		$('#' + $ol.attr('id')).on('mouseover', '>li', function() { 
			$(this).removeClass('ui-state-default').addClass('ui-state-hover'); 
			makeSortable($ol); 
		// }).live('mouseout', function() {
		}).on('mouseout', '>li', function() {
			$(this).removeClass('ui-state-hover').addClass('ui-state-default'); 
		}); 

	},

	/**
	 * Callback function executed when a page is selected from PageList
	 *
	 */
	pageSelected: function($ol, page) {

		var dup = false;
		
		$ol.children('li').each(function() {
			var v = parseInt($(this).children('.itemValue').text());	
			if(v == page.page_id) dup = $(this);
		});
		
		var $inputText = $('#' + $ol.attr('data-id') + '_input');
		$inputText.blur();

		if(dup) {
			dup.effect('highlight'); 
			return;
		}
		
		var $li = $ol.children(".itemTemplate").clone();

		$li.removeClass("itemTemplate"); 
		$li.children('.itemValue').text(page.page_id); 
		$li.children('.itemLabel').text(page.label); 

		$ol.append($li);

		InputfieldPageAutocomplete.rebuildInput($ol); 

	},

	/**
	 * Rebuild the CSV values present in the hidden input[text] field
	 *
	 */
	rebuildInput: function($ol) {
		var id = $ol.attr('data-id');
		var name = $ol.attr('data-name');
		//id = id.substring(0, id.lastIndexOf('_')); 
		var $input = $('#' + id);
		var value = '';
		var addValue = '';
		var max = parseInt($input.attr('data-max'));

		var $children = $ol.children(':not(.itemTemplate)');
		if(max > 0 && $children.size() > max) { 
			while($children.size() > max) $children = $children.slice(1); 
			$ol.children(':not(.itemTemplate)').replaceWith($children);
		}
	
		$children.each(function() {
			var v = parseInt($(this).children('.itemValue').text());
			if(v > 0) {
				value += ',' + v; 
			} else if(v < 0) {
				value += ',' + v; 
				addValue += $(this).children('.itemLabel').text() + "\n";
			}
		}); 
		$input.val(value);

		var $addItems = $('#_' + name + '_add_items'); 
		if($addItems.size() > 0) $addItems.val(addValue);
	}


}; 

$(document).ready(function() {

	//$(".InputfieldPageAutocomplete ol li a.itemRemove").live('click', function() { // live() deprecated
	$(".InputfieldPageAutocomplete ol").on('click', 'a.itemRemove', function() {
		var $li = $(this).parent(); 
		var $ol = $li.parent(); 
		var id = $li.children(".itemValue").text();
		$li.remove();
		InputfieldPageAutocomplete.rebuildInput($ol); 
		return false; 
	});

}); 


