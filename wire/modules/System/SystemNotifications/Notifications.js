
/**
 * Notifications for ProcessWire
 *
 * By Avoine and Ryan Cramer
 *
 */

var Notifications = {

	options: { // options that may be passed to init(), these are the defaults:
		ajaxURL: './', // URL to poll for ajax updates
		version: 1, // notifications version 
		updateLast: 0, 
		updateDelay: 5000, 
		updateDelayFast: 1550, 
		iconMessage: 'smile-o',
		iconWarning: 'meh-o',
		iconError: 'frown-o',
		ghostDelay: 2000, 
		ghostDelayError: 4000, 
		ghostFadeSpeed: 'fast',
		ghostOpacity: 0.9
	},

	updateTimeout: null, 	// setTimeout timer for update method
	renderTimeout: null, 	// setTimeout timer for render method
	updating: false,	// are we currently updating right now?
	runtime: [],	 	// notifications added by manual API add calls
	numRender: 0,		// number of times render() has been called 
	ghostsActive: 0, 	// number of ghosts currently visible
	activity: false, 	// whether there is a lot of activity (like with progress bars)

	$menu: null, 		// <div class='NotificationMenu'>
	$bug: null, 		// <div class='NotificationBug'>
	$list: null, 		// <ul class='NotificationList'>

	/**
	 * Check server for new notifications
	 *
	 */
	update: function() {

		if(Notifications.updating) { 
			// if already running, re-schedule it to run in 1 second
			clearTimeout(Notifications.updateTimeout); 
			Notifications.updateTimeout = setTimeout('Notifications.update()', Notifications.options.updateDelay); 
			return false;
		}

		Notifications.updating = true; 

		var rm = '';
		var $rm = Notifications.$list.find("li.removed");

		$rm.each(function() {
			rm += $(this).attr('id') + ',';
			$(this).remove();
		}); 

		var url = "./?Notifications=qty&time=" + Notifications.options.updateLast; 
		if(rm.length) url += '&rm=' + rm;
		
		var updateDelay = Notifications.options.updateDelay;
		if(Notifications.activity) updateDelay = Notifications.options.updateDelayFast; // update more often when progress active

		$.getJSON(url, function(data) {
			Notifications.options.updateLast = data.time; 
			Notifications._update(data, false); 
			clearTimeout(Notifications.updateTimeout); 
			Notifications.updateTimeout = setTimeout('Notifications.update()', updateDelay); 
			Notifications.updating = false; 
		}); 
	},

	/**
	 * Updates .NotificationTrigger
	 *
	 * param object data Notification data from ajax request
	 * param bool Was added from runtime API? (rather than ajax)
	 *
	 */
	_update: function(data, isRuntime) {

		var $bug = Notifications.$bug;
		var $bugQty = Notifications.$bug.children('.qty');
		var qty = 0;

		if(isRuntime) {
			qty = parseInt($bug.attr('data-qty')) + data.qty; 

		} else {
			qty = data.qty + Notifications.runtime.length; 
			$(Notifications.runtime).each(function(n, notification) {
				if(notification.flagNames.indexOf('error') != -1) {
					data.qtyError++;
				} else if(notification.flagNames.indexOf('warning') != -1) {
					data.qtyWarning++;
				} else {
					data.qtyMessage++;
				}
			}); 
		}

		if(data.qtyError > 0) {
			$bug.addClass('NoticeError', 'slow').removeClass('NoticeWarning', 'slow'); 
		} else if(data.qtyWarning > 0) {
			$bug.addClass('NoticeWarning', 'slow').removeClass('NoticeError', 'slow'); 
		} else {
			$bug.removeClass('NoticeWarning NoticeError', 'slow'); 
		}
		
		var qtyNew = data.notifications.length;

		if($bugQty.text() == qty && qtyNew == 0) {
			Notifications.activity = false;
			return;
		}
		
		Notifications.activity = true; 
		
		$bugQty.text(qty); 
		$bug.attr('data-qty', qty); 

		if(qty == 0) {
			if($bug.is(":visible")) $bug.fadeOut();
		} else {
			if(!$bug.is(":visible")) $bug.fadeIn();
		}
		
		$bug.attr('class', $bug.attr('class').replace(/qty\d+/g, 'qty' + data.qty));

		if(Notifications.$menu.hasClass('open') && qtyNew > 0) {

			for(var n = 0; n < qtyNew; n++) {
				Notifications._add(data.notifications[n], true); 
			}

		} else {
			if(qty == 0 && Notifications.$menu.hasClass('open')) $bug.click();

			for(var n = 0; n < qtyNew; n++) {
				// if(notifications[n].flagNames.indexOf('ghost') > -1) {
				var notification = data.notifications[n];
				if('ghostShown' in notification && notification.ghostShown == true) continue; 
				notification.ghostShown = true; 
				Notifications._ghost(notification, n); 
			}
		}

		if(qty > 0) {
			$bug.effect('highlight', 500); 
		}

		//if(isRuntime) Notifications.runtime = []; // reset
	},

	/**
	 * Add a notification (external/public API use)
	 *
	 * param object Notification to add containing these properties
	 *
	 */
	add: function(notification) {
		var qty = Notifications.runtime.length;
		notification.addClass = 'runtime';
		Notifications.runtime[qty] = notification;
	}, 

	/**
	 * Render any add()'d notifications now (for public API)
	 *
	 */
	render: function() {
		Notifications.renderTimeout = setTimeout(function() { 

			var qtyError = 0;
			var qtyWarning = 0;
			var qtyMessage = 0;

			$(Notifications.runtime).each(function(n, notification) {
				if(notification.flagNames.indexOf('error') != -1) {
					qtyError++;
				} else if(notification.flagNames.indexOf('warning') != -1) {
					qtyWarning++;
				} else {
					qtyMessage++;
				}
			}); 
			
			var data = {
				qty: Notifications.runtime.length, 
				qtyMessage: qtyMessage, 
				qtyWarning: qtyWarning,
				qtyError: qtyError, 
				notifications: Notifications.runtime, 
				runtime: true
			}; 

			Notifications._update(data, true); 
		}, 250); 
		Notifications.numRender++;
		/*
		Notifications.updating = false;
		Notifications.updating = true; 
		setTimeout(renderNow, 250); 
		*/
	},

	/**
	 * Add a notification (internal use)
	 *
	 * param object Notification to add
	 * param bool highlight Whether to highlight the notification (use true for new notifications)
	 *
	 */
	_add: function(notification, highlight) {
		
		var exists = false;
		var open = false;
		var $li = Notifications.$list.children("#" + notification.id);
		var progressNext = parseInt(notification.progress); 
		var progressPrev = 0;
		
		if($li.length > 0) {
			exists = true; 
			highlight = false;
			open = $li.hasClass('open'); 
			progressPrev = parseInt($li.find(".NotificationProgress").text());
			$li.empty(); // clear it out
			
		} else {
			$li = $("<li></li>");
		}
		$li.attr('id', notification.id); 
		
		var $icon = $("<i></i>").addClass('fa fa-fw fa-' + notification.icon); 
		var $title = $("<span></span>").addClass('NotificationTitle').html(notification.title); 
		var $p = $("<p></p>").append($title).prepend('&nbsp;').prepend($icon);
		var $div = $("<div></div>").addClass('container').append($p);
		var $text = $("<div></div>").addClass('NotificationText'); 
		var $rm = $("<i class='NotificationRemove fa fa-times-circle'></i>"); 
		var addClass = '';
		var runtime = false;
	
		if(progressNext > 0) {
			$li.prepend(Notifications._progress($title, progressNext, progressPrev)); 
			if(progressNext < 100) $li.addClass('NotificationHasProgress', 'normal'); 
		}

		if('addClass' in notification && notification.addClass.length > 0) {
			addClass = notification.addClass;
			$li.addClass(addClass); 
		}

		if($li.hasClass('runtime')) runtime = true;

		if(!runtime) $title.append(" <small class='created'>" + notification.when + "</small>"); 

		if(notification.flagNames.indexOf('error') != -1) $li.addClass('NoticeError'); 
			else if(notification.flagNames.indexOf('warning') != -1) $li.addClass('NoticeWarning'); 
			else if(notification.flagNames.indexOf('message') != -1) $li.addClass('NoticeMessage'); 
		if(notification.flagNames.indexOf('debug') != -1) $li.addClass('NoticeDebug'); 

		if(notification.html.length > 0) {
			$text.html(notification.html); 
			$p.append(" <i class='fa fa-angle-right'></i>"); 
			$title.click(function() {
				if($li.hasClass('open')) {
					$li.removeClass('open'); 
					$text.slideUp('fast').removeClass('open'); 
				} else {
					$text.slideDown('fast').addClass('open'); 
					$li.addClass('open'); 
				}
			}); 
			$div.append($text); 
			if(notification.flagNames.indexOf('open') != -1) {
				if(!open) {
					setTimeout(function() { 
						$text.fadeIn('slow', function() {
							$li.addClass('open'); 
							$text.addClass('open'); 
						});
					}, 500); 
				} else { 
					$li.addClass('open');
					$text.show().addClass('open');
				}
			}
		}

		$rm.on('click', function() {
			$li.addClass('removed'); 
			$li.slideUp('fast', function() {
				if($li.siblings(":visible").size() == 0) $li.closest('.NotificationMenu').slideUp('fast'); 
			}); 
			clearTimeout(Notifications.updateTimeout); 
			Notifications.updateTimeout = setTimeout('Notifications.update()', 1000); 
		}); 

		if(!runtime) $p.prepend($rm); 
		$li.append($div)

		if(highlight) {
			$li.hide();
			Notifications.$list.prepend($li);
			$li.slideDown('slow').effect('highlight', 1000); 
		} else if(exists) {
			$li.show();
			//$li.effect('highlight', 250); 
		} else {
			Notifications.$list.append($li); 
		}

	},
	
	_progress: function($title, progressNext, progressPrev) {
		var $progress = $("<div></div>").addClass('NotificationProgress')
			.html("<span>" + progressNext + '%</span>').css('width', progressPrev + '%').hide();
		if(progressNext > progressPrev) {
			Notifications.activity = true;
			var duration = progressPrev == 0 ? Notifications.options.updateDelay / 1.4 : 1750;
			var easing = 'linear';
			if(progressNext == 100) {
				duration = 750;
				easing = 'swing';
			} else if(progressPrev == 0) {
				easing = 'swing';
			}
			if(progressNext > 0 && progressNext <= 100) {
				$progress.show().animate({
						width: progressNext + '%'
					}, {
						duration: duration,
						easing: easing,
						complete: function() {
							if(progressNext >= 100) {
								$progress.fadeOut('slow', function() {
									$title.parents(".NotificationHasProgress").removeClass('NotificationHasProgress', 'slow'); 
								});
							}
						}
					});
			} else if(progressNext == 100) {
				// don't show
			} else {
				$progress.css('width', progressNext + '%').show();
			}
			$progress.height('100%');
		}
		$title.append(" <strong class='NotificationProgressPercent'>" + progressNext + '%</strong>');
		return $progress;
	},

	/**
	 * Add a notification ghost (subtle notification that appears than disappears)
	 *
	 * param object notification Notification to ghost
	 * param int n Index of the notification, affects when it is shown so that multiple don't appear and disappear as a group. 
	 *
	 */
	_ghost: function(notification, n) {
		
		if(notification.progress > 0 && notification.progress < 100) return;

		var $icon = $('<i class="fa fa-fw fa-' + notification.icon + '"></i>'); 
		var $ghost = $("<div class='NotificationGhost'></div>").append($icon).append(' ' + $("<span>" + notification.title + "</span>").text());
		var $li = $("<li></li>").append($ghost); 
		var delay = Notifications.options.ghostDelay; 

		if(notification.flagNames.indexOf('error') > -1) {
			$ghost.addClass('NoticeError'); 
			delay = Notifications.options.ghostDelayError;
		} else if(notification.flagNames.indexOf('warning') > -1) {
			$ghost.addClass('NoticeWarning'); 
			delay = Notifications.options.ghostDelayError;
		} else {
			$ghost.addClass('NoticeMessage'); 
		}

		Notifications.$ghosts.append($li.hide());
		Notifications.ghostsActive++;	
		

		var fadeSpeed = Notifications.options.ghostFadeSpeed;
		var opacity = Notifications.options.ghostOpacity;
		var interval = 100 * n; 
		var windowHeight = $(window).height();

		if(fadeSpeed.length == 0) interval = 200 * n; 

		setTimeout(function() { 
			
			if(fadeSpeed.length > 0) {	
				$li.fadeTo(fadeSpeed, opacity);
			} else {
				$li.show().css('opacity', opacity); 
			}
			
			var y = $li.offset().top; 
			var h = $li.height();
			if(y + h > (windowHeight / 2)) {
				Notifications.$ghosts.animate({ top: "-=" + (h+3) }, 'fast'); 
			}
			
			setTimeout(function() {
				var ghostDone = function() {
					$li.addClass('removed');
					Notifications.ghostsActive--;
					if(Notifications.ghostsActive == 0) {
						Notifications.$ghosts.children('li').remove();
					} 
				};
				if(fadeSpeed.length > 0) { 
					$li.fadeTo(fadeSpeed, 0.01, ghostDone); 
				} else {
					$li.css('opacity', 0.01); 
					ghostDone();
				}
			}, delay); 
		}, interval); 
	},

	/**
	 * Ajax load all notifications from server into $menu
	 *
	 */
	_load: function() {

		var $menu = Notifications.$menu;
		var $bug = Notifications.$bug; 

		if($menu.hasClass('json-loading')) return;
		if($menu.hasClass('json-loaded') && $menu.hasClass('open')) return;

		$menu.addClass('json-loading').hide();
		$bug.children('.qty').hide();
		$bug.children('.NotificationSpinner').show();

		$.getJSON("./?Notifications=list", function(data) {

			Notifications.$list.children('li').remove();

			$(data).each(function(n, notification) {
				Notifications._add(notification, false); 
			}); 

			$(Notifications.runtime).each(function(n, notification) {
				Notifications._add(notification, false); 
			}); 

			$menu.slideDown('fast', function() { 
				$menu.addClass('json-loaded').removeClass('json-loading').addClass('open');
				$bug.addClass('open');
				$bug.children('.NotificationSpinner').hide();
				$bug.children('.qty').show();
			}); 

		}); 

	},

	/**
	 * Click event for notification bug element (small red counter)
	 *
	 */
	clickBug: function() {

		var $menu = Notifications.$menu;

		if($menu.hasClass('open')) {

			$menu.slideUp('fast', function() {
				$menu.removeClass('json-loaded').removeClass('open'); 
				Notifications.$bug.removeClass('open'); // css only
				Notifications.$list.children('li').remove();
			}); 	

		} else {

			if(!$menu.hasClass('init')) {
				$menu.prependTo($('body')); 
				$menu.addClass('init'); 
			}

			Notifications._load(); 
			Notifications.$ghosts.find('li').fadeOut('fast'); 
		}

		return false;
	},

	/**
	 * Show a notification right now (runtime, internal)
	 *
	 */
	_show: function(type, title, html, icon, href) {

		var notification = {
			id: 0,
			title: title, 
			from: '',	
			created: 0, 
			modified: 0, 
			when: 'now', 
			href: href, 
			icon: icon, 
			flags: 0, 
			flagNames: type + ' notice', 
			progress: 0, 
			html: html, 
			qty: 1
		};

		Notifications.add(notification); 

		// if notifications have already been rendered, we can show this one now
		if(Notifications.numRender > 0) {
			Notifications.render(); 
		}
		
	},

	/**
	 * Show a runtime message notification right now
	 *
	 */
	message: function(title, html, icon, href) {
		if(typeof html == "undefined") html = '';
		if(typeof icon == "undefined") icon = Notifications.options.iconMessage; 
		if(typeof href == "undefined") href = '';
		Notifications._show('message', title, html, icon, href); 
	},

	/**
	 * Show a runtime warning notification right now
	 *
	 */
	warning: function(title, html, icon, href) {
		if(typeof html == "undefined") html = '';
		if(typeof icon == "undefined") icon = Notifications.options.iconWarning; 
		if(typeof href == "undefined") href = '';
		Notifications._show('warning', title, html, icon, href); 
	},

	/**
	 * Show a runtime error notification right now
	 *
	 */
	error: function(title, html, icon, href) {
		if(typeof html == "undefined") html = '';
		if(typeof icon == "undefined") icon = Notifications.options.iconError; 
		if(typeof href == "undefined") href = '';
		Notifications._show('error', title, html, icon, href); 
	},


	/**
	 * Initialize notifications, to be called at document.ready
	 *
	 */
	init: function(options) {

		$.extend(Notifications.options, options);

		Notifications.$menu = $("#NotificationMenu"); 
		Notifications.$bug = $("#NotificationBug"); 
		Notifications.$list = $("#NotificationList"); 
		Notifications.$ghosts = $("#NotificationGhosts");

		Notifications.$menu.hide();
		Notifications.$bug.click(Notifications.clickBug); 

		// start polling for new notifications
		Notifications.updateTimeout = setTimeout(Notifications.update, Notifications.options.updateDelay); 

		$("#ProcessPageSearchForm input").dblclick(function(e) { 
			Notifications.message(
				"ProcessWire Notifications v" + Notifications.options.version, 
				"Grab a coffee and come and visit us at the <a target='_blank' href='https://processwire.com/talk/'>ProcessWire support forums</a>.</p>", 
				'coffee fa-spin'); 
			return false;
		}); 

	}
};

