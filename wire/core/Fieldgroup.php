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
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
class Fieldgroup extends WireArray implements Saveable, HasLookupItems {

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
 	 * @param Field|string 
	 *
	 */
	public function add($field) {
		if(!is_object($field)) $field = $this->getFuel('fields')->get($field); 

		if($field && $field instanceof Field) {
			if(!$field->id) throw new WireException("You must save field '$field' before adding to Fieldgroup '{$this->name}'"); 
			parent::add($field); 
		} else {
			throw new WireException("Unable to add field '$field' to Fieldgroup '{$this->name}'"); 
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
		$this->trackChange("remove:$field"); 

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
	 * @param Field $field
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
	 * Get a field that is part of this fieldgroup
	 *
	 * Same as get() except that it only checks fields, not other properties of a fieldgroup
	 *
	 * @param string|int $key
	 * @return Field|null
	 *
	 */
	public function getField($key) {

		if($this->isValidKey($key)) return parent::get($key); 

		$value = null;
		foreach($this as $field) {
			if($field->name == $key) {
				$value = $field;
				break;
			}
		}
		return $value; 
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
	public function addLookupItem($item) {
		if($item) $this->add($item); 
		return $this; 
	}


	/**
	 * Overridden ProcessArray set
	 *
	 * @param string $key
	 * @param string|int|object $value
	 * @return Fieldgroup $this
	 *
	 */
	public function set($key, $value) {
		if($key == 'id') $value = (int) $value; 
			else if($key == 'name') $value = $this->fuel('sanitizer')->name($value); 

		if(isset($this->settings[$key])) {
			if($this->settings[$key] !== $value) $this->trackChange($key); 
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
	 * @return Inputfield acting as a container for multiple Inputfields
	 *
	 */
	public function getPageInputfields(Page $page) {
		$container = new InputfieldWrapper();
		foreach($this as $field) {
			$inputfield = $field->getInputfield($page);
			if(!$inputfield) continue; 
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
}


