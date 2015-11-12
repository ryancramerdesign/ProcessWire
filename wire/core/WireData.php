<?php

/**
 * ProcessWire WireData
 *
 * This is the base data container class used throughout ProcessWire. 
 * It provides get and set access to properties internally stored in a $data array. 
 * Otherwise it is identical to the Wire class. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 */

class WireData extends Wire implements IteratorAggregate {

	/**
	 * Array where get/set properties are stored
	 *
	 */
	protected $data = array(); 

	/**
	 * Set a value 
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 *
	 */
	public function set($key, $value) {
		if($key == 'data') {
			if(!is_array($value)) $value = (array) $value;
			return $this->setArray($value); 
		}
		$v = isset($this->data[$key]) ? $this->data[$key] : null;
		if(!$this->isEqual($key, $v, $value)) $this->trackChange($key, $v, $value); 
		$this->data[$key] = $value; 
		return $this; 
	}

	/**
	 * Same as set() but triggers no change tracking or hooks
	 *
	 * If trackChanges is false, then this is no different than set().
	 * If trackChanges is true, then the value will be set but not recorded in the changes list.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 *
	 */
	public function setQuietly($key, $value) {
		$track = $this->trackChanges(); 
		$this->setTrackChanges(false);
		$this->set($key, $value);
		if($track) $this->setTrackChanges(true);
		return $this;
	}

	/**
	 * Is $value1 equal to $value2?
	 *
	 * This template method provided so that descending classes can optionally determine 
 	 * whether a change should be tracked. 
	 *
	 * @param string $key Name of the key that triggered the check (see WireData::set)
	 * @param mixed $value1
	 * @param mixed $value2
	 * @return bool
	 *
	 */
	protected function isEqual($key, $value1, $value2) {
		// $key intentionally not used here, but may be used by descending classes
		return $value1 === $value2; 	
	}

	/**
	 * Set an array of key=value pairs
	 *
	 * @param array $data
	 * @return $this
	 * @see set()
	 *
	 */
	public function setArray(array $data) {
		foreach($data as $key => $value) $this->set($key, $value); 
		return $this; 
	}

	/**
	 * Provides direct reference access to set values in the $data array
	 * 
	 * @param string $key
	 * @param mixed $value
	 * return $this
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value); 
	}

	/**
	 * Provides direct reference access to retrieve values in the $data array
	 *
	 * If the given $key is an object, it will cast it to a string. 
	 * If the given key is a string with "|" pipe characters in it, it will try all till it finds a value. 
	 *
 	 * @param string|object $key
	 * @return mixed|null Returns null if the key was not found. 
	 *
	 */
	public function get($key) {
		if(is_object($key)) $key = "$key";
		if(array_key_exists($key, $this->data)) return $this->data[$key]; 
		if(strpos($key, '|')) {
			$keys = explode('|', $key); 
			foreach($keys as $k) if($value = $this->get($k)) return $value; 
		}
		return parent::__get($key); // back to Wire
	}

	/**
	 * Like get() or set() but will only get/set from $this->data
	 * 
	 * To use as a get() simply specify no $value. To use as a set(), specify a value.
	 * If you omit a $key and $value, this method will return the entire data array.
	 * 
	 * The benefit of this method over get() is that it excludes API vars and potentially
	 * other things (defined by descending classes) that you may not want. 
	 * 
	 * The benefit of this method over set() is that you dictate you only want it to set
	 * the value in the $this->data container, and not potentially elsewhere, if it matters
	 * in the descending class.
	 * 
	 * @param $key Property you want to get or set
	 * @param mixed $value Optionally specify a value if you want to set rather than get
	 * @return mixed|null|this Returns value if geting a value or null if not found.
	 * 	If you intead specify a value to set, it always returns $this.  
	 * 
	 */
	public function data($key = null, $value = null) {
		if(is_null($key)) return $this->data;
		if(is_null($value)) {
			return isset($this->data[$key]) ? $this->data[$key] : null;
		} else {
			$this->data[$key] = $value; 
			return $this;
		}
	}

	/**
	 * Returns the full $data array
	 * 
	 * If descending classes also store data in other containers, they may want to
	 * override this method to include that data as well.
	 * 
	 * @return array
	 *
	 */
	public function getArray() {
		return $this->data; 
	}

