<?php

/**
 * AdminThemeRenoHelpers.php
 * 
 * Rendering helper functions for use with for AdminThemeReno
 * Copyright (C) 2014 by Tom Reno (Renobird)
 * http://www.tomrenodesign.com
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 * @todo: make this extend AdminThemeDefaultHelpers so that all the methods that are the same between
 * the two can be removed. 
 * 
 */

class AdminThemeRenoHelpers extends WireData {

	/**
	 * Perform a translation, based on text from shared admin file: /wire/templates-admin/default.php
	 * 
	 * @param string $text
	 * @return string
	 * 
	 */
	public function _($text) {
		return __($text, $this->wire('config')->paths->root . 'wire/templates-admin/default.php'); 
	}

	public function adminTheme() { 
		$adminTheme = $this->wire('adminTheme');
		return $adminTheme; 
	}

	/**
	 * Get the headline for the current admin page
	 *
	 * @return string
	 *
	 */
	public function getHeadline() {
		$headline = $this->wire('processHeadline'); 
		if(!$headline) $headline = $this->wire('page')->get('title|name'); 
		$headline = $this->wire('sanitizer')->entities1($this->_($headline)); 
		return $headline;
	}

	/**
	 * Render a list of breadcrumbs (list items), excluding the containing <ul>
	 *
	 * @param bool $appendCurrent Whether to append the current title/headline to the breadcrumb trail (default=true)
	 * @return string
	 *
	 */
	public function renderBreadcrumbs($appendCurrent = true) {
		$out = '';
		foreach($this->wire('breadcrumbs') as $breadcrumb) {
			$title = $this->wire('sanitizer')->entities1($this->_($breadcrumb->title));
			$out .= "<li><a href='{$breadcrumb->url}'>{$title}</a><i class='fa fa-angle-right'></i></li>";
		}
		if($appendCurrent) $out .= "<li><a href='{$breadcrumb->url}'>{$this->getHeadline()}</a></li>";
		return $out; 
	}

