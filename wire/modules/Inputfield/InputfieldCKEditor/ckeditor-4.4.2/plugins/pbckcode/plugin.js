
// needed js files
var js = {
    ace                : "//cdn.jsdelivr.net/ace/1.1.3/noconflict/ace.js",
    aceExtWhitespace   : "//cdn.jsdelivr.net/ace/1.1.3/noconflict/ext-whitespace.js",
    pbSyntaxHighlighter: CKEDITOR.plugins.getPath('pbckcode') + "dialogs/PBSyntaxHighlighter.js"
};

var commandName = 'pbckcode';

/**
 * Plugin definition
 */
CKEDITOR.plugins.add('pbckcode', {
    icons      : 'pbckcode',
    lang       : ['fr', 'en'],
    init       : function (editor) {
        var plugin = this;

        // load CSS for the dialog
        editor.on('instanceReady', function () {
            CKEDITOR.document.appendStyleSheet(plugin.path + "dialogs/style.css");
        });

        // add the button in the toolbar
        editor.ui.addButton('pbckcode', {
            label   : editor.lang.pbckcode.addCode,
            command : commandName,
            toolbar : 'pbckcode'
        });

        // link the button to the command
		editor.addCommand(commandName, new CKEDITOR.dialogCommand('pbckcodeDialog', {
				allowedContent: 'pre[*]{*}(*)'
			})
		);

		// disable the button while the required js files are not loaded
	    editor.getCommand(commandName).disable();

        // add the plugin dialog element to the plugin
		CKEDITOR.dialog.add('pbckcodeDialog', plugin.path + 'dialogs/pbckcode.js');

        // add the context menu
        if (editor.contextMenu) {
            editor.addMenuGroup('pbckcodeGroup');
            editor.addMenuItem('pbckcodeItem', {
                label   : editor.lang.pbckcode.editCode,
                icon    : plugin.path + "icons/pbckcode.png",
                command : commandName,
                group   : 'pbckcodeGroup'
            });

            editor.contextMenu.addListener(function (element) {
                if (element.getAscendant('pre', true)) {
                    return { pbckcodeItem : CKEDITOR.TRISTATE_OFF };
                }
            });
        }

        // Load the required js files
        // enable the button when loaded
        CKEDITOR.scriptLoader.load([js.ace, js.pbSyntaxHighlighter], function() {
            editor.getCommand(commandName).enable();

            CKEDITOR.scriptLoader.load([js.aceExtWhitespace]);
        });
    }
});