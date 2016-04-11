<?php namespace ProcessWire;

/**
 * ProcessWire PageArray
 *
 * PageArray provides an array-like means for storing PageReferences and is utilized throughout ProcessWire. 
 * 
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 * 
 * @method string getMarkup($key = null) Render a simple/default markup value for each item
 * 
 * @property Page[] $data
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
	 * @return Page
	 *
	 */
	public function makeBlankItem() {
		return $this->wire('pages')->newPage();
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
			$page = $this->wire('pages')->get("id=$page");
			if($page->id) {
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
	 * Get one or more random pages from this PageArray.
	 *
	 * If one item is requested, the item is returned (unless $alwaysArray is true).
	 * If multiple items are requested, a new WireArray of those items is returned.
	 *
	 * @param int $num Number of items to return. Optional and defaults to 1.
	 * @param bool $alwaysArray If true, then method will always return a container of items, even if it only contains 1.
	 * @return Page|PageArray Returns value of item, or new PageArray of items if more than one requested.
	 */
	public function getRandom($num = 1, $alwaysArray = false) {
		return parent::getRandom($num, $alwaysArray);
	}

	/**
	 * Get a quantity of random pages from this PageArray.
	 *
	 * Unlike getRandom() this one always returns a PageArray (or derived type).
	 *
	 * @param int $num Number of items to return
	 * @return PageArray
	 *
	 */
	public function findRandom($num) {
		return parent::findRandom($num);
	}

	/**
	 * Get a slice of the PageArray.
	 *
	 * Given a starting point and a number of items, returns a new PageArray of those items.
	 * If $limit is omitted, then it includes everything beyond the starting point.
	 *
	 * @param int $start Starting index.
	 * @param int $limit Number of items to include. If omitted, includes the rest of the array.
	 * @return PageArray
	 *
	 */
	public function slice($start, $limit = 0) {
		return parent::slice($start, $limit);
	}

	/**
	 * Returns the item at the given index starting from 0, or NULL if it doesn't exist.
	 *
	 * Unlike the index() method, this returns an actual item and not another PageArray.
	 *
	 * @param int $num Return the nth item in this WireArray. Specify a negative number to count from the end rather than the start.
	 * @return Page|null
	 *
	 */
	public function eq($num) {
		return parent::eq($num);
	}

	/**
	 * Returns the first item in the PageArray or boolean FALSE if empty.
	 *
	 * @return Page|bool
	 *
	 */
	public function first() {
		return parent::first();
	}

	/**
	 * Returns the last item in the PageArray or boolean FALSE if empty.
	 *
	 * @return Page|bool
	 *
	 */
	public function last() {
		return parent::last();
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
	 * @param string|Selectors|array $selectors AttributeSelector string to use as the filter.
	 * @param bool $not Make this a "not" filter? (default is false)
	 * @return PageArray reference to current [filtered] instance
	 *
	 */
	protected function filterData($selectors, $not = false) {
		if(is_string($selectors) && $selectors[0] === '/') $selectors = "path=$selectors";
		return parent::filterData($selectors, $not); 
	}

	/**
	 * Filter out pages that don't match the selector (destructive)
	 *
	 * @param string $selector AttributeSelector string to use as the filter.
	 * @return PageArray reference to current instance.
	 *
	 */
	public function filter($selector) {
		return parent::filter($selector);
	}

	/**
	 * Filter out pages that don't match the selector (destructive)
	 *
	 * @param string $selector AttributeSelector string to use as the filter.
	 * @return PageArray reference to current instance.
	 *
	 */
	public function not($selector) {
		return parent::not($selector);
	}

	/**
	 * Find all pages in this PageArray that match the given selector (non-destructive)
	 *
	 * This is non destructive and returns a brand new PageArray.
	 *
	 * @param string $selector AttributeSelector string.
	 * @return PageArray
	 *
	 */
	public function find($selector) {
		return parent::find($selector);
	}

	/**
	 * Same as find, but returns a single Page rather than PageArray or FALSE if empty.
	 *
	 * @param string $selector
	 * @return Page|bool
	 *
	 */
	public function findOne($selector) {
		return parent::findOne($selector);
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
			/** @var PageArray $item */
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
	 * Allows iteration of the PageArray.
	 *
	 * @return Page[]|\ArrayObject
	 *
	 */
	public function getIterator() {
		return parent::getIterator();
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
	 * @param string|callable $key
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


