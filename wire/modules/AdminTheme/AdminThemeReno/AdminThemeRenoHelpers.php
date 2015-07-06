<?php

/**
 * AdminThemeRenoHelpers.php
 * 
 * Rendering helper functions for use with for AdminThemeReno
 * Copyright (C) 2015 by Tom Reno (Renobird)
 * http://www.tomrenodesign.com
 *
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 * 
 */

require(wire('config')->paths->AdminThemeDefault . 'AdminThemeDefaultHelpers.php'); 

class AdminThemeRenoHelpers extends AdminThemeDefaultHelpers {

	/**
	 * Render runtime notices div#notices
	 *
	 * @param Notices $notices
	 * @param array $options
	 * @return string
	 *
	 */
	public function renderAdminNotices($notices, array $options = array()) {
		$options['messageIcon'] = 'check-circle';
		$options['itemMarkup'] = "\n\t\t<li class='{class}'>{remove}<i class='fa fa-{icon}'></i> {text}</li>";
		return parent::renderAdminNotices($notices, $options); 
	}
	
	/**
	 * Render quicklinks for templates/fields. Designed to be called by renderSideNav()
	 * 
	 * @param Page $page
	 * @param array $items
	 * @param string $title
	 * @param string $json
	 * @return string
	 * 
	 */
	public function renderQuicklinks(Page $page, array $items, $title, $json = '') {
		
		if($json) $json = $page->url . $json;

		$textdomain = str_replace($this->wire('config')->paths->root, '/', $this->wire('modules')->getModuleFile($page->process));
		$out = 
			"<ul class='quicklinks' data-json='$json'>" .
			"<li class='quicklink-close'><i class='fa fa-fw fa-bolt quicklinks-spinner'></i>$title <div class='close'><i class='fa fa-times'></i></div></li>";
		
		foreach($items as $item) {
			if(!empty($item['permission']) && !$this->wire('user')->hasPermission($item['permission'])) continue;
			$label = __($item['label'], $textdomain); // translate from context of Process module
			$url = $page->url . $item['url'];
			$out .= "<li><a href='$url'>$label</a></li>";
		}

		$out .= "</ul>";

		return $out;
	}

	/**
	 * Render top navigation items
	 * 
	 * @return string
	 * 
	 */
	public function renderTopNav() {

		$items = array();
		$class = '';
		$user = $this->wire('user');
		$config = wire("config");
		$adminTheme = $this->wire('adminTheme');
		$adminTheme->avatar_field != '' ?  $imgField = $user->get($adminTheme->avatar_field) : $imgField = '';
		$avatar = "<i class='fa $adminTheme->profile'></i>";
		
		// view site
		$items[] = "<li><a href='{$config->urls->root}'><i class='fa {$adminTheme->home}'></i></a></li>";

		// search icon
		$items[] = "<li><a href='#' class='search-toggle'><i class='fa fa-search'></i></a></li>";

		if ($this->user->isSuperuser()){

			// superuser quick links
			$superuserPages = array(
				"<i class='fa fa-life-ring'></i> " . $this->_('Support Forums')  => "http://processwire.com/talk/",
				"<i class='fa fa-book'></i> " . $this->_('Documentation') => "https://processwire.com/docs/",
				"<i class='fa fa-github'></i> " . $this->_('Github Repo') => "https://github.com/ryancramerdesign/ProcessWire/",
				"<i class='fa fa-code'></i> " . $this->_('Cheatsheet')  => "http://cheatsheet.processwire.com",
				"<i class='fa fa-anchor'></i> " . $this->_('Captain Hook')  => "http://processwire.com/api/hooks/captain-hook/",
			);

			$items[] = array(
				"class" => "superuser",
				"label" => "<i class='fa fa-bolt'></i>",
				"children" => $superuserPages
			);
		}

		if ($imgField != '') {
			$class = 'avatar';
			count($imgField) ? $img = $imgField->first() : $img = $imgField;
			$userImg = $img->size(52,52); // render at 2x for hi-dpi (52x52 for 26x26)
			$avatar = "<img src='$userImg->url' alt='$user->name' />";
		}
		
		// pages for the user dropdown.
		$userPages = array(
			"<i class='fa fa-user'></i> " . $this->_('Profile') => $config->urls->admin . "profile/",
			"<i class='fa $adminTheme->signout'></i> " . $this->_('Logout') => $config->urls->admin . "login/logout/"
		);

		$items[] = array(
			"class" => "avatar",
			"label" => "$avatar <span>" . $this->getDisplayName($user) . "</span>",
			"children" => $userPages
		);

		return $this->topNavItems($items);

	}
	
