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

		$("#selected_image_description").focus(function() {
			$(this).siblings('label').hide();
		}).blur(function() {
			if($(this).val().length < 1) $(this).siblings('label').show();
		}).change(function() {
			if($(this).val().length < 1) $(this).siblings('label').show();
				else $(this).siblings('label').hide();
		}).change();

		populateResizeDimensions();
	}; 

	var $img = $("#selected_image"); 

	if($img.size() > 0) {
		$img = $img.first();

		if($img.width() > 0) {
			setupImage($img); 
		} else {
			$img.load(function() {
				$img = $(this); 
				setupImage($img); 
			}); 
		}

	}

}); 
