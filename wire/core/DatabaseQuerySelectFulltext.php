<?php

/**
 * ProcessWire DatabaseQuerySelectFulltext
 *
 * A wrapper for SELECT SQL queries using FULLTEXT indexes
 * 
 * Decorates a DatabaseQuerySelect object by providing the WHERE and 
 * ORDER parts for a fulltext query based on the table, field, operator 
 * and value you are searching. 
 *
 * Assumes that you are providing at least the SELECT and FROM portions 
 * of the query. 
 *
 * The intention behind these classes is to have a query that can safely
 * be passed between methods and objects that add to it without knowledge
 * of what other methods/objects have done to it. It also means being able
 * to build a complex query without worrying about correct syntax placement.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
class DatabaseQuerySelectFulltext extends Wire {

	const maxQueryValueLength = 50; 

	protected $query; 

	static $scoreFields = array();

	public function __construct(DatabaseQuerySelect $query) {
		$this->query = $query; 
	}

	/**
	 * Escape string for use in a MySQL LIKE
 	 *
	 */
	protected function escapeLIKE($str) {
		return preg_replace('/([%_])/', '\\\$1', $str); 
	}

	public function match($tableName, $fieldName, $operator, $value) {

		$query = $this->query; 
		$value = substr(trim($value), 0, self::maxQueryValueLength); 
		$tableName = $this->db->escapeTable($tableName); 
		$fieldName = $this->db->escapeCol($fieldName); 
		$tableField = "$tableName.$fieldName";

		switch($operator) {

			case '=':
			case '!=': 
				$v = $this->db->escape_string($value); 
				$query->where("$tableField$operator'$v'"); 
				break;	

			case '*=':
				$this->matchContains($tableName, $fieldName, $operator, $value); 
				break;

			case '~=':
				$words = preg_split('/[-\s,]/', $value, -1, PREG_SPLIT_NO_EMPTY); 
				$n = 0; 
				foreach($words as $word) {
					if(DatabaseStopwords::has($word)) continue; 			
					$n++; 
					$this->matchContains($tableName, $fieldName, $operator, $word); 
				}
				if(!$n) $query->where("1>2"); // force it not to match if all words were stopwords
				break;

			case '%=':
				$v = $this->db->escape_string($value); 
				$v = $this->escapeLIKE($v); 
				$query->where("$tableField LIKE '%$v%'"); // SLOW, but assumed
				break;

			case '^=':
			case '%^=': // match at start using only LIKE (no index)
				$v = $this->db->escape_string($value);
				$v = $this->escapeLIKE($v); 
				$query->where("$tableField LIKE '$v%'"); 
				break;

			case '$=':
			case '%$=': // RCD match at end using only LIKE (no index)
				$v = $this->db->escape_string($value);
				$v = $this->escapeLIKE($v); 
				$query->where("$tableField LIKE '%$v'"); 
				break;

			default:
				throw new WireException("Unimplemented operator in " . get_class($this) . "::match()"); 
		}

		return $this; 
	}

	protected function matchContains($tableName, $fieldName, $operator, $value) {

		$query = $this->query; 
		$tableField = "$tableName.$fieldName";
		$v = $this->db->escape_string($value); 

		$n = 0; 
		do {
			$scoreField = "_score_{$tableName}_{$fieldName}" . (++$n);
		} while(in_array($scoreField, self::$scoreFields)); 
		self::$scoreFields[] = $scoreField;

		$query->select("MATCH($tableField) AGAINST('$v') AS $scoreField"); 
		$query->orderby($scoreField . " DESC");

		$partial = $operator != '~=';
		$booleanValue = $this->db->escape_string($this->getBooleanQueryValue($value, true, $partial));
		if($booleanValue) $j = "MATCH($tableField) AGAINST('$booleanValue' IN BOOLEAN MODE) "; 
			else $j = '';
			
		if($operator == '^=' || $operator == '$=' || ($operator == '*=' && (!$j || preg_match('/[-\s]/', $v)))) { 
			// if $operator is a ^begin/$end, or if there are any word separators in a *= operator value

			if($operator == '^=' || $operator == '$=') {
				$type = 'RLIKE';
				$v = $this->db->escape_string(preg_quote($value)); // note $value not $v
				$like = "[[:space:]]*(<[^>]+>)*[[:space:]]*"; 
				if($operator == '^=') {
					$like = "^" . $like . $v; 
				} else {
					$like = $v . '[[:space:]]*[[:punct:]]*' . $like . '$';

				}

			} else {
				$type = 'LIKE';
				$v = $this->escapeLIKE($v); 
				$like = "%$v%";
			}

			$j = trim($j); 
			$j .= (($j ? "AND " : '') . "($tableField $type '$like')"); // note the LIKE is used as a secondary qualifier, so it's not a bottleneck
		}

		$query->where($j); 
	}

	public function getQuery() {
		return $this->query; 
	}

	/**
	 * Generate a boolean query value for use in an SQL MATCH/AGAINST statement. 
	 *
	 * @param string $value
	 * @param bool $required Is the given value required in the query?
	 * @param bool $partial Is it okay to match a partial value? i.e. can "will" match "willy"
	 * @return string Value provided to the function with boolean operators added. 
	 *
	 */
	protected function getBooleanQueryValue($value, $required = true, $partial = true) {
		$newValue = '';
		//$a = preg_split('/[-\s,+*!.?()=;]+/', $value); 
		$a = preg_split('/[-\s,+*!?()=;]+/', $value); 
		foreach($a as $k => $v) {
			if(DatabaseStopwords::has($v)) {
				continue; 
			}
			if($required) $newValue .= "+$v"; else $newValue .= "$v";
			if($partial) $newValue .= "*";
			$newValue .= " ";
		}
		return trim($newValue); 
	}
}
