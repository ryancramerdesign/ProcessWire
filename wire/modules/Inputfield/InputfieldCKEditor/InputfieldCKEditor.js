/**
 * InputfieldCKEditor.js
 *
 * Initialization for CKEditor
 *
 */

/**
 * Add external plugins
 * 
 * These are located in:
 * 	/wire/modules/Inputfield/InputfieldCKEditor/plugins/[name]/plugin.js (core external plugins)
 * 	/site/modules/InputfieldCKEditor/plugins/[name]/plugin.js (site external plugins)
 * 
 */
for(var name in config.InputfieldCKEditor.plugins) {
	var file = config.InputfieldCKEditor.plugins[name];
	CKEDITOR.plugins.addExternal(name, file, '');
}

/**
 * A collection of inline editor instances 
 *
 * We keep this so that we can later pull the getData() method of each on form submit.
 *
 */ 
var inlineCKEditors = [];

/**
 * CKEditors hidden in jQuery UI tabs sometimes don't work so this initializes them when they become visible
 *
 */ 
function initCKEditorTab(event, ui) {
	var $t = ui.newTab; 
	var $a = $t.find('a'); 
	if($a.hasClass('InputfieldCKEditor_init')) return;
	var editorID = $a.attr('data-editorID');
	var cfgName = $a.attr('data-cfgName');
	CKEDITOR.replace(editorID, config[cfgName]);
	$a.addClass('InputfieldCKEditor_init'); 
	ui.oldTab.find('a').addClass('InputfieldCKEditor_init'); // in case it was the starting one
}

/**
 * Prepare inline editors
 *
 */ 
$(document).ready(function() {

	/**
	 * Regular editors
	 * 
	 */
	
	for(var editorID in config.InputfieldCKEditor.editors) {
		var cfgName = config.InputfieldCKEditor.editors[editorID];
		var $editor = $('#' + editorID);
		var $parent = $editor.parent();
		
		if($parent.hasClass('ui-tabs-panel') && $parent.css('display') == 'none') {
			// CKEditor in a jQuery UI tab (like langTabs)
			var parentID = $editor.parent().attr('id'); 
			var $a = $parent.closest('.ui-tabs, .langTabs').find('a[href=#' + parentID + ']');
			$a.attr('data-editorID', editorID).attr('data-cfgName', cfgName); 
			$parent.closest('.ui-tabs, .langTabs').on('tabsactivate', initCKEditorTab); 
		} else { 
			// visible CKEditor
			CKEDITOR.replace(editorID, config[cfgName]);
		}
	}

	/**
	 * Inline editors
	 * 
	 */

	var $inlines = $(".InputfieldCKEditorInline"); 
	var pageID = $("#Inputfield_id").val();

	CKEDITOR.disableAutoInline = true; 

	if($inlines.size() > 0) {

		$inlines.mouseover(function() {
			// we initialize the inline editor only when moused over
			// so that a page can handle lots of editors at once without
			// them all being active
			var $t = $(this);
			if($t.is(".InputfieldCKEditorLoaded")) return;
			$t.effect('highlight', {}, 500); 
			$t.attr('contenteditable', 'true'); 
			var configName = $t.attr('data-configName'); 
			var editor = CKEDITOR.inline($(this).attr('id'), config[configName]); 
			var n = inlineCKEditors.length; 
			inlineCKEditors[n] = editor; 
			$t.attr('data-n', n); 
			$t.addClass("InputfieldCKEditorLoaded"); 
		});

		$("form.InputfieldForm").submit(function() {
			$(this).find('.InputfieldCKEditorInline').each(function() {
				var $t = $(this);
				var value; 
				if($t.is('.InputfieldCKEditorLoaded')) {
					var n = parseInt($t.attr('data-n')); 
					var editor = inlineCKEditors[n];
					// getData() ensures there are no CKE specific remnants in the markup
					value = editor.getData();
				} else {
					value = $t.html();
				}
				var $input = $t.next('input'); 
				$input.attr('value', value); 
			}); 
		}); 	
	}
}); 
