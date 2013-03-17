<?php

/**
 * ProcessWire Fields
 *
 * Manages collection of ALL Field instances, not specific to any particular Fieldgroup
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * WireArray of Field instances, as used by Fields class
 *
 */
class FieldsArray extends WireArray {

	/**
	 * Per WireArray interface, only Field instances may be added
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Field; 	
	}

	/**
	 * Per WireArray interface, Field keys have to be integers
	 *
	 */
	public function isValidKey($key) {
		return is_int($key) || ctype_digit($key); 
	}

	/**
	 * Per WireArray interface, Field instances are keyed by their ID
	 *
	 */
	public function getItemKey($item) {
		return $item->id; 
	}

	/**
	 * Per WireArray interface, return a blank Field
	 *
	 */
	public function makeBlankItem() {
		return new Field();
	}
}

/**
 * Manages the collection of all Field instances, not specific to any one Fieldgroup
 *
 */
class Fields extends WireSaveableItems {

	/**
	 * Instance of FieldsArray
	 *
	 */
	protected $fieldsArray = null;

	/**
	 * Instance of Database
	 *
	 */
	protected $db = null;

	/**
	 * Field names that are native/permanent to the system and thus treated differently in several instances. 
	 *
	 * For example, a Field can't be given one of these names. 
	 *
	 * @TODO This really doesn't belong here. This check can be performed from a Page without needing to maintain this silly list.
	 *
	 */
	static protected $nativeNames = array(
		'id', 
		'parent_id', 
		'parent', // alias
		'parents', 
		'templates_id', 
		'template', // alias
		'name', 
		'status',
		'created',
		'createdUser', 
		'createdUserID',
		'createdUsersID',
		'created_users_id', 
		'include',
		'modified', 
		'modifiedUser', 
		'modifiedUserID',
		'modifiedUsersID',
		'modified_users_id', 
		'num_children',
		'numChildren', 
		'sort', 
		'sortfield', 
		'flags', 
		'find',
		'get',
		'child', 
		'children', 
		'siblings', 
		//'roles', 
		'url', 
		'path', 
		'templatePrevious', 
		'rootParent', 
		'fieldgroup',
		'fields', 
		'description',
		'data',
		'isNew',
		); 

	public function __construct() {
		$this->fieldsArray = new FieldsArray();
		$this->db = $this->fuel('db'); 
	}

	/**
	 * Construct and load the Fields
	 *
	 */
	public function init() {
		$this->load($this->fieldsArray); 
	}

	/**
	 * Per WireSaveableItems interface, return a blank instance of a Field
	 *
	 */
	public function makeBlankItem() {
		return new Field();
	}

	/**
	 * Per WireSaveableItems interface, return all available Field instances
	 *
	 */
	public function getAll() {
		return $this->fieldsArray; 
	}

	/**
	 * Per WireSaveableItems interface, return the table name used to save Fields
	 *
	 */
	public function getTable() {
		return "fields";
	}

	/**
	 * Return the name that fields should be initially sorted by
	 *
	 */
	public function getSort() {
		return $this->getTable() . ".name";
	}

	/**
	 * Save a Field to the database
	 *
	 * @param Field $item The field to save
	 * @return bool True on success, false on failure
	 *
	 */
	public function ___save(Saveable $item) {

		if($item->flags & Field::flagFieldgroupContext) throw new WireException("Field $item is not saveable because it is in a specific context"); 

		$isNew = $item->id < 1;
		$prevTable = $this->fuel('db')->escapeTable($item->prevTable);
		$table = $this->fuel('db')->escapeTable($item->getTable());

		if(!$isNew && $prevTable && $prevTable != $table) {
			// note that we rename the table twice in order to force MySQL to perform the rename 
			// even if only the case has changed. 
			$this->fuel('db')->query("RENAME TABLE `$prevTable` TO `tmp_$table`"); // QA
			$this->fuel('db')->query("RENAME TABLE `tmp_$table` TO `$table`"); // QA
			$item->prevTable = '';
		}

		if($item->prevFieldtype && $item->prevFieldtype->name != $item->type->name) {
			if(!$this->changeFieldtype($item)) {
				$item->type = $item->prevFieldtype; 
				$this->error("Error changing fieldtype for '$item', reverted back to '{$item->type}'"); 
			} else {
				$item->prevFieldtype = null;
			}
		}

		if(!$item->type) throw new WireException("Can't save a Field that doesn't have it's 'type' property set to a Fieldtype"); 
		if(!parent::___save($item)) return false;
		if($isNew) $item->type->createField($item); 

		if($item->flags & Field::flagGlobal) {
			// make sure that all template fieldgroups contain this field and add to any that don't. 
			foreach(wire('templates') as $template) {
				if($template->noGlobal) continue; 
				$fieldgroup = $template->fieldgroup; 
				if(!$fieldgroup->hasField($item)) {
					$fieldgroup->add($item); 
					$fieldgroup->save();
					if(wire('config')->debug) $this->message("Added field '{$item->name}' to template/fieldgroup '{$fieldgroup->name}'"); 
				}
			}	
		}

		return true; 
	}

