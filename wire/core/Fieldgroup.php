<?php

/**
 * ProcessWire Fieldgroup
 *
 * A group of fields that is ultimately attached to a Template.
 *
 * The existance of Fieldgroups is hidden at the ProcessWire web admin level
 * as it appears that fields are attached directly to Templates. However, they
 * are separated in the API in case want want to have fieldgroups used by 
 * multiple templates in the future (like ProcessWire 1.x).
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 * @property int $id Field ID
 * @property string $name Field name
 *
 */
class Fieldgroup extends WireArray implements Saveable, Exportable, HasLookupItems {

	/**
	 * Permanent/common settings for a Fieldgroup, fields in the database
	 *
'	 */
	protected $settings = array(
		'id' => 0, 
		'name' => '', 
		);

	/**
	 * Any fields that were removed from this instance are noted so that Fieldgroups::save() can delete unused data
	 *
	 */
	protected $removedFields = null;

	/**
	 * Array indexed by field_id containing an array of variables specific to the context of that field in this fieldgroup
	 *
	 * This context overrides the values set in the field when it doesn't have context. 
	 *
	 */
	protected $fieldContexts = array();

	/**
	 * Per WireArray interface, items added must be instances of Field
	 *
	 */
	public function isValidItem($item) {
		return is_object($item) && $item instanceof Field; 
	}

	/**
	 * Per WireArray interface, keys must be numeric
	 *
	 */
	public function isValidKey($key) {
		return is_int($key) || ctype_digit("$key"); 
	}

	/**
	 * Per WireArray interface, the item key is it's ID
	 *
	 */
	public function getItemKey($item) {
		return $item->id; 
	}

	/**
	 * Per WireArray interface, return a blank item 
	 *
	 */
	public function makeBlankItem() {
		return new Field();
	}

	/**
	 * Add a field to this Fieldgroup
	 *
	 * @param Field|string $field
	 * @return $this|WireArray
	 * @throws WireException
	 *
	 */
	public function add($field) {
		if(!is_object($field)) $field = $this->getFuel('fields')->get($field); 

		if($field && $field instanceof Field) {
			if(!$field->id) throw new WireException("You must save field '$field' before adding to Fieldgroup '{$this->name}'"); 
			parent::add($field); 
		} else {
			// throw new WireException("Unable to add field '$field' to Fieldgroup '{$this->name}'"); 
		}

		return $this; 
	}

	/**
	 * Remove a field from this fieldgroup
	 *
	 * Performs a deletion by finding all templates using this fieldgroup, then finding all pages using the template, then 
	 * calling upon the Fieldtype to delete them one at a time. This is a potentially expensive/time consuming method, and
	 * may need further consideration. 
	 * 
	 * Note that this must be followed up with a save() before it does anything destructive. This method does nothing more
	 * than queue the removal. 
	 *
	 * @param Field|string $field
	 * @return bool True on success, false on failure.
	 *
	 */
	public function remove($field) {

		if(!is_object($field)) $field = $this->getFuel('fields')->get($field); 
		if(!$this->getField($field->id)) return false; 
		if(!$field) return true; 

		// Make note of any fields that were removed so that Fieldgroups::save()
		// can delete data for those fields
		if(is_null($this->removedFields)) $this->removedFields = new FieldsArray();
		$this->removedFields->add($field); 
		$this->trackChange("remove:$field", $field, null); 

		// parent::remove($field->id); replaced with finishRemove() method below

		return true; 
	}

	/**
	 * Intended to be called by Fieldgroups::save() to complete the field removal
	 *
	 * This completes the removal process. The remove() method above only queues the removal but doesn't execute it.
	 * Instead, Fieldgroups::save() calls this method to finish the removal. This is necessary because if remove()
	 * removes the data from memory, then save() won't still have access to determine what related assets should
	 * be removed. 
	 *
	 * This method is for use by Fieldgroups::save() and not intended for API usage. 
	 * 
	 * @internal
	 * @param Field $field
	 * @return bool
	 *
	 */
	public function finishRemove(Field $field) {
		return parent::remove($field->id); 
	}

	/**
	 * Removes a field from the fieldgroup without deleting any associated field data when $fieldgroup->save() is called.
	 *
	 * This is useful in the API when you want to move a field around within a fieldgroup, like when moving a field to a Fieldset within the Fieldgroup. 
	 *
	 * @param Field $field
	 * @return bool
	 *
	 */
	public function softRemove($field) {

		if(!is_object($field)) $field = $this->getFuel('fields')->get($field); 
		if(!$this->getField($field->id)) return false; 
		if(!$field) return true; 

		return parent::remove($field->id); 
	}

