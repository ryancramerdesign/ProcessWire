
$(document).ready(function() {
	var fieldFilterFormChange = function() {
		$("#field_filter_form").submit();
	}; 
	$("#templates_id").change(fieldFilterFormChange); 
	$("#fieldtype").change(fieldFilterFormChange); 
	$("#show_system").click(fieldFilterFormChange); 

	// instantiate the WireTabs
        $("#ProcessFieldEdit").WireTabs({
                items: $(".Inputfields li.WireTab"),
                id: 'FieldEditTabs'
                });

});