	/**
	 * Return the filename used for admin colors
	 *
	 * @return string
	 *
	 */
	public function getAdminColorsFile() { 
	
		$user = $this->wire('user');
		$adminTheme = $this->wire('adminTheme'); 
		$defaultFile = 'main.css';
		$colors = $adminTheme->colors; 
	
		if($user->isLoggedin() && $user->admin_colors && $user->admin_colors->id) {
			$colors = $user->admin_colors->name; 
		}
	
		if(!$colors) return $defaultFile; // default
	
		$colors = $this->wire('sanitizer')->pageName($colors);
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
	public function renderAdminShortcuts() {
	
		$user = $this->wire('user');
		$config = $this->wire('config');
		
		if($user->isGuest() || !$user->hasPermission('page-edit')) return '';
	
		$language = $user->language && $user->language->id && !$user->language->isDefault() ? $user->language : null;
		$url = $config->urls->admin . 'page/add/';
		$out = '';
	
		foreach(wire('templates') as $template) {
			$parent = $template->getParentPage(true); 
			if(!$parent) continue; 
			if($parent->id) {
				// one parent possible	
				$qs = "?parent_id=$parent->id";
			} else {
				// multiple parents possible
				$qs = "?template_id=$template->id";
			}
			$label = $template->label;
			if($language) $label = $template->get("label$language");
			if(empty($label)) $label = $template->name; 
			$out .= "<li><a href='$url$qs'>$label</a></li>";
		}
	
		if(empty($out)) return '';
	
		$label = $this->_('Add New'); 
	
		$out =	"<div id='head_button'>" . 	
			"<button class='ui-button dropdown-toggle'><i class='fa fa-angle-down'></i> $label</button>" . 
			"<ul class='dropdown-menu shortcuts'>$out</ul>" . 
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
	public function renderAdminNotices($notices) {
	
		if(!count($notices)) return '';
		$config = $this->wire('config'); 
	
		$out = "<ul id='notices' class='ui-widget'>";
	
		foreach($notices as $n => $notice) {
	
			$class = 'ui-state-highlight NoticeMessage';
			$text = $notice->text; 
			$icon = '';
	
			if($notice->flags & Notice::allowMarkup) {
				// leave $text alone
			} else {
				// unencode entities, just in case module already entity encoded otuput
				if(strpos($text, '&') !== false) $text = html_entity_decode($text, ENT_QUOTES, "UTF-8"); 
				// entity encode it
				$text = $this->wire('sanitizer')->entities($text); 
			}
	
			if($notice instanceof NoticeError || $notice->flags & Notice::warning) {
				$class = 'ui-state-error'; 
				if($notice->flags & Notice::warning) {
					$class .= ' NoticeWarning';
					$icon = 'warning';
				} else {
					$class .= ' ui-priority-primary NoticeError';
					$icon = 'exclamation-triangle'; 
				}
			}
	
			if($notice->flags & Notice::debug) {
				$class .= ' ui-priority-secondary NoticeDebug';
				$icon = 'gear';
			}
	
			if(!$icon) $icon = 'check-circle';
	
			if($notice->class && $config->debug) $text = "{$notice->class}: $text";
	
			$remove = $n ? '' : "<a class='notice-remove' href='#'><i class='fa fa-times-circle'></i></a>";
	
			$out .= "\n\t\t<li class='$class'>$remove<i class='fa fa-$icon'></i> {$text}</li>";
		}
	
		$out .= "\n\t</ul><!--/notices-->";
		return $out; 
	}
	
	/**
	 * Render quicklinks. Designed to be called by renderSideNav()
	 * @return string
	 */

	public function renderQuicklinks($items) {

		if ($items instanceof Templates){
			$parentPath = wire("config")->urls->admin . "setup/template/";
			$title = "Templates";
		} else if ($items instanceof Fields) {
			$parentPath = wire("config")->urls->admin . "setup/field/";
			$title = "Fields";
		}

		$out = "<ul class='quicklinks'>" .
		"<li class='quicklink-close'><i class='fa fa-bolt'></i> {$title} <div class='close'><i class='fa fa-times'></i></div></li>".
		"<li class='add'><a href='{$parentPath}add'><i class='fa fa-plus-circle'></i> Add New</a></li>";

		foreach($items as $item) {

			if($item instanceof Field) {
				if(($item->flags & Field::flagSystem) && $item->name != 'title') continue; 
				if($item->type instanceof FieldtypeFieldsetOpen) continue; 

			} else if($item instanceof Template) {
				if($item->flags & Template::flagSystem) continue; 
			}
			$out .= "<li><a href='{$parentPath}{$items->url}edit?id=$item->id'>$item->name</a></li>";
		}

		$out .= "</ul>";

		return $out;

	}

	/**
	 * Render top navigation items
	 * @return string
	 */
	
	public function renderTopNavItems() {

		$out = '';
		$user = $this->wire('user');
		$config = wire("config");
		$adminTheme = $this->wire('adminTheme');

		$adminTheme->avatar_field != '' ?  $imgField = $user->get($adminTheme->avatar_field) : $imgField = '';

			if ($imgField != ''){
				count($imgField) ? $img = $imgField->first() : $img = $imgField;
				$out .= "<li class='avatar'><a href='{$config->urls->admin}profile/'>";
				$userImg = $img->size(48,48); // render at 2x for hi-dpi
				$out .= "<img src={$img->url} /> <span>{$user->name}</span>";
				$out .= "</a></li>";

			} else {
				$out .= "<li><a href='{$config->urls->admin}profile/'><i class='fa fa-user'></i> <span>{$user->name}</span></a></li>";
			}
		
		// view site
		$out .= "<li><a href='{$config->urls->root}'><i class='fa {$adminTheme->home}'></i></a></li>";

		// logout
		$out .= "<li><a href='{$config->urls->admin}login/logout/'><i class='fa {$adminTheme->signout}'></i></a></li>";

		return $out;

	}

	
	/**
	 * Render a side navigation items
	 *
	 * This function designed primarily to be called by the renderSideNavItems() function. 
	 *
	 * @param Page $p
	 * @return string
	 *
	 */
	public function renderSideNavItem(Page $p) {
		
		$isSuperuser = $this->wire('user')->isSuperuser();
		$showItem = $isSuperuser;
		$children = $p->numChildren ? $p->children("check_access=0") : array();
		$adminURL = $this->wire('config')->urls->admin;
		$quicklinks = array('11','16'); // array of page ids that use quicklinks.
		$out = '';
		$iconName = $p->name;
		$this->adminTheme()->$iconName ? $icon = $this->adminTheme()->$iconName : $icon = 'fa-file-text-o';
		
		// don't bother with a drop-down here if only 1 child
		if($p->name == 'page' && !$isSuperuser) $children = array();

		if(!$showItem) { 
			$checkPages = count($children) ? $children : array($p); 
			foreach($checkPages as $child) {
				if($child->viewable()) {
					$showItem = true;
					break;
				}
			}
		}
	
		if(!$showItem) return '';
	
		$class = strpos(wire('page')->path, $p->path) === 0 ? 'current' : ''; // current class
		$class .= count($children) > 0 ? " parent" : ''; // parent class
		$title = strip_tags((string)$p->get('title|name')); 
		$title = $this->_($title); // translate from context of default.php
		

		$out .= "<li>";
	
		if(count($children)) {

			$out .= "<a href='$p->url' class='$class $p->name '><i class='fa {$icon}'></i> $title</a>"; 
			$out .= "<ul>";

			foreach($children as $c) {
				
				$showQuickLinks = false;
				$class = strpos(wire('page')->path, $c->path) === 0 ? 'current' : ''; // child current class
				
				if($c->viewable()) {

					$isSuperuser && in_array($c->id, $quicklinks) ? $showQuickLinks = true : '';
					$showQuickLinks ? $qlink = "<i class='quicklink-open fa fa-bolt'></i>" : $qlink = '';
					
					$url = $c->url;
			
					// The /page/ and /page/list/ are the same process, so just keep them on /page/ instead. 
					if(strpos($url, '/page/list/') !== false) $url = str_replace('/page/list/', '/page/', $url); 

					$out .= "<li><a href='$url' class='$class'>" . $this->_($c->title) . $qlink ."</a>";
						
						if ($showQuickLinks){
							$c->id == 11 ? $type = "templates" : '';
							$c->id == 16 ? $type = "fields" : '';
							$out .= $this->renderQuicklinks(wire($type)); // not thrilled with this solution. Explore options.
						}
				
					$out .= "</li>";
					
				}
			}

			$out .= "</ul>";

		} else {
			
			$class = $class ? " class='$class $p->name'" : "class='$p->name'";
			$out .= "<a href='$p->url' $class><i class='fa {$icon}'></i> $title</a>"; 

		}

	$out .= "</li>";

	return $out; 
}
	
	/**
	 * Render all sidenav navigation items, ready to populate in ul#main-nav
	 *
	 * @return string
	 *
	 */
	public function renderSideNavItems() {
		$out = '';
		$admin = $this->wire('pages')->get(wire('config')->adminRootPageID); 
		$config = $this->wire('config'); 
		$user = $this->wire('user'); 
	
		foreach($admin->children("check_access=0") as $p) {
			if(!$p->viewable()) continue; 
			$out .= $this->renderSideNavItem($p);
			
		}
		return $out; 
	}

	
	/**
	 * Render the browser <title>
	 *
	 * @return string
	 *
	 */
	public function renderBrowserTitle() {
		$browserTitle = $this->wire('processBrowserTitle'); 
		if(!$browserTitle) $browserTitle = $this->_(strip_tags(wire('page')->get('title|name'))) . ' &bull; ProcessWire';
		if(strpos($browserTitle, '&') !== false) $browserTitle = html_entity_decode($browserTitle, ENT_QUOTES, 'UTF-8'); // we don't want to make assumptions here
		$browserTitle = $this->wire('sanitizer')->entities($browserTitle, ENT_QUOTES, 'UTF-8'); 
		$httpHost = $this->wire('config')->httpHost;
		if(strpos($httpHost, 'www.') === 0) $httpHost = substr($httpHost, 4); // remove www
		if(strpos($httpHost, ':')) $httpHost = preg_replace('/:\d+/', '', $httpHost); // remove port
		$browserTitle = $this->wire('sanitizer')->entities($httpHost) . ' &bull; ' . $browserTitle; 
		return $browserTitle; 
	}
	
	/**
	 * Render the class that will be used in the <body class=''> tag
	 *
	 * @return string
	 *
	 */
	public function renderBodyClass() {
		$page = $this->wire('page');
		$bodyClass = $this->wire('input')->get->modal ? 'modal ' : '';
		$bodyClass .= "id-{$page->id} template-{$page->template->name}";
		if(wire('config')->js('JqueryWireTabs')) $bodyClass .= " hasWireTabs";
		return $bodyClass; 
	}
	
	/**
	 * Render the required javascript 'config' variable for the document <head>
	 *
	 * @return string
	 *
	 */
	public function renderJSConfig() {
	
		$config = $this->wire('config'); 
	
		$jsConfig = $config->js();
		$jsConfig['debug'] = $config->debug;
	
		$jsConfig['urls'] = array(
			'root' => $config->urls->root, 
			'admin' => $config->urls->admin, 
			'modules' => $config->urls->modules, 
			'core' => $config->urls->core, 
			'files' => $config->urls->files, 
			'templates' => $config->urls->templates,
			'adminTemplates' => $config->urls->adminTemplates,
			); 
	
		return "var config = " . json_encode($jsConfig);
	}

}
