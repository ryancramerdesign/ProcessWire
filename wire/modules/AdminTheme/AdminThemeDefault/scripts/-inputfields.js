/**
 * inputfields.js - JS specific to behavior of ProcessWire inputfields.
 * 
 * For other admin theme developers: you do not need to include this file in your admin theme
 * unless you want to (and it's better not to). Instead you should direct your admin theme to
 * load this exact file: $config->urls->root . 'wire/templates-admin/scripts/inputfields.js';
 *
 */

function consoleLog(note) {
	// uncomment the line below to enable debugging console
	console.log(note);
}

/**
 * Inputfield Depedendencies
 *
 */

function InputfieldDependencies() {

	$(".InputfieldStateShowIf, .InputfieldStateRequiredIf").each(function() {

		 // Wrapper of field that we are operating on (i.e. #wrap_Inputfield_[name])
		var $fieldToShow = $(this);
		
		//Name of the field contained by $fieldToShow
		var fieldNameToShow = $fieldToShow.attr('id').replace(/wrap_Inputfield_/, '');

		// Array of conditions required to show a field
		var conditions = [];

		/**
		 * Convert string value to integer or float when appropriate
		 * 
		 * @param str string
		 * @param str2 string Optional second value for context
		 * @return string|int|float
		 * 
		 */
		function parseValue(str, str2) {
			
			str = jQuery.trim(str);
			if(str.length > 0 && !jQuery.isNumeric(str)) {
				return str; 
			}
			
			if(str.length == 0) {
				// empty value: should it be a blank or a 0?
				var t = typeof str2;
				if(t != "undefined") {
					// str2 is present for context
					if(t == "integer") return 0;
					if(t == "float") return 0.0;
					return str;
				} else {
					// no context, assume blank
					return str; 	
				}
			}
			
			var dot1 = str.indexOf('.');
			var dot2 = str.lastIndexOf('.');
			
			if(dot1 == -1 && /^-?\d+$/.test(str)) {
				// no dot present, and all numbers so must be integer
				return parseInt(str);
			}
			
			if(dot2 > -1 && dot1 != dot2) {
				// more than one dot, can't be a float
				return str; 
			}
			
			if(/^-?[\d.]+$/.test(str)) {
				// looks to be a float
				return parseFloat(str);
			}
			
			return str;
		}

		/**
		 * Called when a targeted Inputfield has changed
		 *
		 */
		function inputfieldChange() {

			consoleLog('-------------------------------------------------------------------');
			consoleLog('Field "' + $fieldToShow.attr('id') + '" detected a change to a dependency field! Beginning dependency checks...');

			// number of changes that were actually made to field visibility
			var numVisibilityChanges = 0;

			// quantity of matched conditions
			var show = 0;

			for(var c = 0; c < conditions.length; c++) {

				// current condition we are checking in this iteration 
				var condition = conditions[c];

				consoleLog('----');
				consoleLog('Start Dependency ' + c);
				consoleLog('Condition type: ' + condition.type);
				consoleLog('Field: ' + condition.field);
				if(condition.subfield.length > 0) consoleLog('Subfield: ' + condition.subfield); 
				consoleLog('Operator: ' + condition.operator);
				consoleLog('Required value: ' + condition.value);

				// matched contains positive value when condition matches
				var matched = 0;

				// Dependency field that we are checking for the condition
				var $field = $("#Inputfield_" + condition.field);

				var value = null;

				if($field.size() == 0) {
					// if field isn't present by #id it may be present by #id+value as a checkbox/radio field is
					consoleLog('Detected checkbox or radio: ' + condition.field);
					if(condition.subfield == 'count' || condition.subfield == 'count-checkbox') {
						// count number of matching checked inputs
						$field = $("#wrap_Inputfield_" + condition.field + " :input"); 
						value = $("#wrap_Inputfield_" + condition.field + " :checked").size();
						consoleLog('Using count checkbox condition'); 
						condition.subfield = 'count-checkbox';
					} else {
						$field = $("#Inputfield_" + condition.field + "_" + condition.value);
					}
				}

				// value of the dependency field we are checking
				if(value === null) value = $field.val();

				// value will be placed in values so we can handle multiple value checks
				var values = [];

				// prefer blank to null for our chcks
				if(value == null) value = '';

				// special case for checkbox and radios: 
				// if the field is not checked then we assume a blank value
				var attrType = $field.attr('type');
				if((attrType == 'checkbox' || attrType == 'radio') && !$field.is(":checked")) value = '';

				// special case for 'count' subfield condition, 
				// where we take the value's length rather than the value
				if(condition.subfield == 'count') value = value.length;

				// if value is an object, make it in array
				// in either case, convert value to an array called values
				if(typeof value == 'object') {
					// object, convert to array
					values = jQuery.makeArray(value);
				} else if(typeof value == 'array') {
					// array, already
					values = value;
				} else {
					// string: single value array
					values[0] = value;
				}
			
				// also allow for matching a "0" as an unchecked value
				if((attrType == 'checkbox' || attrType == 'radio') && !$field.is(":checked")) values[1] = '0';
				
				// cycle through the values (most of the time, just 1 value).
				// increment variable 'show' each time a condition matches
				for(var n = 0; n < values.length; n++) {
					value = parseValue(values[n], condition.value);
					
					switch(condition.operator) {
						case '=': if(value == condition.value) matched++; break;
						case '!=': if(value != condition.value) matched++; break;
						case '>': if(value > condition.value) matched++; break;
						case '<': if(value < condition.value) matched++; break;
						case '>=': if(value >= condition.value) matched++; break;
						case '<=': if(value <= condition.value) matched++; break;
						case '*=':
						case '%=': if(value.indexOf(condition.value) > -1) matched++; break;
					}

					consoleLog('Value #' + n + ' - Current value: ' + value);
					consoleLog('Value #' + n + ' - Matched? ' + (matched > 0 ? 'YES' : 'NO'));
				}

				consoleLog('----');

				// if at least one value matched, then increment or 'show' value
				if(matched > 0) show++;
			}

			consoleLog('Summary (required/matched): ' + conditions.length + ' / ' + show);

			// determine whether to show or hide the field
			if(show > 0 && conditions.length == show) {
				// show it
				consoleLog('Determined that field "' + fieldNameToShow + '" should be visible.');
				if(condition.type == 'show') {
					if($fieldToShow.is('.InputfieldStateHidden')) {
						// field is hidden so show/fade in
						$fieldToShow.removeClass('InputfieldStateHidden').fadeIn();
						numVisibilityChanges++;
						consoleLog('Field is now visible.');
					} else {
						consoleLog('Field is already visible.');
					}

				} else if(condition.type == 'required') {
					$fieldToShow.addClass('InputfieldStateRequired').find(":input:visible[type!=hidden]").addClass('required'); // may need to focus a specific input?
				}
			} else {
				consoleLog('Determined that field "' + fieldNameToShow + '" should be hidden.');
				// hide it
				if(condition.type == 'show') {
					if(!$fieldToShow.is('.InputfieldStateHidden')) {
						$fieldToShow.addClass('InputfieldStateHidden').hide();
						consoleLog('Field is now hidden.');
						numVisibilityChanges++;
					} else {
						consoleLog('Field is already hidden.');
					}
				} else if(condition.type == 'required') {
					$fieldToShow.removeClass('InputfieldStateRequired').find(":input.required").removeClass('required');
				}
			}

			if(numVisibilityChanges > 0) {
				consoleLog(numVisibilityChanges + ' visibility changes were made.');
				InputfieldColumnWidths();
				$(window).resize(); // trigger for FormBuilder or similar
			}
		}; // END inputfieldChange()

		/***************************************************************************************************************
		 * Process an individual Inputfield.InputfieldShowStateIf and build a list of conditions for $fieldToShow
		 *
		 */

		var conditionTypes = ['show', 'required'];

		for(var t = 0; t < conditionTypes.length; t++) {

			var conditionType = conditionTypes[t];

			// find attribute data-show-if or data-required-if
			var selector = $(this).attr('data-' + conditionType + '-if');

			// if attribute wasn't present, skip...
			if(!selector || selector.length < 1) continue;

			// un-encode entities in the data attribute value (selector)
			selector = $("<div />").html(selector).text();

			consoleLog('-------------------------------------------------------------------');
			consoleLog('Analyzing "' + conditionType + '" selector: ' + selector);

			// separate each key=value component in the selector to parts array
			var parts = selector.match(/(^|,)([^,]+)/g);

			for(var n = 0; n < parts.length; n++) {

				var part = parts[n];

				// separate out the field, operator and value
				var match = part.match(/^[,\s]*([_.a-zA-Z0-9]+)(=|!=|<=|>=|<|>|%=)([^,]+),?$/);
				if(!match) continue;
				var field = match[1];
				var operator = match[2];
				var value = match[3];
				var subfield = '';

				// extract subfield, if there is one
				var dot = field.indexOf('.');
				if(dot > 0) {
					subfield = field.substring(dot+1);
					field = field.substring(0, dot);
				}

				consoleLog("Field: " + field);
				if(subfield.length) consoleLog("Subfield: " + subfield);
				consoleLog("Operator: " + operator);
				consoleLog("value: " + value);

				// determine if we need to trim off quotes
				var first = value.substring(0,1);
				var last = value.substring(value.length-1, value.length);
				if((first == '"' || first == "'") && first == last) value = value.substring(1, value.length-1);

				// build the condition
				var condition = {
					'type': conditionType,
					'field': field,
					'subfield': subfield,
					'operator': operator,
					'value': parseValue(value)
				};

				// append to conditions array
				conditions[conditions.length] = condition;

				// locate the dependency inputfield
				var $inputfield = $("#Inputfield_" + field);

				// if the dependency inputfield isn't found, locate its wrapper..
				if($inputfield.size() == 0) {
					// use any inputs within the wrapper
					$inputfield = $("#wrap_Inputfield_" + field).find(":input");
				}

				// attach change event to dependency inputfield
				$inputfield.change(inputfieldChange);

				// run the event for the first time to initalize the field
				inputfieldChange();
			}
		}
	});

}

