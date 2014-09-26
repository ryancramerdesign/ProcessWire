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
		if($appendCurrent) $out .= "<li class='title'>" . $this->getHeadline() . "</li>";
		return $out; 
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
		$url = $config->urls->admin . 'page/add/';
		$out = '';
	
		foreach($this->wire('templates') as $template) {
			$parent = $template->getParentPage(true); 
			if(!$parent) continue; 
			if($parent->id) {
				// one parent possible	
				$qs = "?parent_id=$parent->id";
			} else {
				// multiple parents possible
				$qs = "?template_id=$template->id";
			}
			$icon = $template->getIcon();
			if(!$icon) $icon = "plus-circle";
			$label = $this->wire('sanitizer')->entities1($template->getLabel());
			$out .= "<li><a href='$url$qs'><i class='fa fa-fw fa-$icon'></i>&nbsp;$label</a></li>";
		}
	
		if(empty($out)) return '';
	
		$label = $this->getAddNewLabel();
	
		$out =	
			"<div id='head_button'>" . 	
			"<button class='ui-button dropdown-toggle'><i class='fa fa-angle-down'></i> $label</button>" . 
			"<ul class='dropdown-menu shortcuts' data-at='right bottom+1'>$out</ul>" . 
			"</div>";
	
		return $out; 
	}
	
	/**
	 * Render runtime notices div#notices
	 *
	 * @param array $options See defaults in method
	 * @param Notices $notices
	 * @return string
	 *
	 */
	public function renderAdminNotices($notices, array $options = array()) {
		
		$defaults = array(
			'messageClass' => 'ui-state-highlight NoticeMessage', // class for messages
			'messageIcon' => 'check-square', // default icon to show with notices

			'warningClass' => 'ui-state-error NoticeWarning', // class for warnings
			'warningIcon' => 'warning', // icon for warnings

			'errorClass' => 'ui-state-error ui-priority-primary NoticeError', // class for errors
			'errorIcon' => 'exclamation-triangle', // icon for errors
		
			'debugClass' => 'ui-priority-secondary NoticeDebug', // class for debug items (appended)
			'debugIcon' => 'gear', // icon for debug notices
		
			'closeClass' => 'notice-remove', // class for close notices link <a>
			'closeIcon' => 'times-circle', // icon for close notices link
	
			'listMarkup' => "\n\t<ul id='notices' class='ui-widget'>{out}</ul><!--/notices-->", 
			'itemMarkup' => "\n\t\t<li class='{class}'><div class='container'><p>{remove}<i class='fa fa-fw fa-{icon}'></i> {text}</p></div></li>",
			);

		if(!count($notices)) return '';
		$options = array_merge($defaults, $options); 
		$config = $this->wire('config'); 
		$out = '';
	
		foreach($notices as $n => $notice) {
	
			$text = $notice->text; 
			if($notice->flags & Notice::allowMarkup) {
				// leave $text alone
			} else {
				// unencode entities, just in case module already entity some or all of output
				if(strpos($text, '&') !== false) $text = html_entity_decode($text, ENT_QUOTES, "UTF-8"); 
				// entity encode it
				$text = $this->wire('sanitizer')->entities($text); 
			}
	
			if($notice instanceof NoticeError) {
				$class = $options['errorClass'];
				$icon = $options['errorIcon']; 
			} else if($notice->flags & Notice::warning) {
				$class = $options['warningClass'];
				$icon = $options['warningIcon'];
			} else {
				$class = $options['messageClass'];
				$icon = $options['messageIcon'];
			}
	
			if($notice->flags & Notice::debug) {
				$class .= " " . $options['debugClass'];
				$icon = $options['debugIcon'];
			}

			// indicate which class the notice originated from in debug mode
			if($notice->class && $config->debug) $text = "{$notice->class}: $text";

			// show remove link for first item only
			$remove = $n ? '' : "<a class='$options[closeClass]' href='#'><i class='fa fa-$options[closeIcon]'></i></a>";
			
			$replacements = array(
				'{class}' => $class, 
				'{remove}' => $remove, 
				'{icon}' => $icon,
				'{text}' => $text, 
				);
			
			$out .= str_replace(array_keys($replacements), array_values($replacements), $options['itemMarkup']); 
		}
		
		$out = str_replace('{out}', $out, $options['listMarkup']); 
		return $out; 
	}

	/**
	 * Get markup for icon used by the given page
	 * 
	 * @param Page $p
	 * @return mixed|null|string
	 * 
	 */
	public function getPageIcon(Page $p) {
		$icon = '';
		if($p->template == 'admin') {
			$info = $this->wire('modules')->getModuleInfo($p->process); 
			if(!empty($info['icon'])) $icon = $info['icon'];
		}
		if($p->page_icon) $icon = $p->page_icon; // allow for option of an admin field overriding the module icon
		if(!$icon && $p->parent->id != $this->wire('config')->adminRootPageID) $icon = 'file-o ui-priority-secondary';
		if($icon) $icon = "<i class='fa fa-fw fa-$icon'></i>&nbsp;";
		return $icon;
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
		$children = $p->numChildren && !$level ? $p->children("check_access=0") : array();
		$numChildren = count($children); 
		$out = '';
	
		if(!$showItem) { 
			$checkPages = $numChildren ? $children : array($p); 
			foreach($checkPages as $child) {
				if($child->viewable()) {
					$showItem = true;
					break;
				}
			}
		}
		
		if(!$showItem) return '';

		if($numChildren && $p->name == 'page') {
			// don't bother with a drop-down for "Pages" if user will only see 1 "tree" item, duplicating the tab
			if($numChildren == 2 && !$isSuperuser && !$this->wire('user')->hasPermission('page-lister')) $children = array();
			if($numChildren == 1) $children = array();
		}

		$class = strpos($this->wire('page')->path, $p->path) === 0 ? 'on' : '';
		$title = strip_tags((string) $p->title); 
		if(!strlen($title)) $title = $p->name; 
		$title = $this->_($title); // translate from context of default.php
		$out .= "<li>";
	
		if(!$numChildren && $p->template == 'admin' && $p->process) {
			$moduleInfo = $this->wire('modules')->getModuleInfo($p->process); 
			if(!empty($moduleInfo['nav'])) $children = $moduleInfo['nav'];
		}
	
		if(!$level && count($children)) {
	
			$class = trim("$class dropdown-toggle"); 
			$out .= "<a href='$p->url' id='topnav-page-$p' data-from='topnav-page-{$p->parent}' class='$class'>$title</a>"; 
			$my = 'left-1 top';
			if(in_array($p->name, array('access', 'page', 'module'))) $my = 'left top';
			$out .= "<ul class='dropdown-menu topnav' data-my='$my' data-at='left bottom'>";
	
			if($children instanceof PageArray) foreach($children as $c) {
			
				if(!$c->process) continue; 
				$moduleInfo = $this->wire('modules')->getModuleInfo($c->process); 
				if($isSuperuser) $hasPermission = true; 
					else if(isset($moduleInfo['permission'])) $hasPermission = $this->wire('user')->hasPermission($moduleInfo['permission']); 	
					else $hasPermission = false;
				
				if(!$hasPermission) continue; 
				
				if(!empty($moduleInfo['nav'])) {
					// process defines its own subnav
					$icon = $this->getPageIcon($c);
					$title = $this->_($c->title); 
					if(!$title) $title = $c->name; 
					$out .= 
						"<li><a class='has-items' data-from='topnav-page-$p' href='$c->url'>$icon$title</a>" . 
						"<ul>" . $this->renderTopNavItemArray($c, $moduleInfo['nav']) . "</ul></li>";
					
				} else if(!empty($moduleInfo['useNavJSON'])) {
					// has ajax items
					$icon = $this->getPageIcon($c);
					$out .=
						"<li><a class='has-items has-ajax-items' data-from='topnav-page-$p' data-json='{$c->url}navJSON/' " .
						"href='$c->url'>$icon" . $this->_($c->title) . "</a><ul></ul></li>";

				} else {
					// regular nav item
					$out .= $this->renderTopNavItem($c, $level+1);
				}
				
			} else if(is_array($children) && count($children)) {
				$out .= $this->renderTopNavItemArray($p, $children); 
			}
	
			$out .= "</ul>";
	
		} else {
			
			$class = $class ? " class='$class'" : '';
			$url = $p->url;
			$icon = $level > 0 ? $this->getPageIcon($p) : '';
			
			// The /page/ and /page/list/ are the same process, so just keep them on /page/ instead. 
			if(strpos($url, '/page/list/') !== false) $url = str_replace('/page/list/', '/page/', $url); 
			
			$out .= "<a href='$url'$class>$icon$title</a>"; 
		}
	
		$out .= "</li>";
	
		return $out; 
	}

	/**
	 * Renders static navigation from an array coming from getModuleInfo()['nav'] array (see wire/core/Process.php)
	 * 
	 * @param Page $p
	 * @param array $nav
	 * @return string
	 * 
	 */
	protected function renderTopNavItemArray(Page $p, array $nav) {
		// process module with 'nav' property
		$out = '';
		$textdomain = str_replace($this->wire('config')->paths->root, '/', $this->wire('modules')->getModuleFile($p->process));
		
		foreach($nav as $item) {
			if(!empty($item['permission']) && !$this->wire('user')->hasPermission($item['permission'])) continue;
			$icon = empty($item['icon']) ? '' : "<i class='fa fa-fw fa-$item[icon]'></i>&nbsp;";
			$label = __($item['label'], $textdomain); // translate from context of Process module
			if(empty($item['navJSON'])) {
				$out .= "<li><a href='{$p->url}$item[url]'>$icon$label</a></li>";
			} else {
				$out .= 
					"<li><a class='has-items has-ajax-items' data-from='topnav-page-$p' data-json='{$p->url}$item[navJSON]' " . 
					"href='{$p->url}$item[url]'>$icon$label</a><ul></ul></li>";
			}
		}
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
		$config = $this->wire('config'); 
		$admin = $this->wire('pages')->get($config->adminRootPageID); 
		$user = $this->wire('user'); 
	
		foreach($admin->children("check_access=0") as $p) {
			if(!$p->viewable()) continue; 
			$out .= $this->renderTopNavItem($p);
			$outMobile .= "<li><a href='$p->url'>$p->title</a></li>";
		}
	
		$outTools .=	
			"<li><a href='{$config->urls->root}'><i class='fa fa-fw fa-eye'></i> " . 
			$this->_('View Site') . "</a></li>";
	
		if($user->isLoggedin()) {
			if($user->hasPermission('profile-edit')) {
				$outTools .= 
					"<li><a href='{$config->urls->admin}profile/'><i class='fa fa-fw fa-user'></i> " . 
					$this->_('Profile') . " <small>{$user->name}</small></a></li>";
			}
			$outTools .= 
				"<li><a href='{$config->urls->admin}login/logout/'>" . 
				"<i class='fa fa-fw fa-power-off'></i> " . $this->_('Logout') . "</a></li>";
		}
	
		$outMobile = "<ul id='topnav-mobile' class='dropdown-menu topnav' data-my='left top' data-at='left bottom'>$outMobile$outTools</ul>";
	
		$out .=	
			"<li>" . 
			"<a target='_blank' id='tools-toggle' class='dropdown-toggle' href='{$config->urls->root}'>" . 
			"<i class='fa fa-wrench'></i></a>" . 
			"<ul class='dropdown-menu topnav' data-my='left top' data-at='left bottom'>" . $outTools . 
			"</ul></li>";
	
		$out .=	
			"<li class='collapse-topnav-menu'><a href='$admin->url' class='dropdown-toggle'>" . 
			"<i class='fa fa-lg fa-bars'></i></a>$outMobile</li>";
		
		return $out; 
	}
	
	/**
	 * Returns editable items of array('url to edit' => 'label) or booean if $checkOnly is true
	 *
	 * @param Page $page
	 * @param bool $checkOnly Specify true to have this method return true/false if items are available.
	 * 
	 * @return bool|array
	 *
	 */
	protected function ___getEditableItems(Page $page, $checkOnly = false) {

		$items = array();
		if(!$this->wire('user')->isSuperuser()) {
			if($checkOnly) return false; 
			return array();
		}

		if($page->id == 11) {

			if($checkOnly) return true;
			
			$url = $this->wire('config')->urls->admin . 'setup/template/edit?id=';
			foreach($this->wire('templates') as $template) {
				if($template->flags & Template::flagSystem) continue;
				$items[$url . $template->id] = $template->name;
			}
			
		} else if($page->id == 16) {

			if($checkOnly) return true;
			
			$url = $this->wire('config')->urls->admin . 'setup/field/edit?id=';
			foreach($this->wire('fields') as $field) {
				if(($field->flags & Field::flagSystem) && $field->name != 'title') continue;
				$items[$url . $field->id] = $field->name;
			}
			
		} else {
			if($checkOnly) return false; 
		}

		return $items;
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
		if(!$this->wire('input')->get('modal')) {
			$httpHost = $this->wire('config')->httpHost;
			if(strpos($httpHost, 'www.') === 0) $httpHost = substr($httpHost, 4); // remove www
			if(strpos($httpHost, ':')) $httpHost = preg_replace('/:\d+/', '', $httpHost); // remove port
			$browserTitle .= ' &bull; ' . $this->wire('sanitizer')->entities($httpHost);
		}
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
		$bodyClass .= "id-{$page->id} template-{$page->template->name} pw-init";
		if($this->wire('config')->js('JqueryWireTabs')) $bodyClass .= " hasWireTabs";
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

		return "var config = " . wireEncodeJSON($jsConfig, true, $config->debug);
	}
	
	public function getAddNewLabel() {
		return $this->_('Add New');
	}


}