	/**
	 * Render top navigation items (hookable)
	 * 
	 * @return string
	 * 
	 */
	public function ___topNavItems(array $items) {
		
		$out = '';
		foreach ($items as $item){
			if (is_array($item)){
				$out .= "<li class='" . $item['class'] . " dropdown'><a href='#'>" . $item['label'] . "</a>";
					$out .= "<ul>";
						foreach($item['children'] as $label => $link){
							$current = ($this->wire('page')->path == $link) ? 'current' : ''; // current class
							$out .= "<li><a href='$link' class='$current'>$label</a></li>";
						}
					$out .= "</ul>";
				$out .= "</li>";
			
			} else {
				$out .= $item;
			}
		}

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
		$sanitizer = $this->wire('sanitizer');
		$modules = $this->wire('modules'); 
		$showItem = $isSuperuser;
		$children = $p->numChildren() ? $p->children("check_access=0") : array();
		$out = '';
		$iconName = $p->name;
		$icon = $this->wire('adminTheme')->$iconName;
		if(!$icon) $icon = 'fa-file-text-o';
		$numViewable = 0; 
		
		if(!$showItem) { 
			$checkPages = count($children) ? $children : array($p); 
			foreach($checkPages as $child) {
				if($child->viewable()) {
					$showItem = true;
					$numViewable++;
					if($numViewable > 1) break; // we don't need to know about any more
				}
			}
		}
		
		// don't bother with a drop-down here if only 1 child
		if($p->name == 'page' && !$isSuperuser && $numViewable < 2) {
			$children = array();
		}
	
		if(!$showItem) return '';
	
		if($p->process) { 
			$moduleInfo = $modules->getModuleInfo($p->process);
			$textdomain = str_replace($this->wire('config')->paths->root, '/', $this->wire('modules')->getModuleFile($p->process));
		} else {
			$moduleInfo = array();
			$textdomain = '';
		}
		
		if(!count($children) && !empty($moduleInfo['nav'])) $children = $moduleInfo['nav'];
		
		$class = strpos($this->wire('page')->path, $p->path) === 0 ? 'current' : ''; // current class
		$class .= count($children) > 0 ? " parent" : ''; // parent class
		$title = $sanitizer->entities1((string) $this->_($p->get('title|name')));
		$currentPagePath = $this->wire('page')->path;

		$out .= "<li>";
	
		if(count($children)) {

			$out .= "<a href='$p->url' class='$class $p->name '><i class='fa {$icon}'></i> $title</a>"; 
			$out .= "<ul>";

			foreach($children as $c) {
				$navJSON = '';
				
				if(is_array($c)) {
					// $c is moduleInfo nav array
					$moduleInfo = array();
					if(isset($c['permission']) && !$this->wire('user')->hasPermission($c['permission'])) continue;
					$segments = $this->input->urlSegments ? implode("/", $this->input->urlSegments) . '/' : '';
					$class = $currentPagePath . $segments == $p->path . $c['url'] ? 'current' : '';
					$title = $sanitizer->entities1($this->_($c['label'], $textdomain));
					$url = $p->url . $c['url'];
					if(isset($c['navJSON'])) {
						$navJSON = $c['navJSON']; // url part
						$moduleInfo['useNavJSON'] = true;
					}
					$c = $p; // substitute 
					
				} else {
					// $c is a Page object
					$c->path == wire('config')->urls->admin . "page/list/" ? $currentPagePath = $currentPagePath . "list/" : '';
					$class = strpos($currentPagePath, $c->path) === 0 ? 'current' : ''; // child current class
					$name = $c->name;

					if(!$c->viewable()) continue;
					$moduleInfo = $c->process ? $modules->getModuleInfo($c->process) : array();
					$title = $sanitizer->entities1((string) $this->_($c->get('title|name')));
					$url = $c->url;
					// The /page/ and /page/list/ are the same process, so just keep them on /page/ instead. 
					if(strpos($url, '/page/list/') !== false) $url = str_replace('/page/list/', '/page/', $url);
				}
				
				$quicklinks = '';
				
				if(!empty($moduleInfo['useNavJSON'])) {
					// NOTE: 'useNavJSON' comes before 'nav' for AdminThemeReno only, since it does not support navJSON beyond one level
					// meaning this bypasses 'nav' if the module happens to also provide navJSON (see ProcessRecentPages example)
					if(empty($navJSON)) $navJSON = "navJSON/";
					$quicklinks = $this->renderQuicklinks($c, array(), $title, $navJSON); 
				} else if(!empty($moduleInfo['nav'])) {
					$quicklinks = $this->renderQuicklinks($c, $moduleInfo['nav'], $title); 
				}
				
				$icon = isset($moduleInfo['icon']) ? $moduleInfo['icon'] : '';
				$toggle = $quicklinks ? "<i class='quicklink-open fa fa-bolt'></i>" : "";
				if($class == 'current' && $icon) $title = "<i class='fa fa-fw fa-$icon current-icon'></i>$title";
				if($quicklinks) $class .= " has-quicklinks";
				$out .= "<li><a href='$url' class='$class' data-icon='$icon'><span>$title</span>$toggle</a>" . $quicklinks;
				$out .= "</li>";
			}

			$out .= "</ul>";

		} else {
			
			$class = $class ? " class='$class $p->name'" : "class='$p->name'";
			$out .= "<a href='$p->url' $class><i class='fa {$icon}'></i> <span>$title</span></a>"; 

		}

		$out .= "</li>";

		return $out; 
	}
	
	/**
	 * Render the the user display name as specified in module config.
	 *
	 * @return string
	 *
	 */
	
	public function getDisplayName(User $user) {
        
        $out = "";
        $adminTheme = $this->wire('adminTheme');
        $userFields = explode(',', $adminTheme->userFields);
        
        foreach($userFields as $f){
            $f = trim($f);
            if ($f == 'name'){
                $out .= "{$user->name} "; // can't use wire('fields') to get name field
            } else {
                $field = $this->wire('fields')->get($f);
                if($field instanceof Field && ($field->type == "FieldtypeText" || $field->type == "FieldtypeConcat")){
                    $out .= "{$user->$f} ";
				}
            }
        }

        return rtrim($out, ' '); // clean up the trailing space. 
    }

	/**
	 * Render all sidenav navigation items, ready to populate in ul#main-nav
	 *
	 * @return string
	 *
	 */
	public function renderSideNavItems() {
		$out = '';
		$admin = $this->wire('pages')->get($this->wire('config')->adminRootPageID); 
	
		foreach($admin->children("check_access=0") as $p) {
			if(!$p->viewable()) continue; 
			$out .= $this->renderSideNavItem($p);
		}
		
		return $out; 
	}
}