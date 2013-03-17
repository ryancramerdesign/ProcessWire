<?php

/**
 * ProcessWire PageArray
 *
 * PageArray provides an array-like means for storing PageReferences and is utilized throughout ProcessWire. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class PageArray extends WireArray {

	/**
	 * Total number of pages, including those here and others that aren't, but may be here in pagination.
	 * @var int
	 */
	protected $numTotal = 0;	

	/**
	 * If this PageArray is a partial representation of a larger set, this will contain the max number of pages allowed to be 
	 * present/loaded in the PageArray at once. 
	 * 
	 * May vary from count() when on the last page of a result set. 
	 * As a result, paging routines should refer to their own itemsPerPage rather than count().
	 * Applicable for paginated result sets. This number is not enforced for adding items to this PageArray.
	 *
	 * @var int
	 */
	protected $numLimit = 0; 	

	/**
	 * If this PageArray is a partial representation of a larger set, this will contain the starting result number if previous results preceded it. 
	 *
	 * @var int
	 */
	protected $numStart = 0; 

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
	 * Get a property of the PageArray
	 *
	 * These map to functions form the array and are here for convenience.
	 * Properties include count, total, start, limit, last, first, keys, values, 
	 * These can also be accessed by direct reference. 
	 *
	 * @param string $property
	 * @return mixed
	 *
	 */
	public function getProperty($property) {
		static $properties = array(
			// property => method to map to
			'total' => 'getTotal',	
			'start' => 'getStart',
			'limit' => 'getLimit',
			);
		if(!in_array($property, $properties)) return parent::getProperty($property);
		$func = $properties[$property];
		return $this->$func();
	}

	/**
	 * Does this PageArray contain the given index or Page? 
	 *
	 * @param Page|id $page Array index or Page object. 
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

		} else if($page instanceof PageArray) {
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
	 */
	public function set($key, $value) {
		$has = $this->has($key); 
		parent::set($key, $value); 
		if(!$has) $this->numTotal++;
	}

	/**
	 * Prepend a Page to the beginning of the PageArray. 
	 *
	 * @param Page $item 
	 * @return WireArray This instance.
	 */
	public function prepend($item) {
		parent::prepend($item);
		$this->numTotal++; 
		return $this; 
	}


	/**
	 * Remove the given Page or key from the PageArray. 
	 * 
	 * @param int|Page $key
	 * @return bool true if removed, false if not
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
	 */
	public function shift() {
		if($this->numTotal) $this->numTotal--; 
		return parent::shift(); 
	}

	/**
	 * Pop the last page off of the PageArray and return it. 
	 *
	 * @return Page|NULL 
	 */ 
	public function pop() {
		if($this->numTotal) $this->numTotal--; 
		return parent::pop();
	}


	/**
	 * Set the total number of pages, if more than are in this PageArray. 
	 * 
	 * Used for pagination. 
	 *
	 * @param int $total 
	 * @return PageArray reference to current instance.
	 */
	public function setTotal($total) { 
		$this->numTotal = (int) $total; 
		return $this;
	}	


	/**
	 * Get the total number of pages, if more than are in this PageArray. 
	 * 
	 * Used for pagination. 
	 *
	 * @return int
	 */
	public function getTotal() {
		return $this->numTotal;
	}


	/**
	 * Get the imposed limit on number of pages. 
	 * 
	 * If no limit set, then return number of pages currently in this PageArray. 
	 * 
	 * Used for pagination. 
	 * 
	 * @return int
	 */
	public function getLimit() {
		if($this->numLimit) return $this->numLimit; 
			else return $this->count(); 
	}


	/**
	 * Set the 'start' limitor that resulted in this PageArray
	 *
	 * @param int $numStart; 
	 *
	 */

	public function setStart($numStart) {
		$this->numStart = (int) $numStart; 
		return $this;
	}

	/**
	 * If a limit was imposed, get the index of the starting result assuming other results preceded those present in this PageArray
	 *
	 * Used for pagination.
	 * 	
	 * @return int
	 *
	 */
	public function getStart() {
		return $this->numStart; 
	}


	/**
	 * Set the imposed limit that resulted in this PageArray.
	 * 
	 * Used for pagination. 
	 *
	 * @param int $numLimit
	 * @return PageArray reference to current instance.
	 */
	public function setLimit($numLimit) {
		$this->numLimit = $numLimit; 
		return $this; 
	}

	/**
	 * Set the Selectors that led to this PageArray, if applicable
	 *
	 * @param Selectors $selectors
	 *
	 */
	public function setSelectors(Selectors $selectors) {
		$this->selectors = $selectors; 
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

}


