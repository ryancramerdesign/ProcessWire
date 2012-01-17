
$(document).ready(function() {

        var options = {
		selectStartLabel: config.ProcessPageEditLink.selectStartLabel
        };

	var selectedPageData = {
		id: 0,
		title: '', 
		url: ''
	};

	var $fileSelect = $("#link_page_file"); 

	function populateFileSelect(selectedPageData) {
		// populate the files field
		var $wrap = $("#wrap_link_page_file"); 
		$.getJSON("./files?id=" + selectedPageData.id, function(data) {
			$fileSelect.empty();	
			$fileSelect.append("<option></option>"); 
			$.each(data, function(key, val) {
				var $option = $("<option value='" + key + "'>" + val + "</option>"); 		
				$fileSelect.append($option);
			});
			$wrap.find("p.notes").text(selectedPageData.url);
			$wrap.children().effect('highlight', {}, 500); 
			$fileSelect.effect('bounce', {}, 50);
		}); 
	}

        $("#link_page_id").ProcessPageList(options).hide()
		.bind('pageSelected', function(event, data) {
			if(data.url.length) {
				selectedPageData = data; 
				selectedPageData.url = config.urls.root + data.url.substring(1); 
				$("#link_page_url").val(selectedPageData.url); 
				if($fileSelect.is(":visible")) populateFileSelect(selectedPageData);
			}
		}); 

	$fileSelect.change(function() {
		var $t = $(this);
		var src = $t.val();
		if(src.length) $("#link_page_url").val(src); 
	}); 

	$("#link_page_url").focus();


}); 
