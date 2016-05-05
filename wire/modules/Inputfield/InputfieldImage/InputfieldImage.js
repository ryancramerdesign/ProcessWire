function InputfieldImage($) {
	
	// When uploading a file in place: .gridItem that file(s) will be placed before
	var $uploadBeforeItem = null;

	// When replacing a file: .gridImage item that is being replaced
	var uploadReplace = {
		file: '',	// basename of file being replaced
		item: null, // the .gridImage item that is being replaced
		edit: null, // the .InputfieldImageEdit.gridImage item (edit panel) that is being replaced
	};
	
	// Base options for MagnificPopup
	var magnificOptions = {
		type: 'image',
		closeOnContentClick: true,
		closeBtnInside: true
	};

	/**
	 * Whether or not AJAX drag/drop upload is allowed?
	 * 
	 * @returns bool
	 */
	function useAjaxUpload() {
		var isFileReaderSupport = window.File && window.FileList && window.FileReader;
		var isAjaxUpload = $('.InputfieldAllowAjaxUpload').length > 0;
		var isPageIDIndicator = $("#PageIDIndicator").length > 0;
		return (isFileReaderSupport && (isPageIDIndicator || isAjaxUpload));
	}

	/**
	 * LostKobrakai: what does this function do?
	 *
	 * @param fn
	 * @param threshhold
	 * @param scope
	 * @returns {Function}
	 *
	 */
	function throttle(fn, threshhold, scope) {
		threshhold || (threshhold = 250);
		var last, deferTimer;
		return function() {
			var context = scope || this;
			var now = +new Date(), args = arguments;
			if(last && now < last + threshhold) {
				clearTimeout(deferTimer);
				deferTimer = setTimeout(function() {
					last = now;
					fn.apply(context, args);
				}, threshhold);
			} else {
				last = now;
				fn.apply(context, args);
			}
		};
	}

	/**
	 * Helper function for inversing state of checkboxes
	 *
	 * @param index
	 * @param old
	 * @returns {boolean}
	 *
	 */
	function inverseState(index, old) {
		return !old;
	}

	/**
	 * Make an element sortable
	 *
	 * @param $el
	 *
	 */
	function setupSortable($el) {
		if($el.hasClass('ui-sortable')) {
			$el.sortable('destroy');
			// re-build sort indexes
			$el.children("li").each(function(n) {
				var $sort = $(this).find("input.InputfieldFileSort"); 
				$sort.val(n);
			});
		}
		$el.sortable({
			items: "> .gridImage", 
			start: function(e, ui) {
				ui.placeholder.append($("<div/>").css({
					display: "block",
					height: $el.data("gridsize") + "px",
					width: $el.data("gridsize") + "px"
				}));
				// Prevent closing, if this really meant to be a click
				timer = window.setTimeout(function() {
					closeEdit($el, null);
				}, 100);
				$el.addClass('InputfieldImageSorting');
			},
			stop: function(e, ui) {
				$this = $(this);
				if(timer !== null) {
					ui.item.find(".InputfieldImageEdit__edit").click();
					clearTimeout(timer);
				}
				$this.children("li").each(function(n) {
					$(this).find(".InputfieldFileSort").val(n);
				});
				ui.item.find('.InputfieldFileSort').change();
				$el.removeClass('InputfieldImageSorting');
			},
			cancel: ".InputfieldImageEdit"
		});
	}

	/**
	 * Setup MagnificPopup plugin for renderValue mode
	 *
	 * @param $el
	 *
	 */
	function setupMagnificForRenderValue($el) {
		var options = magnificOptions;
		options.callbacks = {
			elementParse: function(item) {
				item.src = $(item.el).attr('data-original');
			}
		};
		options.gallery = {
			enabled: true
		};
		$el.find("img").magnificPopup(options);
	}
	
	/**
	 * Setup MagnificPopup plugin for Single mode
	 *
	 * @param $el
	 *
	 */
	function setupMagnificForSingle($el) {
		var options = magnificOptions;
		options.callbacks = {
			elementParse: function(item) {
				item.src = $(item.el).attr('src');
			}
		};
		options.gallery = {
			enabled: false
		};
		$el.find("img").magnificPopup(options);
	}

	/**
	 * Manage image per row issues
	 *
	 * @param $parent
	 * @returns {*}
	 *
	 */
	function findEditedElement($parent) {
		return $parent.find(".InputfieldImageEdit--active");
	}

	/**
	 * LostKobrakai: Can you describe this function?
	 *
	 * @param $edit
	 *
	 */
	function findEditMarkup($edit) {
		return $("#" + $edit.find(".InputfieldImageEdit__edit").attr("data-current"));
	}

	/**
	 * Populates a 'data-per-row' attribute on $el containing number of images in row
	 *
	 * @param $el
	 *
	 */
	function getImagesPerRow($el) {
		var $itemWidth = $el.children().first().outerWidth(true),
			$containerWidth = $el.width();

		$el.attr("data-per-row", Math.floor($containerWidth / $itemWidth));
	}

	/**
	 * Sets the checkbox delete state of all items to have the same as that of $input
	 *
	 * @param $input
	 *
	 */
	function setDeleteStateOnAllItems($input) {
		var checked = $input.is(":checked");
		var $items = $input.parents('.gridImages').find('.gridImage__deletebox');
		if(checked) {
			$items.prop("checked", "checked").change();
		} else {
			$items.removeAttr("checked").change();
		}
	}

	/**
	 * LostKobrakai: Can you describe this function?
	 *
	 */
	var updateGrid = function() {
		var a = function() {
			var $grid = $(this),
				$edit = findEditedElement($grid);

			if($edit.length) {
				getImagesPerRow($grid);
				moveEdit(findEditMarkup($edit), $edit);
			}
		};
		$(".gridImages").each(a);
	};

	/**
	 * Updates outer class of item to match that of its "delete" checkbox
	 *
	 * @param $checkbox
	 *
	 */
	function updateDeleteClass($checkbox) {
		if($checkbox.is(":checked")) {
			$checkbox.parents('.ImageOuter').addClass("gridImage--delete");
		} else {
			$checkbox.parents('.ImageOuter').removeClass("gridImage--delete");
		}
	}

	/**
	 * LostKobrakai: this function doesn't appear to be in use–okay to remove?
	 *
	 * @param $el
	 *
	 */
	function markForDeletion($el) {
		$el.parents('.gridImage').toggleClass("gridImage--delete");
		$el.find("input").prop("checked", inverseState);
	}

	/**
	 * LostKobrakai: Can you describe this function?
	 *
	 * @param $el
	 * @param $edit
	 *
	 */
	function setup($el, $edit) {
		var $img = $edit.find(".InputfieldImageEdit__image");
		$img.attr({
			src: $el.find("img").attr("data-original"),
			"data-original": $el.find("img").attr("data-original"),
			alt: $el.find("img").attr("alt")
		});

		var options = magnificOptions;
		options.callbacks = {
			elementParse: function(item) {
				item.src = $(item.el).attr('data-original');
			}
		};
		options.gallery = {
			enabled: true
		};
		$edit
			.parents(".gridImages")
			.find(".gridImage")
			.not($el)
			.find("img")
			.add($img)
			.magnificPopup(options);

		$edit.find(".InputfieldImageEdit__edit")
			.attr("data-current", $el.attr("id"))
			.append($el.find(".ImageData").children().not(".InputfieldFileSort, .InputfieldFileReplace"));
	}

	/**
	 * LostKobrakai: Can you describe this function?
	 *
	 * @param $edit
	 *
	 */
	function tearDown($edit) {
		$inputArea = $edit.find(".InputfieldImageEdit__edit");
		if($inputArea.children().not(".InputfieldFileSort, .InputfieldFileReplace").length) {
			$("#" + $inputArea.attr("data-current")).find(".ImageData").append($inputArea.children());
		}
	}

	/**
	 * LostKobrakai: Can you describe this function?
	 *
	 * @param $parent
	 * @param $not
	 *
	 */
	function closeEdit($parent, $not) {
		var $edit;

		if($parent) {
			$edit = $parent.find(".InputfieldImageEdit--active");
		} else if($not) {
			$edit = $(".InputfieldImageEdit--active").not($not.find(".InputfieldImageEdit--active"));
		} else {
			$edit = $(".InputfieldImageEdit--active");
		}
		
		if($edit.length) {
			tearDown($edit);
			$edit.removeClass("InputfieldImageEdit--active");
			$('#' + $edit.attr('data-for')).removeClass('gridImageEditing');
		}
		
		$(".InputfieldImageEdit__replace").removeClass("InputfieldImageEdit__replace"); 
	}

	/**
	 * LostKobrakai: Can you describe this function?
	 *
	 * @param $el
	 * @param $edit
	 *
	 */
	function moveEdit($el, $edit) {
		
		if(!$el || !$el.length) return;
		getImagesPerRow($el.parent());

		var $children = $el.parent().children().not(".InputfieldImageEdit"),
			perRow = parseInt($el.parent().attr("data-per-row")),
			index = $children.index($el);

		for(var i = 0; i < 30; i++) {
			if(index % perRow !== perRow - 1) {
				index++;
			} else {
				continue;
			}
		}

		index = Math.min(index, $children.length - 1); // Do not excede number of items
		$edit.insertAfter($children.eq(index));

		var $arrow = $edit.find(".InputfieldImageEdit__arrow");
		if($arrow.length) $arrow.css("left", $el.position().left + ($el.outerWidth() / 2) + "px");
	}
	
	/*** GRID INITIALIZATION ****************************************************************************/
	
	/**
	 * Initialize non-upload related events
	 *
	 */
	function initGridEvents() {

		// resize window event
		$(window).resize(throttle(updateGrid, 200));

		// click or double click trash event
		$(document).on('click dblclick', '.gridImage__trash', function(e) {
			var $input = $(this).find("input");
			$input.prop("checked", inverseState).change();
			if(e.type == "dblclick") setDeleteStateOnAllItems($input);
		});

		// change of "delete" status for an item event
		$(document).on("change", ".gridImage__deletebox", function() {
			updateDeleteClass($(this));
		});

		// click on "edit" link event
		$(document).on('click', '.gridImage__edit', function(e) {
			
			var $el = $(this).closest(".gridImage");
			if(!$el.length) return;
			
			var $all = $el.closest(".gridImages");
			var $edit = $all.find(".InputfieldImageEdit");
			
			if($el.hasClass('gridImageEditing')) {
				// if item already has its editor open, then close it
				$edit.find(".InputfieldImageEdit__close").click();	
				
			} else {
				moveEdit($el, $edit);
				tearDown($edit);
				setup($el, $edit);

				$edit.addClass("InputfieldImageEdit--active").attr('data-for', $el.attr('id'));
				$all.find('.gridImageEditing').removeClass('gridImageEditing');
				$el.addClass('gridImageEditing');
			}
		});

		// LostKobrakai: can this be adjusted so it isn't triggered on all document click events?
		// just seems like a lot of code to execute every time there is a click, and might be 
		// better to narrow focus it in on only those applicable to InputfieldImage
		$(document).on("click", function(e) {
			var $el = $(e.target);

			if($el.closest(".InputfieldImageEdit").length) {
				closeEdit(null, $el.parents(".gridImages"));
			} else if($el.closest(".gridImage__inner").length) {
				closeEdit(null, $el.parents(".gridImages"));
			} else if($el.closest(".mfp-container").length) {
				return;
			} else if($el.closest(".ui-dialog").length) {
				return;
			} else if($el.is(".mfp-close")) {
				return;
			} else {
				closeEdit(null, null);
			}
		});

		// close "edit" panel
		$(".InputfieldImageEdit").on("click", ".InputfieldImageEdit__close", function(e) {
			closeEdit($(this).parents(".gridImages"), null);
		});

		// LostKobrakai: can you describe what this does?
		$(".ImagesGrid").on("click", "button.pw-modal", function(e) {
			e.preventDefault();
		});

		/*
		// Longclick .gridItem to open magnific popup
		// Stops working as soon as an "Edit" panel has been opened, and 
		// also prevents any image zooms from working. :-/
		$(document).on('longclick', '.gridImage__edit', function() {
			var $img = $(this).closest('.gridImage').find('img');
			console.log($img.attr('data-original'));
			var options = magnificOptions;
			options['items'] = {
				src: $img.attr('data-original'),
				title: $img.attr('alt')
			};
			$.magnificPopup.open(options, 0);
			return false;
		});
		*/
	}

	/**
	 * Initialize an .InputfieldImage for lightbox (magnific) and sortable
	 * 
	 * @param $inputfield
	 * 
	 */
	function initInputfield($inputfield) {
		var maxFiles = parseInt($inputfield.find(".InputfieldImageMaxFiles").val());
		if($inputfield.hasClass('InputfieldRenderValue')) {
			// return setupMagnificForRenderValue($el); // Lostkobrakai ($el not in scope, I changed to $this)
			return setupMagnificForRenderValue($inputfield);
		} else if(maxFiles == 1) {
			$inputfield.addClass('InputfieldImageMax1');
			setupMagnificForSingle($inputfield);
		} else {
			setupSortable($inputfield.find('.gridImages'));
		}
	}

	/*** UPLOAD **********************************************************************************/

	/**
	 * Initialize non-HTML5 uploads
	 *
	 */
	function initUploadOldSchool() {
		$("body").addClass("ie-no-drop");

		$(".InputfieldImage.InputfieldFileMultiple").each(function() {
			var $field = $(this),
				maxFiles = parseInt($field.find(".InputfieldFileMaxFiles").val()),
				$list = $field.find('.gridImages'),
				$uploadArea = $field.find(".InputfieldImageUpload");

			$uploadArea.on('change', 'input[type=file]', function() {
				var $t = $(this),
					$mask = $t.parent(".InputMask");

				if($t.val().length > 1) $mask.addClass("ui-state-disabled");
				else $mask.removeClass("ui-state-disabled");

				if($t.next("input.InputfieldFile").length > 0) return;
				var numFiles = $list.children('li').length + $uploadArea.find('input[type=file]').length + 1;
				if(maxFiles > 0 && numFiles >= maxFiles) return;

				$uploadArea.find(".InputMask").not(":last").each(function() {
					var $m = $(this);
					if($m.find("input[type=file]").val() < 1) $m.remove();
				});

				// add another input
				var $i = $mask.clone().removeClass("ui-state-disabled");
				$i.children("input[type=file]").val('');
				$i.insertAfter($mask);
			});
		});
	}

	/**
	 * Initialize HTML5 uploads
	 *
	 * By apeisa with additional code by Ryan and LostKobrakai
	 *
	 * Based on the great work and examples of Craig Buckler (http://www.sitepoint.com/html5-file-drag-and-drop/)
	 * and Robert Nyman (http://robertnyman.com/html5/fileapi-upload/fileapi-upload.html)
	 *
	 */
	function initUploadHTML5($inputfield) {
	
		// target is one or more .InputfieldImageUpload elements
		var $target;

		if($inputfield.length > 0) {
			// Inputfield provided, target it
			$target = $inputfield.find(".InputfieldImageUpload"); 
		} else {
			// No Inputfield provided, focus on all 
			$target = $(".InputfieldImageUpload"); 
		}

		// initialize each found item
		$target.each(function(i) {
			var $content = $(this).closest('.InputfieldContent');
			if($content.hasClass('InputfieldFileInit')) return;
			initHTML5Item($content, i);
			$content.addClass('InputfieldFileInit');
		}); 

		/**
		 * Initialize an "InputfieldImage > .InputfieldContent" item
		 * 
		 * @param $this
		 * @param i
		 */
		function initHTML5Item($this, i) {

			var $form = $this.parents('form');
			var postUrl = $form.attr('action');
			postUrl += (postUrl.indexOf('?') > -1 ? '&' : '?') + 'InputfieldFileAjax=1';

			// CSRF protection
			var $postToken = $form.find('input._post_token');
			var postTokenName = $postToken.attr('name');
			var postTokenValue = $postToken.val();
			var $errorParent = $this.find('.InputfieldImageErrors').first();
			
			var fieldName = $this.find('.InputfieldImageUpload').data('fieldname');
			fieldName = fieldName.slice(0, -2);
			
			var extensions = $this.find('.InputfieldImageUpload').data('extensions').toLowerCase();
			var maxFilesize = $this.find('.InputfieldImageUpload').data('maxfilesize');
			var filesUpload = $this.find("input[type=file]").get(0);
			var $fileList = $this.find(".gridImages");
			var fileList = $fileList.get(0);
			var gridSize = $fileList.data("gridsize");
			var doneTimer = null; // for AjaxUploadDone event
			var maxFiles = parseInt($this.find('.InputfieldImageMaxFiles').val());

			setupDropzone($this);
			if(maxFiles != 1) setupDropInPlace($fileList);
			setupDropHere();

			$fileList.children().addClass('InputfieldFileItemExisting'); // identify items that are already there

			/**
			 * Setup the .AjaxUploadDropHere 
			 * 
			 */
			function setupDropHere() {
				$dropHere = $this.find('.AjaxUploadDropHere');
				$dropHere.show().click(function() {
					var $i = $(this).find('.InputfieldImageRefresh');
					if($i.is(":visible")) {
						$i.hide().siblings('span').show();
						$(this).find('input').val('0');
					} else {
						$i.show().siblings('span').hide();
						$(this).find('input').val('1');
					}
				});
			}
			

			/**
			 * Render and return markup for an error item
			 * 
			 * @param message
			 * @param filename
			 * @returns {string}
			 * 
			 */
			function errorItem(message, filename) {
				if(typeof filename !== "undefined") message = '<b>' + filename + ':</b> ' + message;
				return '<li>' + message + '</li>';
			}

			/**
			 * Given a filename, return the basename 
			 * 
			 * @param str
			 * @returns {string}
			 * 
			 */
			function basename(str) {
				var base = new String(str).substring(str.lastIndexOf('/') + 1);
				if(base.lastIndexOf(".") != -1) base = base.substring(0, base.lastIndexOf("."));
				return base;
			}
			
			/**
			 * Setup the dropzone where files are dropped
			 *
			 * @param $el
			 *
			 */
			function setupDropzone($el) {

				var el = $el.get(0);

				el.addEventListener("dragleave", function() {
					$el.removeClass('ui-state-hover');
				}, false);
				
				el.addEventListener("dragenter", function() {
					$el.addClass('ui-state-hover');
				}, false);

				el.addEventListener("dragover", function(evt) {
					if(!$el.is('ui-state-hover')) $el.addClass('ui-state-hover');
					evt.preventDefault();
					evt.stopPropagation();
					return false;
				}, false);

				el.addEventListener("drop", function(evt) {
					traverseFiles(evt.dataTransfer.files);
					$el.removeClass("ui-state-hover");
					evt.preventDefault();
					evt.stopPropagation();
					return false;
				}, false);
			}

			/**
			 * Support for drag/drop uploading an image at a place within the grid
			 * 
			 * @param $el 
			 * 
			 */
			function setupDropInPlace($gridImages) {
			
				var $i = null; // placeholder .gridItem
				var haltDrag = false; // true when drag should be halted
				var timer = null; // for setTimeout
				
				function allowDropInPlace() {
					// we disable drop-in-place when the "edit" panel is open
					return $gridImages.find(".InputfieldImageEdit--active").length == 0;
				}

				function getCenterCoordinates($el) {
					var offset = $el.offset();
					var width = $el.width();
					var height = $el.height();

					var centerX = offset.left + width / 2;
					var centerY = offset.top + height / 2;
					
					return {
						clientX: centerX,
						clientY: centerY
					}
				}

				function dragEnter(evt) {
					haltDrag = false;
					evt.preventDefault();
					if(!allowDropInPlace()) return;
					if($i == null) {
						var gridSize = $gridImages.attr('data-gridsize') + 'px';
						var $o = $("<div/>").addClass('gridImage__overflow').css({ width: gridSize, height: gridSize });
						$i = $("<li/>").addClass('ImageOuter gridImage gridImagePlaceholder').append($o);
						$gridImages.append($i);
					}
					var coords = getCenterCoordinates($i);
					$i.simulate("mousedown", coords);
				}
				
				function dragOver(evt) {
					haltDrag = false;
					if($i == null) return;
					evt.preventDefault();
					if(!allowDropInPlace()) return;
					// $('.gridImage', $gridImages).trigger('drag');
					var coords = {
						clientX: evt.originalEvent.clientX,
						clientY: evt.originalEvent.clientY
					};
					$i.simulate("mousemove", coords);
				}
				
				function dragEnd(evt) {
					if($i == null) return false;
					if(!allowDropInPlace()) return;
					haltDrag = true;
					if(timer) clearTimeout(timer);
					timer = setTimeout(function() {
						if(!haltDrag || $i == null) return;
						$i.remove();
						$i = null;
					}, 1000); 
				}
				
				function drop(evt) {
					
					haltDrag = false;
					if(!allowDropInPlace()) return;

					var coords = {
						clientX: evt.clientX,
						clientY: evt.clientY
					};

					$i.simulate("mouseup", coords);
					
					/*
					 // var files = evt.dataTransfer.files;
					for(var j = 0; j < files.length; j++) {
						$i.clone()
							.removeClass("gridImagePlaceholder")
							//.text(files[j].name.substr(0, 12))
							.insertAfter($i);
					}
					*/
					
					$uploadBeforeItem = $i.next('.gridImage');
					$i.remove();
					$i = null;
				}
			
				if($gridImages.length) {
					$gridImages.on('dragenter', dragEnter);
					$gridImages.on('dragover', dragOver);
					$gridImages.on('dragleave', dragEnd);
					$gridImages[0].addEventListener('drop', drop);
				}
			}
			
			/**
			 * Upload file
			 * 
			 * @param file
			 * 
			 */
			function uploadFile(file) {
			
				var labels = ProcessWire.config.InputfieldImage.labels;
				var filesizeStr = parseInt(file.size / 1024, 10) + '&nbsp;kB';
				var tooltipMarkup = '' +
					'<div class="gridImage__tooltip">' + 
						'<table><tbody><tr>' + 
							'<th>' + labels.dimensions + '</th>' + 
							'<td class="dimensions">' + labels.na + '</td>' +
						'</tr><tr>' + 
							'<th>' + labels.filesize + '</th>' + 
							'<td>' + filesizeStr + '</td>' + 
						'</tr><tr>' + 
							'<th>' + labels.variations + '</th>' + 
							'<td>0</td>' + 
						'</tr></tbody></table>' + 
					'</div>';
				

				var $progressItem = $('<li class="gridImage"></li>'),
					$tooltip = $(tooltipMarkup),
					$imgWrapper = $('<div class="gridImage__overflow"></div>'),
					$img = $('<img width="184" height="130" alt="">'), // LostKobrakai: where do these dimensions come from? Do we need to instead pull them from somewhere?
					$imageData = $('<div class="ImageData"></div>'),
					$hover = $("<div class='gridImage__hover'><div class='gridImage__inner'></div></div>"),
					$progressBar = $("<progress class='gridImage__progress' min='-1' max='100' value='0'></progress>"),
					$edit = $('<a class="gridImage__edit" title="' + file.name + '"><span>' + labels.details + '</span></a>'),
					$spinner = $('<div class="gridImage__resize"><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i></div>'),
					img,
					reader,
					xhr,
					fileData,
					fileUrl = URL.createObjectURL(file),
					singleMode = maxFiles == 1; 

				$imgWrapper.append($img);
				$hover.find(".gridImage__inner").append($edit);
				$hover.find(".gridImage__inner").append($spinner.css('display', 'none'));
				$hover.find(".gridImage__inner").append($progressBar);
				$imageData.append($('' + 
					'<h2 class="InputfieldImageEdit__name">' + file.name + '</h2>' +
					'<span class="InputfieldImageEdit__info">' + filesizeStr + '</span>')
				);

				$imgWrapper.css({
					width: gridSize + "px",
					height: gridSize + "px"
				});

				$progressItem
					.append($tooltip)
					.append($imgWrapper)
					.append($hover)
					.append($imageData);

				$img.attr({
					src: fileUrl,
					"data-original": fileUrl
				});

				img = new Image();
				img.addEventListener('load', function() {
					$tooltip.find(".dimensions").html(this.width + "&nbsp;&times;&nbsp;" + this.height);
					var factor = Math.min(this.width, this.height) / gridSize;
					$img.attr({
						width: this.width / factor,
						height: this.height / factor
					});
				}, false);
				img.src = fileUrl;

				// Uploading - for Firefox, Google Chrome and Safari
				xhr = new XMLHttpRequest();

				// Update progress bar
				xhr.upload.addEventListener("progress", function(evt) {
					if(!evt.lengthComputable) return;
					$progressBar.attr("value", parseInt((evt.loaded / evt.total) * 100));
					$spinner.css('display', 'block');
				}, false);

				// File uploaded: called for each file
				xhr.addEventListener("load", function() {
					var response = $.parseJSON(xhr.responseText),
						wasZipFile = response.length > 1;
					if(response.error !== undefined) response = [response];
					// response = [{error: "Invalid"}];

					// note the following loop will always contain only 1 item, unless a file containing more files (ZIP file) was uploaded
					for(var n = 0; n < response.length; n++) {

						var r = response[n];

						if(r.error) {
							$errorParent.append(errorItem(r.message));
							continue;
						}

						var $item = null;
						var $markup = $(r.markup).hide();

						// IE 10 fix
						var $input = $this.find('input[type=file]');
						if($input.val()) $input.replaceWith($input.clone(true));

						// look for replacements
						if(r.overwrite) $item = $fileList.children('#' + $markup.attr('id'));
						if(r.replace || singleMode) $item = $fileList.find('.InputfieldImageEdit:eq(0)');
						
						if(uploadReplace.item && response.length == 1 && !singleMode) {
							$item = uploadReplace.item;
						}	

						// Insert the markup
						if($item && $item.length) {
							$item.replaceWith($markup);
						} else if($uploadBeforeItem && $uploadBeforeItem.length) {
							$uploadBeforeItem.before($markup); 
							$uploadBeforeItem = $markup;
						} else if(n === 0) {
							$progressItem.replaceWith($markup);
						} else {
							$fileList.append($markup);
						}

						// Show Markup
						$markup.fadeIn().css("display", "");
						$markup.addClass('InputfieldFileItemExisting');

						if($item && $item.length) $markup.effect('highlight', 500);
						if($progressItem.length) $progressItem.remove();
						
						if(uploadReplace.item && !singleMode) {
							// re-open replaced item
							$markup.find(".gridImage__edit").click();
							$markup.find(".InputfieldFileReplace").val(uploadReplace.file);
						}
				
						// reset uploadReplace data
						uploadReplace.file = '';
						uploadReplace.item = null
						uploadReplace.edit = null;
					}

					if(doneTimer) clearTimeout(doneTimer);
					$uploadBeforeItem = null;
					
					doneTimer = setTimeout(function() {
						if(maxFiles != 1) {
							setupSortable($fileList);
						} else {
							setupMagnificForSingle($fileList.closest('.Inputfield'));
						}
						$fileList.trigger('AjaxUploadDone'); // for things like fancybox that need to be re-init'd
					}, 500);

				}, false);
		
				// close editor, if open
				if(uploadReplace.edit) {
					uploadReplace.edit.find('.InputfieldImageEdit__close').click();
				}

				// Here we go
				xhr.open("POST", postUrl, true);
				xhr.setRequestHeader("X-FILENAME", encodeURIComponent(file.name));
				xhr.setRequestHeader("X-FIELDNAME", fieldName);
				xhr.setRequestHeader("Content-Type", "application/octet-stream"); // fix issue 96-Pete
				xhr.setRequestHeader("X-" + postTokenName, postTokenValue);
				xhr.setRequestHeader("X-REQUESTED-WITH", 'XMLHttpRequest');
				xhr.send(file);

				// Present file info and append it to the list of files
				if(uploadReplace.item) {
					uploadReplace.item.replaceWith($progressItem);
					uploadReplace.item = $progressItem;
				} else if($uploadBeforeItem && $uploadBeforeItem.length) {
					$uploadBeforeItem.before($progressItem); 
				} else {
					$fileList.append($progressItem);
				}
				updateGrid();
				var $inputfield = $fileList.closest('.Inputfield');
				$inputfield.addClass('InputfieldStateChanged');
				var numFiles = $inputfield.find('.InputfieldFileItem').length;
				if(numFiles == 1) {
					$inputfield.removeClass('InputfieldFileEmpty').removeClass('InputfieldFileMultiple').addClass('InputfieldFileSingle');
				} else if(numFiles > 1) {
					$inputfield.removeClass('InputfieldFileEmpty').removeClass('InputfieldFileSingle').addClass('InputfieldFileMultiple');
				}
			}

			/**
			 * Traverse files queued for upload
			 * 
			 * @param files
			 * 
			 */
			function traverseFiles(files) {

				var toKilobyte = function(i) {
					return parseInt(i / 1024, 10);
				};

				if(typeof files === "undefined") {
					fileList.innerHTML = "No support for the File API in this web browser";
					return;
				}

				for(var i = 0, l = files.length; i < l; i++) {

					var extension = files[i].name.split('.').pop().toLowerCase();
					var message;

					if(extensions.indexOf(extension) == -1) {
						message = extension + ' is a invalid file extension, please use one of:  ' + extensions;
						$errorParent.append(errorItem(message, files[i].name));

					} else if(files[i].size > maxFilesize && maxFilesize > 2000000) {
						// I do this test only if maxFilesize is at least 2M (php default). 
						// There might (not sure though) be some issues to get that value so don't want to overvalidate here -apeisa
						var filesizeKB = toKilobyte(files[i].size),
							maxFilesizeKB = toKilobyte(maxFilesize);

						message = 'Filesize ' + filesizeKB + ' kb is too big. Maximum allowed is ' + maxFilesizeKB + ' kb';
						$errorParent.append(errorItem(message, files[i].name));

					} else {
						uploadFile(files[i]);
					}
					if(maxFiles == 1) break;
				}
			}

			filesUpload.addEventListener("change", function(evt) {
				traverseFiles(this.files);
				evt.preventDefault();
				evt.stopPropagation();
				this.value = '';
			}, false);
			
		}

		/**
		 * Setup dropzone within an .InputfieldImageEdit panel so one can drag/drop new photo into existing image enlargement
		 * 
		 * This method populates the uploadReplace variable
		 * 
		 */
		function setupEnlargementDropzones() {
			var sel = ".InputfieldImageEdit__imagewrapper img";
			$(document).on("dragenter", sel, function() {
				var $this = $(this);	
				if($this.closest('.InputfieldImageMax1').length) return;
				var src = $this.attr('src');
				var $edit = $this.closest(".InputfieldImageEdit");
				var $parent = $this.closest(".InputfieldImageEdit__imagewrapper");
				$parent.addClass('InputfieldImageEdit__replace');
				uploadReplace.file = new String(src).substring(src.lastIndexOf('/') + 1);
				uploadReplace.item = $('#' + $edit.attr('data-for')); 
				uploadReplace.edit = $edit;
			}).on("dragleave", sel, function() {
				var $this = $(this);
				if($this.closest('.InputfieldImageMax1').length) return;
				var $parent = $this.closest(".InputfieldImageEdit__imagewrapper");
				$parent.removeClass('InputfieldImageEdit__replace');
				uploadReplace.file = '';
				uploadReplace.item = null;
				uploadReplace.edit = null;
			});
		}
		setupEnlargementDropzones();
		
	} // initUploadHTML5
	
	/**
	 * Initialize InputfieldImage
	 * 
	 */
	function init() {
		
		// initialize all grid images for sortable and render value mode (if applicable)
		$('.InputfieldImage.Inputfield').each(function() {
			initInputfield($(this));
		});

		initGridEvents()
	
		// Initialize Upload 
		if(useAjaxUpload()) {
			initUploadHTML5('');
		} else {
			initUploadOldSchool();
		}
		
		$(document).on('reloaded', '.InputfieldImage', function() {
			var $inputfield = $(this);
			initInputfield($inputfield);
			initUploadHTML5($inputfield);
		});

	}
	
	init();
}

jQuery(document).ready(function($) {
	InputfieldImage($);
});
