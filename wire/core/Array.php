<?php

/**
 * ProcessWire WireArray
 *
 * WireArray is the base array access object used in the ProcessWire framework.
 * 
 * Several methods are duplicated here for syntactical convenience and jQuery-like usability. 
 * Many methods act upon the array and return $this, which enables WireArrays to be used for fluent interfaces.
 * WireArray is the base of the PageArray (subclass) which is the most used instance. 
 *
 * TODO narrow down to one method of addition and removal, especially for removal, i.e. make shift() run through remove()
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class WireArray extends Wire implements IteratorAggregate, ArrayAccess, Countable {

	/**
	 * Basic type managed by the WireArray for data storage
	 *
	 */
	protected $data = array();

	/**
	 * Array containing the items that have been removed from this WireArray while trackChanges is on
	 * 
	 * @see getRemovedKeys()
	 *
	 */
	protected $itemsRemoved = array(); 

	/**
	 * Array containing the items that have been added to this WireArray while trackChanges is on
	 * 
	 * @see getRemovedKeys()
	 *
	 */
	protected $itemsAdded = array();

	/**
	 * Template mehod that descendant classes may use to validate items added to this WireArray
	 *
	 * @param mixed $item Item to add
	 * @return bool True if item is valid and may be added, false if not
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Wire; 
	}


	/**
	 * Template method that descendant classes may use to validate the key of items added to this WireArray
	 *
	 * @param string|int $key
	 * @return bool True if key is valid and may be used, false if not
	 *
	 */
	public function isValidKey($key) {
		return true; 
	}

	/**
	 * Template method that descendant classes may use to find a key from the item itself, or null if disabled. 
	 *
	 * Used by add() and prepend()
	 *
	 * @param object $item
	 * @return string|int|null 
	 *
	 */
	public function getItemKey($item) {
		// in this base class, we don't make assumptions how the key is determined
		// so we just search the array to see if the item is already here and 
		// return it's key if it is here
		$key = array_search($item, $this->data, true); 
		return $key === false ? null : $key; 
	}

	/**
	 * Get a blank copy of an item of the type that this WireArray holds
	 *
	 * @return mixed
	 *
	 */
	public function makeBlankItem() {
		$class = get_class($this); 
		if($class != 'WireArray') throw new WireException("Class '$class' doesn't yet implement method 'makeBlankItem()' and it needs to."); 
		return null;
	}

	/**
	 * Creates a new blank instance of itself. For internal use. 
	 *
	 * @param array $items Array of items to populate (optional)
	 * @return WireArray
	 */
	public function makeNew() {
		$class = get_class($this); 
		$newArray = new $class(); 
		return $newArray; 
	}


	/**
	 * Import items into this WireArray.
	 * 
	 * @param array|WireArray $items Items to import.
	 * @return WireArray This instance.
	 */
	public function import($items) {

		if(!is_array($items) && !self::iterable($items)) 
			throw new WireException('WireArray cannot import non arrays or non-iterable objects'); 

		foreach($items as $key => $value) {
			if(($k = $this->getItemKey($value)) !== null) $key = $k;
			if(isset($this->data[$key])) continue; // won't overwrite existing keys
			$this->set($key, $value); 
		}

		return $this;
	}

	/**
	 * Add an item to the end of the WireArray.
	 * 
	 * @param int|string|array|object $item Item to add. 
	 * @return WireArray This instance.
	 */
	public function add($item) {

		if(!$this->isValidItem($item)) {
			if($item instanceof WireArray) {
				foreach($item as $i) $this->prepend($i); 
				return $this; 
			} else {
				throw new WireException("Item added to " . get_class($this) . " is not an allowed type"); 
			}
		}

		$key = null;
		if(($key = $this->getItemKey($item)) !== null) {
			if(isset($this->data[$key])) unset($this->data[$key]); // avoid two copies of the same item, re-add it to the end 
			$this->data[$key] = $item; 
		} else {
			$this->data[] = $item;
		}

		$this->trackChange("add"); 
		$this->trackAdd($item); 
		return $this;
	}

	/**
	 * Insert an item either before or after another
 	 *
	 * Provides the implementation for the insertBefore and insertAfter functions
	 *
	 */
	protected function _insert($item, $existingItem, $insertBefore = true) {

		if(!$this->isValidItem($item)) throw new WireException("You may not insert this item type"); 
		$data = array();
		$this->add($item); // first add the item, then we'll move it
		$itemKey = $this->getItemKey($item); 

		foreach($this->data as $key => $value) {
			if($value === $existingItem) {
				// found $existingItem, so insert $item and then insert $existingItem
				if($insertBefore) { 
					$data[$itemKey] = $item; 
					$data[$key] = $value; 
				} else {
					$data[$key] = $value; 
					$data[$itemKey] = $item; 
				}
				
			} else if($value === $item) {
				// skip over it since the above is doing the insert 
				continue; 

			} else {
				// continue populating existing data
				$data[$key] = $value; 
			}
		}
		$this->data = $data; 
		return $this; 
	}

	/**
	 * Insert an item before an existing item
	 *
	 * @param int|string|array|object $item Item you want to insert
	 * @param int|string|array|object Wire $existingItem Item already present that you want to insert before
	 *
	 */
	public function insertBefore($item, $existingItem) {
		return $this->_insert($item, $existingItem, true); 
	}

	/**
	 * Insert an item after an existing item
	 *
	 * @param int|string|array|object $item Item you want to insert
	 * @param int|string|array|object Wire $existingItem Item already present that you want to insert after
	 *
	 */
	public function insertAfter($item, $existingItem) {
		return $this->_insert($item, $existingItem, false); 
	}

	/**
	 * Sets an index in the WireArray.
	 *
	 * @param int|string $key Key of item to set.
	 * @param int|string|array|object $value Value of item. 
	 */
	public function set($key, $value) {

		if(!$this->isValidItem($value)) throw new WireException("Item '$key' set to " . get_class($this) . " is not an allowed type"); 
		if(!$this->isValidKey($key)) throw new WireException("Key '$key' is not an allowed key for " . get_class($this)); 

		$this->data[$key] = $value; 
		$this->trackChange("set"); 
		$this->trackAdd($value); 
		return $this; 
	}

	/**
	 * Enables setting of WireArray elements in object notation.
	 *
	 * Example: $myArray->myElement = 10; 
	 * Not applicable to numerically indexed arrays.
	 *
	 * @param int|string $property Key of item to set. 
	 * @param int|string|array|object Value of item to set. 
	 */
	public function __set($property, $value) {
		$this->set($property, $value); 
	}

	/**
	 * Ensures that isset() and empty() work for this classes properties. 
	 *
	 */
	public function __isset($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Ensures that unset() works for this classes data. 
	 *
	 */
	public function __unset($key) {
		$this->remove($key); 
	}
	
	/**
	 * Like set() but accepts an array or WireArray to set multiple values at once
	 *
	 * @param array|WireArray $data
	 *
	 */
	public function setArray($data) {
		if(self::iterable($data)) {
			foreach($data as $key => $value) $this->set($key, $value); 
		}
		return $this; 
	}


	/**
	 * Returns the value of the item at the given index, or false if not set. 
	 *
	 * You may also specify a selector, in which case this method will return the same result as the findOne() method. 
	 *
	 * @param int|string $key Key of item to retrieve. If not specified, 0 is assumed (for first item).
	 * @return int|string|array|object Value of item requested, or null if it doesn't exist. 
	 */
	public function get($key) {

		// if an object was provided, get it's key
		if(is_object($key)) $key = $this->getItemKey($key); 

		// don't allow arrays as keys
		if(is_array($key)) throw new WireException("WireArray::get cannot accept an array as a key"); 

		// check if the index is set and return it if so
		if(isset($this->data[$key])) return $this->data[$key]; 

		// check if key contains a selector
		if(Selectors::stringHasOperator($key)) return $this->findOne($key); 

		// if the WireArray uses numeric keys, then it's okay to
		// match a 'name' field if the provided key is a string
		$match = null;
		if(is_string($key) && $this->usesNumericKeys()) $match = $this->getItemThatMatches('name', $key); 

		return $match; 
	}

	/**
	 * Enables derefencing of WireArray elements in object notation. 
	 *
	 * Example: $myArray->myElement
	 * Not applicable to numerically indexed arrays. 
	 * Fuel properties and hooked properties have precedence with this type of call.
	 * 
	 * @param int|string $property 
	 * @return Value of requested index, or false if it doesn't exist. 
	 */
	public function __get($property) {
		$value = parent::__get($property); 
		if(is_null($value)) $value = $this->get($property); 
		return $value; 
	}

	/**
	 * Return the first item in this WireArray having a property called $key with the value of $value or NULL if not matched.
	 *
	 * Used internally by get() and has().
	 *
	 * @param string $key Property to match. 
	 * @param string|int|object $value $value to match.
	 * @return Wire|null
	 *
	 */
	protected function getItemThatMatches($key, $value) {
		if(ctype_digit("$key")) return null;
		$item = null;
		foreach($this->data as $wire) {
			if($wire->$key === $value) {
				$item = $wire; 
				break;
			}
		}
		return $item; 
	}

	/**
	 * Does this WireArray have the given index or match the given selector?
	 *
	 * If the WireArray uses numeric keys, then this will also match a wire's "name" field.
	 * 
	 * @param int|string $key Key of item to check or selector.
	 * @return bool True if the item exists, false if not. 
	 */ 
	public function has($key) {

		if(is_object($key)) $key = $this->getItemKey($key); 

		if(array_key_exists($key, $this->data)) return true; 

		$match = null;
		if(is_string($key)) {

			if(Selectors::stringHasOperator($key)) {
				$match = $this->findOne($key); 

			} else if($this->usesNumericKeys()) {
				$match = $this->getItemThatMatches('name', $key); 
			}

		} 

		return $match ? true : false; 
	}

	/**
	 * Get a regular PHP array of all the items in this WireArray. 
	 *
	 * @return array Copy of the array that WireArray uses internally. 
	 */
	public function getArray() {
		return $this->data; 
	}

	/**
	 * Returns all items in the WireArray. 
	 *
	 * This is for syntax convenience, as it simply eturns this instance of the WireArray. 
	 *
	 * @return WireArray
	 */
	public function getAll() {
		return $this;
	}


	/**
	 * Returns an array of all keys used in this WireArray. 
	 * 
	 * @return array Keys used in the WireArray.
	 */
	public function getKeys() {
		return array_keys($this->data); 
	}

	/**
	 * Returns an array of all values used in this WireArray. 
	 * 
	 * @return array Values used in the WireArray.
	 */
	public function getValues() {
		return array_values($this->data); 
	}


	/**
	 * Get one or more random elements from this WireArray. 
	 *
	 * If one item is requested, the item is returned (unless $alwaysArray is true).
	 * If multiple items are requested, a new WireArray of those items is returned. 
	 *
	 * @param int $num Number of items to return. Optional and defaults to 1. 
	 * @param bool $alwaysArray If true, then method will always return a container of items, even if it only contains 1. 
	 * @return int|string|array|object|WireArray Returns value of item, or new WireArray of items if more than one requested. 
	 */
	public function getRandom($num = 1, $alwaysArray = false) {
		$items = $this->makeNew(); 
		$count = $this->count();
		if(!$count) return $items; 
		//if($num > $count) $num = $count; 
		$keys = array_rand($this->data, ($num > $count ? $count : $num)); 
		if($num == 1 && !$alwaysArray) return $this->data[$keys]; 
		if(!is_array($keys)) $keys = array($keys); 
		foreach($keys as $key) $items->add($this->data[$key]); 
		$items->setTrackChanges(true); 
		return $items; 
	}

	/**
	 * Get a slice of the WireArray.
	 *
	 * Given a starting point and a number of items, returns a new WireArray of those items. 
	 * If $limit is ommitted, then it includes everything beyond the starting point. 
	 *
	 * @param int $start Starting index. 
	 * @param int $limit Number of items to include. If ommitted, includes the rest of the array.
	 * @return WireArray Returns a new WireArray.
	 */
	public function slice($start, $limit = 0) {
		if($limit) $slice = array_slice($this->data, $start, $limit); 
			else $slice = array_slice($this->data, $start); 
		$items = $this->makeNew(); 
		$items->import($slice); 
		$items->setTrackChanges(true); 
		return $items; 
	}

	/**
	 * Prepend an element to the beginning of the WireArray. 
	 *
	 * @param int|string|array|object Item to prepend. 
	 * @return WireArray This instance.
	 */
	public function prepend($item) {

		if(!$this->isValidItem($item)) {
			if($item instanceof WireArray) {
				foreach($item as $i) $this->prepend($i); 
				return $this; 
			} else {
				throw new WireException("Item prepend to " . get_class($this) . " is not an allowed type"); 
			}
		}

		if(($key = $this->getItemKey($item)) !== null) {
			$a = array($key => $item); 
			$this->data = $a + $this->data; // UNION operator for arrays
			// $this->data = array_merge($a, $this->data); 
		} else { 
			array_unshift($this->data, $item); 
		}
		//if($item instanceof Wire) $item->setTrackChanges();
		$this->trackChange('prepend'); 
		$this->trackAdd($item); 
		return $this; 
	}

	/**
	 * Append an item to the end of the WireArray.
	 *
	 * @param int|string|array|object Item to append. 
	 * @return WireArray This instance.
	 */
	public function append($item) {
		$this->add($item); 
		return $this; 
	}

	/**
	 * Unshift an element to the beginning of the WireArray. 
	 *
	 * Alias for prepend()
	 * 
	 * @param int|string|array|object Item to prepend. 
	 * @return WireArray This instance.
	 */
	public function unshift($item) {
		return $this->prepend($item); 
	}

	/**
	 * Shift an element off the beginning of the WireArray.
	 *
	 * @return int|string|array|object Item shifted off the beginning.
	 */
	public function shift() {
		$item = array_shift($this->data); 
		$this->trackChange('shift');
		$this->trackRemove($item); 
		return $item; 
	}

	/**
	 * Push an item at the end of the WireArray.
	 * 
	 * Same as add() and append(), but here for syntax convenience.
	 *
	 * @param int|string|array|object Item to push. 
	 * @return WireArray This instance.
	 */
	public function push($item) {
		$this->add($item); 	
		return $this; 
	}

	/**
	 * Pop an element off the end of the WireArray.
	 * 
	 * @return int|string|array|object Item popped off the end. 
	 */
	public function pop() {
		$item = array_pop($this->data); 
		$this->trackChange('pop');
		$this->trackRemove($item); 
		return $item; 
	}

	/**
	 * Shuffle/randomize the WireArray. 
	 *
	 * @return WireArray This instance.
	 */
	public function shuffle() {

		$keys = $this->getKeys(); 
		$data = array();

		// shuffle the keys rather than the original array in case it's associative
		// because PHP's shuffle reindexes the array
		shuffle($keys); 
		foreach($keys as $key) {
			$data[$key] = $this->data[$key]; 
		}

		$this->data = $data; 

		$this->trackChange('shuffle'); 
		return $this; 
	}

	/**
	 * Returns a WireArray of the item at the given index. 
	 *  
	 * Unlike get() this returns a new WireArray with a single item, or a blank WireArray if item doesn't exist. 
	 * Applicable to numerically indexed ProcesArray's only. 
	 * 
	 * @param int $num 
	 * @return WireArray
	 */
	public function index($num) {
		return $this->slice($num, 1); 
	}

	/**
	 * Returns the item at the given index starting from 0, or NULL if it doesn't exist.
	 *  
	 * Unlike the index() method, this returns an actual item and not another WireArray. 
	 * 
	 * @param int $num Return the nth item in this WireArray. Specify a negative number to count from the end rather than the start.
	 * @return Wire|null
	 */
	public function eq($num) {
		$num = (int) $num; 
		$item = array_slice($this->data, $num, 1); 
		$item = count($item) ? reset($item) : null;
		return $item; 
	}
	
	/**
	 * Returns the first item in the WireArray or boolean FALSE if empty. 
	 *
	 * Note that this resets the internal WireArray pointer, which would affect other active iterations. 
	 *
	 * @return int|string|array|object 
	 */
	public function first() {
		return reset($this->data);
	}
	
	/**
	 * Returns the last item in the WireArray or boolean FALSE if empty.
	 *
	 * Note that this resets the internal WireArray pointer, which would affect other active iterations. 
	 * 
	 * @return int|string|array|object
	 */
	public function last() {
		return end($this->data); 
	}

	
	/**
	 * Removes the item at the given index from the WireArray (if it exists).
	 * 
	 * @return WireArray This instance.
	 *
	 */
	public function remove($key) {

		if(is_object($key)) {
			$key = $this->getItemKey($key); 
		}

		if($this->has($key)) {
			$item = $this->data[$key];
			unset($this->data[$key]); 
			$this->trackChange("remove"); 
			$this->trackRemove($item); 
			
		}

		return $this;
	}

	/**
	 * Removes all items from the WireArray
	 *
	 */
	public function removeAll() {
		foreach($this as $key => $value) {
			$this->remove($key); 
		}
		return $this; 
	}

	/**
	 * Sort this WireArray by the given property. 
	 *
	 * You may also specify the $property as "property.subproperty", where property resolves to a Wire derived object, 
	 * and subproperty resolves to a property within that object. 
	 * 
	 * @TODO Currently only sorts by one field at a time. Upgrade to support a sortByField string, i.e. "name, datestamp" 
	 *
	 * @param string $property Field name to sort by. Prepend or append a minus "-" to reverse the sort. 
	 * @return WireArray reference to current instance.
	 */
	public function sort($property) {

		$unidentified = array();
		$sortable = array();
		$reverse = false;
		$subProperty = '';

		if(substr($property, 0, 1) == '-' || substr($property, -1) == '-') {
			$reverse = true; 
			$property = trim($property, '-'); 
		}

		if($property == 'random') return $this->shuffle();

		if($pos = strpos($property, ".")) {
			$subProperty = substr($property, $pos+1); 
			$property = substr($property, 0, $pos); 
		}

		foreach($this as $item) {

			$key = $item->$property; 

			// if item->property resolves to another Wire, then try to get the subProperty from that Wire (if it exists)
			if($key instanceof Wire && $subProperty) {
				$key = $key->$subProperty; 
			}

			// check for items that resolve to blank
			if(is_null($key) || (is_string($key) && !strlen(trim($key)))) {
				$unidentified[] = $item;
				continue; 
			}

			$key = (string) $key; 

			// ensure numeric sorting if the key is a number
			if(ctype_digit("$key")) $key = (int) $key; 

			if(isset($sortable[$key])) {
				// key resolved to the same value that another did, so keep them together by converting this index to an array
				if(is_array($sortable[$key])) $sortable[$key][] = $item; 
					else $sortable[$key] = array($sortable[$key], $item); 
			} else { 
				$sortable[$key] = $item; 
			}
		}

		// sorth the items by the keys we collected
		if($reverse) krsort($sortable);
			else ksort($sortable); 

		// add the items that resolved to no key to the end
		foreach($unidentified as $item) $sortable[] = $item; 

		// restore sorted array to lose sortable keys and restore proper keys
		$a = array();
		foreach($sortable as $key => $value) {
			if(is_array($value)) {
				foreach($value as $k => $v) {
					$newKey = $this->getItemKey($v); 
					$a[$newKey] = $v; 
				}
			} else {
				$newKey = $this->getItemKey($value); 
				$a[$newKey] = $value; 	
			}
		}

		// overwrite the WireArray's data with the new sorted data
		$this->data = $a; 
		$this->trackChange("sort:$property"); 

		return $this;
	}

	/**
	 * Filter out Wires that don't match the selector. 
	 * 
	 * This is applicable to and destructive to the WireArray.
	 *
	 * @param string|Selectors $selectors AttributeSelector string to use as the filter.
	 * @param bool $not Make this a "not" filter? (default is false)
	 * @return WireArray reference to current [filtered] instance
	 *
	 */
	protected function filterData($selectors, $not = false) {

		if(is_object($selectors) && $selectors instanceof Selectors) {
			// fantastic
		} else {
			if(ctype_digit("$selectors")) $selectors = "id=$selectors";
			$selectors = new Selectors($selectors); 
		}

		$sort = '';
		$limit = '';

		foreach($this->data as $key => $item) {

			foreach($selectors as $selector) {

				if($selector->field === 'sort') {
					$sort = $selector->value; 

				} else if($selector->field === 'limit') {
					$limit = (int) $selector->value; 

				} else if($not === $selector->matches($item)) {
					unset($this->data[$key]); 
				}	
			}
		}

		if($sort) $this->sort($sort); 
		if($limit) while($this->count() > $limit) array_pop($this->data); 

		$this->trackChange("filterData:$selectors"); 
		return $this; 
	}

	/**
	 * Filter out Wires that don't match the selector. 
	 *
	 * Same as filterData, but for public interface without the $not option. 
	 * 
	 * @param string $selector AttributeSelector string to use as the filter. 
	 * @return WireArray reference to current instance.
	 * @see filterData
	 *
	 */
	public function filter($selector) {
		// destructive
		return $this->filterData($selector, false); 
	}


	/**
	 * Filter out Wires that don't match the selector. 
	 *
	 * Same as filterData, but for public interface with the $not option specifically set to "true".
	 * Example: $pages->not("nonav"); // returns all pages that don't have a nonav variable set to a positive value. 
	 * 
	 * @param string $selector AttributeSelector string to use as the filter. 
	 * @return WireArray reference to current instance.
	 * @see filterData
	 *
	 */
	public function not($selector) {
		// destructive
		return $this->filterData($selector, true); 
	}


	/**
	 * Find all Wires in this WireArray that match the given selector.
	 * 
	 * This is non destructive and returns a brand new WireArray.
	 *
	 * $param string $selector AttributeSelector string. 
	 * @return WireArray 
	 *
	 */
	public function find($selector) {
		// non descructive
		$a = clone $this; 
		if(!strlen($selector)) return $a;
		$a->filter($selector); 	

		return $a; 
	}

	/**
	 * Same as find, but returns a single Page rather than WireArray or FALSE if empty.
	 *
	 */
	public function findOne($selector) {
		return $this->find($selector)->first();
	}


	/**	
	 * Determines if the given item iterable as an array.
	 *
	 * Returns true for arrays and WireArrays. 
	 * Can be called statically like this WireArray::iterable($a).
	 * 
	 * @param mixed Item to check for iterability. 
	 * @return bool True if item is an iterable array or WireArray (or subclass of WireArray).
	 */
	public static function iterable($item) {
		if(is_array($item)) return true;
		if($item instanceof WireArray) return true;
		return false;
	}

	/**
	 * Allows iteration of the WireArray. 
	 *
	 * Fulfills IteratorAggregate interface. 
	 * TODO return $this rather than ArrayObject ?
	 * 
	 * @return ArrayObject
	 */
	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	/**
	 * Returns the number of items in this WireArray.
	 *
	 * Fulfills Countable interface. 
	 * 
	 * @return int
	 */
	public function count() {
		return count($this->data); 
	}

	/**
	 * Sets an index in the WireArray.
	 *
	 * For the ArrayAccess interface. 
	 * 
	 * @param int|string $key Key of item to set.
	 * @param int|string|array|object $value Value of item. 
	 */
	public function offsetSet($key, $value) {
		$this->set($key, $value); 
	}

	/**
	 * Returns the value of the item at the given index, or false if not set. 
	 *
	 * @param int|string $key Key of item to retrieve. 
	 * @return int|string|array|object Value of item requested, or false if it doesn't exist. 
	 */
	public function offsetGet($key) {
		return $this->get($key); 
	}
	
	/**
	 * Unsets the value at the given index. 
	 *
	 * For the ArrayAccess interface.
	 *
	 * @param int|string $key Key of the item to unset. 
	 * @return bool True if item existed and was unset. False if item didn't exist. 
	 */
	public function offsetUnset($key) {
		return $this->remove($key); 
	}


	/**
	 * Determines if the given index exists in this WireArray. 	
	 *
	 * For the ArrayAccess interface. 
	 * 
	 * @param int|string $key Key of the item to check for existance.
	 * @return bool True if the item exists, false if not.
	 */
	public function offsetExists($key) {
		return $this->has($key); 
	}

	/**
	 * Returns a string representation of this WireArray.
	 * 
	 * @return string
	 */
	public function __toString() {
		$s = '';
		foreach($this as $key => $value) {
			$s .= "$value|";
		}
		$s = rtrim($s, '|'); 
		return $s; 
	}

	/**
	 * Return a reversed version of this WireArray
	 *
	 * Non destructive
	 *
	 */ 
	public function reverse() {
		$a = $this->makeNew();
		$a->import(array_reverse($this->data, true)); 
		return $a; 
	}

	/**
	 * Return a new array that is unique (no two of the same elements)
	 *
	 * @return WireArray 
	 *
	 */
	public function unique($sortFlags = SORT_STRING) {
		$a = $this->makeNew();	
		$a->import(array_unique($this->data, $sortFlags)); 
		return $a; 
	}


	/**
	 * Clears out any tracked changes and turns change tracking ON or OFF
	 *
	 * @param bool $trackChanges True to turn change tracking ON, or false to turn OFF. Default of true is assumed. 
	 * @return this
	 *
	 */
	public function resetTrackChanges($trackChanges = true) {
		$this->itemsAdded = array();
		$this->itemsRemoved = array();
		return parent::resetTrackChanges($trackChanges); 
	}

	/**
	 * Track an item added
 	 *
	 */
	protected function trackAdd($item) {
		if($this->trackChanges()) $this->itemsAdded[] = $item;
	}

	/**
	 * Track an item removed
 	 *
	 */
	protected function trackRemove($item) {
		if($this->trackChanges()) $this->itemsRemoved[] = $item; 
	}

	/**
	 * Return array of all keys added while trackChanges was on
	 *
	 * @return array
	 *
	 */
	public function getItemsAdded() {
		return $this->itemsAdded; 
	}

	/**
	 * Return array of all keys removed while trackChanges was on
	 *
	 * @return array
	 *
	 */
	public function getItemsRemoved() {
		return $this->itemsRemoved; 
	}

	/**
	 * Given the current item, get the next in the array
	 *
	 * @param Wire $item
	 * @return Wire|null
	 *
	 */
	public function getNext($item) {
		if(!$this->isValidItem($item)) return null;
		$key = $this->getItemKey($item); 
		$getNext = false; 
		$nextItem = null;
		foreach($this->data as $k => $v) {
			if($getNext) {
				$nextItem = $v; 	
				break;
			}
			if($k === $key) $getNext = true; 
			
		}
		return $nextItem; 
	}

	/**
	 * Given the current item, get the previous item in the array
	 *
	 * @param Wire $item
	 * @return Wire|null
	 *
	 */
	public function getPrev($item) {
		if(!$this->isValidItem($item)) return null;
		$key = $this->getItemKey($item); 
		$prevItem = null; 
		$lastItem = null;
		foreach($this->data as $k => $v) {
			if($k === $key) {
				$prevItem = $lastItem; 
				break;
			}
			$lastItem = $v; 
			
		}
		return $prevItem; 
	}

	/**
	 * Does this WireArray use numeric keys only? 
	 *
	 * We determine this by creating a blank item and seeing what the type is of it's key. 
	 * 
	 * @return bool
	 *
	 */
	protected function usesNumericKeys() {

		static $testItem = null;
		static $usesNumericKeys = null; 

		if(!is_null($usesNumericKeys)) return $usesNumericKeys; 
		if(is_null($testItem)) $testItem = $this->makeBlankItem(); 
		if(is_null($testItem)) return true; 

		$key = $this->getItemKey($testItem); 
		$usesNumericKeys = is_int($key) ? true : false;
		return $usesNumericKeys; 
	}

}