	/**
	 * Delete a Field from the database
	 *
	 * @param Saveable $item Item to save
	 * @return bool True on success, false on failure
	 *
	 */
	public function ___delete(Saveable $item) {

		if(!$this->fieldsArray->isValidItem($item)) throw new WireException("Fields::delete(item) only accepts items of type Field"); 

		// if the field doesn't have an ID, so it's not one that came from the DB
		if(!$item->id) throw new WireException("Unable to delete from '" . $item->getTable() . "' for field that doesn't exist in fields table"); 

		// if it's in use by any fieldgroups, then we don't allow it to be deleted
		if($item->numFieldgroups()) throw new WireException("Unable to delete field '{$item->name}' because it is in use by " . $item->numFieldgroups() . " fieldgroups"); 

		// if it's a system field, it may not be deleted
		if($item->flags & Field::flagSystem) throw new WireException("Unable to delete field '{$item->name}' because it is a system field."); 

		// delete entries in fieldgroups_fields table. Not really necessary since the above exception prevents this, but here in case that changes. 
		$this->fuel('fieldgroups')->deleteField($item); 

		// drop the field's table
		$item->type->deleteField($item); 

		return parent::___delete($item); 
	}


	/**
	 * Create and return a cloned copy of the given Field
	 *
	 * @param Saveable $item Item to clone
	 * @param bool|Saveable $item Returns the new clone on success, or false on failure
	 *
	 */
	public function ___clone(Saveable $item) {
	
		$item = $item->type->cloneField($item); 
	
		// don't clone system flags	
		if($item->flags & Field::flagSystem || $item->flags & Field::flagPermanent) {
			$item->flags = $item->flags | Field::flagSystemOverride; 
			if($item->flags & Field::flagSystem) $item->flags = $item->flags & ~Field::flagSystem;
			if($item->flags & Field::flagPermanent) $item->flags = $item->flags & ~Field::flagPermanent;
			$item->flags = $item->flags & ~Field::flagSystemOverride;
		}

		// don't clone the 'global' flag
		if($item->flags & Field::flagGlobal) $item->flags = $item->flags & ~Field::flagGlobal;


		return parent::___clone($item);
	}

	/**
	 * Save the context of the given field for the given fieldgroup
	 *
	 * @param Field $field Field to save context for
	 * @param Fieldgroup $fieldgroup Context for when field is in this fieldgroup
	 * @return bool True on success
	 *
	 */
	public function ___saveFieldgroupContext(Field $field, Fieldgroup $fieldgroup) {

		// get field without contxt
		$fieldOriginal = wire('fields')->get($field->name);

		$data = array();

		// make sure given field and fieldgroup are valid
		if(!($field->flags & Field::flagFieldgroupContext)) throw new WireException("Field must be in fieldgroup context before its context can be saved"); 
		if(!$fieldgroup->has($fieldOriginal)) throw new WireException("Fieldgroup $fieldgroup does not contain field $field"); 

		$newValues = $field->getArray();
		$oldValues = $fieldOriginal->getArray();

		// 0 is the same as 100 for columnWidth, so we specifically set it just to prevent this from being saved when it doesn't need to be
		if(!isset($oldValues['columnWidth'])) $oldValues['columnWidth'] = 100;

		// add the label and description built-in fields
		foreach(array('label', 'description') as $key) {
			$newValues[$key] = $field->$key;
			$oldValues[$key] = $fieldOriginal->$key;
		}

		// cycle through and determine which values should be saved
		foreach($newValues as $key => $value) {
			$oldValue = empty($oldValues[$key]) ? '' : $oldValues[$key]; 
			if(strlen("$value") && $value != $oldValue) $data[$key] = $value;
		}

		// keep all in the same order so that it's easier to compare (by eye) in the DB
		ksort($data);

		// if there is something in data, then JSON encode it. If it's empty then make it null.
		$data = count($data) ? wireEncodeJSON($data, true) : null;

		if(is_null($data)) {
			$data = 'NULL';
		} else {
			$data = "'" . $this->db->escape_string($data) . "'";
		}
		$field_id = (int) $field->id; 
		$fieldgroup_id = (int) $fieldgroup->id; 
		$result = $this->db->query("UPDATE fieldgroups_fields SET data=$data WHERE fields_id=$field_id AND fieldgroups_id=$fieldgroup_id"); // QA

		return $result; 
	}


