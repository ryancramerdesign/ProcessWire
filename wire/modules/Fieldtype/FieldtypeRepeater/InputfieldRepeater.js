/**
 * ProcessWire Repeater Inputfield Javascript
 *
 * Maintains a collection of fields that are repeated for any number of times.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 *
 */

function InputfieldRepeaterDeleteClick(e) {
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
}

function InputfieldRepeaterInit($this) {
	var $inputfields = $this.find('.Inputfields:eq(0)');
	if(!$inputfields.length) return;
	if($inputfields.hasClass('InputfieldRepeaterInit')) return;
	$inputfields.addClass('InputfieldRepeaterInit');
	var $delete = $("<i class='fa fa-trash InputfieldRepeaterTrash'></i>").css('display', 'block')
		.click(InputfieldRepeaterDeleteClick);

	$("input.InputfieldRepeaterDelete", $this).parents('.InputfieldCheckbox').hide();

	$(".InputfieldFieldset > .InputfieldHeader", $this).addClass('ui-state-default')
		.prepend("<i class='fa fa-fw fa-sort InputfieldRepeaterDrag'></i>")
		.prepend($delete.clone(true));

	$(".InputfieldRepeaterDrag", $this).hover(function() {
		$(this).parent('label').addClass('ui-state-focus');
	}, function() {
		$(this).parent('label').removeClass('ui-state-focus');
	});

	$(".InputfieldRepeaterTrash", $this).hover(function() {
		var $label = $(this).parent('label');
		if(!$label.parent().is('.InputfieldRepeaterDeletePending')) $label.addClass('ui-state-error');
	}, function() {
		var $label = $(this).parent('label');
		if(!$label.parent().is('.InputfieldRepeaterDeletePending')) $label.removeClass('ui-state-error');
	});
	
	$inputfields.sortable({
		items: '> li:not(.InputfieldRepeaterNewItem):not(.InputfieldRepeaterReady)',
		axis: 'y',
		handle: '.InputfieldRepeaterDrag',
		start: function(e, ui) {
			ui.item.find('.InputfieldHeader').addClass("ui-state-highlight");

			// TinyMCE instances don't like to be dragged, so we disable them temporarily
			ui.item.find('.InputfieldTinyMCE textarea').each(function() {
				tinyMCE.execCommand('mceRemoveControl', false, $(this).attr('id'));
			});
		},
		stop: function(e, ui) {
			ui.item.find('.InputfieldHeader').removeClass("ui-state-highlight");
			$(this).children().each(function(n) {
				$(this).find('.InputfieldRepeaterSort').slice(0,1).attr('value', n);
			});

			// Re-enable the TinyMCE instances
			ui.item.find('.InputfieldTinyMCE textarea').each(function() {
				tinyMCE.execCommand('mceAddControl', false, $(this).attr('id'));
			});
		}
	});


	$(".InputfieldRepeaterAddLink", $this).click(function() {

		var $inputfields = $(this).parent('p').prev('ul.Inputfields');
		var $readyItem = $inputfields.children('.InputfieldRepeaterReady');

		if($readyItem.size() > 0) {
			$readyItem = $readyItem.slice(0,1);
			$readyItem.hide();
			$readyItem.removeClass('InputfieldRepeaterReady');
			$readyItem.find('input.InputfieldRepeaterDisabled').remove(); // allow it to be saved
			$readyItem.find('input.InputfieldRepeaterPublish').attr('value', 1); // identify it as added
			$readyItem.slideDown('fast', function() {
				$(window).resize(); // for inputfields.js to recognize
			});
			$readyItem.children('.InputfieldContent').effect('highlight', {}, 1000);
			return false;
		}

		var $newItem = $inputfields.children('.InputfieldRepeaterNewItem');
		var $numAddInput = $(this).parent().children('input');
		var total = $newItem.length;

		if(total > 1) $newItem = $newItem.slice(0,1);
		var $addItem = $newItem.clone(true)
		var $label = $addItem.children('label');
		var labelHTML = $label.html();
		var num = labelHTML.substring(labelHTML.lastIndexOf('#')+1);
		num = parseInt(num);
		labelHTML = labelHTML.replace(/#[0-9]+/, '#' + ((num-1)+total));
		$label.html(labelHTML);

		// make sure it has a unique ID
		var id = $addItem.attr('id') + '_';
		while($('#' + id).size() > 0) id += '_';
		$addItem.attr('id', id);

		$inputfields.append($addItem);
		$addItem.css('display', 'block');
		$addItem.find('.InputfieldRepeaterTrash').click(InputfieldRepeaterDeleteClick);

		$numAddInput.attr('value', total);

		return false;
	});

}

$(document).ready(function() {
	$(".InputfieldRepeater").each(function() {
		InputfieldRepeaterInit($(this));
	});
	$(document).on('reloaded', '.InputfieldRepeater', function() {
		InputfieldRepeaterInit($(this));
	});
}); 

