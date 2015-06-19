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
	refreshRowPageID: 0, // when set, only the row representing the given page ID will be updated during a refresh

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
		$(document).on('click', '.actions a.ajax', ProcessLister.actionClickAjax);
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
		
		if(ProcessLister.refreshRowPageID > 0) {
			submitData['row_page_id'] = ProcessLister.refreshRowPageID;
			ProcessLister.resetTotal = false;
		}

		$.ajax({
			url: url, 
			type: 'POST', 
			data: submitData, 
			success: function(data) {
				
				if(ProcessLister.refreshRowPageID) {
					// update one row
					var idAttr = "#page" + ProcessLister.refreshRowPageID;
					var $oldRow = $(idAttr).closest('tr'); 
					var $newRow = $(data).find(idAttr).closest('tr');
					var message = $oldRow.find(".actions_toggle").attr('data-message');
					if($oldRow.length && $newRow.length) {
						$oldRow.replaceWith($newRow);
						$newRow.effect('highlight', 'fast');	
						if(message) {
							var $message = $("<span class='row_message notes'>" + message + "</span>");
							$newRow.find(".actions_toggle").addClass('row_message_on').closest('td').append($message);
							setTimeout(function() {
								$message.fadeOut('normal', function() { 
									$newRow.find('.actions_toggle').removeClass('row_message_on').click(); 
								});
							}, 1000);
						} else {
							$newRow.find(".actions_toggle").addClass('open');
						}
					}
					ProcessLister.refreshRowPageID = 0;
					
				} else {
					// update entire table
					var sort = $("#lister_sort").val();
					ProcessLister.results.html(data).find("th").each(function () {
						var $b = $(this).find('b');
						var txt = $b.text();
						$b.remove();
						$(this).find('span').remove();
						var $icon = $(this).find('i');
						var label = $(this).text();
						if (txt == sort) {
							$(this).html("<u>" + label + "</u><span>&nbsp;&darr;</span><b>" + txt + "</b>");
						} else if (sort == '-' + txt) {
							$(this).html("<u>" + label + "</u><span>&nbsp;&uarr;</span><b>" + txt + "</b>");
						} else {
							$(this).html(label + "<b>" + txt + "</b>");
						}
						if ($icon.length > 0) $(this).prepend($icon);
					}).end().effect('highlight', 'fast');
				}
				
				if(ProcessLister.clickAfterRefresh.length > 0) {
					if(ProcessLister.clickAfterRefresh.indexOf('#') < 0 && ProcessLister.clickAfterRefresh.indexOf('.') < 0) {
						// assume ID attribute if no id or class indicated
						ProcessLister.clickAfterRefresh = '#' + ProcessLister.clickAfterRefresh;
					}
					$(ProcessLister.clickAfterRefresh).each(function() {
						var $a = $(this);
						$a.click();
						var $tr = $a.closest('tr');
						$tr.fadeTo(100, 0.1);
						setTimeout(function() { $tr.fadeTo(250, 1.0); }, 250);
					});
					ProcessLister.clickAfterRefresh = '';
				}
				
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

		var $toggle = $(this);
		if($toggle.hasClass('row_message_on')) return false;
		var $tr = $toggle.closest('tr'); 
		var $actions = $toggle.next('.actions'); 
		// var $icon = $toggle.children('i.fa').eq(0);

		if($tr.is('.open')) {
			$actions.hide();
			$tr.removeClass('open'); 
			/*
			if($icon.length) {
				var prev = $icon.data('class-prev');
				if(prev) $icon.attr('class', $icon.data('class-prev'));
					else $icon.remove();
			}
			*/
			return false;
		} else {
			$actions.css('display', 'inline-block');
			$tr.addClass('open');
			/*
			if($icon.length) {
				$icon.data('class-prev', $icon.attr('class'));
				$icon.attr('class', 'fa fa-fw fa-check-square-o');
			}
			*/
		}
		
		var $extraActions = $actions.find(".PageExtra").hide();
		var $extraTrigger = $actions.find(".PageExtras");
		if($("body").hasClass("AdminThemeDefault")) $extraTrigger.addClass('ui-priority-secondary');
		
		$extraTrigger.click(function() {
			var $t = $(this);
			var $defaultActions = $actions.find("a:not(.PageExtra):not(.PageExtras)");
			if($t.hasClass('extras-open')) {
				$extraActions.hide();
				$defaultActions.show();
				$t.removeClass('extras-open');
			} else {
				$defaultActions.hide();
				$extraActions.show();
				$t.addClass('extras-open');
			}
			$t.children('i.fa').toggleClass('fa-flip-horizontal');	
			return false;
		});

		return false; 
	},
	
	actionClickAjax: function() {
		
		var $a = $(this);
		var $toggle = $a.closest('td').find('.actions_toggle');
		var pageID = parseInt($toggle.attr('id').replace('page', ''));
		var $actions = $a.closest('.actions');
		var href = $a.attr('href');

		$actions.after("<i class='fa fa-spin fa-spinner ui-priority-secondary'></i>");
		$actions.hide();
		
		$.post(href, { ProcessPageLister: 1 }, function(data) {
			if(typeof data.page != "undefined" || data.action == 'trash') {
				// highlight page mentioned in json return value
				ProcessLister.clickAfterRefresh = '#page' + data.page;
				ProcessLister.resetTotal = true;
			} else {
				// highlight page where action was clicked
				ProcessLister.refreshRowPageID = pageID;
			}
			if(data.message) $toggle.attr('data-message', data.message);
			ProcessLister.submit();
		}, 'json');
		
		return false;
	}
};

$(document).ready(function() {
	ProcessLister.init();
}); 
