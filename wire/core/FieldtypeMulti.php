<?php

/**
 * ProcessWire FieldtypeMulti
 *
 * Interface and some functionality for Fieldtypes that can contain multiple values.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
abstract class FieldtypeMulti extends Fieldtype {

	/**
	 * Separator for multi values when using GROUP_CONCAT()
 	 *
	 * TODO sanitize set() values from ever containing this separator
	 *
	 */
	const multiValueSeparator = "\0,";

	/**
	 * For internal use to count the number of calls to getMatchQuery
	 *
	 * Used for creating unique table names to the same field in the same query
	 *
	 */
	protected static $getMatchQueryCount = 0;

	/**
	 * Modify the default schema provided by Fieldtype to include a 'sort' field, and integrate that into the primary key.
	 *
	 */
	public function getDatabaseSchema(Field $field) {
		$schema = parent::getDatabaseSchema($field); 
		$schema['sort'] = 'int unsigned NOT NULL'; 
		$schema['keys']['primary'] = 'PRIMARY KEY (pages_id, sort)'; 
		return $schema; 
	}

	/**
	 * Get an array of Fieldtypes that are compatible with this one (i.e. ones the user may change the type to)
	 *
	 * @param Field $field Just in case it's needed
	 * @return Fieldtypes|null
	 *
	 */
	public function ___getCompatibleFieldtypes(Field $field) {
		$fieldtypes = new Fieldtypes();
		foreach($this->fuel('fieldtypes') as $fieldtype) {
			if($fieldtype instanceof FieldtypeMulti) $fieldtypes->add($fieldtype); 
		}
		return $fieldtypes; 
	}

	/**
	 * Per Fieldtype interface, return a blank value of this Fieldtype
	 *
	 */
	public function getBlankValue(Page $page, Field $field) {
		return new WireArray();
	}

	/**
	 * Per the Fieldtype interface, sanitize the combined value for use in a Page
	 *
	 * In this case, make sure that it's a WireArray (able to hold multiple values)
	 *
	 */
	public function sanitizeValue(Page $page, Field $field, $value) {
		return $value instanceof WireArray ? $value : new WireArray();
	}

	/**
	 * Process the value to convert it from array to whatever object it needs to be
	 *
	 */ 
	public function ___wakeupValue(Page $page, Field $field, $value) {
		$target = $this->getBlankValue($page, $field);
		if(!is_array($value)) $value = array($value); 
		foreach($value as $val) {
			$target->add($val); 
		}
		$target->resetTrackChanges(true);
		return $target; 
	}

        /**
         * Given an 'awake' value, as set by wakeupValue, convert the value back to a basic type for storage in DB. 
	 *
	 * FieldtypeMulti::savePageField expects values as an array, so we convert the $value object to an array
	 *
	 * Note that FieldtypeMulti is designed around potentially supporting more than just the 'data' field in 
	 * the table, so other fieldtypes may want to override this and return an array of associative arrays containing a 'data' field
	 * and any other fields that map to the table. i.e. $values[] = array('data' => $data, 'description' => $description), etc. 
	 * See FieldtypePagefiles module class for an example of this. 
         *              
         * @param Page $page
         * @param Field $field
         * @param string|int|array|object $value
         * @return string|int
         *
         */
	public function ___sleepValue(Page $page, Field $field, $value) {
		$values = array();
		if(!$value instanceof WireArray) return $values; 
		foreach($value as $v) {
			// note $v is typecast as string, which calls __toString if it's an object
			$values[] = "$v";
		}
		return $values; 
	}

	/**
	 * Per the Fieldtype interface, load the Page Field value from the database.
	 *
	 * Because Multi fields are not subject to 'autojoin', this will always be called, unlike 'autojoin' fields
	 * which may be automatically loaded in the same query as the Page
	 *
	 * @param Page $page
	 * @param Field $field
	 * @return WireArray 
	 *
	public function ___loadPageField(Page $page, Field $field) {

		if(!$page->id) return null;
		if(!$field->id) throw new WireException("Unable to load '{$field->table}' for field that doesn't exist in fields table"); 

		$values = array();

		// we use '*' rather than 'data' because it's possible that some FieldtypeMulti's will be joining fields beyond just 'data'
		// See the handling of this in the while loop below, as well as in the savePageField method
		$sql = "SELECT * FROM `{$field->table}` WHERE pages_id='{$page->id}' ORDER BY sort";

		$result = $this->db->query($sql);

		while($row = $result->fetch_assoc()) {

			// unset the fields that are only applicable to the DB
			unset($row['sort'], $row['pages_id']); 

			// if the only field left is 'data', then we extract that to be the value.
			// but if there are multiple values remaining, then we include them all,
			// which will ultimately be converted to the relevant object type by wakeupValue()
			if(count($row) == 1) $values[] = $row['data']; 
				else $values[] = $row; 
		}

		return $values; 
	}
	 */

	/**
	 * Per the Fieldtype interface, Save the given Field from the given Page to the database
	 *
	 * Because the number of values may have changed, this method plays it safe and deletes all the old values
	 * and reinserts them as new. 
	 *
	 * @param Page $page
	 * @param Field $field
	 * @return bool
	 *
	 */
	public function ___savePageField(Page $page, Field $field) {

		if(!$page->id || !$field->id) return false;

		$values = $page->get($field->name);

		if(is_object($values)) {
			 if(!$values->isChanged() && !$page->isChanged($field->name)) return true; 
		} else if(!$page->isChanged($field->name)) return true; 

		$values = $this->sleepValue($page, $field, $values); 
		$table = $this->db->escapeTable($field->table); 
		$page_id = (int) $page->id; 

		// since we don't manage IDs of existing values for multi fields, we delete the existing data and insert all of it again
		$this->db->query("DELETE FROM `$table` WHERE pages_id=$page_id"); // QA

		if(count($values)) {

			// get first value to find key definition
			$value = reset($values); 

			// if the first value is not an associative (key indexed) array, then force it to be with 'data' as the key.
			// this is to allow for this method to be able to save fields that have more than just a 'data' field,
			// even though most instances will probably just use only the data field

			if(is_array($value)) {
				$keys = array_keys($value); 
				foreach($keys as $k => $v) $keys[$k] = $this->db->escapeTableCol($v); 
			} else {
				$keys = array('data'); 
			}

			$sql = "INSERT INTO `$table` (pages_id, sort, " . implode(', ', $keys) . ") VALUES";
			$sort = 0; 	

			// cycle through the values to generate the query
			foreach($values as $value) {
				$sql .= "($page_id, $sort, ";

				// if the value is not an associative array, then force it to be one
				if(!is_array($value)) $value = array('data' => $value); 

				// cycle through the keys, which represent DB fields (i.e. data, description, etc.) and generate the insert query
				foreach($keys as $key) {
					$v = $value[$key]; 
					$sql .= "'" . $this->db->escape_string("$v") . "', ";
				}
				$sql = rtrim($sql, ", ") . "), ";
				$sort++; 	
			}	

			$sql = rtrim($sql, ", "); 
			$result = $this->db->query($sql); // QA
			return $result; 
		}

		return true; 
	}

	/**
	 * Return the query used for Autojoining this field (if different from getLoadQuery) or NULL if autojoin not allowed. 
	 *
	 * @param Field $field
	 * @param DatabaseQuerySelect $query
	 * @return DatabaseQuerySelect|NULL
	 *
	 */
	public function getLoadQueryAutojoin(Field $field, DatabaseQuerySelect $query) {
		$table = $this->db->escapeTable($field->table);	
		$schema = $this->trimDatabaseSchema($this->getDatabaseSchema($field)); 
		$fieldName = $this->db->escapeCol($field->name); 
		$separator = self::multiValueSeparator; 
		foreach($schema as $key => $unused) {
			$query->select("GROUP_CONCAT($table.$key SEPARATOR '$separator') AS `{$fieldName}__$key`"); // QA
		}		
		return $query; 
	}


	/**
	 * Get the query that matches a Fieldtype table's data with a given value
	 *
	 * Possible template method: If overridden, children should NOT call this parent method. 
	 *
	 * @param DatabaseQuerySelect $query
	 * @param string $table The table name to use
	 * @param string $field Name of the field (typically 'data', unless selector explicitly specified another)
	 * @param string $operator The comparison operator
	 * @param mixed $value The value to find
	 * @return DatabaseQuery $query
	 *
	 */
	public function getMatchQuery($query, $table, $subfield, $operator, $value) {

		self::$getMatchQueryCount++;
		$n = self::$getMatchQueryCount;

		$field = $query->field;
		$table = $this->db->escapeTable($table);

		if($subfield === 'count' && ctype_digit(ltrim("$value", '-')) && in_array($operator, array("=", "!=", ">", "<", ">=", "<="))) {

			$value = (int) $value;
			$t = $table . "_" . $n;
			$c = $this->db->escapeTable($this->className()) . "_" . $n;

			$query->select("$t.num_$t AS num_$t");
			$query->leftjoin(
				"(" .
				"SELECT $c.pages_id, COUNT($c.pages_id) AS num_$t " .
				"FROM " . $this->db->escapeTable($field->table) . " AS $c " .
				"GROUP BY $c.pages_id " .
				") $t ON $t.pages_id=pages.id");

			if( 	(in_array($operator, array('<', '<=', '!=')) && $value) || 
				(in_array($operator, array('>', '>=')) && $value < 0) ||
				(in_array($operator, array('=', '>=')) && !$value)) {
				// allow for possible zero values	
				$query->where("(num_$t{$operator}$value OR num_$t IS NULL)"); // QA
			} else {
				// non zero values
				$query->where("num_$t{$operator}$value"); // QA
			}

			// only allow matches using templates with the requested field
			$sql = 'pages.templates_id IN(';
			foreach($field->getTemplates() as $template) {
				$sql .= ((int) $template->id) . ',';	
			}
			$sql = rtrim($sql, ',') . ')';
			$query->where($sql); // QA

		} else {
			$query = parent::getMatchQuery($query, $table, $subfield, $operator, $value);
		}

		return $query;
	}

}


