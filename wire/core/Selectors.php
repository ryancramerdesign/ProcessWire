<?php

/**
 * ProcessWire Selectors
 *
 * Processes a Selector string and can then be iterated to retrieve each resulting Selector object.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

require_once(PROCESSWIRE_CORE_PATH . "Selector.php"); 

class Selectors extends WireArray {

	/**
	 * Maximum length for a selector value
	 *
	 */
	const maxValueLength = 500; 

	/**
	 * Maximum length for a selector operator
	 *
	 */
	const maxOperatorLength = 10; 

	/**
	 * Maximum length for a selector field name
	 *
	 */
	const maxFieldLength = 50; 

	/**
	 * Maximum number of selectors that can be present in a given selectors string
	 *
	 */
	const maxSelectors = 20; 

	/**
	 * Static array of Selector types of $operator => $className
	 *
	 */
	static $selectorTypes = array();

	/**
	 * Array of all individual characters used by operators
	 *
	 */
	static $operatorChars = array();

	/**
	 * Given a selector string, extract it into one or more corresponding Selector objects, iterable in this object.
	 *
	 */
	public function __construct($selectorStr) {
		if(empty(self::$selectorTypes)) Selector::loadSelectorTypes();
		$this->extractString($selectorStr); 
	}

	/**
	 * Per WireArray interface, return true if the item is a Selector instance
	 *
	 */
	public function isValidItem($item) {
		return is_object($item) && $item instanceof Selector; 
	}

	/**
	 * Per WireArray interface, return a blank Selector
	 *
	 */
	public function makeBlankItem() {
		return new Selector();
	}

	/**
	 * Add a Selector type that processes a specific operator
	 *
	 * Static since there may be multiple instances of this Selectors class at runtime. 
	 * See Selector.php 
	 *
	 * @param string $operator
	 * @param string $class
	 *
	 */
	static public function addType($operator, $class) {
		self::$selectorTypes[$operator] = $class; 
		for($n = 0; $n < strlen($operator); $n++) {
			$c = $operator[$n]; 
			self::$operatorChars[$c] = $c; 
		}
	}

	/**
	 * Return array of all valid operator characters
	 *
	 */
	static public function getOperatorChars() {
		return self::$operatorChars; 
	}

	/**
	 * Does the given string have an operator in it? 
	 *
	 * @return bool
	 *
	 */
	static public function stringHasOperator($str) {
		$has = false;
		foreach(self::$selectorTypes as $operator => $unused) {
			if(strpos($str, $operator) !== false) {
				$has = true;
				break;
			}	
		}
		return $has; 
	}

	/**
	 * Does the given string start with a selector? 
	 *
	 * Meaning string starts with [field][operator] like "field="
	 *
	 * @return bool
	 *
	 */
	static public function stringHasSelector($str) {

		if(!self::stringHasOperator($str)) return false; 

		if(preg_match('/^([-._a-zA-Z0-9|]+)([' . implode('', self::getOperatorChars()) . ']+)/', $str, $matches)) {

			$field = $matches[1]; 
			$operator = $matches[2]; 

			// fields can't start with a dash or a period or a pipe
			if(in_array($field[0], array('-', '.', '|'))) return false;

			// if it's not an operator we recognize then abort
			if(!isset(self::$selectorTypes[$operator])) return false;

			// if we made it here, then we've found a selector
			return true; 
		}

		return false;
	}


	/**
	 * Create a new Selector object from a field name, operator, and value
	 *
	 * @param string $field
	 * @param string $operator
	 * @param string $value
	 *
	 */
	protected function create($field, $operator, $value) {
		if(!isset(self::$selectorTypes[$operator])) throw new WireException("Unknown Selector operator: '$operator' -- was your selector value properly escaped?"); 
		$class = self::$selectorTypes[$operator]; 
		$selector = new $class($field, $value); 
		return $selector; 		
	}


	/**
	 * Given a selector string, return an array of (field, value, operator) for each selector in the strong. 
	 *
	 * @param string $str The string containing a selector (or multiple selectors, separated by commas)
	 * @return array 
	 *
	 */
	protected function extractString($str) {

		$cnt = 0; 
		
		while(strlen($str)) {
		
			$field = $this->extractField($str); 
			$operator = $this->extractOperator($str, $this->getOperatorChars());
			$value = $this->extractValue($str); 

			if($field || strlen("$value")) {
				$selector = $this->create($field, $operator, $value);
				$this->add($selector); 
			}

			if(++$cnt > self::maxSelectors) break;
		}

	}


	/**
	 * Given a string starting with a field, return that field, and remove it from $str. 
	 *
	 */
	protected function extractField(&$str) {
		$field = '';

		if(preg_match('/^(!?[_|.a-zA-Z0-9]+)(.*)/', $str, $matches)) {

			$field = trim($matches[1], '|'); 
			$str = $matches[2];

			if(strpos($field, '|')) {
				$field = explode('|', $field); 
			}

		}
		return $field; 
	}


	/**
	 * Given a string starting with an operator, return that operator, and remove it from $str. 
	 *
	 */
	protected function extractOperator(&$str, array $operatorChars) {
		$n = 0;
		$operator = '';
		while(isset($str[$n]) && in_array($str[$n], $operatorChars) && $n < self::maxOperatorLength) {
			$operator .= $str[$n]; 
			$n++; 
		}
		if($operator) $str = substr($str, $n); 
		return $operator; 
	}


	/**
	 * Given a string starting with a value, return that value, and remove it from $str. 
	 *
	 */
	protected function extractValue(&$str) {

		$str = trim($str); 
		if(!strlen($str)) return '';

		if($str[0] == '"' || $str[0] == "'") {
			$openingQuote = $str[0]; 
			$n = 1; 
		} else {
			$openingQuote = '';
			$n = 0; 
		}

		$value = '';
		$lastc = '';

		do {
			if(!isset($str[$n])) break;

			$c = $str[$n]; 

			if($openingQuote) {
				// we are in a quoted value string

				if($c == $openingQuote) {

					if($lastc != '\\') {
						// same quote that opened, and not escaped
						// means the end of the value

						$n++; // skip over quote 
						break;

					} else {
						// this is an intentionally escaped quote
						// so remove the escape
						$value = rtrim($value, '\\'); 
					}
				}

			} else {
				// we are in an un-quoted value string

				if($c == ',' || $c == '|') {
					if($lastc != '\\') {
						// a non-quoted, non-escaped comma terminates the value
						break;

					} else {
						// an intentionally escaped comma
						// so remove the escape
						$value = rtrim($value, '\\'); 
					}
				}
			}

			$value .= $c; 
			$lastc = $c;

		} while(++$n < self::maxValueLength); 

		if(strlen("$value")) $str = substr($str, $n);
		$str = ltrim($str, ' ,"\''); // should be executed even if blank value

		// check if a pipe character is present next, indicating an OR value may be provided
		if(strlen($str) > 1 && substr($str, 0, 1) == '|') {
			$str = substr($str, 1); 
			// perform a recursive extract to account for all OR values
			$v = $this->extractValue($str); 
			$value = array($value); 
			if(is_array($v)) $value = array_merge($value, $v); 
				else $value[] = $v; 
		}

		return $value; 
	}

	public function __toString() {
		$str = '';
		foreach($this as $selector) {
			$str .= $selector->str . ", "; 	
		}
		return rtrim($str, ", "); 
	}

	

}
