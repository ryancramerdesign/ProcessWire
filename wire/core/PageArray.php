<?php

/**
 * ProcessWire PageArray
 *
 * PageArray provides an array-like means for storing PageReferences and is utilized throughout ProcessWire. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 * @method string getMarkup($key = null) Render a simple/default markup value for each item
 *
 */

class PageArray extends PaginatedArray implements WirePaginatable {

	/**
	 * Reference to the selectors that led to this PageArray, if applicable
	 *
	 * @var Selectors
	 *
	 */
	protected $selectors = null;

	/**
	 * Template mehod that descendant classes may use to validate items added to this WireArray
	 *
	 * @param mixed $item Item to add
	 * @return bool True if item is valid and may be added, false if not
	 *
	 */
	public function isValidItem($item) {
		return is_object($item) && $item instanceof Page; 
	}

	/**
	 * Validate the key used to add a Page
	 *
	 * PageArrays are keyed by an incremental number that does NOT relate to the Page ID. 
	 *
	 * @param string|int $key
	 * @return bool True if key is valid and may be used, false if not
	 *
	 */
	public function isValidKey($key) {
		return ctype_digit("$key");
	}

	/**
	 * Does this PageArray use numeric keys only? (yes it does)
	 * 
	 * Defined here to override the slower check in WireArray
	 *
	 * @return bool
	 *
	 */
	protected function usesNumericKeys() {
		return true;
	}

	/**
	 * Per WireArray interface, return a blank Page
	 *
	 */
	public function makeBlankItem() {
		return new Page();
	}

	/**
	 * Import the provided pages into this PageArray.
	 * 
	 * @param array|PageArray|Page $pages Pages to import. 
	 * @return PageArray reference to current instance. 
	 *
	 */
	public function import($pages) {
		if(is_object($pages) && $pages instanceof Page) $pages = array($pages); 
		if(!self::iterable($pages)) return $this; 
		foreach($pages as $page) $this->add($page); 
		if($pages instanceof PageArray) {
			if(count($pages) < $pages->getTotal()) $this->setTotal($this->getTotal() + ($pages->getTotal() - count($pages))); 
		}
		return $this;
	}

	/*
	public function get($key) {
		if(ctype_digit("$key")) return parent::get($key); 
		@todo check if selector, then call findOne(). If it returns null, return a NullPage instead. 
		return null;
	}
	*/

	/**
	 * Does this PageArray contain the given index or Page? 
	 *
	 * @param Page|int $key Page Array index or Page object. 
	 * @return bool True if the index or Page exists here, false if not. 
	 */  
	public function has($key) {

		if(is_int($key) || is_string($key)) return parent::has($key); 

		$has = false; 

		if(is_object($key) && $key instanceof Page) {
			foreach($this as $k => $pg) {
				$has = ($pg->id == $key->id); 
				if($has) break;
			}
		}

		return $has; 
	}


	/**
	 * Add a Page to this PageArray.
	 *
	 * @param Page|PageArray|int $page Page object, PageArray object, or Page ID. 
	 *	If Page, the Page will be added. 
	 * 	If PageArray, it will do the same thing as the import() function: import all the pages. 
	 * 	If Page ID, it will be loaded and added. 
	 * @return PageArray reference to current instance.
	 */
	public function add($page) {

		if($this->isValidItem($page)) {
			parent::add($page); 
			$this->numTotal++;

		} else if($page instanceof PageArray || is_array($page)) {
			return $this->import($page);

		} else if(ctype_digit("$page")) {
			if($page = $this->getFuel('pages')->findOne("id=$page")) {
				parent::add($page); 
				$this->numTotal++;
			}
		}
		return $this;
	}


	/**
	 * Sets an index in the PageArray.
	 *
	 * @param int $key Key of item to set.
	 * @param Page $value Value of item. 
	 * @return $this
	 * 
	 */
	public function set($key, $value) {
		$has = $this->has($key); 
		parent::set($key, $value); 
		if(!$has) $this->numTotal++;
		return $this; 
	}

	/**
	 * Prepend a Page to the beginning of the PageArray. 
	 *
	 * @param Page|PageArray $item 
	 * @return WireArray This instance.
	 * 
	 */
	public function prepend($item) {
		parent::prepend($item);
		// note that WireArray::prepend does a recursive call to prepend with each item,
		// so it's only necessary to increase numTotal if the given item is Page (vs. PageArray)
		if($item instanceof Page) $this->numTotal++; 
		return $this; 
	}


