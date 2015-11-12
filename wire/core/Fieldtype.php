<?php

/**
 * ProcessWire Fieldtype Base
 *
 * Abstract base class from which all Fieldtype modules are descended from.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 * 
 * Hookable methods
 * ================
 * @method InputfieldWrapper getConfigInputfields(Field $field)
 * @method InputfieldWrapper getConfigAdvancedInputfields(Field $field)
 * @method array getConfigAllowContext(Field $field)
 * @method array exportConfigData(Field $field, array $data)
 * @method array importConfigData(Field $field, array $data)
 * @method Fieldtypes|null getCompatibleFieldtypes(Field $field)
 * @method mixed formatValue(Page $page, Field $field, $value)
 * @method string|MarkupFieldtype markupValue(Page $page, Field $field, $value = null, $property = '')
 * @method mixed wakeupValue(Page $page, Field $field, $value)
 * @method string|int|array sleepValue(Page $page, Field $field, $value)
 * @method string|float|int|array exportValue(Page $page, Field $field, $value, array $options = array())
 * @method bool createField(Field $field)
 * @method array getSelectorInfo(Field $field, array $data = array())
 * @method mixed|null loadPageField(Page $page, Field $field)
 * @method bool savePageField(Page $page, Field $field)
 * @method bool deleteField(Field $field)
 * @method bool deletePageField(Page $page, Field $field)
 * @method bool emptyPageField(Page $page, Field $field)
 * @method bool replacePageField(Page $src, Page $dst, Field $field)
 * @method bool deleteTemplateField(Template $template, Field $field)
 * @method Field cloneField(Field $field)
 * @method void install()
 * @method void uninstall()
 * 
 * @property bool $_exportMode True when Fieldtype is exporting config data, false otherwise. 
 * 
 */
abstract class Fieldtype extends WireData implements Module {

	/**
	 * Get information about this module
	 *
	public static function getModuleInfo() {
		return array(
			'title' => '', 
			'version' => 1, 
			'summary' => '', 
			); 
	}
	 */

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
		$inputfield = wire('modules')->get('InputfieldText'); 
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
	 * NOTE: Inputfields with a name that starts with an underscore, i.e. "_myname" are assumed to be for runtime 
	 * use and are NOT stored in the database. 
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
		
