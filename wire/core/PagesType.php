<?php

/**
 * ProcessWire PagesType
 *
 * Provides an interface to the Pages class but specific to 
 * a given page class/type, with predefined parent and template. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class PagesType extends Wire implements IteratorAggregate, Countable {

	/**
	 * First template defined for use in this PagesType (legacy)
	 * 
	 * @var Template
	 * 
	 */
	protected $template = null;

	/**
	 * Templates defined for use in this PagesType
	 * 
	 * @var array of Template objects indexed by template id
	 * 
	 */
	protected $templates = array();

	/**
	 * ID of the first parent page used by this PagesType (legacy)
	 * 
	 * @var int
	 *
	 */
	protected $parent_id = null;

	/**
	 * Parent IDs defined for use in this PagesType
	 * 
	 * @var array of page IDs indexed by ID.
	 * 
	 */
	protected $parents = array();

	/**
	 * Class name to instantiate pages as
	 * 
	 * Default=blank, which makes it pull from the $template->pageClass property instead. 
	 * 
	 * @var string
	 * 
	 */
	protected $pageClass = '';
	
	/**
	 * Construct this PagesType manager for the given parent and template
	 *
	 * @param Template|int|string|array $templates Template object or array of template objects, names or IDs
	 * @param int|Page|array $parents Parent ID or array of parent IDs (may also be Page or array of Page objects)
	 *
	 */
	public function __construct($templates = array(), $parents = array()) {
		$this->addTemplates($templates);
		$this->addParents($parents); 
	}

	/**
	 * Add one or more templates that this PagesType represents
	 * 
	 * @param array|int|string $templates Single or array of Template objects, IDs, or names
	 * 
	 */
	public function addTemplates($templates) {
		if(WireArray::iterable($templates)) {
			// array already provided
			foreach($templates as $template) {
				if(is_int($template) || !$template instanceof Template) $template = $this->wire('templates')->get($template);
				if(!$template) continue;
				$this->templates[$template->id] = $template;
			}
		} else {
			// single template object, id, or name provided
			if($templates instanceof Template) {
				$this->templates[$templates->id] = $templates;
			} else {
				// template id or template name
				$template = $this->wire('templates')->get($templates);
				if($template) $this->templates[$template->id] = $template;
			}
		}
		if(empty($this->template)) $this->template = reset($this->templates); // legacy deprecated
	}

	/**
	 * Add one or more of parents that this PagesType represents
	 * 
	 * @param array|int|string|Page $parents Single or array of Page objects, IDs, or paths
	 * 
	 */
	public function addParents($parents) {
		if(!WireArray::iterable($parents)) $parents = array($parents);
		foreach($parents as $parent) {
			if(is_int($parent)) {
				$id = $parent;
			} else if(is_string($parent) && ctype_digit($parent)) {
				$id = (int) $parent;
			} else if(is_string($parent)) {
				$parent = $this->wire('pages')->findOne($parent, array('loadOptions' => array('autojoin' => false)));
				$id = $parent->id;
			} else if(is_object($parent) && $parent instanceof Page) {
				$id = $parent->id;
			}
			if($id) {
				$this->parents[$id] = $id;
			}
		}
		if(empty($this->parent_id)) $this->parent_id = reset($this->parents); // legacy deprecated
	}
		

	/**
	 * Convert the given selector string to qualify for the proper page type
	 *
	 * @param string $selectorString
	 * @return string
	 *
	 */
	protected function selectorString($selectorString) {
		if(ctype_digit("$selectorString")) $selectorString = "id=$selectorString"; 
		if(strpos($selectorString, 'sort=') === false && !preg_match('/\bsort=/', $selectorString)) {
			$template = reset($this->templates);
			if($template->sortfield) {
				$sortfield = $template->sortfield;
			} else {
				$sortfield = $this->getParent()->sortfield;
			}
			if(!$sortfield) $sortfield = 'sort';
			$selectorString = trim($selectorString, ", ") . ", sort=$sortfield";
		}
		if(count($this->parents)) $selectorString .= ", parent_id=" . implode('|', $this->parents);
		if(count($this->templates)) $selectorString .= ", templates_id=" . implode('|', array_keys($this->templates));
		return $selectorString; 
	}

	/**
	 * Each loaded page is passed through this function for additional checks if needed
	 *	
	 */
	protected function loaded(Page $page) { }

	/**
	 * Is the given page a valid type for this class?
	 *
	 * @param Page $page
	 * @return bool
	 *
	 */
	public function isValid(Page $page) {

		// quick exit when possible
		if($this->template->id == $page->template->id && $this->parent_id == $page->parent_id) return true;
		
		$validTemplate = false;
		foreach($this->templates as $template) {
			if($page->template->id == $template->id) {
				$validTemplate = true;
				break;
			}
		}
		
		if(!$validTemplate && count($this->templates)) {
			$validTemplates = implode(', ', array_keys($this->templates));
			$this->error("Page $page->path must have template: $validTemplates");
			return false;
		}
		
		$validParent = false;
		foreach($this->parents as $parent_id) {
			if($parent_id == $page->parent_id) {
				$validParent = true;
				break;
			}
		}
	
		if(!$validParent && count($this->parents)) {
			$validParents = impode(', ', $this->parents);
			$this->error("Page $page->path must have parent: $validParents");
			return false;
		}
		
		return true; 
	}

	/**
	 * Get options that will be passed to Pages::getById()
	 * 
	 * @param array $loadOptions Optionally specify options to merge with and override defaults
	 * @return array
	 * 
	 */
	protected function getLoadOptions(array $loadOptions = array()) {
		$_loadOptions = array(
			'pageClass' => $this->getPageClass(),
			//'getNumChildren' => false, 
			'joinSortfield' => false,
			'joinFields' => $this->getJoinFieldNames()
		);
		if(count($loadOptions)) $_loadOptions = array_merge($_loadOptions, $loadOptions);
		return $_loadOptions; 
	}

	/**
	 * Given a Selector string, return the Page objects that match in a PageArray. 
	 *
	 * @param string $selectorString
	 * @param array $options 
		- findOne: apply optimizations for finding a single page and include pages with 'hidden' status
	 * @return PageArray
	 *
	 */
	public function find($selectorString, $options = array()) {
		if(!isset($options['findAll'])) $options['findAll'] = true;
		if(!isset($options['loadOptions'])) $options['loadOptions'] = array();
		$options['loadOptions'] = $this->getLoadOptions($options['loadOptions']); 
		$pages = $this->wire('pages')->find($this->selectorString($selectorString), $options);
		foreach($pages as $page) $this->loaded($page); 
		return $pages; 
	}

	/**
	 * Like find() but returns only the first match as a Page object (not PageArray)
	 * 
	 * This is an alias of the findOne() method for syntactic convenience and consistency.
	 *
	 * @param string $selectorString
	 * @return Page|null
	 */
	public function get($selectorString) {
		
		$options = $this->getLoadOptions(array('getOne' => true));
		
		if(ctype_digit("$selectorString")) {
			// selector string contains a page ID
			if(count($this->templates) == 1 && count($this->parents) == 1) {
				// optimization for when there is only 1 template and 1 parent
				$options['template'] = $this->template;
				$options['parent_id'] = $this->parent_id; 
				$page = $this->wire('pages')->getById(array((int) $selectorString), $options);
				return $page ? $page : new NullPage();
			} else {
				// multiple possible templates/parents
				$page = $this->wire('pages')->getById(array((int) $selectorString), $options); 
				return $page; 
			}
			
		} else if(strpos($selectorString, '=') === false) { 
			// selector string contains no operators, so it is a page name or path
			if(strpos($selectorString, '/') === false) {
				// selector string contains no operators or slashes, so we assume it to be a page ame
				$s = $this->sanitizer->name($selectorString);
				if($s === $selectorString) $selectorString = "name=$s";
			} else {
				// page path, can pass through
			}
			
		} else {
			// selector string with operators, can pass through
		}

		$page = $this->pages->findOne($this->selectorString($selectorString), array('loadOptions' => $options)); 
		if($page->id) $this->loaded($page);
		
		return $page; 
	}

	/**
	 * Save a page object and it's fields to database. 
	 *
	 * If the page is new, it will be inserted. If existing, it will be updated. 
	 *
	 * This is the same as calling $page->save()
	 *
	 * If you want to just save a particular field in a Page, use $page->save($fieldName) instead. 
	 *
	 * @param Page $page
	 * @return bool True on success
	 * @throws WireException
	 *
	 */
	public function ___save(Page $page) {
		if(!$this->isValid($page)) throw new WireException($this->errors('first'));
		return $this->wire('pages')->save($page);
	}
	
	/**
	 * Permanently delete a page and it's fields. 
	 *
	 * Unlike trash(), pages deleted here are not restorable. 
	 *
	 * If you attempt to delete a page with children, and don't specifically set the $recursive param to True, then 
	 * this method will throw an exception. If a recursive delete fails for any reason, an exception will be thrown.
	 *
	 * @param Page $page
	 * @param bool $recursive If set to true, then this will attempt to delete all children too. 
	 * @return bool
	 * @throws WireException
	 *
	 */
	public function ___delete(Page $page, $recursive = false) {
		if(!$this->isValid($page)) throw new WireException($this->errors('first'));
		return $this->pages->delete($page, $recursive);
	}

	/**
	 * Adds a new page with the given $name and returns the Page
	 *
	 * If they page has any other fields, they will not be populated, only the name will.
	 * Returns a NullPage if error, such as a page of this type already existing with the same name.
	 *
	 * @param string $name
	 * @return Page|NullPage
	 *
	 */
	public function ___add($name) {
		
		$className = $this->getPageClass();
		$parent = $this->getParent();

		$page = new $className(); 
		$page->template = $this->template; 
		$page->parent = $parent; 
		$page->name = $name; 
		$page->sort = $parent->numChildren; 

		try {
			$this->save($page); 

		} catch(Exception $e) {
			$page = new NullPage();
		}

		return $page; 
	}

	/**
	 * Make it possible to iterate all pages of this type per the IteratorAggregate interface.
	 *
	 * Only recommended for page types that don't contain a lot of pages. 
	 *
	 */
	public function getIterator() {
		return $this->find("id>0, sort=name"); 
	}	

	public function getTemplate() {
		return $this->template; 
	}
	
	public function getTemplates() {
		return count($this->templates) ? $this->templates : array($this->template);
	}

	public function getParentID() {
		return $this->parent_id; 
	}
	
	public function getParentIDs() {
		return count($this->parents) ? $this->parents : array($this->parent_id); 
	}

	public function getParent() {
		return $this->wire('pages')->findOne($this->parent_id);
	}
	
	public function getParents() {
		if(count($this->parents)) {
			return $this->wire('pages')->getById($this->parents);
		} else {
			$parent = $this->getParent();
			$parents = new PageArray();
			$parents->add($parent);
			return $parents; 
		}
	}
	
	public function setPageClass($class) {
		$this->pageClass = $class;
	}
	
	public function getPageClass() {
		if($this->pageClass) return $this->pageClass;
		if($this->template && $this->template->pageClass) return $this->template->pageClass;
		return 'Page';
	}
	
	public function count($selectorString = '', array $options = array()) {
		if(empty($selectorString) && empty($options) && count($this->parents) == 1) {
			return $this->getParent()->numChildren();
		}
		$selectorString = $this->selectorString($selectorString); 
		$defaults = array('findAll' => true); 
		$options = array_merge($defaults, $options); 
		return $this->wire('pages')->count($selectorString, $options); 
	}

	/**
	 * Get names of fields that should always be autojoined
	 * 
	 * @return array
	 * 
	 */
	protected function getJoinFieldNames() {
		return array();
	}

}
