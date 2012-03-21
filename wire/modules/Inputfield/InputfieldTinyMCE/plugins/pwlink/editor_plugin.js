/**
 */

var tinymceSelection = null; 
var editorCursorPosition; // for IE8

(function() {
	tinymce.create('tinymce.plugins.PwLinkPlugin', {
		init : function(ed, url) {
			this.editor = ed;

			// Register commands
			ed.addCommand('mcePwLink', function() {

				var se = ed.selection;
				var target = '';
				var href = '';
				var $node = $(se.getNode()); 
				var nodeParent = ed.dom.getParent(se.getNode(), 'A'); 
				var $nodeParent = $(nodeParent); 
				

				// No selection and not in link
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A')) return;
				if($nodeParent.is("a")) se.select(nodeParent); 

				tinymceSelection = se; 	

				// store selection IE fix
				editorCursorPosition = ed.selection.getBookmark(false);

				if($node.attr('href')) {
					target = $node.attr('target'); 
					href = $node.attr('href'); 
				} else if($nodeParent.attr('href')) {
					target = $nodeParent.attr('target');
					href = $nodeParent.attr('href');
				}

				var page_id = $("#Inputfield_id").val(); 
				var modalUri = config.urls.admin + 'page/link/?id=' + page_id + '&modal=1';
				var $iframe = $('<iframe id="pwlink_iframe" frameborder="0" src="' + modalUri + '"></iframe>'); 

				$iframe.load(function() {
					var $i = $iframe.contents();
					$i.find("#link_page_url").val(href); 
					$i.find("#ProcessPageEditLinkForm").data('iframe', $iframe); 
					if(target && target.length) $i.find("#link_target").attr('checked', 'checked'); 
				});

				var windowWidth = $(window).width() -300; 
				var windowHeight = $(window).height()-300; 
				if(windowHeight > 800) windowHeight = 800; 

				var insertLinkLabel = config.InputfieldTinyMCE.pwlink.label; 
				var cancelLabel = config.InputfieldTinyMCE.pwlink.cancel;

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
					buttons: [
						{ 
							text: insertLinkLabel,
							click: function() {

								var $i = $iframe.contents();

								// restore selection IE fix
								ed.selection.moveToBookmark(editorCursorPosition);

								var selection = tinymceSelection;
								var url = $("#link_page_url", $i).val();
								var target = $("#link_target", $i).is(":checked") ? "_blank" : ''; 
								var anchorText = '';
								var html = '';
								var $node = $(selection.getNode());

								if($node.is("a")) {
									anchorText = $node.html();

								} else if($nodeParent.is("a")) {
									anchorText = $nodeParent.html();

								} else {
									anchorText = selection.getContent();
								}

								if(target.length > 0) target = ' target="' + target + '"';
								if(url.length) { 
									html = '<a href="' + url + '"' + target + '>' + anchorText + '</a>';
									tinyMCE.execCommand('mceInsertContent', false, html);
								}
								$iframe.dialog("close"); 

							}
						}, {
							text: cancelLabel, 
							click: function() { $iframe.dialog("close"); }
						}
					]
				}).width(windowWidth).height(windowHeight); 

			});

			// Register buttons
			ed.addButton('link', {
				title : 'Insert Link',
				cmd : 'mcePwLink'
			});

			//ed.addShortcut('ctrl+k', 'pwlink.pwlink_desc', 'mcePwLink');

			ed.onNodeChange.add(function(ed, cm, n, co) {
				//cm.setDisabled('link', (co && n.nodeName != 'A') || ed.selection.isCollapsed());
				//cm.setActive('link', n.nodeName == 'A' && !n.name && !ed.selection.isCollapsed());
				cm.setDisabled('link', true); 
				cm.setActive('link', false); 
			});
		},

		getInfo : function() {
			return {
				longname : 'ProcessWire TinyMCE Link Plugin',
				author : 'Ryan Cramer',
				authorurl : 'http://www.ryancramer.com',
				infourl : 'http://www.processwire.com/',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('pwlink', tinymce.plugins.PwLinkPlugin);
})();

