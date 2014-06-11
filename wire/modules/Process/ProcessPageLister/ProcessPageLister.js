
var ProcessLister = {

	form: null,
	inInit: true,
	inTimeout: false, 
	lastVal: '', 
	spinner: null,
	numSubmits: 0, 

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

		ProcessLister.spinner = $("<li class='title' id='ProcessListerSpinner'><i class='fa fa-lg fa-spin fa-spinner'></i></li>"); 
		$("#breadcrumbs ul.nav").append(ProcessLister.spinner); 

		$("#ProcessListerFilters").change(function() {
			ProcessLister.submit();
		}); 

		$("#ProcessListerResults").on('click', 'th', ProcessLister.columnSort)

		$(document).on('click', 'a.actions_toggle', ProcessLister.pageClick); 
		$("#actions_items_open").attr('disabled', 'disabled').parent('label').addClass('ui-state-disabled'); 

		$(document).on('click', '.MarkupPagerNav a', function() {
			var url = $(this).attr('href'); 
			ProcessLister.submit(url); 
			return false; 
		}); 

		$("#submit_refresh").click(function() {
			ProcessLister.submit();
			$(this).fadeOut("normal", function() {
				$("#submit_refresh").removeClass('ui-state-active').fadeIn();
			}); 
			return false; 
		}); 

		$("#lister_columns").change(function() {
			ProcessLister.submit();
		}); 

		var $lister = $("#ProcessLister"); 
		$("#ProcessListerActionsForm").find('script').remove(); // to prevent from running twice after being WireTabbed
		if($lister.size() > 0) $lister.WireTabs({ items: $(".WireTab") });


		$("#_ProcessListerRefreshTab").html("<i class='fa fa-refresh ui-priority-secondary'></i>")
			.unbind('click')
			.click(function() {
				ProcessLister.submit();
				return false;
			});

		$("#_ProcessListerResetTab").html("<i class='fa fa-rotate-left ui-priority-secondary'></i>")
			.unbind('click')
			.click(function() {
				window.location.href = './?reset=1';
				return false;
			});

		$(document).on('click', 'a.PageEdit.modal', ProcessLister.clickForModal); 
		$(document).on('click', 'a.PageView.modal', ProcessLister.clickForModal); 

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

		$.ajax({
			url: url, 
			type: 'POST', 
			data: { 
				filters: $('#ProcessListerFilters').val(),
				columns: $('#lister_columns').val(), 
				sort: $('#lister_sort').val()
			}, 
			success: function(data) {
				var sort = $("#lister_sort").val();
				$("#ProcessListerResults").html(data).find("th").each(function() {
					var $b = $(this).find('b'); 
					var txt = $b.text();
					$b.remove();
					$(this).find('span').remove();
					var label = $(this).text();
					if(txt == sort) {
						$(this).html("<u>" + label + "</u><span>&nbsp;&darr;</span><b>" + txt + "</b>");
					} else if(sort == '-' + txt) {
						$(this).html("<u>" + label + "</u><span>&nbsp;&uarr;</span><b>" + txt + "</b>");
					} else {
						$(this).html(label + "<b>" + txt + "</b>");
					}
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
					$("a.actions_toggle.open").click().removeClass('open'); // auto open items corresponding to "open" get var
				}, 250); 
				
			},
			error: function(error) {
				$("#ProcessListerResults").html("<p>Error retrieving results: " + error + "</p>"); 
			}
		}); 
	},

	refreshLister: false, // true when lister should refresh after a dialog close
	clickAfterRefresh: '', // 'id' attribute of link to automatically click after a refresh

	clickForModal: function() {

		var $a = $(this);
		var isEditLink = $a.hasClass('PageEdit'); 
		var href = $a.attr('href'); 
		var url = href + (isEditLink ? '&modal=1' : '');
		var closeOnSave = true; 
		var $iframe = $('<iframe class="ListerDialog" frameborder="0" src="' + url + '"></iframe>');
		var windowWidth = $(window).width()-100;
		var windowHeight = isEditLink ? $(window).height()-220 : $(window).height()-160; 
		var dialogPageID = 0;

		if(isEditLink) ProcessLister.clickAfterRefresh = $a.parents('.actions').siblings('.actions_toggle').attr('id'); 

		var $dialog = $iframe.dialog({
			modal: true,
			height: windowHeight,
			width: windowWidth,
			position: [50,49],
			close: function(event, ui) {
				if(!ProcessLister.refreshLister) return;
				var $refresh = $("#ProcessListerResults .MarkupPagerNavOn a"); 
				if($refresh.size() == 0) $refresh = $("#submit_refresh"); 
				$refresh.click();
				ProcessLister.refreshLister = false; 
			}
		}).width(windowWidth).height(windowHeight);

		$iframe.load(function() {

			var buttons = []; 	
			//$dialog.dialog('option', 'buttons', {}); 
			var $icontents = $iframe.contents();
			var n = 0;
			var title = $icontents.find('title').text();

			dialogPageID = $icontents.find('#Inputfield_id').val(); // page ID that will get added if not already present

			// set the dialog window title
			$dialog.dialog('option', 'title', title); 
			
			if(!isEditLink) return;

			// hide things we don't need in a modal context
			//$icontents.find('#wrap_Inputfield_template, #wrap_template, #wrap_parent_id').hide();
			$icontents.find('#breadcrumbs ul.nav, #_ProcessPageEditChildren').hide();

			closeOnSave = $icontents.find('#ProcessPageAdd').size() == 0; 

			// copy buttons in iframe to dialog
			$icontents.find("#content form button.ui-button[type=submit]").each(function() {
				var $button = $(this); 
				var text = $button.text();
				var skip = false;
				// avoid duplicate buttons
				for(i = 0; i < buttons.length; i++) {
					if(buttons[i].text == text || text.length < 1) skip = true; 
				}
				if(!skip) {
					buttons[n] = {
						'text': text, 
						'class': ($button.is('.ui-priority-secondary') ? 'ui-priority-secondary' : ''), 
						'click': function() {
							$button.click();
							if(closeOnSave) setTimeout(function() { 
								ProcessLister.refreshLister = true; 
								$dialog.dialog('close'); 
							}, 500); 
							closeOnSave = true; // only let closeOnSave happen once
						}
					};
					n++;
				}; 
				$button.hide();
			}); 

			$icontents.find("#submit_delete").click(function() {
				ProcessLister.refreshLister = true; 
				setTimeout(function() {
					$dialog.dialog('close'); 
				}, 500); 
			}); 

			// cancel button
			/*
			buttons[n] = {
				'text': 'Cancel', 
				'class': 'ui-priority-secondary', 
				'click': function() {
					$dialog.dialog('close'); 
				}
			}; 
			*/

			if(buttons.length > 0) $dialog.dialog('option', 'buttons', buttons); 
			$dialog.width(windowWidth).height(windowHeight);
		}); 

		return false; 
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

		var $wrap_actions_items = $("#wrap_actions_items"); 
		var $counter = $("#lister_open_cnt"); 
		var $counter2 = $("#lister_open_cnt2"); 
		var $openItems = $("#ProcessListerResults").find("tr.open");
		var cnt = $openItems.size();

		if(!$counter2.size()) {
			$counter2 = $("<span id='lister_open_cnt2'></span>"); 
			$("#actions_items_open").after($counter2); 
		}

		$counter.find('span').text(cnt); 
		$counter2.html('&nbsp;' + cnt); 

		if(cnt > 0) {
			var ids = []; 
			$openItems.each(function(n) {
				var $a = $(this).find("a.actions_toggle"); 
				ids[n] = $a.attr('id').replace('page', ''); 
			}); 

			$counter.show();
			if($wrap_actions_items.hasClass('InputfieldStateCollapsed')) {
				$wrap_actions_items.removeClass('InputfieldStateCollapsed'); 
			}
			$("#actions_items_all").removeAttr('checked'); 
			$("#actions_items_open")
				.removeAttr('disabled')
				.attr('checked', 'checked')
				.val(ids.join(','))
				.parent('label')
					.removeClass('ui-state-disabled'); 
		} else {
			$counter.hide();
			$("#actions_items_open")
				.removeAttr('checked')
				.attr('disabled', 'disabled')
				.val('')
				.parent('label')
					.addClass('ui-state-disabled'); 
			$("#actions_items_all").attr('checked', 'checked'); 
			if(!$wrap_actions_items.hasClass('InputfieldStateCollapsed')) {
				$wrap_actions_items.addClass('InputfieldStateCollapsed'); 
			}
		}

		return false; 
	}
};

$(document).ready(function() {
	ProcessLister.init();
}); 
