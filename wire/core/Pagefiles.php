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
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
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
	 * The Field object associated with these Pagefiles
	 *
	 */
	protected $field; 

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

	public function setField(Field $field) {
		$this->field = $field; 
	}

	public function getPage() {
		return $this->page; 
	}

	public function getField() {
		return $this->field; 
	}

	/**
	 * Creates a new blank instance of itself. For internal use, part of the WireArray interface. 
	 *
	 * Adapted here so that $this->page can be passed to the constructor of a newly created Pagefiles. 
	 *
	 * @return WireArray
	 * 
	 */
	public function makeNew() {
		$class = get_class($this); 
		$newArray = new $class($this->page); 
		$newArray->setField($this->field); 
		return $newArray; 
	}

	/**
	 * Make a copy, overriding the default clone method used by WireArray::makeCopy
	 *
	 * This is necessary because our __clone() makes new copies of each Pagefile (deep clone)
	 * and we don't want that to occur for the regular find() and filter() operations that
	 * make use of makeCopy().
	 *
	 * @return Pagefiles
	 *
	 */
	public function makeCopy() {
		$newArray = $this->makeNew();
		foreach($this->data as $key => $value) $newArray[$key] = $value; 
		foreach($this->extraData as $key => $value) $newArray->data($key, $value); 
		$newArray->resetTrackChanges($this->trackChanges());
		foreach($newArray as $item) $item->setPagefilesParent($newArray); 
		return $newArray; 
	}

	/**
	 * When Pagefiles is cloned, ensure that the individual Pagefile items are also cloned
	 *
	 */
	public function __clone() {
		foreach($this as $key => $pagefile) {
			$pagefile = clone $pagefile;
			$pagefile->setPagefilesParent($this);
			$this->set($key, $pagefile); 
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
		if($key == 'field') return $this->getField(); 
		if($key == 'url') return $this->url();
		if($key == 'path') return $this->path(); 
		return parent::get($key);
	}

	public function __get($key) {
		if(in_array($key, array('page', 'field', 'url', 'path'))) return $this->get($key); 
		return parent::__get($key); 
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
	 * @return $this
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
	 * @return $this
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
	 * @return $this
	 * @throws WireException
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
	 * @return $this
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
	 * @param bool $allowDots If true, dots "." are allowed in the basename portion of the filename. 
	 * @param bool $translate True if we should translate accented characters to ascii equivalents (rather than substituting underscores)
	 * @return string
	 *
	 */ 
	public function cleanBasename($basename, $originalize = false, $allowDots = true, $translate = false) {

		$basename = strtolower($basename); 
		$dot = strrpos($basename, '.'); 
		$ext = $dot ? substr($basename, $dot) : ''; 
		$basename = basename($basename, $ext);
		$test = str_replace(array('-', '_', '.'), '', $basename);
		
		if(!ctype_alnum($test)) {
			if($translate) {
				$basename = $this->wire('sanitizer')->filename($basename, Sanitizer::translate); 
			} else {
				$basename = preg_replace('/[^-_.a-z0-9]/', '_', $basename);
			}
		}
		
		if(!ctype_alnum(ltrim($ext, '.'))) $ext = preg_replace('/[^a-z0-9.]/', '_', $ext); 
		if(!$allowDots && strpos($basename, '.') !== false) $basename = str_replace('.', '_', $basename); 
		$basename .= $ext;

		if($originalize) { 
			$path = $this->path(); 
			$n = 0; 
			$p = pathinfo($basename);
			while(is_file($path . $basename)) {
				$n++;
				$basename = "$p[filename]-$n.$p[extension]"; // @hani
				// $basename = (++$n) . "_" . preg_replace('/^\d+_/', '', $basename); 
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

	public function trackChange($what, $old = null, $new = null) {
		if($this->field && $this->page) $this->page->trackChange($this->field->name); 
		return parent::trackChange($what, $old, $new); 
	}

	/**
	 * Get the given file
	 * 
	 * @param string $name
	 * @return null|Pagefile
	 * 
	 */
	public function getFile($name) {
		$hasFile = null;
		$name = basename($name);
		foreach($this as $pagefile) {
			if($pagefile->basename == $name) {
				$hasFile = $pagefile;
				break;
			}
		}
		return $hasFile;
	}

	/**
	 * Returns true if the given Pagefile is temporary, not yet published. 
	 * 
	 * You may also provide a 2nd argument boolean to set the temp status or check if temporary AND deletable.
	 *
	 * @param Pagefile $pagefile
	 * @param bool|string $set Optionally set the temp status to true or false, or specify string "deletable" to check if file is temporary AND deletable.
	 * @return bool
	 *
	 */
	public function isTemp(Pagefile $pagefile, $set = null) {

		$isTemp = Pagefile::createdTemp == $pagefile->created;
		$checkDeletable = ($set === 'deletable' || $set === 'deleteable');
		
		if(!is_bool($set)) { 
			// temp status is not being set
			if(!$isTemp) return false; // if not a temp file, we can exit now
			if(!$checkDeletable) return $isTemp; // if not checking deletable, we can exit now
		}
		
		$now = time();
		$session = $this->wire('session');
		$pageID = $this->page ? $this->page->id : 0;
		$fieldID = $this->field ? $this->field->id : 0;
		$sessionKey = "tempFiles_{$pageID}_{$fieldID}";
		$tempFiles = $pageID && $fieldID ? $session->get($this, $sessionKey) : array();
		if(!is_array($tempFiles)) $tempFiles = array();
		
		if($isTemp && $checkDeletable) {
			$isTemp = false; 
			if(isset($tempFiles[$pagefile->basename])) {
				// if file was uploaded in this session and still temp, it is deletable
				$isTemp = true; 		
			} else if($pagefile->modified < ($now - 14400)) {
				// if file was added more than 4 hours ago, it is deletable, regardless who added it
				$isTemp = true; 
			}
			// isTemp means isDeletable at this point
			if($isTemp) {
				unset($tempFiles[$pagefile->basename]); 	
				// remove file from session - note that this means a 'deletable' check can only be used once, for newly uploaded files
				// as it is assumed you will be removing the file as a result of this method call
				if(count($tempFiles)) $session->set($this, $sessionKey, $tempFiles); 
					else $session->remove($this, $sessionKey); 
			}
		}

		if($set === true) {
			// set temporary status to true
			$pagefile->created = Pagefile::createdTemp;
			$pagefile->modified = $now; 
			//                          mtime                  atime
			@touch($pagefile->filename, Pagefile::createdTemp, $now);
			$isTemp = true;
			if($pageID && $fieldID) { 
				$tempFiles[$pagefile->basename] = 1; 
				$session->set($this, $sessionKey, $tempFiles); 
			}

		} else if($set === false && $isTemp) {
			// set temporary status to false
			$pagefile->created = $now;
			$pagefile->modified = $now; 
			@touch($pagefile->filename, $now);
			$isTemp = false;
			
			if(isset($tempFiles[$pagefile->basename])) {
				unset($tempFiles[$pagefile->basename]); 
				if(count($tempFiles)) {
					// set temp files back to session, minus current file
					$session->set($this, $sessionKey, $tempFiles); 
				} else {
					// if temp files is empty, we can remove it from the session
					$session->remove($this, $sessionKey); 
				}
			}
		}

		return $isTemp;
	}

	/**
	 * Remove all deletable temporary pagefiles immediately
	 *
	 * @return int Number of files removed
	 * 
	 */
	public function deleteAllTemp() {
		$removed = array();
		foreach($this as $pagefile) {
			if(!$this->isTemp($pagefile, 'deletable')) continue; 
			$removed[] = $pagefile->basename();
			$this->remove($pagefile); 
		}
		if(count($removed) && $this->page && $this->field) {
			$this->page->save($this->field->name, array('quiet' => true)); 
			$this->message("Removed '{$this->field->name}' temp file(s) for page {$this->page->path} - " . implode(', ', $removed), Notice::debug | Notice::log); 
		}
		return count($removed); 
	}

	/**
	 * Is the given Pagefiles identical to this one?
	 *
	 * @param WireArray $items
	 * @param bool|int $strict
	 * @return bool
	 *
	 */
	public function isIdentical(WireArray $items, $strict = true) {
		if($strict) return $this === $items;
		return parent::isIdentical($items, $strict);
	}


}
