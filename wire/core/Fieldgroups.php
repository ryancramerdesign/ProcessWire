<?php

/**
 * ProcessWire Fieldgroups
 *
 * Maintains collections of Fieldgroup instances. 
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
 * Array of Fieldgroup instances, as used by the Fieldgroups class
 *
 */
class FieldgroupsArray extends WireArray {

	/**
	 * Per WireArray interface, this class only carries Fieldgroup instances
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Fieldgroup; 
	}

	/**
	 * Per WireArray interface, items are keyed by their ID
	 *
	 */
	public function getItemKey($item) {
		return $item->id; 
	}

	/**
	 * Per WireArray interface, keys must be integers
	 *
	 */
	public function isValidKey($key) {
		return is_int($key); 
	}

	/**
	 * Per WireArray interface, return a blank Fieldgroup
	 *
	 */
	public function makeBlankItem() {
		return new Fieldgroup();
	}

}

/**
 * Maintains collection of all fieldgroups
 *
 */
class Fieldgroups extends WireSaveableItemsLookup {

	/**
	 * Instances of FieldgroupsArray
	 *
	 */
	protected $fieldgroupsArray; 

	public function init() {
		$this->fieldgroupsArray = new FieldgroupsArray();
		$this->load($this->fieldgroupsArray);
	}

	/**
	 * Load all the Fieldgroups from the database
	 *
	 * The loading is delegated to WireSaveableItems.
	 * After loaded, we check for any 'global' fields and add them to the Fieldgroup, if not already there.
	 *
	 * @param Selectors|string|null $selectors Selectors or a selector string to find, or NULL to load all. 
	 * @return WireArray Returns the same type as specified in the getAll() method.
	 *
	 */
	protected function ___load(WireArray $items, $selectors = null) {
		$items = parent::___load($items, $selectors); 	
		return $items; 
	}

	/**
	 * Per WireSaveableItems interface, return all available Fieldgroup instances
	 *
	 */
	public function getAll() {
		return $this->fieldgroupsArray;
	}

	/**
	 * Per WireSaveableItems interface, create a blank instance of a Fieldgroup
	 *
	 */
	public function makeBlankItem() {
		return new Fieldgroup(); 
	}

	/**
	 * Per WireSaveableItems interface, return the name of the table that Fieldgroup instances are stored in
	 *
	 */
	public function getTable() {
		return 'fieldgroups';
	}

	/**
	 * Per WireSaveableItemsLookup interface, return the name of the table that Fields are linked to Fieldgroups 
	 *
	 */
	public function getLookupTable() {
		return 'fieldgroups_fields';
	}

	/**
	 * Get the number of templates using the given fieldgroup. 
	 *
	 * Primarily used to determine if the Fieldgroup is deleteable. 
	 *
	 * @param Fieldgroup $fieldgroup
	 * @return int
	 *
	 */
	public function getNumTemplates(Fieldgroup $fieldgroup) {
		return count($this->getTemplates($fieldgroup)); 
	}

	/**
	 * Given a Fieldgroup, return a TemplatesArray of all templates using the Fieldgroup
	 *
	 * @param Fieldgroup $fieldgroup
	 * @return TemplatesArray
	 *
	 */
	public function getTemplates(Fieldgroup $fieldgroup) {
		$templates = new TemplatesArray();
		$cnt = 0;
		foreach($this->fuel('templates') as $tpl) {
			if($tpl->fieldgroup->id == $fieldgroup->id) $templates->add($tpl); 
		}
		return $templates; 
	}

	/**
	 * Save the Fieldgroup to DB
	 *
	 * If fields were removed from the Fieldgroup, then track them down and remove them from the associated field_* tables
	 *
	 */
	public function ___save(Saveable $item) {

		if($item->removedFields) {

			foreach($this->fuel('templates') as $template) {

				if($template->fieldgroup->id !== $item->id) continue; 

				foreach($item->removedFields as $field) { 

					if(($field->flags & Field::flagGlobal) && !$template->noGlobal) {
						throw new WireException("Field '$field' may not be removed from fieldgroup '$this' because it is globally required (Field::flagGlobal)"); 
					}

					if($field->flags & Field::flagPermanent) {
						throw new WireException("Field '$field' may not be removed from fieldgroup '$this' because it is permanent."); 
					}

					$pages = $this->fuel('pages')->find("templates_id={$template->id}"); 

					foreach($pages as $page) {

						try { 
							$field->type->deletePageField($page, $field); 
							$page->save($field->name); 

						} catch(Exception $e) {
							$this->error($e->getMessage()); 
						}

						if($this->fuel('config')->debug) $this->message("Deleted '{$field->name}' from '{$page->path}'"); 
					}

					$item->finishRemove($field); 
				}
			}
		}

		return parent::___save($item); 
	}

	/**
	 * Delete the given fieldgroup from the database
	 *
	 * Also deletes the references in fieldgroups_fields table
	 *
	 * @param Fieldgroup $fieldgroup
	 * @return Fieldgroups $this
	 *
	 */
	public function ___delete(Saveable $item) {
		parent::___delete($item); 
	}

	/**
	 * Delete the entries in fieldgroups_fields for the given Field
	 *
	 * @param Field $field
	 * @return bool
	 *
	 */
	public function deleteField(Field $field) {
		$sql = "DELETE FROM fieldgroups_fields WHERE fields_id={$field->id}";
		return $this->fuel('db')->query($sql); 
	}

}

