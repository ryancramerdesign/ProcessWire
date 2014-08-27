$(document).ready(function() {

	var $page_id = $("#page_id"); 
	var page_id = $page_id.val();

	$page_id.bind("pageSelected", function(event, data) {
		if(data.id == page_id) return;
		window.location = "./?id=" + data.id + "&modal=1";	
	}); 


	function setupImage($img) {

		var originalWidth = $img.width();
		var maxWidth = $("#input_width").attr('max'); 
		var maxHeight = $("#input_height").attr('max');
		
		function populateResizeDimensions() {
			var w = $img.width();
			var h = $img.height();
			
			if(h >= maxHeight || w >= maxWidth) {
				w = maxWidth;
				h = maxHeight;
				$("#selected_image_link").removeAttr('checked'); 
				$("#wrap_link_original").hide();
			} else {
				if(!$("#wrap_link_original").is(":visible")) {
					$("#wrap_link_original").fadeIn();
					if($("#wrap_link_original").attr('data-was-checked') == 1) {
						$("#wrap_link_original").attr('checked', 'checked'); 
					}
				}
			}

			$("#input_width").val(w); 
			$("#input_height").val(h); 
			
			$img.attr('width', w); 
			$img.attr('height', h); 
		}

		function setupImageResizable() { 
			$img.resizable({
				aspectRatio: true,
				maxWidth: maxWidth, 
				maxHeight: maxHeight,
				stop: function() {
					$img.attr('width', $img.width()).attr('height', $img.height()); 
					if(originalWidth != $img.width()) $img.addClass('resized'); 
				},
				resize: populateResizeDimensions
			});
		}
		setupImageResizable();

		var inputPixelsChange = function() {

			var w, h; 

			if($(this).attr('id') == 'input_width') { 
				w = parseInt($(this).val());
				h = (w / $img.attr('width')) * $img.attr('height'); 
			} else {
				h = parseInt($(this).val()); 
				w = (h / $img.attr('height')) * $img.attr('width'); 
			}

			w = Math.floor(w);
			h = Math.floor(h);
			
			if(w < 1 || h < 1 || w == $img.attr('width') || h == $img.attr('height') || w > maxWidth || h > maxHeight) {
				
				$("#input_width").val($img.attr('width')); 
				$("#input_height").val($img.attr('height')); 
				return false;
			}

			$img.resizable("destroy"); 
			$("#input_height").val(h); 
			$img.width(w).height(h).attr('width', w).attr('height', h);  
			$img.addClass('resized'); 
			populateResizeDimensions();
			setupImageResizable();
		};
	
		$("#selected_image_settings .input_pixels").change(inputPixelsChange); 

		$("#selected_image_class").change(function() {
			var resized = $img.is(".resized"); 
			$img.attr('class', $(this).val()); 	
			if(resized) $img.addClass('resized'); 
		});

		populateResizeDimensions();
	}; 

	var $img = $("#selected_image"); 

	if($img.size() > 0) {
		$img = $img.first();

		if($img.width() > 0 && $img.height() > 0) {
			setupImage($img); 
		} else {
			$img.load(function() {
				$img = $(this); 
				setupImage($img); 
			}); 
		}

	}

}); 
