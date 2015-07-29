<?php

/**
 * ProcessWire Process
 *
 * Process is the base Module class for each part of ProcessWire's web admin.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2014 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

abstract class Process extends WireData implements Module {

	/**
	 * Per the Module interface, return an array of information about the Process
	 *
	 * The 'permission' property is specific to Process instances, and allows you to specify the name of a permission
	 * required to execute this process. 
	 * 
	 * Note that you may want your Process module to use the 'page' property defined below. To make use of it, make
	 * sure it is included in your module info, and make sure your Process module either omits install/uninstall methods,
	 * or calls the ones in this class, i.e. 
	 * 
	 * public function ___install() {
	 *   parent::___install(); 
	 * }
	 *
	 */
	
	/*
	public static function getModuleInfo() {
		return array(
			'title' => '',				// printable name/title of module
			'version' => 1, 			// version number of module
			'summary' => '', 			// one sentence summary of module
			'href' => '', 				// URL to more information (optional)
			'permanent' => true, 		// true if module is permanent and thus not uninstallable (3rd party modules should specify 'false')
	 		'page' => array( 			// optionally install/uninstall a page for this process automatically
	 			'name' => 'page-name', 	// name of page to create
	 			'parent' => 'setup', 	// parent name (under admin) or omit or blank to assume admin root
	 			'title' => 'Title', 	// title of page, or omit to use the title already specified above
	 			)
			),
			'useNavJSON' => true, 		// Supports JSON navigation?
			'nav' => array(				// Optional navigation options for admin theme drop downs
				array(
					'url' => 'action/',
					'label' => 'Some Action', 
					'permission' => 'some-permission', // optional permission required to access this item
					'icon' => 'folder-o', // optional icon
					'navJSON' => 'navJSON/?custom=1' // optional JSON url to get items, relative to page URL that Process module lives on
				),
				array(
					'url' => 'action2/',
					'label' => 'Another Action', 
					'icon' => 'plug',
				),
			),
			'permission' => '', 		// name of permission required to execute this Process (optional)
			'permissions' => array(..),	// see Module.php for details
			'permissionMethod' => '', 	// Optional name of a static method to perform additional permission checks. 
										// It receives array with: wire (PW instance), user (User), page (Page), 
										// info (moduleInfo array), method (requested method)
										// It should return a true or false.
	}
 	*/

	/**
	 * File to use for output view
	 * 
	 * Used when execute methods return an array of vars, or have called setViewVars()
	 * 
	 * @var string
	 * 
	 */
	private $_viewFile = '';

	/**
	 * Variables to send to the output view file, populated only if setViewVars() has been called
	 * 
	 * @var array associative
	 * 
	 */
	private $_viewVars = array();

	/**
	 * Per the Module interface, Initialize the Process, loading any related CSS or JS files
	 *
	 */
	public function init() { 
		$this->wire('modules')->loadModuleFileAssets($this); 
	}

	/**
	 * Execute this Process and return the output 
	 *
	 * @return string
	 *
	 */
	public function ___execute() { }

	/**
	 * Get a value stored in this Process
	 * 
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {
		if(($value = $this->getFuel($key)) !== null) return $value; 
		return parent::get($key); 
	}

	/**
	 * Per the Module interface, Process modules only retain one instance in memory
	 *
	 */
	public function isSingular() {
		return true; 
	}

	/**
	 * Per the Module interface, Process modules are not loaded until requested from from the API
	 *
	 */
	public function isAutoload() {
		return false; 
	}

	/**
	 * Set the current headline to appear in the interface
	 * 
	 * @param string $headline
	 * @return this
	 *
	 */
	public function ___headline($headline) {
		$this->wire('processHeadline', $headline); 
		return $this; 
	}

	/**
	 * Add a breadcrumb
	 * 
	 * @param string $href
	 * @param string $label
	 * @return this
	 *
	 */
	public function ___breadcrumb($href, $label) {
		$pos = strpos($label, '/'); 
		if($pos !== false && strpos($href, '/') === false) {
			// arguments got reversed, we'll work with it anyway...
			if($pos === 0 || $label[0] == '.' || substr($label, -1) == '/') {
				$_href = $href; 
				$href = $label;
				$label = $_href;
			}
		}
		$this->wire('breadcrumbs')->add(new Breadcrumb($href, $label));
		return $this;
	}

	/**
	 * Per the Module interface, Install the Process module
	 *
	 * By default a permission equal to the name of the class is installed, unless overridden with the 'permission' property in getModuleInfo().
	 *
	 */
	public function ___install() {
		$info = $this->wire('modules')->getModuleInfo($this, array('noCache' => true)); 
		// if a 'page' property is provided in the moduleInfo, we will create a page and assign this process automatically
		if(!empty($info['page'])) { // bool, array, or string
			$defaults = array(
				'name' => '', 
				'parent' => null, 
				'title' => '', 
				'template' => 'admin'
			);
			$a = $defaults;
			if(is_array($info['page'])) {
				$a = array_merge($a, $info['page']);
			} else if(is_string($info['page'])) {
				$a['name'] = $info['page'];
			}
			// find any other properties that were specified, which will will send as $extras properties
			$extras = array();
			foreach($a as $key => $value) {
				if(in_array($key, array_keys($defaults))) continue; 
				$extras[$key] = $value; 
			}
			// install the page
			$this->installPage($a['name'], $a['parent'], $a['title'], $a['template'], $extras); 
		}
	}

	/**
	 * Uninstall this Process
	 *
	 * Note that the Modules class handles removal of any Permissions that the Process may have installed
	 *
	 */
	public function ___uninstall() {
		$info = $this->wire('modules')->getModuleInfo($this, array('noCache' => true));
		// if a 'page' property is provided in the moduleInfo, we will trash pages using this Process automatically
		if(!empty($info['page'])) $this->uninstallPage();
	}


	/**
	 * Install a dedicated page for this Process module and assign it this Process
	 * 
	 * To be called by Process module's ___install() method. 
	 *
	 * @param string $name Desired name of page, or omit (or blank) to use module name
	 * @param Page|string|int|null Parent for the page, with one of the following:
	 * 	- name of parent, relative to admin root, i.e. "setup"
	 * 	- Page object of parent
	 * 	- path to parent
	 * 	- parent ID
	 * 	- Or omit and admin root is assumed
	 * @param string $title Omit or blank to pull title from module information
	 * @param string|Template Template to use for page (omit to assume 'admin')
	 * @param array $extras Any extra properties to assign (like status)
	 * @return Page Returns the page that was created
	 * @throws WireException if page can't be created
	 *
	 */
	protected function ___installPage($name = '', $parent = null, $title = '', $template = 'admin', $extras = array()) {
		$info = $this->wire('modules')->getModuleInfo($this);
		$name = $this->wire('sanitizer')->pageName($name);
		if(!strlen($name)) $name = strtolower(preg_replace('/([A-Z])/', '-$1', str_replace('Process', '', $this->className()))); 
		$adminPage = $this->wire('pages')->get($this->wire('config')->adminRootPageID); 
		if($parent instanceof Page) $parent = $parent; // nice
			else if(ctype_digit("$parent")) $parent = $this->wire('pages')->get((int) $parent); 
			else if(strpos($parent, '/') !== false) $parent = $this->wire('pages')->get($parent); 
			else if($parent) $parent = $adminPage->child("include=all, name=" . $this->wire('sanitizer')->pageName($parent)); 
		if(!$parent || !$parent->id) $parent = $adminPage; // default
		$page = $parent->child("include=all, name=$name"); // does it already exist?
		if($page->id && $page->process == $this) return $page; // return existing copy
		$page = new Page();
		$page->template = $template ? $template : 'admin';
		$page->name = $name; 
		$page->parent = $parent; 
		$page->process = $this;
		$page->title = $title ? $title : $info['title'];
		foreach($extras as $key => $value) $page->set($key, $value); 
		$this->wire('pages')->save($page, array('adjustName' => true)); 
		if(!$page->id) throw new WireException("Unable to create page: $parent->path$name"); 
		$this->message(sprintf($this->_('Created Page: %s'), $page->path)); 
		return $page;
	}

	/**
	 * Uninstall (trash) dedicated pages for this Process module
	 *
	 * If there is more than one page using this Process, it will trash them all.
	 * 
	 * To be called by the Process module's ___uninstall() method. 
	 * 
	 * @return int Number of pages trashed
	 *
	 */
	protected function ___uninstallPage() {
		$moduleID = $this->wire('modules')->getModuleID($this);
		if(!$moduleID) return 0;
		$n = 0; 
		foreach($this->wire('pages')->find("process=$moduleID, include=all") as $page) {
			if($page->process != $this) continue; 
			$page->process = null;
			$this->message(sprintf($this->_('Trashed Page: %s'), $page->path)); 
			$this->wire('pages')->trash($page);
			$n++;
		}
		return $n;
	}

	/**
	 * Return JSON data of items managed by this Process for use in navigation
	 * 
	 * Optional/applicable only to Process modules that manage groups of items.
	 * 
	 * This method is only used if your getModuleInfo returns TRUE for useNavJSON
	 * 
	 * @param array $options For descending classes to modify behavior (see $defaults in method)
	 * @return string rendered JSON string
	 * @throws Wire404Exception if getModuleInfo() doesn't specify useNavJSON=true;
	 * 
	 */
	public function ___executeNavJSON(array $options = array()) {
		
		$defaults = array(
			'items' => array(),
			'itemLabel' => 'name', 
			'itemLabel2' => '', // smaller secondary label, when needed
			'edit' => 'edit?id={id}', // URL segment for edit
			'add' => 'add', // URL segment for add
			'addLabel' => __('Add New', '/wire/templates-admin/default.php'),
			'iconKey' => 'icon', // property/field containing icon, when applicable
			'icon' => '', // default icon to use for items
			'sort' => true, // automatically sort items A-Z?
			);
		
		$options = array_merge($defaults, $options); 
		$moduleInfo = $this->modules->getModuleInfo($this); 
		if(empty($moduleInfo['useNavJSON'])) throw new Wire404Exception();
		
		$page = $this->wire('page');
		$data = array(
			'url' => $page->url,
			'label' => $this->_((string) $page->get('title|name')),
			'icon' => empty($moduleInfo['icon']) ? '' : $moduleInfo['icon'], // label icon
			'add' => array(
				'url' => $options['add'],
				'label' => $options['addLabel'], 
			),
			'list' => array(),
		);
		
		if(empty($options['add'])) $data['add'] = null;
		
		foreach($options['items'] as $item) {
			$icon = '';
			if(is_object($item)) {
				$id = $item->id;
				$name = $item->name; 
				$label = (string) $item->{$options['itemLabel']};
				$icon = str_replace(array('icon-', 'fa-'),'', $item->{$options['iconKey']});
			} else if(is_array($item)) {
				$id = $item['id'];
				$name = $item['name'];
				$label = $item[$options['itemLabel']];
				if(isset($item['icon'])) $icon = str_replace(array('icon-', 'fa-'),'', $item[$options['iconKey']]);
			} else {
				$this->error("Item must be object or array: $item"); 
				continue;
			}
			if(empty($icon) && $options['icon']) $icon = $options['icon'];
			$_label = $label;
			$label = $this->wire('sanitizer')->entities1($label);
			while(isset($data['list'][$_label])) $_label .= "_";
		
			if($options['itemLabel2']) {
				$label2 = is_array($item) ? $item[$options['itemLabel2']] : $item->{$options['itemLabel2']}; 
				if(strlen($label2)) {
					$label2 = $this->wire('sanitizer')->entities1($label2);
					$label .= " <small>$label2</small>";
				}
			}
			
			$data['list'][$_label] = array(
				'url' => str_replace(array('{id}', '{name}'), array($id, $name), $options['edit']),
				'label' => $label,
				'icon' => $icon, 
			);
		}
		if($options['sort']) ksort($data['list']); // sort alpha
		$data['list'] = array_values($data['list']); 

		if($this->wire('config')->ajax) header("Content-Type: application/json");
		return json_encode($data);
	}

	/**
	 * Set the file to use for the output view, if different from default
	 * 
	 * @param string $file File must be relative to the module's home directory.
	 * @return $this
	 * @throws WireException if file doesn't exist
	 * 
	 */
	public function setViewFile($file) {
		$path = $this->wire('config')->paths . $this->className(); 
		if(strpos($file, $path) !== 0) $file = $path . ltrim($file, '/');
		if(strpos($file, '..') !== false) throw new WireException("Invalid view file"); 
		if(!is_file($file)) throw new WireException("View file $file does not exist"); 
		$this->_viewFile = $file;
		return $this;	
	}

	/**
	 * If a view file has been set, this returns the full path to it
	 * 
	 * @return string Blank if no view file set, full path and file if set
	 * 
	 */
	public function getViewFile() {
		return $this->_viewFile;
	}

	/**
	 * Set a variable that will be passed to the output view
	 * 
	 * @param string|array $key Property to set, or array of property=>value to set (leaving 2nd argument as null)
	 * @param mixed|null $value Value to set
	 * @return $this
	 * @throws WireException if given an invalid type for $key
	 * 
	 */
	public function setViewVars($key, $value = null) {
		if(is_array($key)) {
			$this->_viewVars = array_merge($this->_viewVars, $key);
		} else if(is_string($key)) {
			$this->_viewVars[$key] = $value;
		} else {
			throw new WireException("Invalid setViewVars('key')");
		}
		return $this;
	}

	/**
	 * Get all variables set for the output view
	 * 
	 * @return array associative
	 * 
	 */
	public function getViewVars() {
		return $this->_viewVars; 
	}
}
