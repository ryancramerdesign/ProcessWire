<?php

/**
 * AdminThemeDefaultHelpers.php
 * 
 * Rendering helper functions for use with ProcessWire admin theme.
 * 
 * __('FOR TRANSLATORS: please translate the file /wire/templates-admin/default.php rather than this one.'); 
 *
 */ 

class AdminThemeDefaultHelpers extends WireData {

	public function __construct() {
		$renderType = $this->input->get->admin_theme_render;
		if($renderType && $this->wire('user')->isSuperuser()) {
			if($renderType == 'templates') echo $this->renderTemplatesNav();
			if($renderType == 'fields') echo $this->renderFieldsNav();
			exit; 
		}
	}

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

	/**
	 * Get the headline for the current admin page
	 *
	 * @return string
	 *
	 */
	public function getHeadline() {
		$headline = $this->wire('processHeadline'); 
		if(!$headline) $headline = $this->wire('page')->get('title|name'); 
		$headline = $this->_($headline); 
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
			$title = $this->_($breadcrumb->title);
			$out .= "<li><a href='{$breadcrumb->url}'>{$title}</a><i class='fa fa-angle-right'></i></li>";
		}
		if($appendCurrent) $out .= "<li class='title'>" . $this->getHeadline() . "</li>";
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
			"<button class='dropdown-toggle'><i class='fa fa-angle-down'></i> $label</button>" . 
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
	
			if(!$icon) $icon = 'check-square';
	
			if($notice->class && $config->debug) $text = "{$notice->class}: $text";
	
			$remove = $n ? '' : "<a class='notice-remove' href='#'><i class='fa fa-times-circle'></i></a>";
	
			$out .= "\n\t\t<li class='$class'><div class='container'><p>$remove<i class='fa fa-$icon'></i> {$text}</p></div></li>";
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
	public function renderTopNavItem(Page $p, $level = 0) {
	
		$isSuperuser = $this->wire('user')->isSuperuser();
		$showItem = $isSuperuser;
		$children = $p->numChildren && !$level && $p->name != 'page' ? $p->children("check_access=0") : array();
		$adminURL = $this->wire('config')->urls->admin;
		$out = '';
	
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
	
		$class = strpos(wire('page')->path, $p->path) === 0 ? 'on' : '';
		$title = strip_tags((string)$p->get('title|name')); 
		$title = $this->_($title); // translate from context of default.php
		$out .= "<li>";
	
		if(!$level && count($children)) {
	
			$class = trim("$class dropdown-toggle"); 
			$out .= "<a href='$p->url' id='topnav-page-$p' data-from='topnav-page-{$p->parent}' class='$class'>$title</a>"; 
			$my = 'left-1 top';
			if($p->name == 'access') $my = 'left top';
			$out .= "<ul class='dropdown-menu topnav' data-my='$my' data-at='left bottom'>";
	
			foreach($children as $c) {
				
				if($isSuperuser && ($c->id == 11 || $c->id == 16)) {
					// has ajax items
	
					$addLabel = $this->_('Add New');
					$out .=	"<li><a class='has-ajax-items' data-from='topnav-page-$p' href='$c->url'>" . $this->_($c->title) . "</a><ul>" . 
						"<li class='add'><a href='{$c->url}add'><i class='fa fa-plus-circle'></i> $addLabel</a></li>" . 
						"</ul></li>";
				} else {
					$out .= $this->renderTopNavItem($c, $level+1);
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
	public function renderTopNavItems() {
		$out = '';
		$outMobile = '';
		$outTools = '';
		$admin = $this->wire('pages')->get(wire('config')->adminRootPageID); 
		$config = $this->wire('config'); 
		$user = $this->wire('user'); 
	
		foreach($admin->children("check_access=0") as $p) {
			if(!$p->viewable()) continue; 
			$out .= $this->renderTopNavItem($p);
			$outMobile .= "<li><a href='$p->url'>$p->title</a></li>";
		}
	
		$outTools .=	"<li><a href='{$config->urls->root}'><i class='fa fa-eye'></i> " . 
				$this->_('View Site') . "</a></li>";
	
		if($user->isLoggedin()) {
			if($user->hasPermission('profile-edit')) {
				$outTools .= 
					"<li><a href='{$config->urls->admin}profile/'><i class='fa fa-user'></i> " . 
					$this->_('Profile') . " <small>{$user->name}</small></a></li>";
			}
			$outTools .= 
				"<li><a href='{$config->urls->admin}login/logout/'>" . 
				"<i class='fa fa-power-off'></i> " . $this->_('Logout') . "</a></li>";
		}
	
		$outMobile = "<ul id='topnav-mobile' class='dropdown-menu topnav' data-my='left top' data-at='left bottom'>$outMobile$outTools</ul>";
	
		$out .=	"<li>" . 
			"<a target='_blank' id='tools-toggle' class='dropdown-toggle' href='{$config->urls->root}'>" . 
			"<i class='fa fa-wrench'></i></a>" . 
			"<ul class='dropdown-menu topnav' data-my='left top' data-at='left bottom'>" . $outTools . 
			"</ul></li>";
	
		$out .=	"<li class='collapse-topnav-menu'><a href='$admin->url' class='dropdown-toggle'>" . 
			"<i class='fa fa-lg fa-bars'></i></a>$outMobile</li>";
		
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
		if(strpos($httpHost, 'www.') === 0) $browserTitle = substr($browserTitle, 4); // remove www
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
