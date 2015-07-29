
$(document).ready(function() {

	/**
	 * Setup a live change event for the delete links
	 *
	 */
	
	// not IE < 9
	$(document).on('change', '.InputfieldFileDelete input', function() {
		setInputfieldFileStatus($(this));

	}).on('dblclick', '.InputfieldFileDelete', function() {
		// enable double-click to delete all
		var $input = $(this).find('input'); 
		var $items = $(this).parents('.InputfieldFileList').find('.InputfieldFileDelete input');
		if($input.is(":checked")) $items.removeAttr('checked').change();
			else $items.attr('checked', 'checked').change();
		return false; 
	}); 

	function setInputfieldFileStatus($t) {
		if($t.is(":checked")) {
			// not an error, but we want to highlight it in the same manner
			$t.parents(".InputfieldFileInfo").addClass("ui-state-error")
				.siblings(".InputfieldFileData").slideUp("fast");

		} else {
			$t.parents(".InputfieldFileInfo").removeClass("ui-state-error")
				.siblings(".InputfieldFileData").slideDown("fast");
		}	
	}

	/**
	 * Make the lists sortable and hoverable
	 *
	 */
	function initSortable($fileLists) { 

		$fileLists.each(function() {
			
			var $this = $(this);
			var qty = $this.children("li").size();
			
			var $inputfield = $this.closest('.Inputfield')
		
			if(qty < 2) {
				// added to support additional controls when multiple items are present 
				// and to hide them when not present
				if(qty == 0) $inputfield.addClass('InputfieldFileEmpty').removeClass('InputfieldFileMultiple InputfieldFileSingle');
					else $inputfield.addClass('InputfieldFileSingle').removeClass('InputfieldFileEmpty InputfieldFileMultiple');
				// if we're dealing with a single item list, then don't continue with making it sortable
				return;
			} else {
				$this.closest('.Inputfield').removeClass('InputfieldFileSingle InputfieldFileEmpty').addClass('InputfieldFileMultiple');
			}

			$this.sortable({
				//axis: 'y', 
				start: function(e, ui) {
					ui.item.children(".InputfieldFileInfo").addClass("ui-state-highlight"); 
				}, 
				stop: function(e, ui) {
					$(this).children("li").each(function(n) {
						$(this).find(".InputfieldFileSort").val(n); 
					}); 
					ui.item.children(".InputfieldFileInfo").removeClass("ui-state-highlight"); 
					// Firefox has a habit of opening a lightbox popup after a lightbox trigger was used as a sort handle
					// so we keep a 500ms class here to keep a handle on what was a lightbox trigger and what was a sort
					$inputfield.addClass('InputfieldFileJustSorted InputfieldStateChanged'); 
					setTimeout(function() { $inputfield.removeClass('InputfieldFileJustSorted'); }, 500); 
				}
			});

		}).find(".ui-widget-header, .ui-state-default").hover(function() {
			$(this).addClass('ui-state-hover'); 
		}, function() {
			$(this).removeClass('ui-state-hover'); 
		});
	}

	/**
	 * Initialize non-HTML5 uploads
	 *
	 */
	function InitOldSchool() {
		// $(".InputfieldFileUpload input[type=file]").live('change', function() {
		$(document).on('change', '.InputfieldFileUpload input[type=file]', function() {
			var $t = $(this); 
			if($t.next("input.InputfieldFile").size() > 0) return; // not the last one
			var maxFiles = parseInt($t.siblings('.InputfieldFileMaxFiles').val()); 
			var numFiles = $t.parent('.InputfieldFileUpload').siblings('.InputfieldFileList').children('li').size() + $t.siblings('input[type=file]').size() + 1; 
			if(maxFiles > 0 && numFiles >= maxFiles) return; 
	
			// if there are any empty inputs, then don't add another
			var numEmpty = 0;
			$t.siblings('input[type=file]').each(function() { if($(this).val().length < 1) numEmpty++; });
			if(numEmpty > 0) return;
	
			// add another input
			var $i = $t.clone().hide().val(''); 
			$t.after($i); 	
			$i.slideDown(); 
		});
	}

	/**	
	 * Initialize HTML5 uploads 
	 *
	 * By apeisa with additional code by Ryan
	 * 
	 * Based on the great work and examples of Craig Buckler (http://www.sitepoint.com/html5-file-drag-and-drop/)
	 * and Robert Nyman (http://robertnyman.com/html5/fileapi-upload/fileapi-upload.html)
	 * 	
	 */
	function InitHTML5($inputfield) {

		if($inputfield.length > 0) {
			var $target = $inputfield.find(".InputfieldFileUpload"); // just one
		} else {
			var $target = $(".InputfieldFileUpload"); // all 
		}
		$target.closest('.ui-widget-content, .InputfieldContent').each(function (i) {
			initHTML5Item($(this), i);
		});
			
		function initHTML5Item($this, i) {

			var $form = $this.parents('form'); 
			var postUrl = $form.attr('action'); 

			// CSRF protection
			var $postToken = $form.find('input._post_token'); 
			var postTokenName = $postToken.attr('name');
			var postTokenValue = $postToken.val();

			var fieldName = $this.find('p.InputfieldFileUpload').data('fieldname');
			fieldName = fieldName.slice(0,-2);

			var extensions = $this.find('p.InputfieldFileUpload').data('extensions').toLowerCase();
			var maxFilesize = $this.find('p.InputfieldFileUpload').data('maxfilesize');
			
			var filesUpload = $this.find("input[type=file]").get(0);
			var dropArea = $this.get(0);
			var $fileList = $this.find(".InputfieldFileList"); 

			if($fileList.size() < 1) {
				$fileList = $("<ul class='InputfieldFileList InputfieldFileListBlank'></ul>");
				$this.prepend($fileList); 
				$this.parent('.Inputfield').addClass('InputfieldFileEmpty'); 
			}

			var fileList = $fileList.get(0);
			var maxFiles = parseInt($this.find('.InputfieldFileMaxFiles').val()); 
			
			$fileList.children().addClass('InputfieldFileItemExisting'); // identify items that are already there

			$this.find('.AjaxUploadDropHere').show();
			
			var doneTimer = null; // for AjaxUploadDone event
			
			function uploadFile(file) {

				var $progressItem = $('<li class="InputfieldFile ui-widget AjaxUpload"><p class="InputfieldFileInfo ui-widget ui-widget-header InputfieldItemHeader"></p></li>'),
					$progressBar = $('<div class="ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>'),
					$progressBarValue = $('<div class="ui-progressbar-value ui-widget-header InputfieldItemHeader ui-corner-left" style="width: 0%; "></div>'),
					img,
					reader,
					xhr,
					fileData;
				
				$progressBar.append($progressBarValue);
				$progressItem.append($progressBar);
				
				// Uploading - for Firefox, Google Chrome and Safari
				xhr = new XMLHttpRequest();
				
				// Update progress bar
				xhr.upload.addEventListener("progress", function (evt) {
					if(evt.lengthComputable) {
						var completion = (evt.loaded / evt.total) * 100;
						$progressBarValue.width(completion + "%");
						if(completion > 4) {
							$progressBarValue.html("<span>" + parseInt(completion) + "%</span>");
						}
						/*
						// code for freezing progressbar during testing
						$progressBarValue.width("60%");
						if(completion > 50) setTimeout(function() { alert('test'); }, 10); 
						*/
					} else {
						// No data to calculate on
					}
				}, false);

				
				// File uploaded: called for each file
				xhr.addEventListener("load", function() {

					var response = $.parseJSON(xhr.responseText); 
					if(response.error !== undefined) response = [response];
					
					// note the following loop will always contain only 1 item, unless a file containing more files (ZIP file) was uploaded
					for(var n = 0; n < response.length; n++) {

						var r = response[n]; 
						
						if(r.error) {
							var $pi = $progressItem.clone(); 
							$pi.find(".InputfieldFileInfo").addClass('ui-state-error'); 
							$pi.find(".InputfieldFileStats").text(' - ' + r.message); 
							$pi.find(".ui-progressbar").remove();
							$progressItem.after($pi); 

						} else {

							if(r.replace) {
								var $child = $this.find('.InputfieldFileList').children('li:eq(0)');
								if($child.size() > 0) $child.slideUp('fast', function() { $child.remove(); });
							}
                           
							// ie10 file field stays populated, this fixes that
							var $input = $this.find('input[type=file]');
							if($input.val()) $input.replaceWith($input.clone(true));

							var $markup = $(r.markup);
							$markup.hide();

							// look for and handle replacements
							if(r.overwrite) {
								var basename = $markup.find('.InputfieldFileName').text();
								var $item = null;
								// find an existing item having the same basename
								$fileList.children('.InputfieldFileItemExisting').each(function() {
									if($item === null && $(this).find('.InputfieldFileName').text() == basename) {
										// filenames match
										$item = $(this);
									}
								});
								if($item !== null) {
									// found replacement
									var $newInfo = $markup.find(".InputfieldFileInfo");
									var $newLink = $markup.find(".InputfieldFileLink"); 
									var $info = $item.find(".InputfieldFileInfo"); 
									var $link = $item.find(".InputfieldFileLink"); 
									$info.html($newInfo.html() + "<i class='fa fa-check'></i>");
									$link.html($newLink.html());
									$item.addClass('InputfieldFileItemExisting'); 
									$item.effect('highlight', 500); 
								} else {
									// didn't find a match, just append
									$fileList.append($markup);
									$markup.slideDown();
									$markup.addClass('InputfieldFileItemExisting');
								}
								
							} else {
								// overwrite mode not active
								$fileList.append($markup);
								$markup.slideDown();
							}
						}
						
					}

					$progressItem.remove();
					
					if(doneTimer) clearTimeout(doneTimer); 
					doneTimer = setTimeout(function() { 
						if(maxFiles != 1 && !$fileList.is('.ui-sortable')) initSortable($fileList); 
						$fileList.trigger('AjaxUploadDone'); // for things like fancybox that need to be re-init'd
					}, 500); 

				}, false);
				
				// Here we go
				xhr.open("POST", postUrl, true);
				xhr.setRequestHeader("X-FILENAME", unescape(encodeURIComponent(file.name)));
				xhr.setRequestHeader("X-FIELDNAME", fieldName);
				xhr.setRequestHeader("Content-Type", "application/octet-stream"); // fix issue 96-Pete
				xhr.setRequestHeader("X-" + postTokenName, postTokenValue);
				xhr.setRequestHeader("X-REQUESTED-WITH", 'XMLHttpRequest');
				xhr.send(file);
				
				// Present file info and append it to the list of files
				fileData = '' + 
					"<span class='ui-icon ui-icon-arrowreturnthick-1-e' style='margin-left: 2px;'></span>" + 
					'<span class="InputfieldFileName">' + file.name + '</span>' + 
					'<span class="InputfieldFileStats"> &bull; ' + parseInt(file.size / 1024, 10) + " kb</span>";
				
				$progressItem.find('p.ui-widget-header').html(fileData);
				$fileList.append($progressItem);
				$fileList.closest('.Inputfield').addClass('InputfieldStateChanged');
			}
			
	
			function traverseFiles(files) {

				function errorItem(filename, message) { 
					return 	'<li class="InputfieldFile ui-widget AjaxUpload">' + 
						'<p class="InputfieldFileInfo ui-widget ui-widget-header InputfieldItemHeader ui-state-error">&nbsp; ' + filename  + ' ' + 
						'<span class="InputfieldFileStats"> &bull; ' + message + '</span></p></li>';
				}

				if(typeof files !== "undefined") {
					for(var i=0, l=files.length; i<l; i++) {

						var extension = files[i].name.split('.').pop().toLowerCase();

						if(extensions.indexOf(extension) == -1) {
							$fileList.append(errorItem(files[i].name, extension + ' is a invalid file extension, please use one of:  ' + extensions)); 

						} else if(files[i].size > maxFilesize && maxFilesize > 2000000) {
							// I do this test only if maxFilesize is at least 2M (php default). 
							// There might (not sure though) be some issues to get that value so don't want to overvalidate here -apeisa
							$fileList.append(errorItem(files[i].name, 'Filesize ' + parseInt(files[i].size / 1024, 10) +' kb is too big. Maximum allowed is ' + parseInt(maxFilesize / 1024, 10) + ' kb')); 

						} else {
							uploadFile(files[i]);
						}
						if(maxFiles == 1) break;
					}
				} else {
					fileList.innerHTML = "No support for the File API in this web browser";
				}	
			}
			
			filesUpload.addEventListener("change", function(evt) {
				traverseFiles(this.files);
				evt.preventDefault();
				evt.stopPropagation();
				this.value = '';
			}, false);

			dropArea.addEventListener("dragleave", function() { $(this).removeClass('ui-state-hover'); }, false);
			dropArea.addEventListener("dragenter", function() { $(this).addClass('ui-state-hover'); }, false);

			dropArea.addEventListener("dragover", function (evt) {
				if(!$(this).is('ui-state-hover')) $(this).addClass('ui-state-hover'); 
				evt.preventDefault();
				evt.stopPropagation();
			}, false);
			
			dropArea.addEventListener("drop", function (evt) {
				traverseFiles(evt.dataTransfer.files);
				$(this).removeClass("ui-state-hover");
				evt.preventDefault();
				evt.stopPropagation();
			}, false);		
		} // initHTML5Item
	} // initHTML5

	/**
	 * MAIN
	 *
	 */

	initSortable($(".InputfieldFileList")); 
	
	/**
	 * Progressive enchanchment for browsers that support html5 File API
	 * 
	 * #PageIDIndictator.size indicates PageEdit, which we're limiting AjaxUpload to since only ProcessPageEdit has the ajax handler
	 * 
	 */
	if (window.File && window.FileList && window.FileReader && $("#PageIDIndicator").size() > 0) {  
		InitHTML5('');  
	} else {
		InitOldSchool();
	}

	var minContainerWidth = 767; // ...or when the container width is this or smaller
	var resizeActive = false;
	
	var windowResize = function() {
		$(".AjaxUploadDropHere").each(function() {
			var $t = $(this); 
			if($t.parent().width() <= minContainerWidth) {
				$t.hide();
			} else {
				$t.show();
			}
		}); 
		resizeActive = false;
	}
	
	$(window).resize(function() {
		if(resizeActive) return;
		resizeActive = true; 
		setTimeout(windowResize, 1000); 
	}).resize();
	
	$(document).on('reloaded', '.InputfieldFileMultiple, .InputfieldFileSingle', function(event) {
		initSortable($(this).find(".InputfieldFileList"));
		InitHTML5($(this)); 
	}); 
	
}); 
