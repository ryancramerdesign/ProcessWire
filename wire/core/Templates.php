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
 * @method Templates find() find($selectorString) Return the templates matching the the given selector query.
 * @method bool save() save(Template $template) Save the given template instance.
 * @method bool delete() delete($template) Delete the given template instance. Note that this will throw a fatal error if the template is in use by any pages.
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
	 * @param Template $item 
	 * @return bool true on success
	 *
	 */
	public function ___save(Saveable $item) {

		$isNew = $item->id < 1; 

		if(!$item->fieldgroup->id) throw new WireException("You must save Fieldgroup '{$item->fieldgroup}' before adding to Template '{$item}'"); 

		$rolesChanged = $item->isChanged('useRoles');

		if($this->fuel('pages')->get("/")->template->id == $item->id) {
			if(!$item->useRoles) throw new WireException("Template '{$item}' is used by the homepage and thus must manage access"); 
			if(!$item->hasRole("guest")) throw new WireException("Template '{$item}' is used by the homepage and thus must have the 'guest' role assigned."); 
		}

		$result = parent::___save($item); 

		if($result && !$isNew && $item->fieldgroupPrevious && $item->fieldgroupPrevious->id != $item->fieldgroup->id) {
			// the fieldgroup has been changed
			// remove data from all fields that are not part of the new fieldgroup
			$removeFields = new FieldsArray();
			foreach($item->fieldgroupPrevious as $field) {
				if(!$item->fieldgroup->has($field)) {
					$removeFields->add($field); 
				}
			}
			if(count($removeFields)) { 
				$pages = $this->fuel('pages')->find("templates_id={$item->id}, check_access=0, status<" . Page::statusMax); 
				foreach($pages as $page) {
					foreach($removeFields as $field) {
						$field->type->deletePageField($page, $field); 
						if($this->fuel('config')->debug) $this->message("Removed field '$field' on page '{$page->url}'"); 
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
	 * Create and return a cloned copy of this template
	 *
	 * Note that this also clones the Fieldgroup if the template being cloned has it's own named fieldgroup.
	 *
	 * @param Saveable $item Item to clone
	 * @param bool|Saveable $item Returns the new clone on success, or false on failure
	 *
	 */
	public function ___clone(Saveable $item) {

		$original = $item;
		$item = clone $item; 

		if($item->flags & Template::flagSystem) {
			// we want to avoid creating clones that have system flags
			$item->flags = $item->flags | Template::flagSystemOverride; 
			$item->flags = $item->flags & ~Template::flagSystem;
			$item->flags = $item->flags & ~Template::flagSystemOverride;
		}

		$item->id = 0; // note this must be after removing system flags

		$fieldgroup = $item->fieldgroup; 

		if($fieldgroup->name == $item->name) {
			// if the fieldgroup and the item have the same name, we'll also clone the fieldgroup
			$fieldgroup = wire('fieldgroups')->clone($fieldgroup); 	
			$item->fieldgroup = $fieldgroup;
		}

		$item = parent::___clone($item);

		if($item && $item->id && !$item->altFilename) { 
			// now that we have a clone, lets also clone the template file, if it exists
			$path = $this->fuel('config')->paths->templates; 
			$file = $path . $item->name . '.' . $this->fuel('config')->templateExtension; 
			if($original->filenameExists() && is_writable($path) && !file_exists($file)) { 
				if(copy($original->filename, $file)) $item->filename = $file;
			}
		}

		return $item;
	}


	/**
	 * Return the number of pages using the provided Template
	 *
	 */
	public function getNumPages(Template $tpl) {
		$result = $this->fuel('db')->query("SELECT COUNT(*) AS total FROM pages WHERE templates_id=" . ((int) $tpl->id)); // QA
		$row = $result->fetch_assoc(); 
		$result->free();
		return (int) $row['total'];
	}

	/**
	 * Overridden from WireSaveableItems to retain specific keys
	 *
	 */
	protected function encodeData(array $value) {
		return wireEncodeJSON($value, array('slashUrls')); 	
	}

}

