<?php

/**
 * ProcessWire Fieldtype Base
 *
 * Abstract base class from which all Fieldtype modules are descended from.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
abstract class Fieldtype extends WireData implements Module {

	/**
	 * Get information about this module
	 *
	 */
	public static function getModuleInfo() {
		return array(
			'title' => '', 
			'version' => 001, 
			'summary' => '', 
			); 
	}

	/**
	 * Per Module interface, this template method is called when all system classes are loaded and ready for API usage
	 *
	 */
	public function init() { }


	/**
	 * Fieldtype modules are singular, in that only one instance is needed per request
	 *
	 */
	public function isSingular() {
		return true; 
	}

	/**
	 * Fieldtype modules are not automatically loaded, they are only loaded when requested
	 *
	 */
	public function isAutoload() {
		return false; 
	}

	/**
	 * Should this Fieldtype only be allowed for new fields in advanced mode?
	 *
	 */
	public function isAdvanced() {
		return false; 	
	}

	/**
	 * Return new instance of the Inputfield associated with this Fieldtype
	 *
	 * Abstract Template Method: child classes should override this. Code snippet is for example purposes only. 
	 *
	 * Page and Field are provided as params in case the Fieldtype needs them for any custom population with the Inputfield.
	 * However, most Fieldtypes won't need them since Inputfield objects don't have any Page dependencies (!)
	 * The Field class handles setting all standard Inputfield attributes rather than this method to reduce code duplication in Inputfield modules. 
	 *
	 * (!) See FieldtypeFile for an example that uses both Page and Field params. 
	 *
	 * @param Page $page 
	 * @param Field $field
	 * @return Inputfield
	 *
	 */
	public function getInputfield(Page $page, Field $field) {
		// TODO make this abstract
		$inputfield = new Inputfield();
		$inputfield->class = $this->className();
		return $inputfield; 
	}

	/**
	 * Get any inputfields used for configuration of this Fieldtype.
	 *
	 * This is in addition any configuration fields supplied by the Inputfield.
	 *
	 * Classes implementing this method should call upon this parent method to obtain the InputfieldWrapper, and then 
	 * append their own Inputfields to that. 
	 *
	 * @param Field $field
	 * @return InputfieldWrapper
	 *
	 */
	public function ___getConfigInputfields(Field $field) {
		$inputfields = new InputfieldWrapper();	
		/* 
		// EXAMPLE
		$f = $this->modules->get("InputfieldCheckbox"); 
		$f->attr('name', 'rydoggytest'); 
		$f->attr('value', 'Value'); 
		$f->attr('checked', $field->rydoggytest == 'Value' ? 'checked' : ''); 
		$f->label = 'Well hi there'; 
		$inputfields->append($f); 
		*/
		return $inputfields; 
	}

	/**
	 * Get inputfields for advanced settings of the Field and Fieldtype
	 *
	 * In most cases, you will want to override getConfigInputfields rather than this method
	 *
	 * @TODO should this be moved back into modules/Process/ProcessField.module? (that's where it is saved)
	 *
	 * @param Field $field
	 * @return InputfieldWrapper
	 *
	 */
	public function ___getConfigAdvancedInputfields(Field $field) {
		// advanced settings
		$inputfields = new InputfieldWrapper();	

		if($this->getLoadQueryAutojoin($field, new DatabaseQuerySelect())) {
			$f = $this->modules->get('InputfieldCheckbox');
			$f->label = $this->_('Autojoin');
			$f->attr('name', 'autojoin');
			$f->attr('value', 1);
			$f->attr('checked', ($field->flags & Field::flagAutojoin) ? 'checked' : '');
			$f->description = $this->_("If checked, the data for this field will be loaded with every instance of the page, regardless of whether it's used at the time. If unchecked, the data will be loaded on-demand, and only when the field is specifically accessed."); // Autojoin description
			$inputfields->append($f);
		}

		$f = $this->modules->get('InputfieldCheckbox');
		$f->attr('name', 'global');
		$f->label = $this->_('Global');
		$f->description = $this->_("If checked, ALL pages will be required to have this field.  It will be automatically added to any fieldgroups/templates that don't already have it. This does not mean that a value is required in the field, only that the editable field will exist in all pages."); // Global description
		$f->attr('value', 1);
		if($field->flags & Field::flagGlobal) $f->attr('checked', 'checked');
			else $f->collapsed = true; 
		$inputfields->append($f);

		if($this->config->advanced) {
			$f = $this->modules->get('InputfieldCheckbox');
			$f->attr('name', 'system');
			$f->label = 'System';
			$f->description = "If checked, this field is considered a system field and is not renameable or deleteable. System fields may not be undone using ProcessWire's API.";
			$f->attr('value', 1);
			if($field->flags & Field::flagSystem) $f->attr('checked', 'checked');
				else $f->collapsed = true; 
			$inputfields->append($f);

			$f = $this->modules->get('InputfieldCheckbox');
			$f->attr('name', 'permanent');
			$f->label = 'Permanent';
			$f->description = "If checked, this field is considered a permanent field and it can't be removed from any of the system templates/fieldgroups to which it is attached. This flag may not be undone using ProcessWire's API.";
			$f->attr('value', 1);
			if($field->flags & Field::flagPermanent) $f->attr('checked', 'checked');
				else $f->collapsed = true; 
			$inputfields->append($f);
		}

                return $inputfields;
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
			if(!$fieldtype instanceof FieldtypeMulti) $fieldtypes->add($fieldtype); 
		}
		return $fieldtypes; 
	}

	/**
	 * Sanitize the value for runtime storage. 
	 *
	 * This method should remove anything that's invalid from the given value. If it can't be sanitized, it should be blanked. 
	 * This method filters every value set to a Page instance. 
	 *
	 * @param Page $page
	 * @param Field $field
	 * @param string|int|WireArray|object $value
	 * @return string|int|WireArray|object 
	 *
	 */
	abstract public function sanitizeValue(Page $page, Field $field, $value);


	/**
	 * Format the given value for output and return a string of the formatted value
	 *
	 * Page instances call upon this method to do any necessary formatting of a value in preparation for output,
	 * but only if $page->outputFormatting is true. The most common use of this method is for text-only fields that
	 * need to have some text formatting applied to them, like Markdown, SmartyPants, Textile, etc. As a result, 
	 * Fieldtype modules don't need to implement this unless it's applicable. 
	 *
	 * Fieldtype modules that implement this do not need to call this parent method. 
	 *
	 * @param Page $page
	 * @param Field $field
	 * @param string|int|WireArray|object $value
	 * @return string
	 *
	 */
	public function ___formatValue(Page $page, Field $field, $value) {
		return $value; 
	}

	/**
	 * Return the blank value for this fieldtype, whether that is a blank string, zero value, blank object or array
	 *
	 * @param Page $page
	 * @param Field $field
	 * @param string|int|object $value
	 * @return string|int|object
	 *
 	 */
	public function getBlankValue(Page $page, Field $field) {
		return '';
	}

	/**
	 * Given a raw value (value as stored in DB), return the value as it would appear in a Page object
	 *
	 * In many cases, no change to the value may be necessary, but if a Page expects this value as an object (for instance) then 
	 * this would be the method that converts that value to an object and returns it. 
	 * This method is called by the Page class, which takes the value provided by loadPageField and sends it to wakeValue 
	 * before making it a part of the Page. 
	 *
	 * @param Page $page
	 * @param Field $field
	 * @param string|int|array $value
	 * @return string|int|array|object $value
	 *
	 */
	public function ___wakeupValue(Page $page, Field $field, $value) {
		return $value; 	
	}

	/**
	 * Given an 'awake' value, as set by wakeupValue, convert the value back to a basic type for storage in DB. 
	 *
	 * In many cases, this may mean no change to the value, which is why the default here just returns the value. 
	 * But for values that are stored with pages as objects (for instance) this method would take that object
	 * and convert it to an array, int or string (serialized or otherwise). 
	 *
	 * @param Page $page
	 * @param Field $field
	 * @param string|int|array|object $value
	 * @return string|int
	 *
	 */
	public function ___sleepValue(Page $page, Field $field, $value) {
		return $value; 
	}

	/**
	 * Return the default value for this fieldtype (may be the same as blank value)
	 *
	 * Under no circumstances should this return NULL, because that is used by Page to determine if a field has been loaded. 
	 *
	 * @param Field $field 
	 * @return mixed 
	 *
 	 */
	public function getDefaultValue(Page $page, Field $field) {
		/* FUTURE
		$value = $field->getDefaultValue(); 
		if(!is_null($value)) return $value; 
		*/
		return $this->getBlankValue($page, $field);
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

		$db = $this->fuel('db');
		$table = $db->escapeTable($table); 
		$subfield = $db->escapeCol($subfield);
		$value = $db->escape_string($value); 

		if(!$db->isOperator($operator)) 
			throw new WireException("Operator '{$operator}' is not implemented in {$this->className}"); 

		$query->where("{$table}.{$subfield}{$operator}'$value'"); // QA
		return $query; 
	}

	/**
	 * Create a new field table in the database.
	 *
	 * This method should execute the SQL query necessary to create $field->table
	 * It should throw an Exception if failure occurs
	 *
 	 * @param Field $field
	 *
	 */
	public function ___createField(Field $field) {

		$db = $this->fuel('db');
		$schema = $this->getDatabaseSchema($field); 

		if(!isset($schema['pages_id'])) throw new WireException("Field '$field' database schema must have a 'pages_id' field."); 
		if(!isset($schema['data'])) throw new WireException("Field '$field' database schema must have a 'data' field."); 

		$table = $db->escapeTable($field->table); 
		$sql = 	"CREATE TABLE `$table` (";

		foreach($schema as $f => $v) {
			if($f == 'keys' || $f == 'xtra') continue; 
			$sql .= "`$f` $v, "; 
		}

		foreach($schema['keys'] as $v) {
			$sql .= "$v, ";
		}

		$sql = rtrim($sql, ", ") . ') ' . (isset($schema['xtra']) ? $schema['xtra'] : ''); 
		$result = $db->query($sql); // QA

		if(!$result) $this->error("Error creating table '{$table}'");

		return $result; 
	}

	/**
	 * Get the database schema for this field
	 *
	 * Should return an array like the following, indexed by field name with type details as the value (as it would be in an SQL statement)
	 * Indexes are passed through with a 'keys' array. Note that 'pages_id' as a field and primary key may be retrieved by starting with the
	 * parent schema return from this root getDatabaseSchema method. 
	 * 
	 * array(
	 * 	'data' => 'mediumtext NOT NULL', 
	 * 	'keys' => array(
	 * 		'FULLTEXT KEY data (data)', 
	 * 	),
	 *	'xtra' => 'ENGINE=MyISAM DEFAULT CHARSET=latin1' // optional extras, MySQL defaults will be used if ommitted
	 *	)
	 * );
	 *
	 * At minimum, each Fieldtype must add a 'data' field as well as an index for it. 
	 *
	 * @param Field $field In case it's needed for the schema, but usually should not. 
	 * @return array
	 *
	 */
	public function getDatabaseSchema(Field $field) {
		$schema = array(
			'pages_id' => 'int UNSIGNED NOT NULL', 
			'data' => "int NOT NULL", // each Fieldtype should override this in particular
			'keys' => array(
				'primary' => 'PRIMARY KEY (`pages_id`)', 
				'data' => 'KEY data (`data`)',
			),
			// any optional statements that should follow after the closing paren (i.e. engine, default charset, etc)
			'xtra' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8', 
		); 
		return $schema; 
	}

	/**
	 * Return trimmed database schema array of any parts that aren't needed for data loading
	 *
	 */
	public function trimDatabaseSchema(array $schema) {
		unset($schema['pages_id'], $schema['keys'], $schema['xtra'], $schema['sort']); 
		return $schema; 
	}

	/**
	 * Load the given page field from the database table and return the value. 
	 *
	 * Return NULL if the value is not available. 
	 * Return the value as it exists in the database, without further processing. 
	 *
	 * This is intended only to be called by Page objects on an as-needed basis. 
	 * Typically this is only called for fields that don't have 'autojoin' turned on.
	 *
	 * @param Page $page Page object to save. 
	 * @param Field $field Field to retrieve from the page. 
	 * @return $value|null
	 *
	 */
	public function ___loadPageField(Page $page, Field $field) {

		if(!$page->id || !$field->id) return null;

		$db = $this->fuel('db');
		$isMulti = $field->type instanceof FieldtypeMulti;
		$page_id = (int) $page->id; 
		$table = $db->escapeTable($field->table); 

		$query = new DatabaseQuerySelect();
		$query = $this->getLoadQuery($field, $query); 
		$query->where("$table.pages_id='$page_id'"); 
		$query->from($table); 
		if($isMulti) $query->orderby('sort'); 

		$value = null;
		$result = $query->execute(); // QA
		$fieldName = $db->escapeCol($field->name); 
		$schema = $this->trimDatabaseSchema($this->getDatabaseSchema($field));

		if(!$result) return $value;

		$values = array();
		while($row = $result->fetch_assoc()) {

			$value = array();
			foreach($schema as $k => $unused) {
				$key = $fieldName . '__' . $k; 
				$value[$k] = $row[$key]; 
			}

			// if there is just one 'data' field here, then don't bother with the array, just make data the value
			if(count($value) == 1 && isset($value['data'])) $value = $value['data']; 
			if(!$isMulti) break;
			$values[] = $value;
		}

		$result->free();

		if($isMulti && count($values)) $value = $values; 

		return $value; 
	}

	/**
	 * Return the query used for loading all parts of the data from this field
	 *
	 * @param Field $field
	 * @param DatabaseQuerySelect $query
	 * @return DatabaseQuerySelect
	 *
	 */ 
	public function getLoadQuery(Field $field, DatabaseQuerySelect $query) {

		$table = $this->fuel('db')->escapeTable($field->table);
		$schema = $this->trimDatabaseSchema($this->getDatabaseSchema($field)); 
		$fieldName = $this->fuel('db')->escapeCol($field->name);

		// now load any extra components (if applicable) in a fieldName__SubfieldName format.
		foreach($schema as $k => $v) {
			$query->select("$table.$k AS `{$fieldName}__$k`"); // QA
		}

		return $query; 
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
		return $this->getLoadQuery($field, $query); 
	}

	/**
	 * Save the given field from page 
	 *
	 * Possible template method: If overridden, children should NOT call this parent method.
	 *
	 * @param Page $page Page object to save. 
	 * @param Field $field Field to retrieve from the page. 
	 * @return bool True on success, false on DB save failure.
	 *
	 */
	public function ___savePageField(Page $page, Field $field) {

		if(!$page->id) throw new WireException("Unable to save to '{$field->table}' for page that doesn't exist in pages table"); 
		if(!$field->id) throw new WireException("Unable to save to '{$field->table}' for field that doesn't exist in fields table"); 

		// if this field hasn't changed since it was loaded, don't bother executing the save
		if(!$page->isChanged($field->name)) return true; 

		$db = $this->fuel('db');
		$value = $page->get($field->name);

		// if the value is the same as the default, then remove the field from the database because it's redundant
		if($value === $this->getDefaultValue($page, $field)) return $this->deletePageField($page, $field); 

		$value = $this->sleepValue($page, $field, $value); 

		$page_id = (int) $page->id; 
		$table = $db->escapeTable($field->table); 

		if(is_array($value)) { 

			$sql1 = "INSERT INTO `$table` (pages_id";
			$sql2 = "VALUES('$page_id'";
			$sql3 = "ON DUPLICATE KEY UPDATE ";

			foreach($value as $k => $v) {
				$k = $db->escapeCol($k);
				$v = $db->escape_string($v);
				$sql1 .= ",$k";
				$sql2 .= ",'$v'";
				$sql3 .= "$k=VALUES($k), ";
			}

			$sql = "$sql1) $sql2) " . rtrim($sql3, ', ');
			
		} else { 
			$value = $db->escape_string($value); 

			$sql = 	"INSERT INTO `$table` (pages_id, data) " . 
				"VALUES('$page_id', '$value') " . 
				"ON DUPLICATE KEY UPDATE data=VALUES(data)";	

		}

		$result = $db->query($sql); // QA

		return $result; 
	}


	/**
	 * Delete the given field, which implies: drop the table $field->table
	 *
	 * This should only be called by the Fields class since fieldgroups_fields lookup entries must be deleted before this method is called. 
	 *
	 * @param Field $field Field object
	 * @return bool True on success, false on DB delete failure.
	 *
	 */
	public function ___deleteField(Field $field) {
		try {
			$db = $this->fuel('db');
			$table = $db->escapeTable($field->table); 
			$result = $db->query("DROP TABLE `$table`"); // QA
		} catch(Exception $e) {
			$result = false; 
			$this->error($e->getMessage()); 
		}
		return $result;
	}

	/**
	 * Delete the given Field from the given Page
	 *
	 * Should delete entries from $field->table that belong to $page->id.
	 * Possible template method.
	 *
	 * @param Page $page 
	 * @param Field $field Field object
	 * @return bool True on success, false on DB delete failure.
	 *
	 */
	public function ___deletePageField(Page $page, Field $field) {

		if(!$field->id) throw new WireException("Unable to delete from '{$field->table}' for field that doesn't exist in fields table"); 

		// no need to delete on a new Page because it's not in the table yet
		if($page->isNew()) return true; 

		// clear the value from the page
		// $page->set($field->name, $this->getBlankValue($page, $field)); 
		unset($page->{$field->name}); 

		// Delete all instances of it from the field table
		$db = $this->fuel('db');
		$table = $db->escapeTable($field->table);
		$page_id = (int) $page->id; 
		$sql = "DELETE FROM `$table` WHERE pages_id=$page_id"; 
		return $db->query($sql); // QA

	}

	/**
	 * Return a cloned copy of $field
	 *
	 * @param Field $field
	 * @return Field cloned copy
	 *
	 */
	public function ___cloneField(Field $field) {
		return clone $field;
	}

	/**
	 * Get a property from this Fieldtype's data
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {
		if($key == 'name') return $this->className();
		if($key == 'shortName') return str_replace('Fieldtype', '', $this->className()); 
		return parent::get($key); 
	}

	/**
	 * Install this Fieldtype, consistent with optional Module interface
	 *
	 * Called once at time of installation by Modules::install().
	 * If custom Fieldtype classes need to perform any setup beyond that performed in ___createTable(),
	 * this method is where they should do it. This is not required, and probably not applicable to most. 
	 *
	 */
	public function ___install() {
		return true; 
	}

	/**
	 * Uninstall this Fieldtype, consistent with optional Module interface
	 *
	 * Checks to make sure it's safe to uninstall this Fieldtype. If not, throw an Exception indicating such. 
	 * It's safe to uninstall a Fieldtype only if it's not being used by any Fields. 
	 * If a Fieldtype overrides this to perform additional uninstall functions, it would be good to call this 
	 * first to make sure uninstall is okay. 
	 *
	 */
	public function ___uninstall() {

		$names = array();
		$fields = $this->getFuel('fields'); 

		foreach($fields as $field) {
			if($field->type === $this->name) $names[] = $field->name; 
		}

		if(count($names)) throw new WireException("Unable to uninstall Fieldtype '{$this->name}' because it is used by Fields: " . implode(", ", $names)); 

		return true; 
	}


	/**
	 * The string value of Fieldtype is always the Fieldtype's name. 
	 *
	 */
	public function __toString() {
		return $this->className();
	}

	
}