/**
 * Adjust inputfield column widths to fill out each row
 *
 */

function InputfieldColumnWidths() {

	var colspacing = null; 

	/**
	 * Return the current with of $item based on its "style" attribute
	 *
	 */
	function getWidth($item) {
		if($item.is(".InputfieldStateHidden")) return 0;
		var style = $item.attr('style');
		var pct = parseInt(style.match(/width:\s*(\d+)/)[1]);
		// store the original width in another attribute, for later retrieval
		if(!$item.attr('data-original-width')) $item.attr('data-original-width', pct);
		// consoleLog('getWidth(' + $item.attr('id') + '): ' + pct + '%'); 
		return pct;
	}

	/**
	 * Retrieve the original width of $item
	 *
	 */
	function getOriginalWidth($item) {
		var w = parseInt($item.attr('data-original-width'));
		if(w == 0) w = getWidth($item);
		return w;
	}

	/**
	 * Set the width of $item to a given percent
	 *
	 * @param $item
	 * @param pct Percentage (10-100)
	 * @param animate Whether to animate the change (bool)
	 *
	 */
	function setWidth($item, pct, animate) {

		$item.width(pct + "%");

		if(animate) {
			$item.css('opacity', 0.5);
			$item.animate( { opacity: 1.0 }, 150, function() { });
		}

		consoleLog('InputfieldColumnWidths.setWidth(' + $item.attr('id') + ': ' + pct + '%');
	}

	function setHeight($item, maxColHeight) {
		var h = $item.height();
		if(h == maxColHeight) return;
		if($item.hasClass('InputfieldStateCollapsed')) return;
		var pad = maxColHeight-h; 
		if(pad < 0) pad = 0;
		var $container = $item.children('.ui-widget-content'); 
		if(pad == 0) {
			// do nothing, already the right height
		} else {
			consoleLog('Adjusting ' + $item.attr('id') + ' from ' + h + ' to ' + maxColHeight); 
			var $spacer = $("<div class='maxColHeightSpacer'></div>");
			$container.append($spacer); 
			$spacer.height(pad); 
		}
	}

	// for columns that don't have specific widths defined, add the InputfieldColumnWidthFirst
	// class to them which more easily enables us to exclude them from our operations below
	$(".Inputfield:not(.InputfieldColumnWidth)").addClass(".InputfieldColumnWidthFirst");

	// cycle through all first columns in a multi-column row
	$(".InputfieldColumnWidthFirst.InputfieldColumnWidth").each(function() {

		if(colspacing === null) {
			colspacing = $(this).parents('form').attr('data-colspacing'); 
			if(typeof colspacing == 'undefined') colspacing = 1; 
		}

		var $firstItem = $(this);

		// find all columns in this row that aren't hidden
		// note that $items excludes $firstItem
		var $items = $firstItem.nextUntil('.InputfieldColumnWidthFirst', '.InputfieldColumnWidth:not(.InputfieldStateHidden)');

		// initalize rowWidth with the width of the first item
		var rowWidth = $firstItem.is(".InputfieldStateHidden") ? 0 : getWidth($firstItem);

		var $item = $firstItem.is(".InputfieldStateHidden") ? null : $firstItem;
		var itemWidth = $item == null ? 0 : rowWidth;
		var numItems = $items.size();

		if($firstItem.is(".InputfieldStateHidden")) {
			numItems--;
			// item that leads the list, even though it may not be the first (first could be hidden)
			var $leadItem = $items.eq(0);
		} else {
			// lead item is first item
			var $leadItem = $firstItem; 
		}

		// remove any spacers already present for adjusting height
		$leadItem.find(".maxColHeightSpacer").remove();
		$items.find(".maxColHeightSpacer").remove();

		// subtract the quantity of items from the maxRowWidth since each item has a 1% margin
		var maxRowWidth = 100 - (numItems * colspacing);

		// keep track of the max column height
		var maxColHeight = $leadItem.height(); 

		// if our temporary class is in any of the items, remove it
		$items.removeClass("InputfieldColumnWidthFirstTmp");

		// determine the total row width
		// note that rowWidth is already initalized with the $firstItem width
		$items.each(function() {
			$item = $(this);
			itemWidth = getWidth($item);
			rowWidth += itemWidth;
			var h = $item.height();
			if(h > maxColHeight) maxColHeight = h; 
		});

		// ensure that all columns in the same row share the same height
		if(maxColHeight > 0) {
			setHeight($leadItem, maxColHeight); 
			$items.each(function() { setHeight($(this), maxColHeight); }); 
		}

		// if the current rowWidth is less than the full width, expand the last item as needed to fill the row
		if(rowWidth < maxRowWidth) {
			consoleLog("Expand width of row because rowWidth < maxRowWidth (" + rowWidth + " < " + maxRowWidth + ')');
			var leftoverWidth = (maxRowWidth - rowWidth);
			consoleLog('leftoverWidth: ' + leftoverWidth);
			itemWidth = itemWidth + leftoverWidth;
			if($item == null && !$firstItem.is(".InputfieldStateHidden")) $item = $firstItem;
			if($item) {
				var originalWidth = getOriginalWidth($item);
				// if the determined width is still less than the original width, then use the original width instead
				if(originalWidth > 0 && itemWidth < originalWidth) itemWidth = originalWidth;
				setWidth($item, itemWidth, true);
			}

		} else if(rowWidth > maxRowWidth) {
			// reduce width of row
			consoleLog("Reduce width of row because rowWidth > maxRowWidth (" + rowWidth + " > " + maxRowWidth + ')');
			if(!$firstItem.is(".InputfieldStateHidden")) $items = $firstItem.add($items); // $items.add($firstItem);
			rowWidth = 0;
			$items.each(function() {
				// restore items in row to original width
				$item = $(this);
				itemWidth = getOriginalWidth($item);
				if(itemWidth > 0) setWidth($item, itemWidth, false);
				rowWidth += itemWidth;
			});
			// reduce width of last item as needed
			var leftoverWidth = maxRowWidth - rowWidth;
			itemWidth += leftoverWidth;
			var originalWidth = getOriginalWidth($item);
			if(originalWidth > 0 && itemWidth < originalWidth) itemWidth = originalWidth;
			setWidth($item, itemWidth, false);
		}

		if($firstItem.is(".InputfieldStateHidden")) {
			// If the first item is not part of the row, setup a temporary class to let the 
			// $leadItem behave in the same way as the first item
			$leadItem.addClass("InputfieldColumnWidthFirstTmp");
		}

	});
}

