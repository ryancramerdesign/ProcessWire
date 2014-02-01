

$(document).ready(function() {

	var options = {
		selectStartLabel: config.ProcessPageEditLink.selectStartLabel,
		langID: config.ProcessPageEditLink.langID
		// openPageIDs: config.ProcessPageEditLink.openPageIDs
		};
	var options2 = {
		selectStartLabel: options.selectStartLabel,
		langID: options.langID,
		rootPageID: config.ProcessPageEditLink.pageID
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

	function absoluteToRelativePath(path) {
		if(config.ProcessPageEditLink.urlType == 0) return path;

		function slashesToRelative(url) {
			url = url.replace(/\//g, '../'); 
			url = url.replace(/[^.\/]/g, ''); 
			return url;
		}

		if(path === config.ProcessPageEditLink.pageUrl) {
			// account for the link to self
			path = './'; 
			if(!config.ProcessPageEditLink.slashUrls) path += config.ProcessPageEditLink.pageName;

		} else if(path.indexOf(config.ProcessPageEditLink.pageUrl) === 0) { 
			// linking to child of current page
			path = path.substring(config.ProcessPageEditLink.pageUrl.length); 
			if(!config.ProcessPageEditLink.slashUrls) path = config.ProcessPageEditLink.pageName + path;

		} else if(config.ProcessPageEditLink.pageUrl.indexOf(path) === 0) {
			// linking to a parent of the current page
			var url = config.ProcessPageEditLink.pageUrl.substring(path.length); 
			if(url.indexOf('/') != -1) {
				url = slashesToRelative(url); 
			} else {
				url = './';
			}
			path = url;
		} else if(path.indexOf(config.ProcessPageEditLink.rootParentUrl) === 0) {
			// linking to a sibling or other page in same branch (but not a child)
			var url = path.substring(config.ProcessPageEditLink.rootParentUrl.length); 
			var url2 = url;
			url = slashesToRelative(url) + url2; 	
			path = url;
			
		} else if(config.ProcessPageEditLink.urlType == 2) { // 2=relative for all
			// page in a different tree than current
			// traverse back to root
			var url = config.ProcessPageEditLink.pageUrl.substring(config.urls.root.length); 
			url = slashesToRelative(url); 
			path = path.substring(config.urls.root.length); 
			path = url + path; 
		}
		return path; 
	}

	function pageSelected(event, data) {

		if(data.url && data.url.length) {
			selectedPageData = data;
			selectedPageData.url = config.urls.root + data.url.substring(1);
			selectedPageData.url = absoluteToRelativePath(selectedPageData.url); 
			$("#link_page_url").val(selectedPageData.url);
			if($fileSelect.is(":visible")) populateFileSelect(selectedPageData);
		}

		$(this).parents(".InputfieldInteger").children(".InputfieldHeader").click() // to close the field
			.parent().find('.PageListSelectHeader').removeClass('hidden').show(); // to open the pagelist select header so it can be re-used if the field is opened again
		
	}
	
	$("#link_page_id").ProcessPageList(options).hide().bind('pageSelected', pageSelected);
	$("#child_page_id").ProcessPageList(options2).hide().bind('pageSelected', pageSelected); 

	$fileSelect.change(function() {
		var $t = $(this);
		var src = $t.val();
		if(src.length) $("#link_page_url").val(src); 
	}); 

	$("#link_page_url").focus();

	// when header is clicked, open up the pageList right away
	$(".InputfieldInteger .InputfieldHeader").click(function() {

		var $t = $(this);
		var $toggle = $t.parent().find(".PageListSelectActionToggle");
		var $pageSelectHeader = $toggle.parents('.PageListSelectHeader'); 

		if($pageSelectHeader.is(".hidden")) {
			// we previously hid the pageSelectHeader since it's not necessary in this context
			// so, we can assume the field is already open, and is now being closed
			return true; 
		}

		// hide the pageSelectHeader since it's extra visual baggage here we don't need
		$pageSelectHeader.addClass('hidden').hide();

		// automatically open the PageListSelect
		setTimeout(function() { $toggle.click(); }, 250); 
		return true; 
	}); 


}); 
