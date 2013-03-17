
/**
 * ProcessWire Image Plugin for TinyMCE
 *
 * Works with ProcessPageEditImage module to support insertion and manipulation of images in a TinyMCE field
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */


var tinymceSelection = null; 
var $iframe; 

(function() {
	tinymce.create('tinymce.plugins.PwImagePlugin', {
		init : function(ed, url) {
			this.editor = ed;

			// Register commands
			ed.addCommand('mcePwImage', function() {

				// Internal image object like a flash placeholder
				if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1)
					return;

				var page_id = parseInt($("#Inputfield_id").val()); 
				var file = '';
				var imgClass = '';
				var imgWidth = 0;
				var imgHeight = 0;
				var imgDescription = '';
				var imgLink = '';
				var se = ed.selection;
				var $node = null;
				var nodeParent = ed.dom.getParent(se.getNode(), 'A');
                                var $nodeParent = $(nodeParent); 

				if(!se.isCollapsed()) {

					$node = $(se.getNode());
					var src = $node.attr('src'); 
					if(src) { 
						var parts = src.split('/'); 
						file = parts.pop();
						imgClass = $node.attr('class'); 
						imgWidth = $node.attr('width');
						imgHeight = $node.attr('height'); 
						imgDescription = $node.attr('alt'); 
						imgLink = $nodeParent.is("a") ? $nodeParent.attr('href') : '';

						parts = parts.reverse();
						var _page_id = 0; 

						for(n = 0; n < parts.length; n++) {
							_page_id = parseInt(parts[n]); 
							if(_page_id > 0) break;
						}
						if(_page_id > 0 && _page_id != page_id) file = _page_id + ',' + file; // page_id,file for images in repeater support
					}
				}

				var modalUri = config.urls.admin + 'page/image/';
				var queryString = '?id=' + page_id + '&modal=1';

				var windowHeight = $(window).height() - 250; 
				var windowWidth = $(window).width() - 200; 

				if(file.length) queryString += "&file=" + file; 
				if(imgWidth) queryString += "&width=" + imgWidth; 
				if(imgHeight) queryString += "&height=" + imgHeight; 
				if(imgClass && imgClass.length) queryString += "&class=" + imgClass; 
				if(imgDescription && imgDescription.length) queryString += "&description=" + escape(imgDescription);
				if(imgLink && imgLink.length) queryString += "&link=" + escape(imgLink);
				queryString += "&winwidth=" + windowWidth; 

				$iframe = $('<iframe id="pwimage_iframe" width="100%" frameborder="0" src="' + modalUri + queryString + '"></iframe>'); 

				var selectImageLabel = '';

				$iframe.dialog({
					title: config.InputfieldTinyMCE.pwimage.selectLabel, // "Select Image", 
					height: windowHeight,
					width: windowWidth,
					position: [100, 80], 
					modal: true,
					overlay: {
						opacity: 0.7,
						background: "black"
					}
				}).width(windowWidth).height(windowHeight);

				$iframe.load(function() {

					var $i = $iframe.contents();

					if($i.find("#selected_image").size() > 0) {

						$iframe.dialog("option", "buttons", [
							{ 
								text: config.InputfieldTinyMCE.pwimage.insertBtn, // "Insert This Image",
								click:  function() {

									function insertImage(src) {

										var $i = $iframe.contents();
										var $img = $("#selected_image", $i); 
										//var src = $img.attr('src'); 
										var nosize = $img.attr('data-nosize'); 
										var width = $img.attr('width');
										var height = $img.attr('height'); 
										var alt = $("#selected_image_description", $i).val();
										var cls = $img.removeClass('ui-resizable').attr('class'); 
										var link = $("#selected_image_link:checked", $i).val();
										var html = '<img class="' + cls + '" src="' + src + '" mce_src="' + src + '" '; 

										if(alt && alt.length > 0) alt = $("<div />").text(alt).html().replace(/"/g, '&quot;'); 

										if(nosize != 1) {
											if(width > 0) html += 'width="' + width + '" '; 
											if(height > 0) html += 'height="' + height + '" '; 
										}
										html += 'alt="' + alt + '" />';
										if(link && link.length > 0) html = "<a href='" + link + "'>" + html + "</a>";
										if(nodeParent && $nodeParent.is("a")) se.select(nodeParent); // add it to the selection 
										//se.select(nodeParent); // add it to the selection 
										tinyMCE.execCommand('mceInsertContent', false, html);
										$iframe.dialog("close"); 
									}

									var $i = $iframe.contents();
									var $img = $("#selected_image", $i); 

									$iframe.dialog("disable").dialog("option", "title", config.InputfieldTinyMCE.pwimage.savingNote); // Saving Image
									$img.removeClass("resized"); 

									var cls = $img.attr('class'); 
									var width = $img.attr('width');
									if(!width) width = $img.width();
									var height = $img.attr('height'); 
									if(!height) height = $img.height();
									var file = $img.attr('data-idname'); 
									var page_id = $("#page_id", $i).val();
									if(file.indexOf('/') > -1) file = file.substring(file.lastIndexOf('/')+1); 

									$.get(modalUri + 'resize?id=' + page_id + '&file=' + file + '&width=' + width + '&height=' + height, function(data) {
										var $div = $("<div></div>").html(data); 
										var src = $div.find('#selected_image').attr('src');
										insertImage(src); 
									}); 

								}
							}, {

								text: config.InputfieldTinyMCE.pwimage.selectBtn, // "Select Another Image", 
								click: function() {
									var $i = $iframe.contents();
									var page_id = $("#page_id", $i).val();
									$iframe.attr('src', modalUri + '?id=' + page_id + '&modal=1'); 
									$iframe.dialog("option", "buttons", {}); 
								}
							}, {
								text: config.InputfieldTinyMCE.pwimage.cancelBtn, // "Cancel",
								click: function() { $iframe.dialog("close"); }
							}
						]).dialog("option", "title", config.InputfieldTinyMCE.pwimage.editLabel).width(windowWidth).height(windowHeight); // "Edit Image"


					} else {
						$iframe.dialog("option", "buttons", [
							{
								text: config.InputfieldTinyMCE.pwimage.cancelBtn, // "Cancel", 
								click: function() { $iframe.dialog("close"); }
							}
						]).width(windowWidth).height(windowHeight);
					}
				});
			});

			// Register buttons
			ed.addButton('image', {
				title : config.InputfieldTinyMCE.pwimage.selectLabel, // 'pwimage.image_desc',
				cmd : 'mcePwImage'
			});

		},

		getInfo : function() {
			return {
				longname : 'ProcessWire TinyMCE Image Select Plugin',
				author : 'Ryan Cramer',
				authorurl : 'http://www.ryancramer.com',
				infourl : 'http://www.processwire.com/',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('pwimage', tinymce.plugins.PwImagePlugin);
})();

