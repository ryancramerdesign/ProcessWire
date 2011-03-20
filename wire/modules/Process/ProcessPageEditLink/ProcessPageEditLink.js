
$(document).ready(function() {

        var options = {
		selectStartLabel: 'choose page'
        };

	var selectedPageData = {
		id: 0,
		title: '', 
		url: ''
	};

        $("#link_page_id").ProcessPageList(options).hide()
		.bind('pageSelected', function(event, data) {
			if(data.url.length) {
				selectedPageData = data; 
				selectedPageData.url = config.urls.root + data.url.substring(1); 
				$("#link_page_url").val(selectedPageData.url); 
			}
		}); 

	$("#link_page_url").focus();


}); 
