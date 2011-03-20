<?php

/**
 * ProcessWire PagesType
 *
 * Provides an interface to the Pages class but specific to 
 * a given page type, with predefined parent and template. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class PagesType extends Wire {

	protected $templatesID;
	protected $parentID; 

	public function __construct($parentID, $templatesID) {
		$this->templatesID = (int) $templatesID;
		$this->parentID = (int) $parentID; 
	}

	protected function selectorString($selectorString) {
		return "$selectorString, parent_id={$this->parentID}, templates_id={$this->templateID}";
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
		return $this->pages->find($this->selectorString($selectorString), $options);
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
		return $this->pages->get($this->selectorString($selectorString)); 
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
		if($page->templates_id != $this->templatesID) {
			throw new WireException("'Unable to save pages of type '{$page->template->name}'"); 
		}
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
		if($page->templates_id != $this->templatesID) {
			throw new WireException("Unable to delete pages of type '{$page->template->name}'"); 
		}
		return $this->pages->delete($page, $recursive);
	}
}