	/**
	 * Clear all removed fields, for use by Fieldgroups::save
	 *
	 */
	public function resetRemovedFields() {
		$this->removedFields = null;
	}

	/**
	 * Get a field that is part of this fieldgroup
	 *
	 * Same as get() except that it only checks fields, not other properties of a fieldgroup
	 *
	 * @param string|int|Field $key
	 * @param bool $useFieldgroupContext If set to true, the field will be a clone of the original with context data set. (default is false)
	 * @return Field|null
	 *
	 */
	public function getField($key, $useFieldgroupContext = false) {
		if(is_object($key) && $key instanceof Field) $key = $key->id;

		if($this->isValidKey($key)) {
			$value = parent::get($key); 

		} else {

			$value = null;
			foreach($this as $field) {
				if($field->name == $key) {
					$value = $field;
					break;
				}
			}
		}

		if($value && $useFieldgroupContext) { // && !empty($this->fieldContexts[$value->id])) {
			$value = clone $value;	
			if(isset($this->fieldContexts[$value->id])) {
				foreach($this->fieldContexts[$value->id] as $k => $v) {
					$value->set($k, $v); 
				}
			}
		}

		if($useFieldgroupContext && $value) {
			$value->flags = $value->flags | Field::flagFieldgroupContext;
		}

		return $value; 
	}

	/**
	 * Does the given field have context available in this fieldgroup?
	 * 
	 * @param int|string|Field $field
	 * @return bool
	 * 
	 */
	public function hasFieldContext($field) {
		if(is_object($field) && $field instanceof Field) $field = $field->id;
		if(is_string($field) && !ctype_digit($field)) {
			$field = $this->wire('fields')->get($field);
			$field = $field && $field->id ? $field->id : 0;
		}
		return isset($this->fieldContexts[(int) $field]) ? true : false;
	}

	/**
	 * Get a field that is part of this fieldgroup, in the context of this fieldgroup. 
	 *
	 * @param string|int|Field $key
	 * @return Field|null
	 *
	 */
	public function getFieldContext($key) {
		return $this->getField($key, true); 
	}

	/**
	 * Does this fieldgroup having the given field?
	 *
	 * @param string|int|Field $key
	 * @return bool
	 *
	 */
	public function hasField($key) {
		return $this->getField($key) !== null;
	}

	/**
	 * Get a Fieldgroup property or a field. 
	 *
	 * It is preferable to use getField() to retrieve fields from the fieldgroup because this checks other properties of the Fieldgroup. 
	 *
	 * @param string|int $key
	 * @return Field|string|int|null
	 *
	 */
	public function get($key) {
		if($key == 'fields') return $this;
		if($key == 'fields_id') {
			$values = array();
			foreach($this as $field) $values[] = $field->id; 
			return $values; 
		}
		if($key == 'removedFields') return $this->removedFields; 
		if(isset($this->settings[$key])) return $this->settings[$key]; 
		if($value = parent::get($key)) return $value; 
		return $this->getField($key); 
	}

	/**
	 * Per HasLookupItems interface, add a Field to this Fieldgroup
	 *
	 */
	public function addLookupItem($item, array &$row) {
		if($item) $this->add($item); 
		if(!empty($row['data'])) {
			// set field context for this fieldgroup
			$this->fieldContexts[(int)$item] = wireDecodeJSON($row['data']); 
		}
		return $this; 
	}


	/**
	 * Overridden ProcessArray set
	 *
	 * @param string $key
	 * @param string|int|object $value
	 * @return Fieldgroup $this
	 * @throws WireException if passed invalid data
	 *
	 */
	public function set($key, $value) {

		if($key == 'data') return $this; // we don't have a data field here

		if($key == 'id') {
			$value = (int) $value; 
			
		} else if($key == 'name') {
			$value = $this->wire('sanitizer')->name($value); 
			
		}

		if(isset($this->settings[$key])) {
			if($this->settings[$key] !== $value) $this->trackChange($key, $this->settings[$key], $value); 
			$this->settings[$key] = $value; 

		} else {
			return parent::set($key, $value); 
		}
		
		return $this; 	
	}


	/**
	 * Save this Fieldgroup to the database
	 *
	 * To hook into ___save, use Fieldgroups::save instead
	 *
	 */
	public function save() {
		$this->getFuel('fieldgroups')->save($this); 
	}

	/**
	 * Fieldgroups always return their name when dereferenced as a string
	 *	
	 */
	public function __toString() {
		return $this->name; 
	}

	/**
	 * Per Saveable interface, get an array of data associated with the database table
	 *
	 */
	public function getTableData() {
		return $this->settings; 
	}

