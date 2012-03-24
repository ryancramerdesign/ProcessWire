
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
		// enables columnWidth to be populated in ProcessTemplate's asmSelect status field
		$columnWidth.addClass('asmListItemStatus');
	
		// update the slider if the columnWidth field is changed manually	
		$columnWidth.change(function() {
			var val = parseInt($(this).val());
			if(val > 100) val = 100; 
			if(val < 10) val = 10; 
			$(this).val(val + '%');
			$slider.slider('option', 'value', val); 
		}); 
	}

	// instantiate the WireTabs
	var $fieldEdit = $("#ProcessFieldEdit"); 
	$fieldEdit.find('script').remove();
        $fieldEdit.WireTabs({
                items: $(".Inputfields li.WireTab"),
                id: 'FieldEditTabs',
		skipRememberTabIDs: ['delete']
                });

	// change fieldgroup context
	$("#fieldgroupContextSelect").change(function() {
		var field_id = $("#Inputfield_id").val();	
		var fieldgroup_id = $(this).val();
		var href = './edit?id=' + field_id;
		if(fieldgroup_id > 0)  href += '&fieldgroup_id=' + fieldgroup_id;
		window.location = href; 
	});

});
