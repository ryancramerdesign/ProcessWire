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
	 * @param Field|Saveable $item The field to save
	 * @return bool True on success, false on failure
	 * @throws WireException
	 *
	 */
	public function ___save(Saveable $item) {

		if($item->flags & Field::flagFieldgroupContext) throw new WireException("Field $item is not saveable because it is in a specific context"); 

		$database = $this->wire('database');
		$isNew = $item->id < 1;
		$prevTable = $database->escapeTable($item->prevTable);
		$table = $database->escapeTable($item->getTable());
		
		if(!$isNew && $prevTable && $prevTable != $table) {
			// note that we rename the table twice in order to force MySQL to perform the rename 
			// even if only the case has changed. 
			$database->exec("RENAME TABLE `$prevTable` TO `tmp_$table`"); // QA
			$database->exec("RENAME TABLE `tmp_$table` TO `$table`"); // QA
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
	 * Check that the given Field's table exists and create it if it doesn't
	 *
 	 * @param Field $field
	 *
	 */
	protected function checkFieldTable(Field $field) {
		// if(!$this->wire('config')->debug) return;
		$database = $this->wire('database'); 
		$table = $database->escapeTable($field->getTable());
		if(empty($table)) return;
		$exists = $database->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
		if($exists) return;
		try {
			if($field->type && count($field->type->getDatabaseSchema($field))) {
				if($field->type->createField($field)) $this->message("Created table '$table'"); 
			}
		} catch(Exception $e) {
			$this->error($e->getMessage()); 
		}
	}

	/**
	 * Check that all fields in the system have their tables installed
	 *
	 * This enables you to re-create field tables when migrating over entries from the Fields table manually (via SQL dumps or the like)
	 *
 	 * @param Field $field
	 *
	 */
	public function checkFieldTables() {
		foreach($this as $field) $this->checkFieldTable($field); 
	}

	/**
	 * Delete a Field from the database
	 *
	 * @param Field|Saveable $item Item to save
	 * @return bool True on success, false on failure
	 * @throws WireException
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
	 * @param Field|Saveable $item Item to clone
	 * @return bool|Saveable $item Returns the new clone on success, or false on failure
	 * @throws WireException
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
	 * @throws WireException
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

			// if both old and new are empty, then don't store a blank value in the context
			if(empty($oldValue) && empty($value)) continue; 

			// if old and new value are the same, then don't duplicate the value in the context
			if($value == $oldValue) continue; 

			// $value differs from $oldValue and should be saved
			$data[$key] = $value;
		}

		// keep all in the same order so that it's easier to compare (by eye) in the DB
		ksort($data);

		// if there is something in data, then JSON encode it. If it's empty then make it null.
		$data = count($data) ? wireEncodeJSON($data, true) : null;

		if(is_null($data)) {
			$data = 'NULL';
		} else {
			$data = "'" . $this->wire('database')->escapeStr($data) . "'";
		}
		$field_id = (int) $field->id; 
		$fieldgroup_id = (int) $fieldgroup->id; 
		
		$database = $this->wire('database');
		$query = $database->prepare("UPDATE fieldgroups_fields SET data=$data WHERE fields_id=:field_id AND fieldgroups_id=:fieldgroup_id"); // QA
		$query->bindValue(':field_id', $field_id, PDO::PARAM_INT);
		$query->bindValue(':fieldgroup_id', $fieldgroup_id, PDO::PARAM_INT); 
		$result = $query->execute();

		return $result; 
	}


	/**
	 * Change a field's type
	 *
	 * @param Field $field1 Field with the new type
	 * @throws WireException
	 * @return bool
	 *
	 */
	protected function ___changeFieldtype(Field $field1) {

		if(!$field1->prevFieldtype) throw new WireException("changeFieldType requires that the given field has had a type change"); 

		if(	($field1->type instanceof FieldtypeMulti && !$field1->prevFieldtype instanceof FieldtypeMulti) || 
			($field1->prevFieldtype instanceof FieldtypeMulti && !$field1->type instanceof FieldtypeMulti)) {
			throw new WireException("Cannot convert between single and multiple value field types"); 
		}

		$fromType = $field1->prevFieldtype;
		$toType = $field1->type;

		$this->changeTypeReady($field1, $fromType, $toType);

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

		$database = $this->wire('database'); 
		$table1 = $database->escapeTable($field1->table); 
		$table2 = $database->escapeTable($field2->table);

		$query = $database->prepare("DESCRIBE `$table1`"); // QA
		$query->execute();
		while($row = $query->fetch(PDO::FETCH_ASSOC)) $schema1[] = $row['Field'];

		$query = $database->prepare("DESCRIBE `$table2`"); // QA
		$query->execute();
		while($row = $query->fetch(PDO::FETCH_ASSOC)) $schema2[] = $row['Field'];
			
		foreach($schema1 as $key => $value) {
			if(!in_array($value, $schema2)) {
				if($this->config->debug) $this->message("changeFieldType loses table field '$value'"); 
				unset($schema1[$key]); 
			}
		}

		$sql = 	"INSERT INTO `$table2` (`" . implode('`,`', $schema1) . "`) " . 
				"SELECT `" . implode('`,`', $schema1) . "` FROM `$table1` ";
		
		$error = '';

		try {
			$result = $database->exec($sql);
			if($result === false || $query->errorCode() > 0) {
				$errorInfo = $query->errorInfo();
				$error = !empty($errorInfo[2]) ? $errorInfo[2] : 'Unknown Error'; 
			}
		} catch(Exception $e) {
			$result = false;
			$error = $e->getMessage();
		}

		if($error) {
			$this->error("Field type change failed. Database reports: $error"); 
			$database->exec("DROP TABLE `$table2`"); // QA
			return false; 
		}

		$database->exec("DROP TABLE `$table1`"); // QA
		$database->exec("RENAME TABLE `$table2` TO `$table1`"); // QA

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

		$this->changedType($field1, $fromType, $toType); 

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

	public function ___changedType(Saveable $item, Fieldtype $fromType, Fieldtype $toType) { }
	public function ___changeTypeReady(Saveable $item, Fieldtype $fromType, Fieldtype $toType) { }

}

