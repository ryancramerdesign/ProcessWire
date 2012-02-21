
$(document).ready(function() {
	var fieldFilterFormChange = function() {
		$("#field_filter_form").submit();
	}; 
	$("#templates_id").change(fieldFilterFormChange); 
	$("#fieldtype").change(fieldFilterFormChange); 
	$("#show_system").click(fieldFilterFormChange); 

	// setup the column width slider
	var $columnWidth = $("#columnWidth"); 
	if($columnWidth.size() > 0) { 
		var $slider = $("<div id='columnWidthSlider'></div>");
		var columnWidthVal = parseInt($("#columnWidth").val());
		$columnWidth.val(columnWidthVal + '%'); 
		$columnWidth.after($slider);
		$slider.slider({
			range: 'min',
			min: 10,
			max: 100,
			value: parseInt($columnWidth.val()),
			slide: function(e, ui) {
				$columnWidth.val(ui.value + '%'); 
			}
		});
	}

	// instantiate the WireTabs
	var $fieldEdit = $("#ProcessFieldEdit"); 
	$fieldEdit.find('script').remove();
        $fieldEdit.WireTabs({
                items: $(".Inputfields li.WireTab"),
                id: 'FieldEditTabs'
                });

});
