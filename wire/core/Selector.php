<?php

/**
 * ProcessWire Selector base type and implementation for various Selector types
 *
 * Selectors hold a field, operator and value and are used in finding things
 *
 * This file provides the base implementation for a Selector, as well as implementation
 * for several actual Selector types under the main Selector class. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 */

/**
 * Selector maintains a single selector consisting of field name, operator, and value.
 * 
 * Field and value may optionally be arrays, where are assumed to be OR values. 
 *
 * Serves as the base class for the different Selector types (seen below this class). 
 * 
 * @property string|array $field Field or fields present in the selector (can be string or array)*
 * @property array $fields Fields that were present in selector (same as $field, but always array)
 * @property string $operator Operator used by the selector**
 * @property string|array $value Value or values present in the selector (can be string or array)*
 * @property array $values Values that were present in selector (same as $value, but always array)
 * @property bool $not Is this a NOT selector? (i.e. returns the opposite if what it would otherwise)
 * @property string|null $group Group name for this selector (if field was prepended with a "group_name@")
 * @property string $quote Type of quotes value was in, or blank if it was not quoted. One of: '"[{(
 * 
 * *The $field and $value properties may either be an array or string. As a result, we recommend
 * accessing the $fields or $values properties (instead of $field or $value), because they are always
 * return an array. 
 * 
 * **Operator is determined by the Selector class name, and thus may not be changed without replacing
 * the entire Selector. 
 * 
 * 
 */
abstract class Selector extends WireData {

	/**
	 * Given a field name and value, construct the Selector. 
	 *
	 * If the provided $field is an array or pipe "|" separated string, Selector may match any of them (OR field condition)
	 * If the provided $value is an array of pipe "|" separated string, Selector may match any one of them (OR value condition).
	 * 
	 * If only one field is provided as a string, and that field is prepended by an exclamation point, i.e. !field=something
	 * then the condition is reversed. 
	 *
	 * @param string|array $field 
	 * @param string|int|array $value 
	 *
	 */
	public function __construct($field, $value) {

		$not = false; 
		if(!is_array($field) && isset($field[0]) && $field[0] == '!') {
			$not = true; 
			$field = ltrim($field, '!'); 
		}

		$this->set('field', $field); 	
		$this->set('value', $value); 
		$this->set('not', $not); 
		$this->set('group', null); // group name identified with 'group_name@' before a field name
		$this->set('quote', ''); // if $value in quotes, this contains either: ', ", [, {, or (, indicating quote type (set by Selectors class)
	}

	public function get($key) {
		if($key == 'operator') return $this->getOperator();
		if($key == 'str') return $this->__toString();
		if($key == 'values') {
			$value = $this->value; 
			if(is_array($value)) return $value; 
			if(!is_object($value) && !strlen($value)) return array();
			return array($value);
		}
		if($key == 'fields') {
			$field = $this->field; 
			if(is_array($field)) return $field;
			if(!strlen($field)) return array();
			return array($field); 
		}
		return parent::get($key); 
	}
	
	public function set($key, $value) {
		if($key == 'fields') return parent::set('field', $value);
		if($key == 'values') return parent::set('value', $value); 
		if($key == 'operator') {
			$this->error("You cannot set the operator on a Selector: $this");
			return $this;
		}
		return parent::set($key, $value); 
	}

	/**
	 * Return the operator used by this Selector
	 *
	 * Strict standards don't let us make static abstract methods, so this one throws an exception if it's not reimplemented.
	 *
	 * @return string
	 * @throws WireException
	 *
	 */
	public static function getOperator() {
		throw new WireException("This getOperator method must be implemented"); 
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
	 * @param string|int|Wire $value If given a Wire, then matches will also operate on OR field=value type selectors, where present
	 * @return bool
	 *
	 */
	public function matches($value) {

		$matches = false;
		$values1 = is_array($this->value) ? $this->value : array($this->value); 
		$field = $this->field; 
		$operator = $this->getOperator();

		// prepare the value we are comparing
		if(is_object($value)) {
			if($this->wire('languages') && $value instanceof LanguagesValueInterface) $value = (string) $value; 
				else if($value instanceof WireData) $value = $value->get($field);
				else if($value instanceof WireArray && is_string($field) && !strpos($field, '.')) $value = (string) $value; // 123|456|789, etc.
				else if($value instanceof Wire) $value = $value->$field; 
			$value = (string) $value; 
		}

		if(is_string($value) && strpos($value, '|') !== false) $value = explode('|', $value); 
		if(!is_array($value)) $value = array($value);
		$values2 = $value; 
		unset($value);

		// now we're just dealing with 2 arrays: $values1 and $values2
		// $values1 is the value stored by the selector
		// $values2 is the value passed into the matches() function

		$numMatches = 0; 
		if($operator == '!=') $numMatchesRequired = (count($values1) + count($values2)) - 1; 
			else $numMatchesRequired = 1; 
		
		$fields = is_array($field) ? $field : array($field); 
		
		foreach($fields as $field) {
	
			foreach($values1 as $v1) {
	
				if(is_object($v1)) {
					if($v1 instanceof WireData) $v1 = $v1->get($field);
						else if($v1 instanceof Wire) $v1 = $v1->$field; 
				}

				foreach($values2 as $v2) {
					if(empty($v2) && empty($v1)) {
						// normalize empty values so that they will match if both considered "empty"
						$v2 = '';
						$v1 = '';
					}
					if($this->match($v2, $v1)) {
						$numMatches++;
					}
				}
	
				if($numMatches >= $numMatchesRequired) {
					$matches = true;
					break;
				}
			}
			if($matches) break;
		}

		return $matches; 
	}

	/**
	 * Provides the opportunity to override or NOT the condition
	 *
	 * Selectors should include a call to this in their matches function
	 *
	 * @param bool $matches
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
		$openingQuote = $this->quote; 
		$closingQuote = $openingQuote; 
		if($openingQuote) {
			if($openingQuote == '[') $closingQuote = ']'; 	
				else if($openingQuote == '{') $closingQuote = '}';
				else if($openingQuote == '(') $closingQuote = ')';
		}
		$str = 	($this->not ? '!' : '') . 
			(is_null($this->group) ? '' : $this->group . '@') . 
			(is_array($this->field) ? implode('|', $this->field) : $this->field) . 
			$this->getOperator() . 
			(is_array($this->value) ? implode("|", $this->value) : $openingQuote . $this->value . $closingQuote);
		return $str; 
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
		Selectors::addType(SelectorStartsLike::getOperator(), 'SelectorStartsLike'); 
		Selectors::addType(SelectorEnds::getOperator(), 'SelectorEnds'); 
		Selectors::addType(SelectorEndsLike::getOperator(), 'SelectorEndsLike'); 
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
		foreach($words as $key => $word) if(!preg_match('/\b' . preg_quote($word) . '\b/i', $value1)) {
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
 * Selector that matches if the value exists at the beginning of another value (specific to SQL LIKE)
 *
 */
class SelectorStartsLike extends SelectorStarts {
	public static function getOperator() { return '%^='; }
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
 * Selector that matches if the value exists at the end of another value (specific to SQL LIKE)
 *
 */
class SelectorEndsLike extends SelectorEnds {
	public static function getOperator() { return '%$='; }
}

/**
 * Selector that matches a bitwise AND '&'
 *
 */
class SelectorBitwiseAnd extends Selector { 
	public static function getOperator() { return '&'; }
	protected function match($value1, $value2) { return $this->evaluate(((int) $value1) & ((int) $value2)); }
}

