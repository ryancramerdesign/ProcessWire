
var InputfieldPageListSelectMultiple = {

	/**
	 * Initialize the given InputfieldPageListSelectMultiple OL by making it sortable
	 *
	 */
	init: function($ol) {

		var makeSortable = function($ol) { 
			$ol.sortable({
				// items: '.InputfieldPageListSelectMultiple ol > li',
				axis: 'y',
				update: function(e, data) {
					InputfieldPageListSelectMultiple.rebuildInput($(this)); 
				},
				start: function(e, data) {
					data.item.addClass('ui-state-highlight');
				},
				stop: function(e, data) {
					data.item.removeClass('ui-state-highlight');
				}
			}); 
			$ol.addClass('InputfieldPageListSelectMultipleSortable'); 
		};

		$('#' + $ol.attr('id') + '>li').live('mouseover', function() {
			$(this).removeClass('ui-state-default').addClass('ui-state-hover'); 
			if(!$ol.is(".InputfieldPageListSelectMultipleSortable")) makeSortable($ol); 
		}).live('mouseout', function() {
			$(this).removeClass('ui-state-hover').addClass('ui-state-default'); 
		}); 

	},

	/**
	 * Callback function executed when a page is selected from PageList
	 *
	 */
	pageSelected: function(e, page) {

		$input = e.data;

		var $ol = $('#' + $input.attr('id') + '_items');
		var $li = $ol.children(".itemTemplate").clone();

		$li.removeClass("itemTemplate"); 
		$li.children('.itemValue').text(page.id); 
		$li.children('.itemLabel').text(page.title); 

		$ol.append($li);

		InputfieldPageListSelectMultiple.rebuildInput($ol); 

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

	$(".InputfieldPageListSelectMultiple ol li a.itemRemove").live('click', function() {
		var $li = $(this).parent(); 
		var $ol = $li.parent(); 
		var id = $li.children(".itemValue").text();
		$li.remove();
		InputfieldPageListSelectMultiple.rebuildInput($ol); 
		return false; 
	}); 

}); 


