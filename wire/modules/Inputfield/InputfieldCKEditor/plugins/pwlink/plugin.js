(function() {

	CKEDITOR.plugins.add('pwlink', {
		
		requires: 'dialog,fakeobjects',
		
		init: function(editor) {
			
			var allowed = 'a[!href,target,name,title,rel]';
			var required = 'a[href]';
			
			var classOptions = config.InputfieldCKEditor.pwlink.classOptions;
			if(classOptions.length) allowed += "(" + classOptions + ")";

			/*
			if ( CKEDITOR.dialog.isTabEnabled( editor, 'link', 'advanced' ) )
				allowed = allowed.replace( ']', ',accesskey,charset,dir,id,lang,name,rel,tabindex,title,type]{*}(*)' );

			if ( CKEDITOR.dialog.isTabEnabled( editor, 'link', 'target' ) )
				allowed = allowed.replace( ']', ',target,onclick]' );
			*/

			// Add the link and unlink buttons.
			editor.addCommand('pwlink', {
				allowedContent: allowed,
				requiredContent: required,
				exec: loadIframeLinkPicker
				}); 

			editor.addCommand('anchor', new CKEDITOR.dialogCommand( 'anchor', {
				allowedContent: 'a[!name,id]',
				requiredContent: 'a[name]'
				}));

			editor.addCommand('unlink', new CKEDITOR.unlinkCommand());
			editor.addCommand('removeAnchor', new CKEDITOR.removeAnchorCommand());

			editor.setKeystroke( CKEDITOR.CTRL + 76 /*L*/, 'pwlink' );
			
			if ( editor.ui.addButton ) {
				editor.ui.addButton( 'PWLink', {
					label: editor.lang.link.toolbar,
					command: 'pwlink',
					toolbar: 'links,10',
					hidpi: true,
					icon: (CKEDITOR.env.hidpi ? this.path + 'images/hidpi/pwlink.png' : this.path + 'images/pwlink.png')
				});
				editor.ui.addButton( 'Unlink', {
					label: editor.lang.link.unlink,
					command: 'unlink',
					toolbar: 'links,20'
				});
				editor.ui.addButton( 'Anchor', {
					label: editor.lang.link.anchor.toolbar,
					command: 'anchor',
					toolbar: 'links,30'
				});
			}
		}
	}); // ckeditor.plugins.add

	function loadIframeLinkPicker(editor) {

		var pageID = $("#Inputfield_id").val();

		// language support
		var $textarea = $('#' + editor.name); // get textarea of this instance
		var selection = editor.getSelection(true);
		var node = selection.getStartElement();
		var nodeName = node.getName(); // will typically be 'a', 'img' or 'p' 
		var selectionText = selection.getSelectedText();
		var $existingLink = null;

		if(nodeName == 'a') {
			// existing link
			$existingLink = $(node.$);
			selectionText = node.getHtml();
			selection.selectElement(node); 

		} else if(nodeName == 'img') {
			// linked image
			var $img = $(node.$);
			$existingLink = $img.parent('a'); 
			selectionText = node.$.outerHTML;

		} else if (selectionText.length < 1) {
			// If not on top of link and there is no text selected - just return (don't load iframe at all)
			return;
		} else {
			// new link
		}
	
		// build the modal URL
		var modalUrl = config.urls.admin + 'page/link/?id=' + pageID + '&modal=1';
		var $langWrapper = $textarea.closest('.LanguageSupport');
		if($langWrapper.length) modalUrl += "&lang=" + $langWrapper.data("language");
		
		if($existingLink != null) {
			var attrs = ['href', 'title', 'class', 'rel', 'target']; 
			for(var n = 0; n < attrs.length; n++) {
				var val = $existingLink.attr(attrs[n]); 	
				if(val && val.length) modalUrl += "&" + attrs[n] + "=" + encodeURIComponent(val);
			} 
		}
	
		// labels
		var insertLinkLabel = config.InputfieldCKEditor.pwlink.label;
		var cancelLabel = config.InputfieldCKEditor.pwlink.cancel;
		var $iframe; // set after modalSettings down

		// action when insert link button is clicked
		function clickInsert() {

			var $i = $iframe.contents();
			var $a = $($("#link_markup", $i).text());
			if($a.attr('href') && $a.attr('href').length) {
				$a.html(selectionText);
				var html = $("<div />").append($a).html();
				editor.insertHtml(html);
			}
		
			$iframe.dialog("close");
		}
	
		// settings for modal window
		var modalSettings = {
			title: "<i class='fa fa-link'></i> " + insertLinkLabel,
			buttons: [ {
				class: "pw_link_submit_insert", 
				html: "<i class='fa fa-link'></i> " + insertLinkLabel,
				click: clickInsert
			}, {
				html: "<i class='fa fa-times-circle'></i> " + cancelLabel,
				click: function() { $iframe.dialog("close"); },
				class: 'ui-priority-secondary'
				}
			]
		};
	
		// create modal window
		var $iframe = pwModalWindow(modalUrl, modalSettings, 'medium'); 
	
		// modal window load event
		$iframe.load(function() {
			
			var $i = $iframe.contents();
			$i.find("#ProcessPageEditLinkForm").data('iframe', $iframe);
		
			// capture enter key in main URL text input
			$("#link_page_url", $i).keydown(function(event) {
				var $this = $(this);
				var val = $.trim($this.val());
				if (event.keyCode == 13) {
					event.preventDefault();
					if(val.length > 0) clickInsert();
					return false;
				}
			});

		}); // load

	} // function loadIframeLinkPicker(editor) {
	
})();
