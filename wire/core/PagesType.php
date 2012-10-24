<?php

/**
 * ProcessWire PagesType
 *
 * Provides an interface to the Pages class but specific to 
 * a given page class/type, with predefined parent and template. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class PagesType extends Wire implements IteratorAggregate {

	/**
	 * Template defined for use in this PagesType
	 *
	 */
	protected $template;

	/**
	 * ID of the parent page used by this PagesType
	 *
	 */
	protected $parent_id; 

	/**
	 * Construct this PagesType manager for the given parent and template
	 *
	 * @param int $parent_id
	 * @param Template $template
	 * @param int|Page $parent_id
	 *
	 */
	public function __construct(Template $template, $parent_id) {
		$this->template = $template; 
		if($parent_id instanceof Page) $parent_id = $parent_id->id; 
		$this->parent_id = (int) $parent_id; 
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
			if($this->template->sortfield) $sortfield = $this->template->sortfield;
				else $sortfield = $this->getParent()->sortfield;
			if(!$sortfield) $sortfield = 'sort';
			$selectorString = trim($selectorString, ", ") . ", sort=$sortfield";
		}
		$selectorString = "$selectorString, parent_id={$this->parent_id}, template={$this->template->name}";
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
		return ($page->template->id == $this->template->id); 
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
		$pages = $this->pages->find($this->selectorString($selectorString), $options);
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

		if(ctype_digit("$selectorString")) {
			$pages = $this->pages->getById(array((int) $selectorString), $this->template, $this->parent_id); 
			if(count($pages)) return $pages->first();
				else return new NullPage();

		} else if(strpos($selectorString, '=') === false) { 
			$s = $this->sanitizer->name($selectorString); 
			if($s === $selectorString) $selectorString = "name=$s"; 	
		}
			
		$page = $this->pages->get($this->selectorString($selectorString)); 
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
	 *
	 */
	public function ___save(Page $page) {
		if(!$this->isValid($page)) throw new WireException("'Unable to save pages of type '{$page->template->name}' ({$this->template->id} != {$page->template->id})"); 
		return $this->pages->save($page);
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
	 *
	 */
	public function ___delete(Page $page, $recursive = false) {
		if(!$this->isValid($page)) throw new WireException("Unable to delete pages of type '{$page->template->name}'"); 
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
		
		$className = $this->template->pageClass ? $this->template->pageClass : 'Page';
		$parent = $this->pages->get($this->parent_id); 

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

	public function getParentID() {
		return $this->parent_id; 
	}

	public function getParent() {
		return wire('pages')->get($this->parent_id);
	}

}
