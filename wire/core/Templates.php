<?php

/**
 * ProcessWire Templates
 *
 * Manages and provides access to all the Template instances
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
 * WireArray of Template instances
 *
 */
class TemplatesArray extends WireArray {

	public function isValidItem($item) {
		return $item instanceof Template; 	
	}

	public function isValidKey($key) {
		return is_int($key) || ctype_digit($key); 
	}

	public function getItemKey($item) {
		return $item->id; 
	}

	public function makeBlankItem() {
		return new Template();
	}
	
}

/**
 * Manages and provides access to all the Template instances
 *
 */
class Templates extends WireSaveableItems {

	/**
	 * Reference to all the Fieldgroups
	 *
	 */
	protected $fieldgroups = null; 

	/**
	 * WireArray of all Template instances
	 *
	 */
	protected $templatesArray; 

	/**
	 * Path where Template files are stored
	 *
	 */
	protected $path; 

	/**
	 * Construct the Templates
	 *
	 * @param Fieldgroups $fieldgroups Reference to the Fieldgroups
	 * @param string $path Path to where template files are stored
	 *
	 */
	public function __construct(Fieldgroups $fieldgroups, $path) {
		$this->fieldgroups = $fieldgroups; 
		$this->templatesArray = new TemplatesArray();
		$this->path = $path;
	}

	/**
	 * Initialize the TemplatesArray and populate
	 *
	 */
	public function init() {
		$this->load($this->templatesArray); 
	}

	/**
	 * Return the WireArray that this DAO stores it's items in
	 *
	 */
	public function getAll() {
		return $this->templatesArray;
	}

	/**
	 * Return a new blank item 
	 *
	 */
	public function makeBlankItem() {
		return new Template(); 
	}

	/**
	 * Return the name of the table that this DAO stores item records in
	 *
	 */
	public function getTable() {
		return 'templates';
	}

	/**
	 * Return the field name that fields should initially be sorted by
	 *
	 */
	public function getSort() {
		return $this->getTable() . ".name";
	}

	/**
	 * Given a template ID or name, return the matching template or NULL if not found.
	 *
	 */
	public function get($key) {
		if($key == 'path') return $this->path;
		$value = $this->templatesArray->get($key); 
		if(is_null($value)) $value = parent::get($key);
		return $value; 
	}


	/**
	 * Update or insert template to database 
	 *
	 * If the template's fieldgroup has changed, then we delete data that's no longer applicable to the new fieldgroup. 
	 *
	 */
	public function ___save(Saveable $item) {

		if(!$item->fieldgroup->id) throw new WireException("You must save Fieldgroup '{$item->fieldgroup}' before adding to Template '{$item}'"); 

		$rolesChanged = $item->isChanged('useRoles');
		$result = parent::___save($item); 

		if($result && $item->fieldgroupPrevious && $item->fieldgroupPrevious->id != $item->fieldgroup->id) {
			// the fieldgroup has been changed
			// remove data from all fields that are not part of the new fieldgroup
			$removeFields = new FieldsArray();
			foreach($item->fieldgroupPrevious as $field) {
				if(!$item->fieldgroup->has($field)) {
					$removeFields->add($field); 
				}
			}
			if(count($removeFields)) { 
				$pages = $this->fuel('pages')->find("templates_id={$item->id}"); 
				foreach($pages as $page) {
					foreach($removeFields as $field) {
						$field->type->deletePageField($page, $field); 
						if($this->config->debug) $this->message("Removed field '$field' on page '{$page->url}'"); 
					}
				}
			}
		}

		if($rolesChanged) { 
			$access = new PagesAccess();
			$access->updateTemplate($item); 
		}

		return $result; 
	}

	/**
	 * Delete a template and unset it from this object. 
	 *
	 */
	public function ___delete(Saveable $item) {
		if($item->flags & Template::flagSystem) throw new WireException("Can't delete template '{$item->name}' because it is a system template."); 
		$cnt = $item->getNumPages();
		if($cnt > 0) throw new WireException("Can't delete template '{$item->name}' because it is used by $cnt pages.");  
		return parent::___delete($item);
	}


	/**
	 * Return the number of pages using the provided Template
	 *
	 */
	public function getNumPages(Template $tpl) {
		$result = $this->fuel('db')->query("SELECT COUNT(*) AS total FROM pages WHERE templates_id={$tpl->id}"); 
		$row = $result->fetch_assoc(); 
		$result->free();
		return (int) $row['total'];
	}

}

