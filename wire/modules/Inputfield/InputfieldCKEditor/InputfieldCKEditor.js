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
 * Prepare inline editors
 *
 */ 
$(document).ready(function() {

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
