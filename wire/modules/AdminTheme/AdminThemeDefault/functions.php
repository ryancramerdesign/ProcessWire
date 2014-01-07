<?php

/**
 * functions.inc
 * 
 * Rendering helper functions for use with ProcessWire admin theme.
 *
 */ 


/**
 * Return the filename used for admin colors
 *
 * @return string
 *
 */
function getAdminColorsFile() { 

	$user = wire('user');
	$config = wire('config'); 
	$adminTheme = wire('adminTheme'); 
	$colors = '';
	$defaultFile = 'main.css';
	$colors = $adminTheme->colors; 

	if($user->isLoggedin() && $user->admin_colors && $user->admin_colors->id) {
		$colors = $user->admin_colors->name; 
	}

	if(!$colors) return $defaultFile; // default

	$colors = wire('sanitizer')->pageName($colors);
	$file = "main-$colors.css";

	if(!is_file(dirname(__FILE__) . "/styles/$file")) $file = $defaultFile;

	return $file; 
}

/**
 * Render the populated shortcuts head button or blank when not applicable
 *
 * @return string
 *
 */
function renderAdminShortcuts() {

	$user = wire('user');
	if($user->isGuest() || !$user->hasPermission('page-edit')) return '';

	$language = $user->language && $user->language->id && !$user->language->isDefault() ? $user->language : null;
	$url = wire('config')->urls->admin . 'page/add/';
	$out = '';

	foreach(wire('templates') as $template) {
		$parent = $template->getParentPage(true); 
		if(!$parent) continue; 
		$label = $template->label;
		if($language) $label = $template->get("label$language");
		if(empty($label)) $label = $template->name; 
		$out .= "<li><a href='$url?parent_id=$parent->id'>$label</a></li>";
	}

	if(empty($out)) return '';
	// $out .= "<li>" . __('Not yet configured: See template family settings.', dirname(__FILE__) . "/default.php") . "</li>";

	$label = __('Add New', dirname(__FILE__) . "/default.php"); 

	$out = 	"<div id='head_button'>" . 	
		"<button class='dropdown-toggle'><i class='icon-angle-down'></i> $label</button>" . 
		"<ul class='dropdown-menu'>$out</ul>" . 
		"</div>";

	return $out; 
}

/**
 * Render runtime notices div#notices
 *
 * @param Notices $notices
 * @return string
 *
 */
function renderAdminNotices($notices) {

	if(!count($notices)) return '';
	$config = wire('config'); 

	$out = "<ul id='notices' class='ui-widget'>";

	foreach($notices as $n => $notice) {

		$class = 'ui-state-highlight NoticeMessage';
		$text = $notice->text; 
		$icon = '';

		if($notice instanceof NoticeError || $notice->flags & Notice::warning) {
			$class = 'ui-state-error'; 
			if($notice->flags & Notice::warning) {
				$class .= ' NoticeWarning';
				$icon = 'warning-sign';
			} else {
				$class .= ' ui-priority-primary NoticeError';
				$icon = 'exclamation-sign'; 
			}
		}

		if($notice->flags & Notice::debug) {
			$class .= ' ui-priority-secondary NoticeDebug';
			$icon = 'gear';
		}

		if(!$icon) $icon = 'ok-sign';

		if($notice->class && $config->debug) $text = "{$notice->class}: $text";

		$remove = $n ? '' : "<a class='notice-remove' href='#'><i class='icon-remove-sign'></i></a>";

		$out .= "\n\t\t<li class='$class'><div class='container'><p>$remove<i class='icon-$icon'></i> {$text}</p></div></li>";
	}

	$out .= "\n\t</ul><!--/notices-->";
	return $out; 
}

/**
 * Render a single top navigation item for the given page
 *
 * This function designed primarily to be called by the renderTopNavItems() function. 
 *
 * @param Page $p
 * @param int $level Recursion level (default=0)
 * @return string
 *
 */
function renderTopNavItem(Page $p, $level = 0) {

	$showItem = wire('user')->isSuperuser() ? true : false;
	$info = array();
	$children = $p->numChildren && !$level && $p->name != 'page' ? $p->children("check_access=0") : array();
	$translationFile = dirname(__FILE__) . '/default.php';
	$out = '';

	if(!$level && !$showItem) { 
		$checkPages = count($children) ? $children : array($p); 
		foreach($checkPages as $child) {
			if($child->viewable()) {
				$showItem = true;
				break;
			}
		}
	}

	if(!$showItem) return '';

	$class = strpos(wire('page')->path, $p->path) === 0 ? 'on' : '';
	$title = strip_tags((string)$p->get('title|name')); 
	$title = __($title, $translationFile); // translate from context of default.php
	$out .= "<li>";

	if(!$level && count($children)) {

		$class = trim("$class dropdown-toggle"); 
		$out .= "<a href='$p->url' class='$class'>$title</a>"; 
		$out .= "<ul class='dropdown-menu topnav' data-my='left-1 top' data-at='left bottom'>";

		foreach($children as $c) {
			
			$items = null;

			if(wire('user')->isSuperuser()) {
				if($c->id == 11) {
					$items = wire('templates'); 
				} else if($c->id == 16) {
					$items = wire('fields'); 
				}
				if(count($items) > 100) $items = null; // don't build excessively large lists
			}


			if($items) {

				$addLabel = __('Add New', dirname(__FILE__) . '/default.php');
				$out .= "<li><a href='$c->url'>" . __($c->title, $translationFile) . "</a><ul>" . 
					"<li class='add'><a href='{$c->url}add'><i class='icon-plus-sign'></i> $addLabel</a></li>";

				foreach($items as $item) {

					if($item instanceof Field) {
						if($item->flags & Field::flagSystem) continue; 
						if($item->type instanceof FieldtypeFieldsetOpen) continue; 

					} else if($item instanceof Template) {
						if($item->flags & Template::flagSystem) continue; 
					}
					$out .= "<li><a href='{$c->url}edit?id=$item->id'>$item->name</a></li>";
				}

				$out .= "</ul></li>";

			} else {
				$out .= renderTopNavItem($c, $level+1);
			}
		}

		$out .= "</ul>";

	} else {
		$class = $class ? " class='$class'" : '';
		$out .= "<a href='$p->url'$class>$title</a>"; 
	}

	$out .= "</li>";

	return $out; 
}

/**
 * Render all top navigation items, ready to populate in ul#topnav
 *
 * @return string
 *
 */
function renderTopNavItems() {
	$out = '';
	$admin = wire('pages')->get(wire('config')->adminRootPageID); 
	foreach($admin->children("check_access=0") as $p) {
		if(!$p->viewable()) continue; 
		$out .= renderTopNavItem($p);
	}
	return $out; 
}


