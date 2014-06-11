<?php

/**
 * ProcessWire Selectors
 *
 * Processes a Selector string and can then be iterated to retrieve each resulting Selector object.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
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
	 * Import items into this WireArray.
	 * 
	 * @throws WireException
	 * @param string|WireArray $items Items to import.
	 * @return WireArray This instance.
	 *
	 */
	public function import($items) {
		if(is_string($items)) {
			$this->extractString($items); 	
		} else {
			return parent::import($items); 
		}
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
		return new SelectorEqual('','');
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
	 * @param string $str
	 * @return bool
	 *
	 */
	static public function stringHasOperator($str) {
		$has = false;
		foreach(self::$selectorTypes as $operator => $unused) {
			if($operator == '&') continue; // this operator is too common in other contexts
			if(strpos($str, $operator) !== false) {
				if(preg_match('/\b[_a-zA-Z0-9]+' . preg_quote($operator) . '/', $str)) {
					$has = true;
					break;
				}
			}	
		}
		return $has; 
	}

	/**
	 * Does the given string start with a selector? 
	 *
	 * Meaning string starts with [field][operator] like "field="
	 *
	 * @param string $str
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
	 * @return Selector
	 * @throws WireException
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

			$quote = '';	
			$group = $this->extractGroup($str); 	
			$field = $this->extractField($str); 
			$operator = $this->extractOperator($str, $this->getOperatorChars());
			$value = $this->extractValue($str, $quote); 

			if($quote == '[' && !self::stringHasOperator($value)) {
				// parse an API variable property to a string value
				$v = $this->parseValue($value); 
				if($v !== null) {
					$value = $v;
					$quote = '';
				}
			}

			if($field || strlen("$value")) {
				$selector = $this->create($field, $operator, $value);
				if(!is_null($group)) $selector->group = $group; 
				if($quote) $selector->quote = $quote; 
				$this->add($selector); 
			}

			if(++$cnt > self::maxSelectors) break;
		}

	}
	
	/**
	 * Given a string like name@field=... or @field=... extract the part that comes before the @
	 *
	 * This part indicates the group name, which may also be blank to indicate grouping with other blank grouped items
	 *
	 * @param string $str
	 * @return null|string
	 *
	 */
	protected function extractGroup(&$str) {
		$group = null;
		$pos = strpos($str, '@'); 
		if($pos === false) return $group; 
		if($pos === 0) {
			$group = '';
			$str = substr($str, 1); 
		} else if(preg_match('/^([-_a-zA-Z0-9]*)@(.*)/', $str, $matches)) {
			$group = $matches[1]; 
			$str = $matches[2];
		}
		return $group; 
	}

	/**
	 * Given a string starting with a field, return that field, and remove it from $str. 
	 *
	 */
	protected function extractField(&$str) {
		$field = '';
		
		if(strpos($str, '(') === 0) {
			// OR selector where specification of field name is optional and = operator is assumed
			$str = '=(' . substr($str, 1); 
			return $field; 
		}

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
	 * @param string $str String to extract value from
	 * @param string $quote Automatically populated with quote type, if found
	 * @return array|string Found values or value (excluding quotes)
	 *
	 */
	protected function extractValue(&$str, &$quote) {

		$str = trim($str); 
		if(!strlen($str)) return '';
		$quotes = array(
			// opening => closing
			'"' => '"', 
			"'" => "'", 
			'[' => ']', 
			'{' => '}', 
			'(' => ')', 
			);

		// if($str[0] == '"' || $str[0] == "'") {
		if(in_array($str[0], array_keys($quotes))) {
			$openingQuote = $str[0]; 
			$closingQuote = $quotes[$openingQuote];
			$n = 1; 
		} else {
			$openingQuote = '';
			$closingQuote = '';
			$n = 0; 
		}

		$value = '';
		$lastc = '';

		do {
			if(!isset($str[$n])) break;

			$c = $str[$n]; 

			if($openingQuote) {
				// we are in a quoted value string

				if($c == $closingQuote) { // reference closing quote

					if($lastc != '\\') {
						// same quote that opened, and not escaped
						// means the end of the value

						$n++; // skip over quote 
						$quote = $openingQuote; 
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
		$str = ltrim($str, ' ,"\']})'); // should be executed even if blank value

		// check if a pipe character is present next, indicating an OR value may be provided
		if(strlen($str) > 1 && substr($str, 0, 1) == '|') {
			$str = substr($str, 1); 
			// perform a recursive extract to account for all OR values
			$v = $this->extractValue($str, $quote); 
			$quote = ''; // we don't support separately quoted OR values
			$value = array($value); 
			if(is_array($v)) $value = array_merge($value, $v); 
				else $value[] = $v; 
		}

		return $value; 
	}

	/**
	 * Given a value string with an "api_var" or "api_var.property" return the string value of the property
	 *
	 * @param string $value var or var.property
	 * @return null|string Returns null if it doesn't resolve to anything or a string of the value it resolves to
	 *
	 */
	public function parseValue($value) {
		if(!preg_match('/^\$?[_a-zA-Z0-9]+(?:\.[_a-zA-Z0-9]+)?$/', $value)) return null;
		$property = '';
		if(strpos($value, '.')) list($value, $property) = explode('.', $value); 
		$allowed = array('session', 'page', 'user'); // @todo make the whitelist configurable
		if(!in_array($value, $allowed)) return null; 
		$value = $this->wire($value); 
		if(is_null($value)) return null; // does not resolve to API var
		if(empty($property)) return (string) $value;  // no property requested, just return string value 
		if(!is_object($value)) return null; // property requested, but value is not an object
		return (string) $value->$property; 
	}

	public function __toString() {
		$str = '';
		foreach($this as $selector) {
			$str .= $selector->str . ", "; 	
		}
		return rtrim($str, ", "); 
	}

	

}
