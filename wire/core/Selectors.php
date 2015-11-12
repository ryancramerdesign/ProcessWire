<?php

/**
 * ProcessWire Selectors
 *
 * Processes a Selector string and can then be iterated to retrieve each resulting Selector object.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
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
	 * Original saved selector string, used for debugging purposes
	 *
	 */
	protected $selectorStr = '';

	/**
	 * Whether or not variables like [user.id] should be converted to actual value
	 * 
	 * In most cases this should be true. 
	 * 
	 * @var bool
	 *
	 */
	protected $parseVars = true;

	/**
	 * API variable names that are allowed to be parsed
	 * 
	 * @var array
	 * 
	 */
	protected $allowedParseVars = array(
		'session', 
		'page', 
		'user',
	);

	/**
	 * Types of quotes selector values may be surrounded in
	 *
	 */
	protected $quotes = array(
		// opening => closing
		'"' => '"',
		"'" => "'",
		'[' => ']',
		'{' => '}',
		'(' => ')',
		);

	/**
	 * Given a selector string, extract it into one or more corresponding Selector objects, iterable in this object.
	 * 
	 * @param string|null Selector string. If not provided here, please follow-up with a setSelectorString($str) call. 
	 *
	 */
	public function __construct($selectorStr = null) {
		if(!is_null($selectorStr)) $this->setSelectorString($selectorStr); 
	}

	/**
	 * Set the selector string (if not provided to constructor)
	 * 
	 * @param string $selectorStr
	 * 
	 */
	public function setSelectorString($selectorStr) {
		$this->selectorStr = $selectorStr;
		$this->extractString(trim($selectorStr)); 
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
		
		
		static $letters = 'abcdefghijklmnopqrstuvwxyz';
		static $digits = '_0123456789';
		
		$has = false;
		
		foreach(self::$selectorTypes as $operator => $unused) {
			
			if($operator == '&') continue; // this operator is too common in other contexts
			
			$pos = strpos($str, $operator); 
			if(!$pos) continue; // if pos is 0 or false, move onto the next
			
			// possible match: confirm that field name precedes an operator
			// if(preg_match('/\b[_a-zA-Z0-9]+' . preg_quote($operator) . '/', $str)) {
			
			$c = $str[$pos-1]; // letter before the operator
			
			if(stripos($letters, $c) !== false) {
				// if a letter appears as the character before operator, then we're good
				$has = true; 
				
			} else if(strpos($digits, $c) !== false) {
				// if a digit appears as the character before operator, we need to confirm there is at least one letter
				// as there can't be a field named 123, for example, which would mean the operator is likely something 
				// to do with math equations, which we would refuse as a valid selector operator
				$n = $pos-1; 	
				while($n > 0) {
					$c = $str[--$n];
					if(stripos($letters, $c) !== false) {
						// if found a letter, then we've got something valid
						$has = true; 
						break;
						
					} else if(strpos($digits, $c) === false) {
						// if we've got a non-digit (and non-letter) then definitely not valid
						break;
					}
				} 
			}
			
			if($has) break;
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
		
		$has = false;

		if(!self::stringHasOperator($str)) {
			
			// default: has=false
			
		} else if(preg_match('/^!?([-._a-zA-Z0-9|]+)([' . implode('', self::getOperatorChars()) . ']+)/', $str, $matches)) {

			$field = $matches[1]; 
			$operator = $matches[2]; 

			if(in_array($field[0], array('-', '.', '|'))) {
				// fields can't start with a dash or a period or a pipe
				$has = false; 
			} else if(!isset(self::$selectorTypes[$operator])) {
				// if it's not an operator we recognize then abort
				$has = false; 
			} else {
				// if we made it here, then we've found a selector
				$has = true; 
			}
		}
		
		return $has;
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
		if(!isset(self::$selectorTypes[$operator])) {
			$debug = $this->wire('config')->debug ? "field='$field', value='$value', selector: '$this->selectorStr'" : "";
			throw new WireException("Unknown Selector operator: '$operator' -- was your selector value properly escaped? $debug"); 
		}
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

			if($this->parseVars && $quote == '[' && $this->valueHasVar($value)) {
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
	 * Early-exit optimizations for extractValue
	 * 
	 * @param string $str String to extract value from, $str will be modified if extraction successful
	 * @param string $openingQuote Opening quote character, if string has them, blank string otherwise
	 * @param string $closingQuote Closing quote character, if string has them, blank string otherwise
	 * @return mixed Returns found value if successful, boolean false if not
	 *
	 */
	protected function extractValueQuick(&$str, $openingQuote, $closingQuote) {
		
		// determine where value ends
		$commaPos = strpos("$str,", $closingQuote . ','); // "$str," just in case value is last and no trailing comma
		
		if($commaPos === false && $closingQuote) {
			// if closing quote and comma didn't match, try to match just comma in case of "something"<space>,
			$commaPos = strpos(substr($str, 1), ',');
			if($commaPos !== false) $commaPos++;
		}

		if($commaPos === false) {
			// value is the last one in $str
			$commaPos = strlen($str); 
			
		} else if($commaPos && $str[$commaPos-1] === '//') {
			// escaped comma or closing quote means no optimization possible here
			return false; 
		}
		
		// extract the value for testing
		$value = substr($str, 0, $commaPos);
	
		// if there is an operator present, it might be a subselector or OR-group
		if(self::stringHasOperator($value)) return false;
	
		if($openingQuote) {
			// if there were quotes, trim them out
			$value = trim($value, $openingQuote . $closingQuote); 
		}

		// determine if there are any embedded quotes in the value
		$hasEmbeddedQuotes = false; 
		foreach($this->quotes as $open => $close) {
			if(strpos($value, $open)) $hasEmbeddedQuotes = true; 
		}
		
		// if value contains quotes anywhere inside of it, abort optimization
		if($hasEmbeddedQuotes) return false;
	
		// does the value contain possible OR conditions?
		if(strpos($value, '|') !== false) {
			
			// if there is an escaped pipe, abort optimization attempt
			if(strpos($value, '\\' . '|') !== false) return false; 
		
			// if value was surrounded in "quotes" or 'quotes' abort optimization attempt
			// as the pipe is a literal value rather than an OR
			if($openingQuote == '"' || $openingQuote == "'") return false;
		
			// we have valid OR conditions, so convert to an array
			$value = explode('|', $value); 
		}

		// if we reach this point we have a successful extraction and can remove value from str
		// $str = $commaPos ? trim(substr($str, $commaPos+1)) : '';
		$str = trim(substr($str, $commaPos+1));

		// successful optimization
		return $value; 
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
		
		if(isset($this->quotes[$str[0]])) {
			$openingQuote = $str[0]; 
			$closingQuote = $this->quotes[$openingQuote];
			$quote = $openingQuote; 
			$n = 1; 
		} else {
			$openingQuote = '';
			$closingQuote = '';
			$n = 0; 
		}
		
		$value = $this->extractValueQuick($str, $openingQuote, $closingQuote); // see if we can do a quick exit
		if($value !== false) return $value; 

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
		if(!in_array($value, $this->allowedParseVars)) return null; 
		$value = $this->wire($value); 
		if(is_null($value)) return null; // does not resolve to API var
		if(empty($property)) return (string) $value;  // no property requested, just return string value 
		if(!is_object($value)) return null; // property requested, but value is not an object
		return (string) $value->$property; 
	}
	
	/**
	 * Set whether or not vars should be parsed
	 *
	 * By default this is true, so only need to call this method to disable variable parsing.
	 *
	 * @param bool $parseVars
	 *
	 */
	public function setParseVars($parseVars) {
		$this->parseVars = $parseVars ? true : false;
	}

	/**
	 * Does the given Selector value contain a parseable value?
	 * 
	 * @param Selector $selector
	 * @return bool
	 * 
	 */
	public function selectorHasVar(Selector $selector) {
		if($selector->quote != '[') return false; 
		$has = false;
		foreach($selector->values as $value) {
			if($this->valueHasVar($value)) {
				$has = true; 
				break;
			}
		}
		return $has;
	}

	/**
	 * Does the given value contain an API var reference?
	 * 
	 * It is assumed the value was quoted in "[value]", and the quotes are not there now. 
	 *
	 * @param string $value The value to evaluate
	 * @return bool
	 *
	 */
	public function valueHasVar($value) {
		if(self::stringHasOperator($value)) return false;
		if(strpos($value, '.') !== false) {
			list($name, $subname) = explode('.', $value);
		} else {
			$name = $value;
			$subname = '';
		}
		if(!in_array($name, $this->allowedParseVars)) return false;
		if(strlen($subname) && $this->wire('sanitizer')->fieldName($subname) !== $subname) return false;
		return true; 
	}

	/**
	 * Does the given Wire match these Selectors?
	 * 
	 * @param Wire $item
	 * @return bool
	 * 
	 */
	public function matches(Wire $item) {
		
		// if item provides it's own matches function, then let it have control
		if($item instanceof WireMatchable) return $item->matches($this);
	
		$matches = true;
		foreach($this as $selector) {
			$value = array();
			foreach($selector->fields as $property) {
				if(strpos($property, '.') && $item instanceof WireData) {
					$value[] =  $item->getDot($property);
				} else {
					$value[] = (string) $item->$property;
				}
			}
			if(!$selector->matches($value)) {
				$matches = false;
				break;
			}
		}
		
		return $matches;
	}

	public function __toString() {
		$str = '';
		foreach($this as $selector) {
			$str .= $selector->str . ", "; 	
		}
		return rtrim($str, ", "); 
	}


	/**
	 * Utility method to convert array to selector string (work in progress, future use)
	 * 
	 * Accepts regular indexed or associative array. 
	 * 
	 * When given an associative array, the keys are assumed to be field names. An operator
	 * may be appended to this field name. If no operator is present, then "=" is assumed. 
	 * The values may be string, int or array. When given an array, it is assumed the values
	 * are OR values. 
	 * 
	 * When given a regular array, the value may be an int or a string. if the value contains 
	 * an operator, it is assumed to be a key=value statement. If the value is an integer or
	 * integer string, it is assumed to be a page ID. Otherwise, if the value contains no
	 * operator, it is discarded. 
	 * 
	 * Currently this method does no sanitization, it only converts an array to a selector
	 * string. 
	 * 
	 * @todo this method is not yet functional or in use
	 * 
	 * @param array $a
	 * @return string
	 * 
	 */
	public static function arrayToSelectorString(array $a) {

		$parts = array(); // regular array, components of the selector
		$ids = array(); // array of page IDs, if present
		$sanitizer = wire('sanitizer');

		foreach($a as $key => $value) {

			if(ctype_digit($key)) {
				
				// regular array, we can ignore $key
				if(is_int($value) || ctype_digit("$value")) {
					// value is page ID
					$ids[] = (int) $value;
					
				} else if(strpos($value, '=') || strpos($value, '<') || strpos($value, '>')) {
					// value contains an operator
					$parts[] = $value; 
					
				} else {
					// we have no idea what $value is? discard it
					continue;
				}

			} else {
				// associative, array key is field name, optionally with operator at end (= assumed otherwise)

				if(is_array($value)) {
					// value contains multiple OR values
					foreach($value as $k => $v) {
						if(!ctype_digit("$v")) $value[$k] = $sanitizer->selectorValue($v);
					}
					$value = implode('|', $value);

				} else if(is_int($value) || ctype_digit("$value")) {
					// number
					$value = (int) $value;

				} else {
					// value is single value
					$value = trim($value); 
					$quotes = substr($value, 0, 1) . substr($value, -1);
					if($quotes == '""' || $quotes == "''" || $quotes == '[]' || $quotes == '()') {
						// value is already quoted so we leave it 
					} else {
						// value may need quotes, let sanitizer decide
						$value = $sanitizer->selectorValue($value);
					}
				}

				if(strpos($key, '=') || strpos($key, '<') || strpos($key, '>')) {
					// key already contains operator at end
				} else {
					// no operator present, so we assume the "=" operator by appending to key
					$key .= '=';
				}

				$parts[] = "$key$value";
			}
		}

		// create selector string
		$str = '';
		if(count($ids)) $str .= "id=" . implode('|', $ids) . ', ';
		if(count($parts)) $str .= implode(', ', $parts);
		
		return rtrim($str, ', ');
	}

	/**
	 * Simple "a=b, c=d" selector-style string conversion to associative array, for fast/simple needs
	 * 
	 * - The only supported operator is "=". 
	 * - Each key=value statement should be separated by a comma. 
	 * - Do not use quoted values. 
	 * - If you need a literal comma, use a double comma ",,".
	 * - If you need a literal equals, use a double equals "==". 
	 * 
	 * @param string $s
	 * @return array
	 * 
	 */
	public static function keyValueStringToArray($s) {
		
		if(strpos($s, '~~COMMA') !== false) $s = str_replace('~~COMMA', '', $s); 
		if(strpos($s, '~~EQUAL') !== false) $s = str_replace('~~EQUAL', '', $s); 
		
		$hasEscaped = false;
		
		if(strpos($s, ',,') !== false) {
			$s = str_replace(',,', '~~COMMA', $s);
			$hasEscaped = true; 
		}
		if(strpos($s, '==') !== false) {
			$s = str_replace('==', '~~EQUAL', $s);
			$hasEscaped = true; 
		}
		
		$a = array();	
		$parts = explode(',', $s); 
		foreach($parts as $part) {
			if(!strpos($part, '=')) continue;
			list($key, $value) = explode('=', $part); 
			if($hasEscaped) $value = str_replace(array('~~COMMA', '~~EQUAL'), array(',', '='), $value); 
			$a[trim($key)] = trim($value); 	
		}
		
		return $a; 
	}

	/**
	 * Given an assoc array, convert to a key=value selector-style string
	 * 
	 * @param $a
	 * @return string
	 * 
	 */
	public static function arrayToKeyValueString($a) {
		$s = '';
		foreach($a as $key => $value) {
			if(strpos($value, ',') !== false) $value = str_replace(array(',,', ','), ',,', $value); 
			if(strpos($value, '=') !== false) $value = str_replace('=', '==', $value); 
			$s .= "$key=$value, ";
		}
		return rtrim($s, ", "); 
	}

}

Selector::loadSelectorTypes();
