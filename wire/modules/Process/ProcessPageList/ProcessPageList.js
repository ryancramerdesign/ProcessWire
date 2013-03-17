
/**
 * ProcessWire Page List Process, JQuery Plugin
 *
 * Provides the Javascript/jQuery implementation of the PageList process when used with the JSON renderer
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

$(document).ready(function() {
	if(config.ProcessPageList) $('#' + config.ProcessPageList.containerID).ProcessPageList(config.ProcessPageList); 
}); 

(function($) {

        $.fn.ProcessPageList = function(customOptions) {

		/**
	 	 * List of options that may be passed to the plugin
		 *
		 */
		var options = {

			// mode: 'select' or 'actions', currently this is automatically determined based on the element the PageList is attached to
			mode: '',		

			// default number of pages to show before pagination
			limit: 35,		

			// the page ID that starts the list
			rootPageID: 0,			 

			// show the page identified by 'rootPageID' ?	
			showRootPage: true,

			// the page ID currently selected
			selectedPageID: 0, 

			// in 'select' mode, allow no value to be selected (to abort a selected value)
			selectAllowUnselect: false,

			// show the 'currently selected' page header? (should be false on multi-selection)
			selectShowPageHeader: true, 

			// show the parent path in the selected page label?
			selectShowPath: true, 

			// the label to click on to change the currently selected page
			selectStartLabel: 'Change', 

			// the label to click on to cancel selecting a page
			selectCancelLabel: 'Cancel',

			// the label to click on to select a given page
			selectSelectLabel: 'Select',

			// the label to click on to unselect a selected page
			selectUnselectLabel: 'Unselect',

			// label used for 'more' in paginated lists
			moreLabel: 'More', 

			// label used for the move instruction
			moveInstructionLabel: "Click and drag to move", 

			// href attribute of 'select' link
			selectSelectHref: '#', 

			// href attribute of 'unselect' link
			selectUnselectHref: '#',
	
			// URL where page lists are loaded from 	
			ajaxURL: config.urls.admin + 'page/list/', 	

			// URL where page move's should be posted
			ajaxMoveURL: config.urls.admin + 'page/sort/',

			// pagination number that you want to open (to correspond with openPageIDs)
			openPagination: 0, 

			// ID sof the pages that we want to automatically open (default none) 
			openPageIDs: []
		}; 

		$.extend(options, customOptions);

		return this.each(function(index) {

			var $container = $(this); 
			var $root; 
			var $loading = $("<span class='PageListLoading'></span>");
			var firstPagination = 0; // used internally by the getPaginationList() function
			var curPagination = 0; // current page number used by getPaginationList() function
			var ignoreClicks = false; // true when operations are occurring where we want to ignore clicks

			/**
	 		 * Initialize the Page List
			 *
			 */
			function init() {

				$root = $("<div class='PageListRoot'></div>"); 
				
				if($container.is(":input")) {
					options.selectedPageID = $container.val();
					if(!options.selectedPageID.length) options.selectedPageID = 0;
					options.mode = 'select';
					$container.before($root); 
					setupSelectMode();
				} else {
					options.mode = 'actions'; 
					$container.append($root); 
					loadChildren(options.rootPageID > 0 ? options.rootPageID : 1, $root, 0, true); 
				}

			}

			/**
	 		 * Sets up a mode where the user is given a "select" link for each page, rather than a list of actions
			 * 
			 * When they hit "select" the list collapses and the selected page ID is populated into an input
			 *
			 */
			function setupSelectMode() {

				var $actions = $("<ul></ul>").addClass('PageListActions PageListSelectActions actions'); 
				var $pageLabel = $("<p></p>").addClass("PageListSelectName"); 
				if(options.selectShowPageHeader) $pageLabel.append($loading); 

				var $action = $("<a></a>").addClass("PageListSelectActionToggle").attr('href', '#').text(options.selectStartLabel).click(function() {

					if($(this).text() == options.selectStartLabel) {

						loadChildren(options.rootPageID > 0 ? options.rootPageID : 1, $root, 0, true); 
						$(this).text(options.selectCancelLabel); 

					} else {
						$root.children(".PageList").slideUp("fast", function() {
							$(this).remove();
						}); 
						$(this).text(options.selectStartLabel); 
					}
					return false; 
				}); 

				$actions.append($("<li></li>").append($action)); 

				$root.append($("<div></div>").addClass('PageListSelectHeader').append($pageLabel).append($actions)); 

				if(options.selectShowPageHeader) { 
					$.getJSON(options.ajaxURL + "?id=" + options.selectedPageID + "&render=JSON&start=0&limit=0", function(data) {
						var parentPath = '';
						if(options.selectShowPath) {
							parentPath = data.page.path;
							parentPath = parentPath.substring(0, parentPath.length-1); 
							parentPath = parentPath.substring(0, parentPath.lastIndexOf('/')+1); 
							parentPath = '<span class="detail">' + parentPath + '</span> ';
						} 
						var label = options.selectedPageID > 0 ? parentPath + data.page.label : '';
						$root.children(".PageListSelectHeader").find(".PageListSelectName").html(label); 
					}); 
				}
			}

			/**
			 * Method that is triggered when the processChildren() method completes
			 *
			 */
			function loaded() {
				ignoreClicks = false;
			}

			/**
			 * Handles pagination of PageList items
			 *
			 * @param int id ID of the page having children to show
			 * @param start Index that we are starting with in the current list
		 	 * @param int limit The limit being applied to the list (items per page)
			 * @param int total The total number of items in the list (excluding any limits)
			 * @return jQuery $list The pagination list ready for insertion
			 *
			 */
			function getPaginationList(id, start, limit, total) {

				// console.log(start + ", " + limit + ", " + total); 

				var maxPaginationLinks = 9; 
				var numPaginations = Math.ceil(total / limit); 
				curPagination = start >= limit ? Math.floor(start / limit) : 0;

				if(curPagination == 0) {		
					firstPagination = 0; 
				
				} else if((curPagination-maxPaginationLinks+1) > firstPagination) {
					firstPagination = curPagination - Math.floor(maxPaginationLinks / 2); 

				} else if(firstPagination > 0 && curPagination == firstPagination) {
					firstPagination = curPagination - Math.ceil(maxPaginationLinks / 2); 
				}


				// if we're on the last page of pagination links, then make the firstPagination static at the end
				if(firstPagination > numPaginations - maxPaginationLinks) firstPagination = numPaginations - maxPaginationLinks; 

				if(firstPagination < 0) firstPagination = 0;

				var $list = $("<ul></ul>").addClass("PageListPagination").data('paginationInfo', {
					start: start,
					limit: limit,
					total: total
				}); 

				/**
				 * paginationClick is the event function called when an item in the pagination nav is clicked
				 *
				 * It loads the new pages (via loadChildren) and then replaces the old pageList with the new one
				 *
				 */
				var paginationClick = function(e) {
					var $curList = $(this).parents("ul.PageListPagination");
					var info = $curList.data('paginationInfo'); 
					if(!info) return false;
					var $newList = getPaginationList(id, parseInt($(this).attr('href')) * info.limit, info.limit, info.total);
					var $loading = $("<li class='PageListLoading'></li>"); 
					$curList.siblings(".PageList").remove(); // remove any open lists below current
					$curList.replaceWith($newList); 
					$newList.append($loading); 
					var $siblings = $newList.siblings().css('opacity', 0.5);
					loadChildren(id, $newList.parent(), $(this).attr('href') * info.limit, false, false, true, function() {
						$loading.remove();
					}); 
					return false;	
				}
		
				var $separator = null;
				var $blankItem = null;
	
				for(var pagination = firstPagination, cnt = 0; pagination < numPaginations; pagination++, cnt++) {

					var $a = $("<a></a>").html(pagination+1).attr('href', pagination).addClass('ui-state-default'); 
					var $item = $("<li></li>").addClass('PageListPagination' + cnt).append($a); // .addClass('ui-state-default');

					if(pagination == curPagination) {
						//$item.addClass("PageListPaginationCurrent ui-state-focus"); 
						$item.addClass("PageListPaginationCurrent").find("a").removeClass('ui-state-default').addClass("ui-state-active"); 
					}

					$list.append($item); 

					if(!$blankItem) {
						$blankItem = $item.clone().removeClass('PageListPaginationCurrent ui-state-active'); 
						$blankItem.find('a').removeClass('ui-state-active').addClass('ui-state-default');  
					}
					// if(!$blankItem) $blankItem = $item.clone().removeClass('PageListPaginationCurrent').find('a').removeClass('ui-state-focus').addClass('ui-state-default'); 
					if(!$separator) $separator = $blankItem.clone().removeClass('ui-state-default').html("&hellip;"); 
					//if(!$separator) $separator = $blankItem.clone().html("&hellip;"); 

					if(cnt >= maxPaginationLinks && pagination < numPaginations) {
						$lastItem = $blankItem.clone();
						$lastItem.find("a").text(numPaginations).attr('href', numPaginations-1);
						$list.append($separator.clone()).append($lastItem); 
						break;
					} 
				}


				if(firstPagination > 0) {
					$firstItem = $blankItem.clone();
					$firstItem.find("a").text("1").attr('href', '0').click(paginationClick); 
					$list.prepend($separator.clone()).prepend($firstItem); 
				}

				//if(curPagination+1 < maxPaginationLinks && curPagination+1 < numPaginations) {
				if(curPagination+1 < numPaginations) {
					$nextBtn = $blankItem.clone();
					$nextBtn.find("a").html("&gt;").attr('href', curPagination+1).addClass('ui-priority-secondary'); 
					$list.append($nextBtn);
				}

				if(curPagination > 0) {
					$prevBtn = $blankItem.clone();
					$prevBtn.find("a").attr('href', curPagination-1).html("&lt;").addClass('ui-priority-secondary');
					$list.prepend($prevBtn); 
				}

				$list.find("a").click(paginationClick)
					.hover(function() { 
						$(this).addClass('ui-state-hover'); 
					}, function() { 
						$(this).removeClass("ui-state-hover"); 
					}); 

				return $list;
			}

			/**
	 		 * Load children via ajax call, attach them to $target and show. 
			 *
			 * @param int id ID of the page having children to show
			 * @param jQuery $target Item to attach children to
		 	 * @param int start If not starting from first item, num of item to start with
			 * @param bool beginList Set to true if this is the first call to create the list
			 * @param bool replace Should any existing list be replaced (true) or appended (false)
			 * @param bool pagination Set to false if you don't want pagination, otherwise leave it out
			 *
			 */
			function loadChildren(id, $target, start, beginList, pagination, replace, callback) {

				if(pagination == undefined) pagination = true; 
				if(replace == undefined) replace = false;

				var processChildren = function(data) {

					if(data && data.error) {
						alert(data.message); 
						$loading.hide();
						ignoreClicks = false;
						return; 
					}

					var $children = listChildren($(data.children)); 
					var nextStart = data.start + data.limit; 

					if(data.page.numChildren > nextStart) {
						var $a = $("<a></a>").attr('href', nextStart).data('pageId', id).text(options.moreLabel).click(clickMore); 
						$children.append($("<ul></ul>").addClass('PageListActions actions').append($("<li></li>").addClass('PageListActionMore').append($a)));
						if(pagination) {
							$children.prepend(getPaginationList(id, data.start, data.limit, data.page.numChildren));
						}


					}

					$children.hide();

					if(beginList) {
						var $listRoot; 
						$listRoot = listChildren($(data.page)); 
						if(options.showRootPage) $listRoot.children(".PageListItem").addClass("PageListItemOpen"); 
							else $listRoot.children('.PageListItem').hide().parent('.PageList').addClass('PageListRootHidden'); 
						$listRoot.append($children); 
						$target.append($listRoot);

					} else if($target.is(".PageList")) {
					
						var $newChildren = $children.children(".PageListItem, .PageListActions"); 
						if(replace) $target.children(".PageListItem, .PageListActions").replaceWith($newChildren); 
							else $target.append($newChildren); 

					} else {
						$target.after($children); 
					}

					$loading.hide();

					if(replace) {
						$children.show();
						loaded();
						if(callback != undefined) callback();
					} else { 
						$children.slideDown("fast", function() {
							loaded();
							if(callback != undefined) callback();
						}); 
					}

					// if a pagination is requested to be opened, and it exists, then open it
					if(options.openPagination > 0) {
						var $a = $(".PageListPagination" + options.openPagination + ">a");
						if($a.size() > 0) {
							$a.click();	
							options.openPagination = 0;
						}
					}

				}; 

				if(!replace) $target.append($loading.show()); 
				$.getJSON(options.ajaxURL + "?id=" + id + "&render=JSON&start=" + start + "&open=" + options.openPageIDs[0], processChildren); 
			}

			/**
			 * Given a list of pages, generates a list of them
			 *
			 * @param jQuery $children
			 *
			 */ 
			function listChildren($children) {

				var $list = $("<div></div>").addClass("PageList");
				var $ul = $list;

				$children.each(function(n, child) {
					$ul.append(listChild(child)); 
				}); 	

				$("a.PageListPage", $ul).click(clickChild); 
				$(".PageListActionMove a", $ul).click(clickMove); 
				$(".PageListActionSelect a", $ul).click(clickSelect); 
				$(".PageListTriggerOpen a.PageListPage", $ul).click();

				return $list; 
			}

			/**
			 * Given a single page, generates the list item for it
			 *
			 * @param map child
			 *
			 */
			function listChild(child) {

				var $li = $("<div></div>").data('pageId', child.id).addClass('PageListItem').addClass('PageListTemplate_' + child.template); 
				var $a = $("<a></a>")
					.attr('href', '#')
					.attr('title', child.path)
					.html(child.label)
					.addClass('PageListPage label'); 

				$li.addClass('PageListID' + child.id); 
				if(child.status == 0) $li.addClass('PageListStatusOff disabled');
				if(child.status & 2048) $li.addClass('PageListStatusUnpublished secondary'); 
				if(child.status & 1024) $li.addClass('PageListStatusHidden secondary'); 
				if(child.status & 16) $li.addClass('PageListStatusSystem'); 
				if(child.status & 8) $li.addClass('PageListStatusSystem'); 
				if(child.status & 4) $li.addClass('PageListStatusLocked'); 
				if(child.addClass && child.addClass.length) $li.addClass(child.addClass); 
				if(child.type && child.type.length > 0) if(child.type == 'System') $li.addClass('PageListStatusSystem'); 

				$(options.openPageIDs).each(function(n, id) {
					if(child.id == id) $li.addClass('PageListTriggerOpen'); 
				}); 

				$li.append($a); 
				var $numChildren = $("<span>" + (child.numChildren ? child.numChildren : '') + "</span>").addClass('PageListNumChildren detail'); 
				$li.append($numChildren); 
		
				if(child.note && child.note.length) $li.append($("<span>" + child.note + "</span>").addClass('PageListNote detail')); 	
				
				var $actions = $("<ul></ul>").addClass('PageListActions actions'); 
				var links = options.rootPageID == child.id ? [] : [{ name: options.selectSelectLabel, url: options.selectSelectHref }]; 
				if(options.mode == 'actions') {
					links = child.actions; 
				} else if(options.selectAllowUnselect) {
					if(child.id == $container.val()) links = [{ name: options.selectUnselectLabel, url: options.selectUnselectHref }]; 
				}

				$(links).each(function(n, action) {
					if(action.name == options.selectSelectLabel) actionName = 'Select';
						else if(action.name == options.selectUnselectLabel) actionName = 'Select'; 
						else actionName = action.cn; // cn = className

					var $a = $("<a></a>").text(action.name).attr('href', action.url); 
					$actions.append($("<li></li>").addClass('PageListAction' + actionName).append($a)); 
				}); 

				$li.append($actions); 
				return $li;
			}

			/**
			 * Event called when a page label is clicked on
			 *
			 * @param event e
			 *
			 */
			function clickChild(e) {

				var $t = $(this); 
				var $li = $t.parent('.PageListItem'); 
				var id = $li.data('pageId');

				if(ignoreClicks && !$li.is(".PageListTriggerOpen")) return false; 


				if($root.is(".PageListSorting") || $root.is(".PageListSortSaving")) {
					return false; 
				}

				if($li.is(".PageListItemOpen")) {
					$li.removeClass("PageListItemOpen").next(".PageList").slideUp("fast", function() { 
						$(this).remove(); 
					}); 
				} else {
					$li.addClass("PageListItemOpen"); 
					if(parseInt($li.children('.PageListNumChildren').text()) > 0) {
						ignoreClicks = true; 
						loadChildren(id, $li, 0, false); 
					}
				}
					
				return false;
			}

			/**
			 * Event called when the 'more' action/link is clicked on
			 *
			 * @param event e
			 *
			 */
			function clickMore(e) {

				var $t = $(this); 
				var $actions = $t.parent('li').parent('ul.PageListActions'); 
				var $pageList = $actions.parent('.PageList'); 
				var id = $t.data('pageId');
				var nextStart = parseInt($t.attr('href')); 
		
				loadChildren(id, $pageList, nextStart, false); 
				$actions.remove();
				return false; 
			}

			/**
			 * Event called when the 'move' action/link is clicked on
			 *
			 * @param event e
			 *
			 */
			function clickMove() {

				if(ignoreClicks) return false;

				var $t = $(this); 
				var $li = $t.parent('li').parent('ul.PageListActions').parent('.PageListItem'); 

				// $li.children(".PageListPage").click(); 
				if($li.hasClass("PageListItemOpen")) $li.children(".PageListPage").click(); // @somatonic PR163

				// make an invisible PageList placeholder that allows 'move' action to create a child below this
				$root.find('.PageListItemOpen').each(function() {
					var numChildren = $(this).children('.PageListNumChildren').text(); 
					// if there are children and the next sibling doesn't contain a visible .PageList, then don't add a placeholder
					if(parseInt(numChildren) > 1 && $(this).next().find(".PageList:visible").size() == 0) {
						return; 
					}
					var $ul = $("<div></div>").addClass('PageListPlaceholder').addClass('PageList');
					$ul.append($("<div></div>").addClass('PageListItem PageListPlaceholderItem').html('&nbsp;'));
					$(this).after($ul);
					//$(this).prepend($ul.clone()); 
					//$(this).addClass('PageListItemNoSort'); 
				}); 

				var sortOptions = {
					stop: stopMove, 
					helper: 'PageListItemHelper', 
					items: '.PageListItem:not(.PageListItemOpen)',
					placeholder: 'PageListSortPlaceholder',
					start: function(e, ui) {
						$(".PageListSortPlaceholder").css('width', ui.item.children(".PageListPage").outerWidth() + 'px'); 
					}
				};

				var $sortRoot = $root.children('.PageList').children('.PageList');

				var $cancelLink = $("<a href='#'>" + options.selectCancelLabel + "</a>").click(function() { 
					return cancelMove($li); 
				}); 

				$li.children("ul.PageListActions").before($("<span class='PageListMoveNote detail'>&lt; " + options.moveInstructionLabel + " </span>").append($cancelLink)); 
				$li.addClass('PageListSortItem'); 
				$li.parent('.PageList').attr('id', 'PageListMoveFrom'); 

				$root.addClass('PageListSorting'); 
				$sortRoot.addClass('PageListSortingList').sortable(sortOptions); 

				return false; 

			}

			/**
			 * Remove everything setup from an active 'move' 
			 *
			 * @param jQuery $li List item that initiated the 'move'
			 *
			 */
			function cancelMove($li) {
				var $sortRoot = $root.find('.PageListSortingList'); 
				$sortRoot.sortable('destroy').removeClass('PageListSortingList'); 
				$li.removeClass('PageListSortItem').parent('.PageList').removeAttr('id'); 
				$li.find('.PageListMoveNote').remove();
				$root.find(".PageListPlaceholder").remove();
				$root.removeClass('PageListSorting'); 
				return false; 
			}

			/**
			 * Event called when the mouse stops after performing a 'move'
			 *
			 * @param event e
			 * @param jQueryUI ui
			 *
			 */
			function stopMove(e, ui) {

				var $li = ui.item; 
				var $a = $li.children('.PageListPage'); 
				var id = parseInt($li.data('pageId')); 
				var $ul = $li.parent('.PageList'); 
				var $from = $("#PageListMoveFrom")

				// get the previous sibling .PageListItem, and skip over the pagination list if it's there
				var $ulPrev = $ul.prev().is('.PageListItem') ? $ul.prev() : $ul.prev().prev();
				var parent_id = parseInt($ulPrev.data('pageId')); 

				// check if item was moved to an invalid spot
				// in this case, a spot between another open PageListItem and it's PageList
				var $liPrev = $li.prev(".PageListItem"); 
				if($liPrev.is(".PageListItemOpen")) return false; 

				// check if item was moved into an invisible parent placeholder PageList
				if($ul.is('.PageListPlaceholder')) {
					// if so, it's no longer a placeholder, but a real PageList
					$ul.removeClass('PageListPlaceholder').children('.PageListPlaceholderItem').remove();
				}

				$root.addClass('PageListSortSaving'); 
				cancelMove($li); 

				// setup to save the change
				$li.append($loading.show()); 
				var sortCSV = '';
			
				// create a CSV string containing the order of Page IDs	
				$ul.children(".PageListItem").each(function() {
					sortCSV += $(this).data('pageId') + ','; 
				}); 

				var postData = {
					id: id, 
					parent_id: parent_id, 
					sort: sortCSV
				}; 

				postData[$('#PageListContainer').attr('data-token-name')] = $('#PageListContainer').attr('data-token-value'); // CSRF Token

				var success = 'unknown'; 
			
				// save the change	
				$.post(options.ajaxMoveURL, postData, function(data) {

					$loading.hide();

					$a.fadeOut('fast', function() {
						$(this).fadeIn("fast")
						$li.removeClass('PageListSortItem'); 
						$root.removeClass('PageListSorting');
					}); 

					if(data && data.error) {
						alert(data.message); 
					}

					// if item moved from one list to another, then update the numChildren counts
					if(!$ul.is("#PageListMoveFrom")) {
						// update count where item came from
						var $fromItem = $from.prev(".PageListItem"); 	
						var $numChildren = $fromItem.children(".PageListNumChildren"); 
						var n = $numChildren.text().length > 0 ? parseInt($numChildren.text()) - 1 : 0; 
						if(n == 0) {
							n = '';
							$from.remove(); // empty list, no longer needed
						}
						$numChildren.text(n); 
				
						// update count where item went to	
						var $toItem = $ul.prev(".PageListItem"); 
						$numChildren = $toItem.children(".PageListNumChildren"); 	
						n = $numChildren.text().length > 0 ? parseInt($numChildren.text()) + 1 : 1; 
						$numChildren.text(n); 
					}
					$from.attr('id', ''); 
					$root.removeClass('PageListSortSaving'); 

				}, 'json'); 

				return true; // whether or not to allow the sort
			}

			/**
			 * Event called when the "select" link is clicked on in select mode
			 *
			 * This also triggers a 'pageSelected' event on the attached text input. 
			 *
			 * @see setupSelectMode()
			 *
			 */
			function clickSelect() {

				var $t = $(this); 
				var $li = $t.parent('li').parent('ul.PageListActions').parent('.PageListItem'); 
				var id = $li.data('pageId');
				var $a = $li.children(".PageListPage"); 
				var title = $a.text();
				var url = $a.attr('title'); 
				var $header = $root.children(".PageListSelectHeader"); 

				if($t.text() == options.selectUnselectLabel) {
					// if unselect link clicked, then blank out the values
					id = 0; 
					title = '';
				}

				if(id != $container.val()) $container.change().val(id);

				if(options.selectShowPageHeader) { 
					$header.children(".PageListSelectName").text(title); 
				}
				
				// trigger pageSelected event	
				$container.trigger('pageSelected', { 
					id: id, 
					url: url, 
					title: title, 
					a: $a 
				}); 	


				$header.find(".PageListSelectActionToggle").click(); // close the list

				// jump to specified anchor, if provided
				if(options.selectSelectHref == '#') return false; 
				return true; 
			}


			// initialize the plugin
			init(); 

		}); 
	};
})(jQuery); 