	/**
	 * Get a property via dot syntax: field.subfield (static)
	 *
	 * Static version for internal core use. Use the non-static getDot() instead.
	 *
	 * @param string $key 
	 * @param Wire $from The instance you want to pull the value from
	 * @return null|mixed Returns value if found or null if not
	 *
	 */
	public static function _getDot($key, Wire $from) {
		$key = trim($key, '.');
		if(strpos($key, '.')) {
			// dot present
			$keys = explode('.', $key); // convert to array
			$key = array_shift($keys); // get first item
		} else {
			// dot not present
			$keys = array();
		}
		if(Wire::getFuel($key) !== null) return null; // don't allow API vars to be retrieved this way
		if($from instanceof WireData) $value = $from->get($key);
			else if($from instanceof WireArray) $value = $from->getProperty($key);
			else $value = $from->$key;
		if(!count($keys)) return $value; // final value
		if(is_object($value)) {
			if(count($keys) > 1) {
				$keys = implode('.', $keys); // convert back to string
				if($value instanceof WireData) $value = $value->getDot($keys); // for override potential
					else $value = self::_getDot($keys, $value);
			} else {
				$key = array_shift($keys);
				// just one key left, like 'title'
				if($value instanceof WireData) {
					$value = $value->get($key);
				} else if($value instanceof WireArray) {
					if($key == 'count') {
						$value = count($value);
					} else {
						$a = array();
						foreach($value as $v) $a[] = $v->get($key); 	
						$value = $a; 
					}
				}
			}
		} else {
			// there is a dot property remaining and nothing to send it to
			$value = null; 
		}
		return $value; 
	}

	/**
	 * Get a property via dot syntax: field.subfield.subfield
	 *
	 * Some classes of WireData may choose to add a call to this as part of their 
	 * get() method as a syntax convenience.
	 *
	 * @param string $key 
	 * @return null|mixed Returns value if found or null if not
	 *
	 */
	public function getDot($key) {
		return self::_getDot($key, $this); 
	}

	/**
	 * Provides direct reference access to variables in the $data array
	 *
	 * Otherwise the same as get()
	 *
	 * @param string $key
	 * @return mixed|null
	 *
	 */
	public function __get($key) {
		return $this->get($key); 
	}

	/**
	 * Enables use of $var('key')
	 * 
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function __invoke($key) {
		return $this->get($key);
	}

	/**
	 * Remove a given $key from the $data array
	 *
	 * @param string $key
	 * @return $this
	 *
	 */
	public function remove($key) {
		$value = isset($this->data[$key]) ? $this->data[$key] : null;
		$this->trackChange("unset:$key", $value, null); 
		unset($this->data[$key]); 
		return $this;
	}

	/**
	 * Make the $data array iterable through this object, per the IteratorAggregate interface
	 * 
	 * @return ArrayObject
	 *
	 */
	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	/**
	 * Does this WireData have the given property in it's $data?
	 *
	 * @param string $key	
	 * @return bool
	 *
	 */
	public function has($key) {
		return ($this->get($key) !== null); 
	}

	/**
	 * Take the current item and append the given items, returning a new WireArray
	 *
	 * This is for syntactic convenience, i.e. 
	 * if($page->and($page->parents)->has("featured=1")) { ... }
	 *
	 * @param WireArray|WireData|string $items May be a WireData, WireArray or gettable property from this object that returns a WireData|WireArray.
	 * @return WireArray
	 * @throws WireException If invalid argument supplied
	 *
	 */
	public function ___and($items) {

		if(is_string($items)) $items = $this->get($items); 

		if($items instanceof WireArray) {
			// great, that's what we want
			$a = clone $items; 
			$a->prepend($this);
		} else if($items instanceof WireData) {
			// single item
			$className = get_class($this) . 'Array';
			if(!class_exists($className)) $className = 'WireArray';		
			$a = new $className(); 
			$a->add($this);
			$a->add($items); 
		} else {
			// unknown
			throw new WireException('Invalid argument provided to WireData::and(...)'); 
		}

		return $a; 
	}

	/**
	 * Ensures that isset() and empty() work for this classes properties. 
	 * 
	 * @param string $key
	 * @return bool
	 *
	 */
	public function __isset($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Ensures that unset() works for this classes data. 
	 * 
	 * @param string $key
	 *
	 */
	public function __unset($key) {
		$this->remove($key); 
	}

	/**
	 * debugInfo PHP 5.6+ magic method
	 *
	 * @return array
	 *
	 */
	public function __debugInfo() {
		$info = parent::__debugInfo();
		if(count($this->data)) $info['data'] = $this->data; 
		return $info; 
	}

}