		// names of fields in the form that are allowed in fieldgroup/template context
		return $inputfields; 
	}

	/**
	 * Same as getConfigInputfields but with definition as an array instead
	 * 
	 * If both getConfigInputfields and getConfigInputfieldsArray are implemented then 
	 * definitions from both will be used. 
	 * 
	 * @param Field $field
	 * @return array
	 * 
	 */
	public function ___getConfigArray(Field $field) {
		return array();
	}

	/**
	 * Return a list of Inputfield names from getConfig[Inputfields|Array] that are allowed in fieldgroup/template context
	 *
	 * @param Field $field
	 * @return array of Inputfield names
	 *
	 */
	public function ___getConfigAllowContext(Field $field) {
		return array(); 
	}

	/**
	 * Get inputfields for advanced settings of the Field and Fieldtype
	 *
	 * In most cases, you will want to override getConfigInputfields rather than this method
	 * 
	 * NOTE: Inputfields with a name that starts with an underscore, i.e. "_myname" are assumed to be for runtime
	 * use and are NOT stored in the database.
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
			$f->icon = 'sign-in';
			$f->attr('name', 'autojoin');
			$f->attr('value', 1);
			$f->attr('checked', ($field->flags & Field::flagAutojoin) ? 'checked' : '');
			$f->description = $this->_("If checked, the data for this field will be loaded with every instance of the page, regardless of whether it's used at the time. If unchecked, the data will be loaded on-demand, and only when the field is specifically accessed."); // Autojoin description
			$inputfields->append($f);
		}

		$f = $this->modules->get('InputfieldCheckbox');
		$f->attr('name', 'global');
		$f->label = $this->_('Global');
		$f->icon = 'globe';
		$f->description = $this->_("If checked, ALL pages will be required to have this field.  It will be automatically added to any fieldgroups/templates that don't already have it. This does not mean that a value is required in the field, only that the editable field will exist in all pages."); // Global description
		$f->attr('value', 1);
		if($field->flags & Field::flagGlobal) {
			$f->attr('checked', 'checked');
		} else {
			$f->collapsed = true;
		}
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
	 * Export configuration values for external consumption
	 *
	 * Use this method to externalize any config values when necessary.
	 * For example, internal IDs should be converted to GUIDs where possible.
	 * 
	 * @param Field $field
	 * @param array $data
	 * @return array
	 *
	 */
	public function ___exportConfigData(Field $field, array $data) {
		
		// set an exportMode variable in case anything needs to know about this
		$this->set('_exportMode', true);
		
		// make sure all potential values are accounted for in the export data
		$sets = array(
			$this->getConfigInputfields($field), 
			$this->getConfigAdvancedInputfields($field)
		);
		foreach($sets as $inputfields) {
			if(!$inputfields || !count($inputfields)) continue; 
			foreach($inputfields->getAll() as $inputfield) {
				$value = $inputfield->isEmpty() ? '' : $inputfield->value; 
				if(is_object($value)) $value = (string) $value; 
				$data[$inputfield->name] = $value; 
			}
		}
		$inputfield = $field->getInputfield(new NullPage()); 
		if($inputfield) {
			$data = $inputfield->exportConfigData($data);
		}
		$this->set('_exportMode', false);
		return $data;
	}

	/**
	 * Convert an array of exported data to a format that will be understood internally (opposite of exportConfigData)
	 *
	 * @param Field $field
	 * @param array $data
	 * @return array Data as given and modified as needed. Also included is $data[errors], an associative array
	 *	indexed by property name containing errors that occurred during import of config data.
	 *
	 */
	public function ___importConfigData(Field $field, array $data) {
		$inputfield = $this->getInputfield(new NullPage(), $field);
		if($inputfield) $data = $inputfield->importConfigData($data);
		return $data;
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
	 * @param string|int|object $value
	 * @return mixed
	 *
	 */
	public function ___formatValue(Page $page, Field $field, $value) {
		return $value; 
	}

	/**
	 * Render a markup string of the value
	 * 
	 * Non-markup components should also be entity encoded where appropriate. 
	 * 
	 * Most Fieldtypes don't need to implement this since the default covers most scenarios. 
	 * 
	 * This is different from formatValue() in that it always returns a string (or object that can be 
	 * typecast to a string) that is output ready with markup. Further, this method may be used to render 
	 * specific properties in compound fieldtypes. The intention here is primarily for admin output purposes, 
	 * but can be used front-end where applicable. 
	 * 
	 * This is different from Inputfield::renderValue() in that the context may be outside that of an Inputfield,
	 * as Inputfields can have external CSS or JS dependencies. 
	 * 
	 * @param Page $page Page that $value comes from
	 * @param Field $field Field that $value comes from
	 * @param mixed $value Optionally specify the $page->getFormatted(value), value must be a formatted value. 
	 * 	If null or not specified (recommended), it will be retrieved automatically.
	 * @param string $property Optionally specify the property or index to render. If omitted, entire value is rendered.
	 * @return string|MarkupFieldtype Returns a string or object that can be output as a string, ready for output.
	 * 	Return a MarkupFieldtype value when suitable so that the caller has potential specify additional
	 * 	config options before typecasting it to a string. 
	 *
	 */
	public function ___markupValue(Page $page, Field $field, $value = null, $property = '') {
		$m = new MarkupFieldtype($page, $field, $value); 	
		if(strlen($property)) return $m->render($property); 
		return $m;
	}

	/**
	 * Return the blank value for this fieldtype, whether that is a blank string, zero value, blank object or array
	 *
	 * @param Page|NullPage $page
	 * @param Field $field
	 * @return string|int|object|null
	 *
 	 */
	public function getBlankValue(Page $page, Field $field) {
		return '';
	}

	/**
	 * Return whether the given value is considered empty or not
	 * 
	 * This an be anything that might be present in a selector value and thus is
	 * typically a string. However, it may be used outside of that purpose so you
	 * shouldn't count on it being a string. 
	 * 
	 * Example: an integer or text Fieldtype might not consider a "0" to be empty,
	 * whereas a Page reference would. 
	 * 
	 * @param Field $field
	 * @param mixed $value
	 * @return bool
	 * 
	 */
	public function isEmptyValue(Field $field, $value) {
		return empty($value); 
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
	 * @return string|int|array
	 *
	 */
	public function ___sleepValue(Page $page, Field $field, $value) {
		return $value; 
	}

	/**
	 * Given a value originally generated by exportValue() convert it to a live/runtime value. 
	 *
	 * This is intended for importing from PW-driven web services. 
	 *
	 * @param Page $page
	 * @param Field $field
	 * @param string|int|array $value
	 * @return string|int|array|object $value
	 *
	public function ___importValue(Page $page, Field $field, $value) {
		$value = $this->wakeupValue($page, $field, $value); 
		return $value; 
	}
	 */

	/**
	 * Given a value, return an portable version of it as either a string, int, float or array
	 *
	 * If an array is returned, it should only contain: strings, ints, floats or more arrays of those types.
	 * This is intended for web service exports. 
 	 *
	 * If not overridden, this takes on the same behavior as sleepValue(). However, if overridden, 
	 * it is intended to be more verbose than wakeupValue, where applicable. 
	 *
	 * @param Page $page
	 * @param Field $field
	 * @param string|int|array|object $value
	 * @param array $options Optional settings to shape the exported value, if needed. 
	 * @return string|float|int|array
	 *
	 */
	public function ___exportValue(Page $page, Field $field, $value, array $options = array()) {
		$value = $this->sleepValue($page, $field, $value); 
		return $value; 
	}

	/**
	 * Return the default value for this fieldtype (may be the same as blank value)
	 *
	 * Under no circumstances should this return NULL, because that is used by Page to determine if a field has been loaded. 
	 *
	 * @param Page $page
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
	 * @param string $subfield Name of the subfield (typically 'data', unless selector explicitly specified another)
	 * @param string $operator The comparison operator
	 * @param mixed $value The value to find
	 * @return DatabaseQuery $query
	 * @throws WireException
	 *
	 */
	public function getMatchQuery($query, $table, $subfield, $operator, $value) {

		$database = $this->wire('database');

		if(!$database->isOperator($operator)) 
			throw new WireException("Operator '{$operator}' is not implemented in {$this->className}"); 

		$table = $database->escapeTable($table); 
		$subfield = $database->escapeCol($subfield);
		$quoteValue = $database->quote($value); 

		$query->where("{$table}.{$subfield}{$operator}$quoteValue"); // QA
		return $query; 
	}

	/**
	 * Create a new field table in the database.
	 *
	 * This method should execute the SQL query necessary to create $field->table
	 * It should throw an Exception if failure occurs
	 *
 	 * @param Field $field
	 * @return bool
	 * @throws WireException
	 *
	 */
	public function ___createField(Field $field) {

		$database = $this->wire('database');
		$schema = $this->getDatabaseSchema($field); 

		if(!isset($schema['pages_id'])) throw new WireException("Field '$field' database schema must have a 'pages_id' field."); 
		if(!isset($schema['data'])) throw new WireException("Field '$field' database schema must have a 'data' field."); 

		$table = $database->escapeTable($field->table); 
		$sql = 	"CREATE TABLE `$table` (";

		foreach($schema as $f => $v) {
			if($f == 'keys' || $f == 'xtra') continue; 
			$sql .= "`$f` $v, "; 
		}

		foreach($schema['keys'] as $v) {
			$sql .= "$v, ";
		}
		
		$xtra = isset($schema['xtra']) ? $schema['xtra'] : array();
		if(is_string($xtra)) $xtra = array('append' => $xtra); // backwards compat: xtra used to be a string, what 'append' is now. 
		$append = isset($xtra['append']) ? $xtra['append'] : '';

		$sql = rtrim($sql, ", ") . ') ' . $append;
		
		$query = $database->prepare($sql);
		$result = $query->execute();

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
	 *	'xtra' => array(
	 *		// optional extras, MySQL defaults will be used if ommitted
	 * 		'append' => 'ENGINE=MyISAM DEFAULT CHARSET=latin1'
	 * 
	 * 		// true (default) if this schema provides all storage for this fieldtype.
	 * 		// false if other storage is involved with this fieldtype, beyond this schema (like repeaters, PageTable, etc.)
	 * 		'all' => true, 
	 *	)
	 * );
	 *
	 * At minimum, each Fieldtype must add a 'data' field as well as an index for it. 
	 * 
	 * If you want a PHP NULL value to become a NULL in the database, your column definition must specify: DEFAULT NULL
	 *
	 * @param Field $field In case it's needed for the schema, but usually should not. 
	 * @return array
	 *
	 */
	public function getDatabaseSchema(Field $field) {
		$engine = $this->wire('config')->dbEngine; 
		$charset = $this->wire('config')->dbCharset;
		$schema = array(
			'pages_id' => 'int UNSIGNED NOT NULL', 
			'data' => "int NOT NULL", // each Fieldtype should override this in particular
			'keys' => array(
				'primary' => 'PRIMARY KEY (`pages_id`)', 
				'data' => 'KEY data (`data`)',
			),
			// additional data 
			'xtra' => array(
				// any optional statements that should follow after the closing paren (i.e. engine, default charset, etc)
				'append' => "ENGINE=$engine DEFAULT CHARSET=$charset", 
				
				// true (default) if this schema provides all storage for this fieldtype.
				// false if other storage is involved with this fieldtype, beyond this schema (like repeaters, PageTable, etc.)
				'all' => true, 
			)
		); 
		return $schema; 
	}

	/**
	 * Return trimmed database schema array of any parts that aren't needed for data loading
	 * 
	 * @param array $schema
	 * @return array
	 *
	 */
	public function trimDatabaseSchema(array $schema) {
		unset($schema['pages_id'], $schema['keys'], $schema['xtra'], $schema['sort']); 
		return $schema; 
	}

	/**
	 * Return array with information about what properties and operators can be used with this field
	 * 
	 * @param Field $field
	 * @param array $data Array of extra data, when/if needed
	 * @return array See FieldSelectorInfo.php for details
	 *
	 */
	public function ___getSelectorInfo(Field $field, array $data = array()) {
		$selectorInfo = new FieldSelectorInfo(); 
		return $selectorInfo->getSelectorInfo($field); 
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
	 * @return mixed|null
	 *
	 */
	public function ___loadPageField(Page $page, Field $field) {

		if(!$page->id || !$field->id) return null;

		$database = $this->wire('database');
		$isMulti = $field->type instanceof FieldtypeMulti;
		$page_id = (int) $page->id; 
		$table = $database->escapeTable($field->table); 
		$query = new DatabaseQuerySelect();
		$query = $this->getLoadQuery($field, $query); 
		$query->where("$table.pages_id='$page_id'"); 
		$query->from($table); 
		if($isMulti) $query->orderby('sort'); 

		$value = null;
		try {
			$stmt = $query->prepare();
			$result = $this->wire('pages')->executeQuery($stmt);
		} catch(Exception $e) {
			$result = false;
			$this->trackException($e, false, true);
		}
		$fieldName = $database->escapeCol($field->name); 
		$schema = $this->trimDatabaseSchema($this->getDatabaseSchema($field));

		if(!$result) return $value;

		$values = array();
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
		$stmt->closeCursor();

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

		$database = $this->wire('database');
		$table = $database->escapeTable($field->table);
		$schema = $this->trimDatabaseSchema($this->getDatabaseSchema($field)); 
		$fieldName = $database->escapeCol($field->name);

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
	 * @throws WireException
	 *
	 */
	public function ___savePageField(Page $page, Field $field) {

		if(!$page->id) throw new WireException("Unable to save to '{$field->table}' for page that doesn't exist in pages table"); 
		if(!$field->id) throw new WireException("Unable to save to '{$field->table}' for field that doesn't exist in fields table"); 

		// if this field hasn't changed since it was loaded, don't bother executing the save
		if(!$page->isChanged($field->name)) return true; 

		$database = $this->wire('database');
		$value = $page->get($field->name);

		// if the value is the same as the default, then remove the field from the database because it's redundant
		if($value === $this->getBlankValue($page, $field)) return $this->deletePageField($page, $field); 

		$value = $this->sleepValue($page, $field, $value); 

		$page_id = (int) $page->id; 
		$table = $database->escapeTable($field->table); 
		$schema = array();

		if(is_array($value)) { 

			$sql1 = "INSERT INTO `$table` (pages_id";
			$sql2 = "VALUES('$page_id'";
			$sql3 = "ON DUPLICATE KEY UPDATE ";

			foreach($value as $k => $v) {
				$k = $database->escapeCol($k);
				$sql1 .= ",`$k`";
				
				if(is_null($v)) {
					// check if schema explicitly allows NULL
					if(empty($schema)) $schema = $this->getDatabaseSchema($field); 
					$sql2 .= isset($schema[$k]) && stripos($schema[$k], ' DEFAULT NULL') ? ",NULL" : ",''";
				} else {
					$v = $database->escapeStr($v);
					$sql2 .= ",'$v'";
				}
				
				$sql3 .= "`$k`=VALUES(`$k`), ";
			}

			$sql = "$sql1) $sql2) " . rtrim($sql3, ', ');
			
		} else {
			
			if(is_null($value)) {
				// check if schema explicitly allows NULL
				$schema = $this->getDatabaseSchema($field); 
				$value = isset($schema[$k]) && stripos($schema[$k], ' DEFAULT NULL') ? "NULL" : "''";
			} else {
				$value = "'" . $database->escapeStr($value) . "'";
			}

			$sql = 	"INSERT INTO `$table` (pages_id, data) " . 
					"VALUES('$page_id', $value) " . 
					"ON DUPLICATE KEY UPDATE data=VALUES(data)";	
		}
		
		$query = $database->prepare($sql);
		$result = $query->execute();

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
			$database = $this->wire('database');
			$table = $database->escapeTable($field->table); 
			$query = $database->prepare("DROP TABLE `$table`"); // QA
			$result = $query->execute();
		} catch(Exception $e) {
			$result = false; 
			$this->trackException($e, true, true);
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
	 * @throws WireException
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
		$database = $this->wire('database');
		$table = $database->escapeTable($field->table);
		$page_id = (int) $page->id; 
		$query = $database->prepare("DELETE FROM `$table` WHERE pages_id=:page_id"); 
		$query->bindValue(":page_id", $page_id, PDO::PARAM_INT);
		$result = $query->execute();
		return $result;

	}
	
	/**
	 * Empty out the DB table data for page field, but leave everything else in tact
	 *
	 * In most cases this may be nearly identical to deletePageField, but would be different
	 * for things like page references where we wouldn't want relational data deleted.
	 *
	 * @param Page $page
	 * @param Field $field Field object
	 * @return bool True on success, false on DB delete failure.
	 * @throws WireException
	 *
	 */
	public function ___emptyPageField(Page $page, Field $field) {
		if(!$field->id) throw new WireException("Unable to empty from '{$field->table}' for field that doesn't exist in fields table");
		$table = $this->wire('database')->escapeTable($field->table);
		$query = $this->wire('database')->prepare("DELETE FROM `$table` WHERE pages_id=:page_id");
		$query->bindValue(":page_id", $page->id, PDO::PARAM_INT);
		return $query->execute();
	}

	
	/**
	 * Move this field's data from one page to another
	 *
	 * @param Page $src Source Page
	 * @param Page $dst Destination Page
	 * @param Field $field
	 * @return bool
	 *
	 */
	public function ___replacePageField(Page $src, Page $dst, Field $field) {
		$database = $this->wire('database');
		$table = $database->escapeTable($field->table);
		$this->emptyPageField($dst, $field); 
		// move the data
		$sql = "UPDATE `$table` SET pages_id=:dstID WHERE pages_id=:srcID";
		$query = $this->wire('database')->prepare($sql);
		$query->bindValue(':dstID', (int) $dst->id);
		$query->bindValue(':srcID', (int) $src->id);
		$result = $query->execute();
		return $result;
	}

	
	/**
	 * Delete the given Field from all pages using the given template, without loading those pages
	 * 
	 * ProcessWire will use this method rather than deletePageField in cases where the quantity of items
	 * to delete is high (above 200 at time this was written). However, if your individual Fieldtype
	 * defines it's own ___deletePageField method (separate from the one above) then it'll still get used. 
	 * 
	 * This was added so that mass deletions can happen without loading every page, which may not be feasible
	 * when dealing with thousands of pages. 
	 * 
	 * @param Template $template
	 * @param Field $field
	 * @return bool
	 * 
	 */
	public function ___deleteTemplateField(Template $template, Field $field) {
		return $this->wire('fields')->deleteFieldDataByTemplate($field, $template); 
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
	 * Called when module version changes
	 *
	 * @param $fromVersion
	 * @param $toVersion
	 * @throws WireException if upgrade fails
	 *
	 */
	public function ___upgrade($fromVersion, $toVersion) {
		// any code needed to upgrade between versions
	}

	/**
	 * The string value of Fieldtype is always the Fieldtype's name. 
	 *
	 */
	public function __toString() {
		return $this->className();
	}

	
}