	/**
	 * Remove the given Page or key from the PageArray. 
	 * 
	 * @param int|Page $key
	 * @return bool true if removed, false if not
	 * 
	 */
	public function remove($key) {

		// if a Page object has been passed, determine it's key
		if($this->isValidItem($key)) {
			foreach($this->data as $k => $pg) {
				if($pg->id == $key->id) {
					$key = $k; 
					break;
				}
			}
		} 

		if($this->has($key)) {
			parent::remove($key); 
			$this->numTotal--;
		}

		return $this; 
	}

	/**
	 * Shift the first Page off of the PageArray and return it. 
	 * 
	 * @return Page|NULL
	 * 
	 */
	public function shift() {
		if($this->numTotal) $this->numTotal--; 
		return parent::shift(); 
	}

	/**
	 * Pop the last page off of the PageArray and return it. 
	 *
	 * @return Page|NULL 
	 * 
	 */ 
	public function pop() {
		if($this->numTotal) $this->numTotal--; 
		return parent::pop();
	}

	/**
	 * Set the Selectors that led to this PageArray, if applicable
	 *
	 * @param Selectors $selectors
	 * @return $this
	 *
	 */
	public function setSelectors(Selectors $selectors) {
		$this->selectors = $selectors; 
		return $this;
	}

	/**
	 * Return the Selectors that led to this PageArray, or null if not set/applicable
	 *
	 * @return Selectors|null
	 *
	 */
	public function getSelectors() {
		return $this->selectors; 
	}

	/**
	 * Filter out Pages that don't match the selector. 
	 * 
	 * This is applicable to and destructive to the WireArray.
	 *
	 * @param string|Selectors $selectors AttributeSelector string to use as the filter.
	 * @param bool $not Make this a "not" filter? (default is false)
	 * @return WireArray reference to current [filtered] instance
	 *
	 */
	protected function filterData($selectors, $not = false) {
		if(is_string($selectors) && $selectors[0] === '/') $selectors = "path=$selectors";
		return parent::filterData($selectors, $not); 
	}

	/**
	 * Prepare selectors for filtering
	 *
	 * Template method for descending classes to modify selectors if needed
	 *
	 * @param Selectors $selectors
	 *
	 */
	protected function filterDataSelectors(Selectors $selectors) { 
		// @todo make it remove references to include= statements since not applicable in-memory
		parent::filterDataSelectors($selectors);
	}

	/**
	 * Get the value of $property from $item
	 *
	 * Used by the WireArray::sort method to retrieve a value from a Wire object. 
	 * If output formatting is on, we turn it off to ensure that the sorting
	 * is performed without output formatting.
	 *
	 * @param Wire $item
	 * @param string $property
	 * @return mixed
	 *
	 */
	protected function getItemPropertyValue(Wire $item, $property) {

		if($item instanceof Page) {
			$value = $item->getUnformatted($property); 
		} else if(strpos($property, '.') !== false) {
			$value = WireData::_getDot($property, $item);
		} else if($item instanceof WireArray) {
			$value = $item->getProperty($property); 
			if(is_null($value)) {
				$value = $item->first();
				$value = $this->getItemPropertyValue($value, $property);
			}
		} else {
			$value = $item->$property;
		}

		if(is_array($value)) $value = implode('|', $value); 

		return $value;
	}

	/**
	 * PageArrays always return a string of the Page IDs separated by pipe "|" characters
	 *
	 * Pipe charactesr are used for compatibility with Selector OR statements
	 *
	 */
	public function __toString() {
		$s = '';
		foreach($this as $key => $page) $s .= "$page|";
		$s = rtrim($s, "|"); 
		return $s; 
	}

	/**
	 * Render a simple/default markup value for each item
	 * 
	 * Primarily for testing/debugging purposes.
	 * 
	 * @param string|callable|function $key
	 * @return string
	 * 
	 */
	public function ___getMarkup($key = null) {
		if($key && !is_string($key)) {
			$out = $this->each($key);
		} else if(strpos($key, '{') !== false && strpos($key, '}')) {
			$out = $this->each($key);
		} else {
			if(empty($key)) $key = "<li>{title|name}</li>";
			$out = $this->each($key);
			if($out) {
				$out = "<ul>$out</ul>";
				if($this->getLimit() && $this->getTotal() > $this->getLimit()) {
					$pager = $this->wire('modules')->get('MarkupPagerNav');
					$out .= $pager->render($this);
				}
			}
		}
		return $out; 
	}


	/**
	 * debugInfo PHP 5.6+ magic method
	 *
	 * @return array
	 *
	 */
	public function __debugInfo() {
		$info = parent::__debugInfo();
		$info['selectors'] = (string) $this->selectors; 
		if(!count($info['selectors'])) unset($info['selectors']);
		return $info;
	}

}