	/**
	 * Change a field's type
	 *
	 * @param Field $field1 Field with the new type
	 *
	 */
	protected function ___changeFieldtype(Field $field1) {

		if(!$field1->prevFieldtype) throw new WireException("changeFieldType requires that the given field has had a type change"); 

		if(	($field1->type instanceof FieldtypeMulti && !$field1->prevFieldtype instanceof FieldtypeMulti) || 
			($field1->prevFieldtype instanceof FieldtypeMulti && !$field1->type instanceof FieldtypeMulti)) {
			throw new WireException("Cannot convert between single and multiple value field types"); 
		}

		$field2 = clone $field1; 
		$flags = $field2->flags; 
		if($flags & Field::flagSystem) {
			$field2->flags = $flags | Field::flagSystemOverride; 
			$field2->flags = 0;
		}
		$field2->name = $field2->name . "_PWTMP";
		$field2->type->createField($field2); 
		$field1->type = $field1->prevFieldtype;

		$schema1 = array();
		$schema2 = array();

		$table1 = $this->db->escapeTable($field1->table); 
		$table2 = $this->db->escapeTable($field2->table);

		$result = $this->db->query("DESCRIBE `$table1`"); // QA
		while($row = $result->fetch_assoc()) $schema1[] = $row['Field']; 
		$result->free();

		$result = $this->db->query("DESCRIBE `$table2`"); // QA
		while($row = $result->fetch_assoc()) $schema2[] = $row['Field']; 
		$result->free();

		foreach($schema1 as $key => $value) {
			if(!in_array($value, $schema2)) {
				if($this->config->debug) $this->message("changeFieldType loses table field '$value'"); 
				unset($schema1[$key]); 
			}
		}

		$sql = 	"INSERT INTO `$table2` (`" . implode('`,`', $schema1) . "`) " . 
			"SELECT `" . implode('`,`', $schema1) . "` FROM `$table1` ";

		try {
			$result = $this->db->query($sql); // QA
		} catch(WireDatabaseException $e) {
			$result = false;
		}

		if(!$result) {
			$this->error("Field type change failed. Database reports: {$this->db->error}"); 
			$this->db->query("DROP TABLE `$table2`"); // QA
			return false; 
		}

		$this->db->query("DROP TABLE `$table1`"); // QA
		$this->db->query("RENAME TABLE `$table2` TO `$table1`"); // QA


		$field1->type = $field2->type; 

		// clear out the custom data, which contains settings specific to the Inputfield and Fieldtype
		foreach($field1->getArray() as $key => $value) {
			// skip fields that may be shared among any fieldtype
			if(in_array($key, array('description', 'required', 'collapsed', 'notes'))) continue; 
			// skip over language labels/descriptions
			if(preg_match('/^(description|label|notes)\d+/', $key)) continue; 
			// remove the custom field
			$field1->remove($key); 
		}

		return true; 	
	}

	/**
	 * Is the given field name native/permanent to the database?
	 *
	 * @param string $name
	 * @return bool
	 *
	 */
	public static function isNativeName($name) {
		return in_array($name, self::$nativeNames); 
	}

	/**
	 * Overridden from WireSaveableItems to retain keys with 0 values and remove defaults we don't need saved
	 *
	 */
	protected function encodeData(array $value) {
		if(isset($value['collapsed']) && $value['collapsed'] === 0) unset($value['collapsed']); 	
		if(isset($value['columnWidth']) && (empty($value['columnWidth']) || $value['columnWidth'] == 100)) unset($value['columnWidth']); 
		return wireEncodeJSON($value, 0); 	
	}

}

