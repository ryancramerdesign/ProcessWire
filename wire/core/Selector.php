<?php

/**
 * ProcessWire Selector base type and implementation for various Selector types
 *
 * Selectors hold a field, operator and value and are used in finding things
 *
 * This file provides the base implementation for a Selector, as well as implementation
 * for several actual Selector types under the main Selector class. 
 * 
 * @TODO should the individual Selector types be convereted to Modules?
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

// require_once(PROCESSWIRE_CORE_PATH . "Selectors.php"); 

/**
 * Selector maintains a single selector consisting of field name, operator, and value.
 * 
 * Field and value may optionally be arrays, where are assumed to be OR values. 
 *
 * Serves as the base class for the different Selector types (seen below this class). 
 * 
 */
abstract class Selector extends WireData {

	/**
	 * Given a field name and value, construct the Selector. 
	 *
	 * If the provided value is an array of values, then it is assumed this Selector may match any one of them (OR condition).
	 *
	 * If only one field is provided, and that field is prepended by an exclamation point, i.e. !field=something
	 * then the condition is reversed. 
	 *
	 * @param string $field
	 * @param string|int|array $value 
	 *
	 */
	public function __construct($field, $value) {

		$not = false; 
		if(!is_array($field) && $field[0] == '!') {
			$not = true; 
			$field = ltrim($field, '!'); 
		}

		$this->set('field', $field); 	
		$this->set('value', $value); 
		$this->set('not', $not); 
		$this->set('str', ($not ? '!' : '') . (is_array($field) ? implode('|', $field) : $field) . $this->getOperator() . (is_array($value) ? implode("|", $value) : $value));
	}

	public function get($key) {
		if($key == 'operator') return $this->getOperator();
		return parent::get($key); 
	}

	/**
	 * Return the operator used by this Selector
	 *
	 * Strict standards don't let us make static abstract methods, so this one throws an exception if it's not reimplemented.
	 *
	 * @return string
	 *
	 */
	public static function getOperator() {
		throw new WireException("This method must be implemented by " . get_class($this)); 
	}

	/**
	 * Does $value1 match $value2?
	 *
	 * @param mixed $value1 Dynamic comparison value
	 * @param string $value2 User-supplied value to compare against
	 * @return bool
	 *
	 */
	abstract protected function match($value1, $value2);

	/**
	 * Does this Selector match the given value?
	 *
	 * If the value held by this Selector is an array of values, it will check if any one of them matches the value supplied here. 
	 *
	 * @param string|int $value
	 *
	 */
	public function matches($value) {

		$matches = false;
		$values = is_array($this->value) ? $this->value : array($this->value); 
		$field = $this->field; 

		foreach($values as $v) {
			if($v instanceof Wire) $v = $v->$field; 
			//if($this->match($v, $value)) {
			if($this->match($value, $v)) {
				$matches = true; 
				break;
			}
		}
		return $this->evaluate($matches); 
	}

	/**
	 * Provides the opportunity to override or NOT the condition
	 *
	 * Selectors should include a call to this in their matches function
	 *
	 * @param bool $condition
	 * @return bool
	 *
	 */
	protected function evaluate($matches) {
		if($this->not) return !$matches; 
		return $matches; 
	}

	/**
	 * The string value of Selector is always the selector string that it originated from
	 *
	 */
	public function __toString() {
		return $this->str; 
	}

	/**
	 * Add all individual selector types to the runtime Selectors
	 *
	 */
	static public function loadSelectorTypes() { 
		Selectors::addType(SelectorEqual::getOperator(), 'SelectorEqual'); 
		Selectors::addType(SelectorNotEqual::getOperator(), 'SelectorNotEqual'); 
		Selectors::addType(SelectorGreaterThan::getOperator(), 'SelectorGreaterThan'); 
		Selectors::addType(SelectorLessThan::getOperator(), 'SelectorLessThan'); 
		Selectors::addType(SelectorGreaterThanEqual::getOperator(), 'SelectorGreaterThanEqual'); 
		Selectors::addType(SelectorLessThanEqual::getOperator(), 'SelectorLessThanEqual'); 
		Selectors::addType(SelectorContains::getOperator(), 'SelectorContains'); 
		Selectors::addType(SelectorContainsLike::getOperator(), 'SelectorContainsLike'); 
		Selectors::addType(SelectorContainsWords::getOperator(), 'SelectorContainsWords'); 
		Selectors::addType(SelectorStarts::getOperator(), 'SelectorStarts'); 
		Selectors::addType(SelectorEnds::getOperator(), 'SelectorEnds'); 
		Selectors::addType(SelectorBitwiseAnd::getOperator(), 'SelectorBitwiseAnd'); 
	}
}

