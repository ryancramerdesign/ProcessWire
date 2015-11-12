<?php

/**
 * ProcessWire Templates
 *
 * Manages and provides access to all the Template instances
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
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
	 * @param Saveable|Template $item 
	 * @return bool true on success
	 * @throws WireException
	 *
	 */
	public function ___save(Saveable $item) {

		$isNew = $item->id < 1; 

		if(!$item->fieldgroup) throw new WireException("Template '$item' cannot be saved because it has no fieldgroup assigned"); 
		if(!$item->fieldgroup->id) throw new WireException("You must save Fieldgroup '{$item->fieldgroup->name}' before adding to Template '{$item}'"); 

		$rolesChanged = $item->isChanged('useRoles');

		if($this->wire('pages')->get("/")->template->id == $item->id) {
			if(!$item->useRoles) throw new WireException("Template '{$item}' is used by the homepage and thus must manage access"); 
			if(!$item->hasRole("guest")) throw new WireException("Template '{$item}' is used by the homepage and thus must have the 'guest' role assigned."); 
		}
		
		if(!$item->isChanged('modified')) $item->modified = time();

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
				foreach($removeFields as $field) {
					$field->type->deleteTemplateField($item, $field); 
				}
				/*
				$pages = $this->fuel('pages')->find("templates_id={$item->id}, check_access=0, status<" . Page::statusMax); 
				foreach($pages as $page) {
					foreach($removeFields as $field) {
						$field->type->deletePageField($page, $field); 
						if($this->fuel('config')->debug) $this->message("Removed field '$field' on page '{$page->url}'"); 
					}
				}
				*/
			}
		}

		if($rolesChanged) { 
			$access = new PagesAccess();
			$access->updateTemplate($item); 
		}
		
		$this->wire('cache')->maintenance($item);

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

		$return = parent::___delete($item);
		$this->wire('cache')->maintenance($item); 
		return $return;
	}

	/**
	 * Create and return a cloned copy of this template
	 *
	 * Note that this also clones the Fieldgroup if the template being cloned has it's own named fieldgroup.
	 * 
	 * @todo: clone the fieldgroup context settings too. 
	 *
	 * @param Template|Saveable $item Item to clone
	 * @param string $name
	 * @return bool|Saveable|Template $item Returns the new clone on success, or false on failure
	 *
	 */
	public function ___clone(Saveable $item, $name = '') {

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
			$fieldgroup = $this->wire('fieldgroups')->clone($fieldgroup, $name); 	
			$item->fieldgroup = $fieldgroup;
		}

		$item = parent::___clone($item, $name);

		if($item && $item->id && !$item->altFilename) { 
			// now that we have a clone, lets also clone the template file, if it exists
			$path = $this->wire('config')->paths->templates; 
			$file = $path . $item->name . '.' . $this->wire('config')->templateExtension; 
			if($original->filenameExists() && is_writable($path) && !file_exists($file)) { 
				if(copy($original->filename, $file)) $item->filename = $file;
			}
		}

		return $item;
	}


	/**
	 * Return the number of pages using the provided Template
	 * 
	 * @param Template $tpl
	 * @return int
	 *
	 */
	public function getNumPages(Template $tpl) {
		$database = $this->wire('database');
		$query = $database->prepare("SELECT COUNT(*) AS total FROM pages WHERE templates_id=:template_id"); // QA
		$query->bindValue(":template_id", $tpl->id, PDO::PARAM_INT);
		$query->execute();
		return (int) $query->fetchColumn();	
	}

	/**
	 * Overridden from WireSaveableItems to retain specific keys
	 *
	 */
	protected function encodeData(array $value) {
		return wireEncodeJSON($value, array('slashUrls')); 	
	}

	/**
	 * Return data for external storage
	 * 
	 * @param Template $template
	 * @return array
	 *
	 */
	public function ___getExportData(Template $template) {

		$template->set('_exportMode', true); 
		$data = $template->getTableData();

		// flatten
		foreach($data['data'] as $key => $value) {
			$data[$key] = $value;
		}

		// remove unnecessary
		unset($data['data'], $data['modified']);

		// convert fieldgroup to guid
		$fieldgroup = $this->wire('fieldgroups')->get((int) $data['fieldgroups_id']);
		if($fieldgroup) $data['fieldgroups_id'] = $fieldgroup->name;

		// convert family settings to guids
		foreach(array('parentTemplates', 'childTemplates') as $key) {
			if(!isset($data[$key])) continue;
			$values = array();
			foreach($data[$key] as $id) {
				if(ctype_digit("$id")) $id = (int) $id;
				$t = $this->wire('templates')->get($id);
				if($t) $values[] = $t->name;
			}
			$data[$key] = $values;
		}

		// convert roles to guids
		if($template->useRoles) {
			foreach(array('roles', 'editRoles', 'addRoles', 'createRoles') as $key) {
				if(!isset($data[$key])) continue;
				$values = array();
				foreach($data[$key] as $id) {
					$role = $id instanceof Role ? $id : $this->wire('roles')->get((int) $id);
					$values[] = $role->name;
				}
				$data[$key] = $values;
			}
		}

		// convert pages to guids
		if(((int) $template->cache_time) != 0) {
			if(!empty($data['cacheExpirePages'])) {
				$values = array();
				foreach($data['cacheExpirePages'] as $id) {
					$page = $this->wire('pages')->get((int) $id);
					if(!$page->id) continue;
					$values[] = $page->path;
				}
			}
		}

		$fieldgroupData = array('fields' => array(), 'contexts' => array());
		if($template->fieldgroup) $fieldgroupData = $template->fieldgroup->getExportData();
		$data['fieldgroupFields'] = $fieldgroupData['fields'];
		$data['fieldgroupContexts'] = $fieldgroupData['contexts'];

		$template->set('_exportMode', false); 
		return $data;
	}

	/**
	 * Given an array of export data, import it to the given template
	 *
	 * @param Template $template
	 * @param array $data
	 * @return bool True if successful, false if not
	 * @return array Returns array(
	 * 	[property_name] => array(
	 * 		'old' => 'old value', // old value (in string comparison format)
	 * 		'new' => 'new value', // new value (in string comparison format)
	 * 		'error' => 'error message or blank if no error'  // error message (string) or messages (array)
	 * 		)
	 *
	 */
	public function ___setImportData(Template $template, array $data) {

		$template->set('_importMode', true); 
		$fieldgroupData = array();
		$changes = array();
		$_data = $this->getExportData($template);

		if(isset($data['fieldgroupFields'])) $fieldgroupData['fields'] = $data['fieldgroupFields'];
		if(isset($data['fieldgroupContexts'])) $fieldgroupData['contexts'] = $data['fieldgroupContexts'];
		unset($data['fieldgroupFields'], $data['fieldgroupContexts'], $data['id']);

		foreach($data as $key => $value) {
			if($key == 'fieldgroups_id' && !ctype_digit("$value")) {
				$fieldgroup = $this->wire('fieldgroups')->get($value);
				if(!$fieldgroup) {
					$fieldgroup = new Fieldgroup();
					$fieldgroup->name = $value;
				}
				$oldValue = $template->fieldgroup ? $template->fieldgroup->name : '';
				$newValue = $fieldgroup->name;
				$error = '';
				try {
					$template->setFieldgroup($fieldgroup);
				} catch(Exception $e) {
					$this->trackException($e, false);
					$error = $e->getMessage();
				}
				if($oldValue != $fieldgroup->name) {
					if(!$fieldgroup->id) $newValue = "+$newValue";
					$changes['fieldgroups_id'] = array(
						'old' => $template->fieldgroup->name,
						'new' => $newValue,
						'error' => $error
					);
				}
			}

			$template->errors("clear");
			$oldValue = isset($_data[$key]) ? $_data[$key] : '';
			$newValue = $value;
			if(is_array($oldValue)) $oldValue = wireEncodeJSON($oldValue, true, false);
			else if(is_object($oldValue)) $oldValue = (string) $oldValue;
			if(is_array($newValue)) $newValue = wireEncodeJSON($newValue, true, false);
			else if(is_object($newValue)) $newValue = (string) $newValue;

			// everything else
			if($oldValue == $newValue || (empty($oldValue) && empty($newValue))) {
				// no change needed
			} else {
				// changed
				try {
					$template->set($key, $value);
					if($key == 'roles') $template->getRoles(); // forces reload of roles (and resulting error messages)
					$error = $template->errors("clear");
				} catch(Exception $e) {
					$this->trackException($e, false);
					$error = array($e->getMessage());
				}
				$changes[$key] = array(
					'old' => $oldValue,
					'new' => $newValue,
					'error' => (count($error) ? $error : array())
				);
			}
		}

		if(count($fieldgroupData)) {
			$_changes = $template->fieldgroup->setImportData($fieldgroupData);
			if($_changes['fields']['new'] != $_changes['fields']['old']) {
				$changes['fieldgroupFields'] = $_changes['fields'];
			}
			if($_changes['contexts']['new'] != $_changes['contexts']['old']) {
				$changes['fieldgroupContexts'] = $_changes['contexts'];
			}
		}

		$template->errors('clear');
		$template->set('_importMode', false); 

		return $changes;
	}

	/**
	 * Return the parent page that this template assumes new pages are added to
	 *
	 * This is based on family settings, when applicable.
	 * It also takes into account user access, if requested (see arg 1).
	 *
	 * If there is no shortcut parent, NULL is returned.
	 * If there are multiple possible shortcut parents, a NullPage is returned.
	 *
	 * @param Template $template
	 * @param bool $checkAccess Whether or not to check for user access to do this (default=false).
	 * @param bool $getAll Specify true to return all possible parents (makes method always return a PageArray)
	 * @return Page|NullPage|null|PageArray
	 *
	 */
	public function getParentPage(Template $template, $checkAccess = false, $getAll = false) {
		
		$foundParent = null;
		$foundParents = $getAll ? new PageArray() : null;

		if($template->noShortcut || !count($template->parentTemplates)) return $foundParents;
		if($template->noParents == -1) {
			// only 1 page of this type allowed 
			if($this->getNumPages($template) > 0) return $foundParents;
		} else if($template->noParents == 1) {
			return $foundParents; 
		}

		foreach($template->parentTemplates as $parentTemplateID) {

			$parentTemplate = $this->wire('templates')->get((int) $parentTemplateID);
			if(!$parentTemplate) continue;

			// if the parent template doesn't have this as an allowed child template, exclude it 
			if($parentTemplate->noChildren) continue;
			if(!in_array($template->id, $parentTemplate->childTemplates)) continue;

			// sort=status ensures that a non-hidden page is given preference to a hidden page
			$include = $checkAccess ? "unpublished" : "all";
			$selector = "templates_id=$parentTemplate->id, include=$include, sort=status";
			if(!$getAll) $selector .= ", limit=2";
			$parentPages = $this->wire('pages')->find($selector);
			$numParentPages = count($parentPages);

			// undetermined parent
			if(!$numParentPages) continue;

			if($getAll) {
				if($numParentPages) $foundParents->add($parentPages);
				continue;
			} else if($numParentPages > 1) {
				// multiple possible parents
				$parentPage = new NullPage();
			} else {
				// one possible parent
				$parentPage = $parentPages->first();
			}

			if($checkAccess) {
				if($parentPage->id) {
					// single defined parent
					$p = new Page();
					$p->template = $template;
					if(!$parentPage->addable($p)) continue;
				} else {
					// multiple possible parents
					if(!$this->wire('user')->hasPermission('page-create', $template)) continue;
				}
			}

			$foundParent = $parentPage;
			break;
		}
		
		if($checkAccess && $foundParents && $foundParents->count()) {
			$p = new Page();
			$p->template = $template; 
			foreach($foundParents as $parentPage) {
				if(!$parentPage->addable($p)) $foundParents->remove($parentPage);
			}
		}
		
		if($getAll) return $foundParents;
		return $foundParent;
	}

	/**
	 * Return all possible parent pages for the given template, if predefined
	 * 
	 * @param Template $template
	 * @param bool $checkAccess Specify true to exclude parent pages that user doesn't have access to add pages to (default=false)
	 * @return PageArray
	 * 
	 */
	public function getParentPages(Template $template, $checkAccess = false) {
		return $this->getParentPage($template, $checkAccess, true);
	}


}