	/**
	 * Per Saveable interface: return data for external storage
	 *
	 */
	public function getExportData() {
		return $this->wire('fieldgroups')->getExportData($this); 
	}

	/**
	 * Given an export data array, import it back to the class and return what happened
	 * 
	 * Changes are not committed until the item is saved
	 *
	 * @param array $data 
	 * @return array Returns array(
	 * 	[property_name] => array(
	 * 		'old' => 'old value',	// old value, always a string
	 * 		'new' => 'new value',	// new value, always a string
	 * 		'error' => 'error message or blank if no error'
	 * 	)
	 * @throws WireException if given invalid data
	 * 
	 */
	public function setImportData(array $data) {
		return $this->wire('fieldgroups')->setImportData($this, $data); 
	}

	/**
	 * Per HasLookupItems interface, get a WireArray of Field instances associated with this Fieldgroup
	 *	
	 */ 
	public function getLookupItems() {
		return $this; 
	}

	/**
	 * Get all of the Inputfields associated with the provided Page and populate them
	 *
	 * @param Page $page
	 * @param string $contextStr Optional context string to append to all the Inputfield's names (helper for things like repeaters)
	 * @param string $fieldName Limit to a particular fieldName (may be a Fieldset too, which will include all fields in fieldset)
	 * @return Inputfield acting as a container for multiple Inputfields
	 *
	 */
	public function getPageInputfields(Page $page, $contextStr = '', $fieldName = '') {

		$container = new InputfieldWrapper();
		$inFieldset = false;
		$inModalGroup = '';

		foreach($this as $field) {
			
			// get a clone in the context of this fieldgroup, if it has contextual settings
			if(isset($this->fieldContexts[$field->id])) $field = $this->getField($field->id, true); 
			
			if($inModalGroup) {
				// we are in a modal group that should be skipped since all the inputs require the modal
				if($field->name == $inModalGroup . "_END") {
					// exit modal group
					$inModalGroup = false; 
				} else {
					// skip field
					continue; 
				}
			}
			
			if($fieldName) {
				// limit to specific field name
				if($inFieldset) {
					// allow the field
					if($field->type instanceof FieldtypeFieldsetClose && $field->name == $fieldName . "_END") {
						// stop, as we've got all the fields we need
						break;
					}
					// allow
					
				} else if($field->name == $fieldName) {
					// start allow fields
					if($field->type instanceof FieldtypeFieldsetOpen) {
						$container = $field->getInputfield($page, $contextStr);
						$inFieldset = true;
						continue; 
					} else {
						// allow 1 field
					}
				} else {
					// disallow
					continue; 
				}
				
			} else if($field->modal && $field->type instanceof FieldtypeFieldsetOpen) {
				// field requires modal
				$inModalGroup = $field->name; 
			}

			$inputfield = $field->getInputfield($page, $contextStr);
			if(!$inputfield) continue; 
			if($inputfield->collapsed == Inputfield::collapsedHidden) continue;

			$inputfield->value = $page->get($field->name); 
			$container->add($inputfield); 
		}		

		return $container; 
	}

	/**
	 * Get a TemplatesArray of all templates using this Fieldgroup
	 *
	 * @return TemplatesArray
	 *
	 */
	public function getTemplates() {
		return $this->fuel('fieldgroups')->getTemplates($this); 
	}

	/**
	 * Get the number of templates using the given fieldgroup. 
	 *
	 * @return int
	 *
	 */
	public function getNumTemplates() {
		return $this->fuel('fieldgroups')->getNumTemplates($this); 
	}

	/**
	 * Alias of getNumTemplates()
	 *
	 * @return int
	 *
	 */
	public function numTemplates() {
		return $this->getNumTemplates();
	}

	/**
	 * Return an array of context data for the given field ID
	 *
	 * @param int|null $field_id Field ID or omit to return all field contexts
	 * @return array 
	 *
	 */
	public function getFieldContextArray($field_id = null) {
		if(is_null($field_id)) return $this->fieldContexts;
		if(isset($this->fieldContexts[$field_id])) return $this->fieldContexts[$field_id];
		return array();
	}

	/**
	 * Set an array of context data for the given field ID
	 * 
	 * @param int $field_id Field ID
	 * @param array $data
	 * 
	 */
	public function setFieldContextArray($field_id, $data) {
		$this->fieldContexts[$field_id] = $data;
	}

	/**
	 * Save field contexts for this fieldgroup
	 * 
	 * @return int Number of contexts saved
	 * 
	 */
	public function saveContext() {
		return $this->wire('fieldgroups')->saveContext($this); 
	}

}


