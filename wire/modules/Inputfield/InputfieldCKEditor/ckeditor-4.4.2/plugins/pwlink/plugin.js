(function() {

	CKEDITOR.plugins.add('pwlink', {
		
		requires: 'dialog,fakeobjects',
		
		init: function(editor) {
			
			var allowed = 'a[!href,target,name,title,rel]';
			var required = 'a[href]';

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
					icon: this.path + 'images/pwlink.png'
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
	}); 

	function loadIframeLinkPicker(editor) {

		var href = '';
		var target = '';
		var innerHTML = '';
		var pageID = $("#Inputfield_id").val();

		// language support
		var langID = '';
		var $textarea = $('#' + editor.name); // get textarea of this instance
		var $langWrapper = $textarea.closest('.LanguageSupport');
		if($langWrapper.length) langID = "&lang=" + $langWrapper.data("language");

		var modalUrl = config.urls.admin + 'page/link/?id=' + pageID + '&modal=1' + langID; 
		var $iframe = $('<iframe id="pwlink_iframe" frameborder="0" src="' + modalUrl + '"></iframe>');
		var selection = editor.getSelection(true);
		var selectionElement = selection.getSelectedElement();
		var node = selection.getStartElement();
		var selectionText = selection.getSelectedText();

		if(node.getName() == 'a') {
			href = node.getAttribute('href'); 
			target = node.getAttribute('target'); 
			selection.selectElement(node); 
			selectionText = node.getHtml();

		} else if(node.getName() == 'img') {
			var $img = $(node.$);
			href = $img.parent("a").attr("href");
			selectionText = node.$.outerHTML;

		} else if (selectionText.length < 1) {
			// If not on top of link and there is no text selected - just return (don't load iframe at all)
			return;
		}

		$iframe.load(function() {
			var $i = $iframe.contents();
			$i.find("#link_page_url").val(href);
			$i.find("#ProcessPageEditLinkForm").data('iframe', $iframe);
			if(target && target.length) $i.find("#link_target").attr('checked', 'checked');
		});

		var windowWidth = $(window).width()-300;
		var windowHeight = $(window).height()-350;
		if(windowHeight > 800) windowHeight = 800;

		var insertLinkLabel = config.InputfieldCKEditor.pwlink.label;
		var cancelLabel = config.InputfieldCKEditor.pwlink.cancel;

		$iframe.dialog({
			title: insertLinkLabel,
			height: windowHeight,
			width: windowWidth,
			position: [150,80],
			modal: true,
			overlay: {
				opacity: 0.7,
				background: "black"
			},
			buttons: [ {
					text: insertLinkLabel,
					click: function() {

						var $i = $iframe.contents();
						var url = $("#link_page_url", $i).val();
						var target = $("#link_target", $i).is(":checked") ? "_blank" : '';

						if(target && target.length > 0) target = ' target="' + target + '"';
						if(url.length) {
							var html = '<a href="' + url + '"' + target + '>' + selectionText + '</a>';
							editor.insertHtml(html); 
						}
						$iframe.dialog("close");
					}
				}, {
					text: cancelLabel,
					click: function() { $iframe.dialog("close"); }
				} 
			]
		}).width(windowWidth).height(windowHeight);
	}
	
})();
