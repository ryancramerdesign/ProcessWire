
var AdminDataTable = {
	
	ready: false,
	tables: [],
	
	isMobileSize: function() {
		return $(window).width() <= 767;
	},

	setupMobile: function($table) {
		if($table.hasClass('AdminDataTableMobile')) return;
		if(!$table.hasClass('AdminDataTableResponsive')) return;
		$table.addClass('AdminDataTableMobile');
		var labels = [];
		var thcolor = '';
		$table.children('thead').children('tr').each(function() {
			$(this).find("th").each(function(n) {
				var $th = $(this);
				if(!thcolor.length) thcolor = $th.css('color');
				if($th.children().length) {
					// th might have hidden parts that we don't want
					var $th2 = $th.clone();
					$th2.children().remove();
					if($th2.text().length) $th = $th2;
				}
				labels[n] = $th.text();
			});
		});
		$table.children('tbody').children('tr').each(function() {
			$(this).children('td').each(function(n) {
				var $td = $(this);
				if(typeof labels[n] == "undefined") return;
				var $th = $("<div class='th'></div>").append(labels[n]).css('color', thcolor); 
				if($td.children('.td').length == 0) $td.wrapInner("<div class='td'></div>");
				$td.prepend($th).addClass('ui-helper-clearfix');
				$th.css('line-height', $td.css('line-height'));
			});
		});
	},

	undoMobile: function($table) {
		if ($table.hasClass('AdminDataTableMobile')) {
			$table.removeClass('AdminDataTableMobile');
			$table.children('tbody').find("td").each(function () {
				var $td = $(this);
				$td.find(".th").remove();
				$td.find(".td").removeClass('td');
				$td.removeClass('ui-helper-clearfix');
			});
		}
	},
	
	resize: function() {
		var $tables = $("table.AdminDataTableResponsive");
		var isMobile = AdminDataTable.isMobileSize();
		$tables.each(function () {
			var $table = $(this);
			if (isMobile) {
				AdminDataTable.setupMobile($table);
			} else if ($table.hasClass('AdminDataTableMobile')) {
				AdminDataTable.undoMobile($table);
			}
		});
	},
	
	initTable: function($table) {
		if (AdminDataTable.ready) {
			if(AdminDataTable.isMobileSize()) AdminDataTable.setupMobile($table);
		} else {
			AdminDataTable.tables.push($table);
		}
	},
	
	init: function() {
		AdminDataTable.ready = true;
		$("table.AdminDataTableSortable").tablesorter();
		
		var resizeTimeout = null;
		$(window).resize(function() {
			if(resizeTimeout) clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(function() { AdminDataTable.resize() }, 500);
		});
		
		if(AdminDataTable.tables.length) {
			for (var n = 0; n < AdminDataTable.tables.length; n++) {
				AdminDataTable.initTable(AdminDataTable.tables[n]);
			}
		}
	}
}

$(document).ready(function() {
	AdminDataTable.init();
}); 