/**
 * Setup the toggles for Inputfields and the animations that occur between opening and closing
 * 
 */
function InputfieldStates() {
	$(".Inputfields > .Inputfield > .ui-widget-header").addClass("InputfieldStateToggle")
		.prepend("<span class='ui-icon ui-icon-triangle-1-s'></span>")
		.click(function() {
			var $li = $(this).parent('.Inputfield');
			$li.toggleClass('InputfieldStateCollapsed', 100);
			$(this).children('span.ui-icon').toggleClass('ui-icon-triangle-1-e ui-icon-triangle-1-s');

			if($.effects && $.effects['highlight']) $li.children('.ui-widget-header').effect('highlight', {}, 300);
			setTimeout('InputfieldColumnWidths()', 500); 
			return false;
		})

	// use different icon for open and closed
	$(".Inputfields .InputfieldStateCollapsed > .ui-widget-header span.ui-icon")
		.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');

}

/*********************************************************************************************/

var InputfieldWindowResizeQueued = false;
function InputfieldWindowResizeActions() {
	consoleLog('InputfieldWindowResizeActions()'); 
	InputfieldColumnWidths(); 
	InputfieldWindowResizeQueued = false;
}

$(document).ready(function() {

	InputfieldStates();
	InputfieldDependencies();
	InputfieldColumnWidths();

	$(window).resize(function() {
		if(InputfieldWindowResizeQueued) return;
		InputfieldWindowResizeQueued = true; 
		setTimeout('InputfieldWindowResizeActions()', 2000); 	
	}); 

}); 
