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

		$icon.click(function() { $input.focus(); }); 

		$input.autocomplete({
			minLength: 2,
			source: function(request, response) {
				
				$icon.addClass('ui-icon-refresh'); 

				$.getJSON(url + '&' + searchField + operator + escape(request.term), function(data) { 

					$icon.removeClass('ui-icon-refresh'); 

					if(data.total > 0) {
						$icon.addClass('ui-icon-arrowreturn-1-s'); 
					} else {
						$icon.addClass('ui-icon-alert'); 
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
				if(ui.item) {
					InputfieldPageAutocomplete.pageSelected($ol, ui.item); 
					$(this).val('');
					return false;
				}
			}

		}).blur(function() {
			if(!$(this).val().length) $input.val('');
			$icon.removeClass('ui-icon-arrowreturn-1-s ui-icon-alert'); 
		}).keyup(function() {
			$icon.removeClass('ui-icon-arrowreturn-1-s ui-icon-alert'); 
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

		$('#' + $ol.attr('id') + '>li').live('mouseover', function() {
			$(this).removeClass('ui-state-default').addClass('ui-state-hover'); 
			makeSortable($ol); 
		}).live('mouseout', function() {
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
		var id = $ol.attr('id');
		id = id.substring(0, id.lastIndexOf('_')); 
		var $input = $('#' + id);
		var value = '';
		$ol.children(':not(.itemTemplate)').each(function() {
			if(value.length > 0) value += ',';
			value += $(this).children('.itemValue').text();
		}); 
		$input.val(value);
	}


}; 

$(document).ready(function() {

	$(".InputfieldPageAutocomplete ol li a.itemRemove").live('click', function() {
		var $li = $(this).parent(); 
		var $ol = $li.parent(); 
		var id = $li.children(".itemValue").text();
		$li.remove();
		InputfieldPageAutocomplete.rebuildInput($ol); 
		return false; 
	}); 

}); 


