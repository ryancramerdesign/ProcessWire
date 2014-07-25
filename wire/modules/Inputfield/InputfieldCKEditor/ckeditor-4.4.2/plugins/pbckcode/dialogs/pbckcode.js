CKEDITOR.dialog.add('pbckcodeDialog', function (editor) {
    "use strict";

    // if there is no user settings
    // create an empty object
    if (editor.config.pbckcode === undefined) {
        editor.config.pbckcode = {};
    }

    // default settings object
    var DEFAULT_SETTINGS = {
        cls      : '',
        modes    : [
			['HTML', 'html'],
			['CSS', 'css'],
			['PHP', 'php'],
			['JS', 'javascript']
        ],
        theme    : 'textmate',
        tab_size : 4
    };

    var tab_sizes = ["1", "2", "4", "8"];

    // merge user settings with default settings
    var settings = CKEDITOR.tools.extend(DEFAULT_SETTINGS, editor.config.pbckcode, true);

	// CKEditor variables
	var dialog;
    var shighlighter = new PBSyntaxHighlighter(settings.highlighter);

    // ACE variables
    var aceEditor, aceSession, whitespace;

    // EDITOR panel
    var editorPanel = {
        id       : 'editor',
        label    : editor.lang.pbckcode.editor,
        elements : [
            {
                type     : 'hbox',
                children : [
                    {
                        type      : 'select',
                        id        : 'code-select',
                        className : 'cke_pbckcode_form',
                        label     : editor.lang.pbckcode.mode,
                        items     : settings.modes,
                        'default' : settings.modes[0][1],
                        setup     : function (element) {
                            if (element) {
                                element = element.getAscendant('pre', true);
                                this.setValue(element.getAttribute("data-pbcklang"));
                            }
                        },
                        commit    : function (element) {
                            if (element) {
                                element = element.getAscendant('pre', true);
                                element.setAttribute("data-pbcklang", this.getValue());
                            }
                        },
                        onChange  : function (element) {
                            aceSession.setMode("ace/mode/" + this.getValue());
                        }
                    },
                    {
                        type      : 'select',
                        id        : 'code-tabsize-select',
                        className : 'cke_pbckcode_form',
                        label     : 'Tab size',
                        items     : tab_sizes,
                        'default' : tab_sizes[2],
                        setup     : function (element) {
                            if (element) {
                                element = element.getAscendant('pre', true);
                                this.setValue(element.getAttribute("data-pbcktabsize"));
                            }
                        },
                        commit    : function (element) {
                            if (element) {
                                element = element.getAscendant('pre', true);
                                element.setAttribute("data-pbcktabsize", this.getValue());
                            }
                        },
                        onChange  : function (element) {
                            if (element) {
                                whitespace.convertIndentation(aceSession, " ", this.getValue());
                                aceSession.setTabSize(this.getValue());
                            }
                        }
                    }
                ]
            },
            {
                type   : 'html',
                html   : '<div></div>',
                id     : 'code-textarea',
                className : 'cke_pbckcode_ace',
                style  : 'position: absolute; top: 80px; left: 10px; right: 10px; bottom: 50px;',
                setup  : function (element) {
                    // get the value of the editor
                    var code = element.getHtml();

                    // replace some regexp
                    code = code.replace(new RegExp('<br/>', 'g'), '\n');
                    code = code.replace(new RegExp('<br>', 'g'), '\n');
                    code = code.replace(new RegExp('&lt;', 'g'), '<');
                    code = code.replace(new RegExp('&gt;', 'g'), '>');
                    code = code.replace(new RegExp('&amp;', 'g'), '&');

                    aceEditor.setValue(code);
                },
                commit : function (element) {
                    element.setText(aceEditor.getValue());
                }
            }
        ]
    };

    // dialog code
    return {
        // Basic properties of the dialog window: title, minimum size.
        title     : editor.lang.pbckcode.title,
        minWidth  : 600,
        minHeight : 400,
        // Dialog window contents definition.
        contents  : [
            editorPanel
        ],
        onLoad    : function () {
			dialog = this;
            // we load the ACE plugin to our div
            aceEditor = ace.edit(dialog.getContentElement('editor', 'code-textarea')
            	.getElement().getId());
            // save the aceEditor into the editor object for the resize event
            editor.aceEditor = aceEditor;

            // set default settings
            aceEditor.setTheme("ace/theme/" + settings.theme);
			aceEditor.setHighlightActiveLine(true);

            aceSession = aceEditor.getSession();
			aceSession.setMode("ace/mode/" + settings.modes[0][1]);
            aceSession.setTabSize(settings.tab_size);
			aceSession.setUseSoftTabs(true);

            // load ace extensions
            whitespace = ace.require('ace/ext/whitespace');
        },
        onShow    : function () {
            // get the selection
            var selection = editor.getSelection();
            // get the entire element
            var element = selection.getStartElement();

            // looking for the pre parent tag
            if (element) {
                element = element.getAscendant('pre', true);
            }
            // if there is no pre tag, it is an addition. Therefore, it is an edition
            if (!element || element.getName() !== 'pre') {
                element = new CKEDITOR.dom.element('pre');

                if (shighlighter.getTag() !== 'pre') {
                    element.append(new CKEDITOR.dom.element('code'));
                }
                this.insertMode = true;
            }
            else {
                if (shighlighter.getTag() !== 'pre') {
                    element = element.getChild(0);
                }
                this.insertMode = false;
            }
            // get the element to fill the inputs
            this.element = element;

            // we empty the editor
            aceEditor.setValue('');

            // we fill the inputs
            if (!this.insertMode) {
                this.setupContent(this.element);
            }
        },
        // This method is invoked once a user clicks the OK button, confirming the dialog.
        onOk      : function () {
            var pre, element;
            pre = element = this.element;

            if (this.insertMode) {
                if (shighlighter.getTag() !== 'pre') {
                    element = this.element.getChild(0);
                }
            }
            else {
                pre = element.getAscendant('pre', true);
            }

            this.commitContent(element);

            // set the full class to the code tag
            shighlighter.setCls(pre.getAttribute("data-pbcklang") + " " + settings.cls);

            element.setAttribute('class', shighlighter.getCls());

            // we add a new code tag into ckeditor editor
            if (this.insertMode) {
                editor.insertElement(pre);
            }
        }
    };
});

/*
 * Resize the ACE Editor
 */
CKEDITOR.dialog.on('resize', function (evt) {
    var AceEditor = evt.editor.aceEditor;
    if (AceEditor !== undefined) {
        AceEditor.resize();
    }
});

