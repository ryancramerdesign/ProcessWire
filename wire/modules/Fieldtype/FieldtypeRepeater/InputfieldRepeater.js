/**
 * ProcessWire Repeater Inputfield Javascript
 *
 * Maintains a collection of fields that are repeated for any number of times.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

$(document).ready(function() {

	var deleteClick = function(e) {

		var $parent = $(this).parent('label').parent('li');

		if($parent.is('.InputfieldRepeaterNewItem')) {
			// delete new item
			var $numAddInput = $parent.parent().parent().find('.InputfieldRepeaterAddItem').children('input');
			$numAddInput.attr('value', parseInt($numAddInput.attr('value')-1)); // total number of new items to add, minus 1
			$parent.remove();
			
		} else { 
			// delete existing item
			var $checkbox = $parent.find('.InputfieldRepeaterDelete'); 

			if($checkbox.is(":checked")) {
				$checkbox.removeAttr('checked'); 
				$parent.children('label').removeClass('ui-state-error').addClass('ui-state-default'); 
				if($parent.is('.InputfieldStateCollapsed')) $parent.toggleClass('InputfieldStateCollapsed', 100);
				$parent.removeClass('InputfieldRepeaterDeletePending'); 
			} else {
				$checkbox.attr('checked', 'checked'); 
				$parent.children('label').removeClass('ui-state-default').addClass('ui-state-error');
				if(!$parent.is('.InputfieldStateCollapsed')) $parent.toggleClass('InputfieldStateCollapsed', 100);
				$parent.addClass('InputfieldRepeaterDeletePending'); 
			}
		}
		e.stopPropagation();
	}; 	

	var $delete = $("<span class='ui-icon ui-icon-trash InputfieldRepeaterTrash'>Delete</span>").css('display', 'block').click(deleteClick); 

	$("input.InputfieldRepeaterDelete").parents('.InputfieldCheckbox').hide();

	$(".InputfieldRepeater .InputfieldFieldset > .ui-widget-header").addClass('ui-state-default')
		.prepend("<span class='ui-icon ui-icon-arrowthick-2-n-s InputfieldRepeaterDrag'></span>")
		.prepend($delete.clone(true));

	$(".InputfieldRepeaterDrag").hover(function() {
		$(this).parent('label').addClass('ui-state-focus'); 
	}, function() {
		$(this).parent('label').removeClass('ui-state-focus'); 
	}); 

	$(".InputfieldRepeaterTrash").hover(function() {
		var $label = $(this).parent('label'); 
		if(!$label.parent().is('.InputfieldRepeaterDeletePending')) $label.addClass('ui-state-error'); 
	}, function() {
		var $label = $(this).parent('label'); 
		if(!$label.parent().is('.InputfieldRepeaterDeletePending')) $label.removeClass('ui-state-error'); 
	}); 


	$(".InputfieldRepeaterAddLink").click(function() {

		var $inputfields = $(this).parent('p').prev('ul.Inputfields'); 
		var $readyItem = $inputfields.children('.InputfieldRepeaterReady'); 

		if($readyItem.size() > 0) {
			$readyItem = $readyItem.slice(0,1); 
			$readyItem.hide();
			$readyItem.removeClass('InputfieldRepeaterReady'); 
			$readyItem.find('input.InputfieldRepeaterDisabled').remove(); // allow it to be saved
			$readyItem.find('input.InputfieldRepeaterPublish').attr('value', 1); // identify it as added
			$readyItem.slideDown('fast'); 
			$readyItem.children('.ui-widget-content').effect('highlight', {}, 1000); 
			return false;
		}

		var $newItem = $inputfields.children('.InputfieldRepeaterNewItem'); 	
		var $numAddInput = $(this).parent().children('input'); 
		var total = $newItem.size();

		if(total > 1) $newItem = $newItem.slice(0,1);
		var $addItem = $newItem.clone(true)
		var $label = $addItem.children('label');
		var labelHTML = $label.html();
		var num = labelHTML.substring(labelHTML.lastIndexOf('#')+1); 
		labelHTML = labelHTML.replace(/#[0-9]+/, '#' + ((num-1)+total)); 
		$label.html(labelHTML); 

		// make sure it has a unique ID
		var id = $addItem.attr('id') + '_';
		while($('#' + id).size() > 0) id += '_';
		$addItem.attr('id', id);

		$inputfields.append($addItem);
		$addItem.css('display', 'block');
		$addItem.find('.InputfieldRepeaterTrash').click(deleteClick); 

		$numAddInput.attr('value', total); 

		return false;
	});

	$('.InputfieldRepeater > .ui-widget-content > .Inputfields').sortable({
		items: '> li:not(.InputfieldRepeaterNewItem):not(.InputfieldRepeaterReady)',
		axis: 'y',
		handle: '.InputfieldRepeaterDrag', 
		start: function(e, ui) {
			ui.item.find('.ui-widget-header').addClass("ui-state-highlight");

			// TinyMCE instances don't like to be dragged, so we disable them temporarily
			ui.item.find('.InputfieldTinyMCE textarea').each(function() {
				tinyMCE.execCommand('mceRemoveControl', false, $(this).attr('id')); 
			}); 
		},
		stop: function(e, ui) {
			ui.item.find('.ui-widget-header').removeClass("ui-state-highlight"); 
			$(this).children().each(function(n) {
				$(this).find('.InputfieldRepeaterSort').slice(0,1).attr('value', n); 
			}); 

			// Re-enable the TinyMCE instances
			ui.item.find('.InputfieldTinyMCE textarea').each(function() {
				tinyMCE.execCommand('mceAddControl', false, $(this).attr('id')); 
			}); 
		}
	});

}); 