/**
 * Selector that matches equality between two values
 *
 */
class SelectorEqual extends Selector {
	public static function getOperator() { return '='; }
	protected function match($value1, $value2) { return $this->evaluate($value1 == $value2); }
}

/**
 * Selector that matches two values that aren't equal
 *
 */
class SelectorNotEqual extends Selector {
	public static function getOperator() { return '!='; }
	protected function match($value1, $value2) { return $this->evaluate($value1 != $value2); }
}

/**
 * Selector that matches one value greater than another
 *
 */
class SelectorGreaterThan extends Selector { 
	public static function getOperator() { return '>'; }
	protected function match($value1, $value2) { return $this->evaluate($value1 > $value2); }
}

/**
 * Selector that matches one value less than another
 *
 */
class SelectorLessThan extends Selector { 
	public static function getOperator() { return '<'; }
	protected function match($value1, $value2) { return $this->evaluate($value1 < $value2); }
}

/**
 * Selector that matches one value greater than or equal to another
 *
 */
class SelectorGreaterThanEqual extends Selector { 
	public static function getOperator() { return '>='; }
	protected function match($value1, $value2) { return $this->evaluate($value1 >= $value2); }
}

/**
 * Selector that matches one value less than or equal to another
 *
 */
class SelectorLessThanEqual extends Selector { 
	public static function getOperator() { return '<='; }
	protected function match($value1, $value2) { return $this->evaluate($value1 <= $value2); }
}

/**
 * Selector that matches one string value (phrase) that happens to be present in another string value
 *
 */
class SelectorContains extends Selector { 
	public static function getOperator() { return '*='; }
	protected function match($value1, $value2) { return $this->evaluate(stripos($value1, $value2) !== false); }
}

/**
 * Same as SelectorContains but serves as operator placeholder for SQL LIKE operations
 *
 */
class SelectorContainsLike extends SelectorContains { 
	public static function getOperator() { return '%='; }
}

/**
 * Selector that matches one string value that happens to have all of it's words present in another string value (regardless of individual word location)
 *
 */
class SelectorContainsWords extends Selector { 
	public static function getOperator() { return '~='; }
	protected function match($value1, $value2) { 
		$hasAll = true; 
		$words = preg_split('/[-\s]/', $value2, -1, PREG_SPLIT_NO_EMPTY);
		foreach($words as $key => $word) if(!preg_match('/\b' . preg_quote($word) . '\b/i')) {
			$hasAll = false;
			break;
		}
		return $this->evaluate($hasAll); 
	}
}

/**
 * Selector that matches if the value exists at the beginning of another value
 *
 */
class SelectorStarts extends Selector { 
	public static function getOperator() { return '^='; }
	protected function match($value1, $value2) { return $this->evaluate(stripos(trim($value1), $value2) === 0); }
}

/**
 * Selector that matches if the value exists at the end of another value
 *
 */
class SelectorEnds extends Selector { 
	public static function getOperator() { return '$='; }
	protected function match($value1, $value2) { 
		$value2 = trim($value2); 
		$value1 = substr($value1, -1 * strlen($value2));
                return $this->evaluate(strcasecmp($value1, $value2) == 0);
	}
}

/**
 * Selector that matches a bitwise AND '&'
 *
 */
class SelectorBitwiseAnd extends Selector { 
	public static function getOperator() { return '&'; }
	protected function match($value1, $value2) { return $this->evaluate(((int) $value1) & ((int) $value2)); }
}

