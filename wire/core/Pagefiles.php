<?php

/**
 * ProcessWire Pagefiles
 *
 * Pagefiles are a collection of Pagefile objects.
 *
 * Typically a Pagefiles object will be associated with a specific field attached to a Page. 
 * There may be multiple instances of Pagefiles attached to a given Page (depending on what fields are in it's fieldgroup).
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 *
 * @property string $path Returns the full server disk path where files are stored	
 * @property string $url Returns the URL where files are stored
 * @property Page $page Returns the $page that contains this set of files
 * @method Pagefiles delete() delete(Pagefile $file) Removes the file and deletes from disk when page is saved (alias of remove)
 *
 */

class Pagefiles extends WireArray {

	/**
	 * The Page object associated with these Pagefiles
	 *
	 */
	protected $page; 

	/**
	 * Items to be deleted when Page is saved
	 *
	 */
	protected $unlinkQueue = array();

	/**
	 * IDs of any hooks added in this instance, used by the destructor
	 *
	 */
	protected $hookIDs = array();

	/**
	 * Construct an instantance of Pagefiles 
	 *
	 * @param Page $page The page associated with this Pagefiles instance
	 *
	 */
	public function __construct(Page $page) {
		$this->setPage($page); 
	}

	public function __destruct() {
		$this->removeHooks();
	}

	protected function removeHooks() {
		if(count($this->hookIDs) && $this->page && $this->page->filesManager) {
			foreach($this->hookIDs as $id) $this->page->filesManager->removeHook($id); 
		}
	}

	public function setPage(Page $page) {
		$this->page = $page; 
		// call the filesmanager, just to ensure paths are where they should be
		$page->filesManager(); 
	}

	public function getPage() {
		return $this->page; 
	}

	/**
	 * Creates a new blank instance of itself. For internal use, part of the WireArray interface. 
	 *
	 * Adapted here so that $this->page can be passed to the constructor of a newly created Pagefiles. 
	 *
	 * @param array $items Array of items to populate (optional)
	 * @return WireArray
	 */
	public function makeNew() {
		$class = get_class($this); 
		$newArray = new $class($this->page); 
		return $newArray; 
	}

	/**
	 * When Pagefiles is cloned, ensure that the individual Pagefile items are also cloned
	 *
	 */
	public function __clone() {
		foreach($this as $key => $pagefile) {
			$this->set($key, clone $pagefile); 
		}
	}

	/**
	 * Per the WireArray interface, items must be of type Pagefile
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Pagefile;
	}

	/**
	 * Per the WireArray interface, items are indexed by Pagefile::basename
	 *
	 */
	public function getItemKey($item) {
		return $item->basename; 
	}

	/**
	 * Per the WireArray interface, return a blank Pagefile
	 *
	 */
	public function makeBlankItem() {
		return new Pagefile($this, ''); 
	}

	/**
	 * Get a value from this Pagefiles instance
	 *
	 * You may also specify a file's 'tag' and it will return the first Pagefile matching the tag.
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {
		if($key == 'page') return $this->getPage(); 
		if($key == 'url') return $this->url();
		if($key == 'path') return $this->path(); 
		return parent::get($key);
	}

	/**
	 * Find all Pagefiles matching the given selector string or tag
	 *
	 * @param string $selector
	 * @return Pagefiles New instance of Pagefiles
	 *
	public function find($selector) {
		if(!Selectors::stringHasOperator($selector)) {
			// if there is no selector operator in the strong, consider it a tag first
			$value = $this->findTag($selector); 
			// if it didn't match any tag, then see if it matches in some other way
			if(!count($value)) $value = parent::find($selector); 
		} else {
			// there is an operator so we send it straight to WireArray
			$value = parent::find($selector);		
		}
		return $value; 
	}
	 */

	/**
	 * Add a new Pagefile item, or create one from it's filename and add it.
	 *
	 * @param Pagefile|string $item If item is a string (filename) then the Pagefile instance will be created automatically.
	 * @return this
	 *
	 */
	public function add($item) {

		if(is_string($item)) {
			$item = new Pagefile($this, $item); 
		}

		return parent::add($item); 
	}

	/**
	 * Make any removals take effect on disk
	 *
	 */
	public function hookPageSave() {
		foreach($this->unlinkQueue as $item) {
			$item->unlink();
		}
		$this->unlinkQueue = array();
		$this->removeHooks();
		return $this; 
	}

	/**
	 * Delete a pagefile item, hookable alias of remove()
	 *
	 * @param Pagefile $item
	 * @return this
	 *
	 */
	public function ___delete($item) {
		return $this->remove($item); 
	}

	/**
	 * Delete/remove a Pagefile item
	 *
	 * Deletes the filename associated with the Pagefile and removes it from this Pagefiles instance. 
	 *
	 * @param Pagefile $item
	 * @return this
	 *
	 */
	public function remove($item) {
		if(is_string($item)) $item = $this->get($item); 
		if(!$this->isValidItem($item)) throw new WireException("Invalid type to {$this->className}::remove(item)"); 
		if(!count($this->unlinkQueue)) {
			$this->hookIDs[] = $this->page->filesManager->addHookBefore('save', $this, 'hookPageSave'); 
		}
		$this->unlinkQueue[] = $item; 
		parent::remove($item); 
		return $this; 
	}

	/**
	 * Delete all files associated with this Pagefiles instance, leaving a blank Pagefiles instance. 
	 *
	 * @return this
	 *
	 */ 
	public function deleteAll() {
		foreach($this as $item) {
			$this->delete($item); 
		}

		return $this; 
	}

	/**
	 * Return the full disk path where files are stored
	 *
	 */
	public function path() {
		return $this->page->filesManager->path();
	}

	/**
	 * Returns the web accessible index URL where files are stored
	 *
	 */
	public function url() {
		return $this->page->filesManager->url();
	}

	/**
	 * Given a basename, this method returns a clean version containing valid characters 
	 *
	 * @param string $basename May also be a full path/filename, but it will still return a basename
	 * @param bool $originalize If true, it will generate an original filename if $basename already exists
	 * @return string
	 *
	 */ 
	public function cleanBasename($basename, $originalize = false) {

		$path = $this->path(); 
		$dot = strrpos($basename, '.'); 
		$ext = $dot ? substr($basename, $dot) : ''; 
		$basename = strtolower(basename($basename, $ext)); 
		$basename = preg_replace('/[^-_.a-zA-Z0-9]/', '_', $basename); 
		$ext = preg_replace('/[^a-z0-9.]/', '_', $ext); 
		$basename .= $ext;
		if($originalize) { 
			$n = 0; 
			while(is_file($path . $basename)) {
				$basename = (++$n) . "_" . preg_replace('/^\d+_/', '', $basename); 
			}
		}
		return $basename; 
	}

	public function uncache() {
		//$this->page = null;		
	}

	/**
	 * Return all Pagefiles that have the given tag
	 *
	 * @param string $tag
	 * @return Pagefiles
	 *
	 */
	public function findTag($tag) {
		$items = $this->makeNew();		
		foreach($this as $pagefile) {
			if($pagefile->hasTag($tag)) $items->add($pagefile);
		}
		return $items; 
	}

	/**
	 * Return the first Pagefile that matches the given tag or NULL if no match
	 *
	 * @param string $tag
	 * @return Pagefile|null
	 *
	 */
	public function getTag($tag) {
		$item = null;
		foreach($this as $pagefile) {
			if(!$pagefile->hasTag($tag)) continue; 
			$item = $pagefile;
			break;
		}
		return $item;
	}

}
