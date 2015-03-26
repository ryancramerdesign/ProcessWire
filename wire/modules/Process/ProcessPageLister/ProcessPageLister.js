var ProcessLister = {

	form: null,
	inInit: true,
	inTimeout: false, 
	lastVal: '', 
	spinner: null,
	numSubmits: 0, 
	results: null,
	filters: null,
	lister: null,
	initialized: false,
	resetTotal: false, 
	clickAfterRefresh: '', // 'id' attribute of link to automatically click after a refresh

	columnSort: function() {
		$(this).find("span").remove();
		var name = $(this).find('b').text();
		var val = $("#lister_sort").val();

		if(val == name) name = '-' + name; // reverse
		if(name.length < 1) name = val;
		$("#lister_sort").val(name); 

		ProcessLister.submit();
	}, 

	init: function() {
		if(ProcessLister.initialized) return;
		ProcessLister.initialized = true;

		ProcessLister.spinner = $("<li class='title' id='ProcessListerSpinner'><i class='fa fa-lg fa-spin fa-spinner'></i></li>"); 
		$("#breadcrumbs ul.nav").append(ProcessLister.spinner); 
		
		ProcessLister.filters = $("#ProcessListerFilters"); 
		ProcessLister.results = $("#ProcessListerResults");
		ProcessLister.lister = $("#ProcessLister"); 

		ProcessLister.filters.change(function() { ProcessLister.submit(); }); 
		ProcessLister.results.on('click', 'th', ProcessLister.columnSort)

		$(document).on('click', 'a.actions_toggle', ProcessLister.pageClick); 
		$("#actions_items_open").attr('disabled', 'disabled').parent('label').addClass('ui-state-disabled'); 

		$(document).on('click', '.MarkupPagerNav a', function() {
			var url = $(this).attr('href'); 
			ProcessLister.submit(url); 
			return false; 
		}); 

		$("#submit_refresh").click(function() {
			ProcessLister.resetTotal = true; 
			ProcessLister.submit();
			$(this).fadeOut("normal", function() {
				$("#submit_refresh").removeClass('ui-state-active').fadeIn();
			}); 
			return false; 
		}); 

		$("#lister_columns").change(function() {
			ProcessLister.submit();
		}); 

		$("#ProcessListerActionsForm").find('script').remove(); // to prevent from running twice after being WireTabbed
		if(ProcessLister.lister.size() > 0) ProcessLister.lister.WireTabs({ items: $(".WireTab") });


		$("#_ProcessListerRefreshTab").html("<i class='fa fa-refresh ui-priority-secondary'></i>")
			.unbind('click')
			.click(function() {
				ProcessLister.resetTotal = true; 
				ProcessLister.submit();
				return false;
			});

		$("#_ProcessListerResetTab").html("<i class='fa fa-rotate-left ui-priority-secondary'></i>")
			.unbind('click')
			.click(function() {
				window.location.href = './?reset=1';
				return false;
			});

		ProcessLister.inInit = false; 
		// if no change events occurred during init, go ahead and submit it now
		if(ProcessLister.numSubmits == 0) ProcessLister.submit();
			else ProcessLister.spinner.fadeOut();
	},

	submit: function(url) {
		if(ProcessLister.inTimeout) clearTimeout(ProcessLister.inTimeout); 
		ProcessLister.inTimeout = setTimeout(function() {
			ProcessLister._submit(url); 
		}, 250); 
	}, 

	_submit: function(url) {

		ProcessLister.numSubmits++;
		if(typeof url == "undefined") var url = "./";

		ProcessLister.spinner.fadeIn('fast'); 
		
		var submitData = {
			filters: ProcessLister.filters.val(),
			columns: $('#lister_columns').val(),
			sort: $('#lister_sort').val()
		};
		
		if(ProcessLister.resetTotal) {
			submitData['reset_total'] = 1;
			ProcessLister.resetTotal = false;
		}

		$.ajax({
			url: url, 
			type: 'POST', 
			data: submitData, 
			success: function(data) {
				var sort = $("#lister_sort").val();
				ProcessLister.results.html(data).find("th").each(function() {
					var $b = $(this).find('b'); 
					var txt = $b.text();
					$b.remove();
					$(this).find('span').remove();
					var $icon = $(this).find('i');
					var label = $(this).text();
					if(txt == sort) {
						$(this).html("<u>" + label + "</u><span>&nbsp;&darr;</span><b>" + txt + "</b>");
					} else if(sort == '-' + txt) {
						$(this).html("<u>" + label + "</u><span>&nbsp;&uarr;</span><b>" + txt + "</b>");
					} else {
						$(this).html(label + "<b>" + txt + "</b>");
					}
					if($icon.length > 0) $(this).prepend($icon);
					if(ProcessLister.clickAfterRefresh.length > 0) {
						var $a = $('#' + ProcessLister.clickAfterRefresh).click(); 
						ProcessLister.clickAfterRefresh = '';
						var $tr = $a.closest('tr'); 
						$tr.fadeTo(100, 0.1); 
						setTimeout(function() { $tr.fadeTo(250, 1.0); }, 250); 
					}
				}).end().effect('highlight', 'fast'); 
				ProcessLister.spinner.fadeOut(); 
				
				setTimeout(function() {
					ProcessLister.results.trigger('loaded'); 
					$("a.actions_toggle.open").click().removeClass('open'); // auto open items corresponding to "open" get var
				}, 250); 
				
			},
			error: function(error) {
				ProcessLister.results.html("<p>Error retrieving results: " + error + "</p>"); 
			}
		}); 
	},

	pageClick: function() {

		var $tr = $(this).closest('tr'); 
		var $actions = $(this).next('.actions'); 

		if($tr.is('.open')) {
			$actions.hide();
			$tr.removeClass('open'); 
		} else {
			$actions.css('display', 'inline-block')
			$tr.addClass('open'); 
			
		}

		return false; 
	}
};

$(document).ready(function() {
	ProcessLister.init();
}); 
