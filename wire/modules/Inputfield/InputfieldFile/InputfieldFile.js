$(document).ready(function() {

	/**
	 * Setup a live change event for the delete links
	 *
	 */

	if($.browser.msie && $.browser.version < 9) {
		
		$(".InputfieldFileDelete span.ui-icon").live("click", function() {
			
			var input = $(this).prev('input'); 
			if(input.is(":checked")){
				input.removeAttr("checked");
			} else {
				input.attr({"checked":"checked"});	
			}
			
			setInputfieldFileStatus(input);
			
		});
		
	} else {
		// not IE < 9
		$(this).find(".InputfieldFileDelete input").live('change', function() {
			setInputfieldFileStatus($(this));
		}); 
	}

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

			// if we're dealing with a single item list, then don't continue with making it sortable
			if($(this).children("li").size() < 2) return; 

			$(this).sortable({
				axis: 'y', 
				start: function(e, ui) {
					ui.item.children(".InputfieldFileInfo").addClass("ui-state-highlight"); 
				}, 
				stop: function(e, ui) {
					$(this).children("li").each(function(n) {
						$(this).find(".InputfieldFileSort").val(n); 
					}); 
					ui.item.children(".InputfieldFileInfo").removeClass("ui-state-highlight"); 
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
		$(".InputfieldFileUpload input[type=file]").live('change', function() {
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
	function InitHTML5() {

		$(".InputfieldFileUpload").parent('.ui-widget-content').each(function(i) {

			var $this = $(this); 
			var $form = $this.parents('form'); 
			var postUrl = $form.attr('action'); 

			// CSRF protection
			var $postToken = $form.find('#_post_token'); 
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
				$fileList = $("<ul class='InputfieldFileList InputfieldFileListBlank'></ul>"),
				$this.prepend($fileList); 
			}

			var fileList = $fileList.get(0);
			var maxFiles = parseInt($this.find('.InputfieldFileMaxFiles').val()); 

			$this.find('.AjaxUploadDropHere').show();
				
			function uploadFile(file) {

				var $progressItem = $('<li class="InputfieldFile ui-widget AjaxUpload"><p class="InputfieldFileInfo ui-widget ui-widget-header"></p></li>'),
					$progressBar = $('<div class="ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>'),
					$progressBarValue = $('<div class="ui-progressbar-value ui-widget-header ui-corner-left" style="width: 0%; "></div>'),
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
					} else {
						// No data to calculate on
					}
				}, false);
				
				// File uploaded
				xhr.addEventListener("load", function() {

					var response = $.parseJSON(xhr.responseText); 
					if(response.error !== undefined) response = [response];

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

							var $markup = $(r.markup); 
							$markup.hide();
							$fileList.append($markup); 
							$markup.slideDown(); 
						}
							
					}

					$progressItem.remove();
					if(maxFiles != 1 && !$fileList.is('.ui-sortable')) initSortable($fileList); 
					$fileList.trigger('AjaxUploadDone'); // for things like fancybox that need to be re-init'd

				}, false);
				
				// Here we go
				xhr.open("POST", postUrl, true);
				xhr.setRequestHeader("X-FILENAME", file.name);
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
			}
			
	
			function traverseFiles(files) {

				function errorItem(filename, message) { 
					return 	'<li class="InputfieldFile ui-widget AjaxUpload">' + 
						'<p class="InputfieldFileInfo ui-widget ui-widget-header ui-state-error">&nbsp; ' + filename  + ' ' + 
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
		});
	}

	/**
	 * MAIN
	 *
	 */

	initSortable($(".InputfieldFileList")); 
	
	/**
	 * Progressive enchanchment for browsers that support html5 File API
	 * 
	 * #PageIDIndictator.size indicates PageEdit, which we're temporarily limiting AjaxUpload to
	 * 
	 */
	if (window.File && window.FileList && window.FileReader && $("#PageIDIndicator").size() > 0) {  
		InitHTML5();  
	} else {
		InitOldSchool();
	}
	
}); 
