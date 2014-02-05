$(document).ready(function() {

	$("#select_parent_submit").hide();
	$("#select_parent_id").change(function() {
		val = $(this).val();
		if(val > 0) $("#select_parent_submit").click();
	});	

        var submitted = false;
        $("#ProcessPageAdd").submit(function() {
                if(submitted) return false;
                submitted = true;
        });
});
