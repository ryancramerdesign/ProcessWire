<?php

/**
 * ProcessWire Template
 *
 * A template is a Page's connection to fields (via a Fieldgroup) and output TemplateFile.
 *
 * Templates also maintain several properties which can affect the render behavior of pages using it. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 * 
 * @property int $id Get or set the template's numbered database ID.
 * @property string $name Get or set the template's name.
 * @property string $filename Get or set a template's filename, including path (this is auto-generated from the name, though you may modify it at runtime if it suits your need).
 * @property string $label Optional short text label to describe Template.
 * @property Fieldgroup $fieldgroup Get or set a template's Fieldgroup. Can also be used to iterate a template's fields.
 * @property Fieldgroup $fields Syntactical alias for $template->fieldgroup. Use whatever makes more sense for your code readability.
 *
 */

class Template extends WireData implements Saveable {

	/**
	 * Flag used to indicate the field is a system-only field and thus can't be deleted or have it's name changed
	 *
	 */
	const flagSystem = 8; 

	/**
	 * Flag set if you need to override the system flag - set this first, then remove system flag in 2 operations. 
	 *
	 */
	const flagSystemOverride = 32768; 

	/**
	 * Cache expiration options: expire only page cache
	 *
	 */
	const cacheExpirePage = 0;

	/**
	 * Cache expiration options: expire entire site cache
	 *
	 */
	const cacheExpireSite = 1; 

	/**
	 * Cache expiration options: expire page and parents
	 *
	 */
	const cacheExpireParents = 2; 

	/**
	 * Cache expiration options: expire page and other specific pages (stored in cacheExpirePages)
	 *
	 */
	const cacheExpireSpecific = 3; 

	/**
	 * Cache expiration options: don't expire anything
	 *
	 */
	const cacheExpireNone = -1; 

	/**
	 * The PHP output filename used by this Template
	 *
	 */
	protected $filename;

	/**
	 * Does the PHP template file exist?
	 *
	 */
	protected $filenameExists = null; 
	 
	/**
	 * The Fieldgroup instance assigned to this Template
	 *
	 */
	protected $fieldgroup; 

	/**
	 * The previous Fieldgroup instance assigned to this template, if changed during runtime
	 *
	 */
	protected $fieldgroupPrevious = null; 

	/**
	 * Roles that pages using this template support
	 *
	 */
	protected $_roles = null;

	/**
	 * The template's settings, as they relate to database schema
	 *
	 */
	protected $settings = array(
		'id' => 0, 
		'name' => '', 
		'fieldgroups_id' => 0, 
		'flags' => 0,
		'cache_time' => 0, 
		); 

	/**
	 * Array where get/set properties are stored
	 *
	 */
	protected $data = array(
		'useRoles' => 0, 		// does this template define access?
		'editRoles' => array(),		// IDs of roles that may edit pages using this template
		'addRoles' => array(),		// IDs of roles that may add children to pages using this template
		'createRoles' => array(),	// IDs of roles that may create pages using this template
		'childrenTemplatesID' => 0, 	// template ID for child pages, or -1 if no children allowed. DEPRECATED
		'sortfield' => '',		// Field that children of templates using this page should sort by. blank=page decides or 'sort'=manual drag-n-drop
		'noChildren' => '', 		// set to 1 to cancel use of childTemplates
		'noParents' => '', 		// set to 1 to cancel use of parentTemplates
		'childTemplates' => array(),	// array of template IDs that are allowed for children. blank array = any. 
		'parentTemplates' => array(),	// array of template IDs that are allowed for parents. blank array = any.
		'allowPageNum' => 0, 		// allow page numbers in URLs?
		'allowChangeUser' => 0,		// allow the createdUser/created_users_id field of pages to be changed? (with API or in admin w/superuser only)
		'redirectLogin' => 0, 		// redirect when no access: 0 = 404, 1 = login page, 'url' = URL to redirec to
		'urlSegments' => 0,		// allow URL segments on pages?
		'https' => 0, 			// use https? 0 = http or https, 1 = https only, -1 = http only
		'slashUrls' => 1, 		// page URLs should have a trailing slash? 1 = yes, 0 = no	
		'altFilename' => '',		// alternate filename for template file, if not based on template name
		'guestSearchable' => 0, 	// pages appear in search results even when user doesn't have access?
		'pageClass' => '', 		// class for instantiated page objects. 'Page' assumed if blank, or specify class name. 
		'pageLabelField' => '',		// CSV or space separated string of field names to be displayed by ProcessPageList (overrides those set with ProcessPageList config)
		'noGlobal' => 0, 		// template should ignore the 'global' option of fields?
		'noMove' => 0,			// pages using this template are not moveable?
		'noTrash' => 0,			// pages using thsi template may not go in trash? (i.e. they will be deleted not trashed)
		'noSettings' => 0, 		// don't show a 'settings' tab on pages using this template?
		'noChangeTemplate' => 0, 	// don't allow pages using this template to change their template?
		'noUnpublish' => 0,		// don't allow pages using this template to ever exist in an unpublished state - if page exists, it must be published 
		'nameContentTab' => 0, 		// pages should display the 'name' field on the content tab?	
		'noCacheGetVars' => '',		// GET vars that trigger disabling the cache (only when cache_time > 0)
		'noCachePostVars' => '',	// POST vars that trigger disabling the cache (only when cache_time > 0)
		'useCacheForUsers' => 0, 	// use cache for: 0 = only guest users, 1 = guests and logged in users
		'cacheExpire' => 0, 		// expire the cache for all pages when page using this template is saved? (1 = yes, 0 = no- only current page)
		'cacheExpirePages' => array(),	// array of Page IDs that should be expired, when cacheExpire == Template::cacheExpireSpecific
		'label' => '',			// label that describes what this template is for (optional)
		'tags' => '',			// optional tags that can group this template with others in the admin templates list 
		); 


	/**
	 * Get a Template property
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {

		if($key == 'filename') return $this->filename();
		if($key == 'fields') $key = 'fieldgroup';
		if($key == 'fieldgroup') return $this->fieldgroup; 
		if($key == 'fieldgroupPrevious') return $this->fieldgroupPrevious; 
		if($key == 'roles') return $this->getRoles();
		if($key == 'cacheTime') $key = 'cache_time'; // for camel case consistency

		return isset($this->settings[$key]) ? $this->settings[$key] : parent::get($key); 
	}

	/**
	 * Get the role pages that are part of this template
	 *
	 * This method returns a blank PageArray if roles haven't yet been loaded into the template. 
	 * If the roles have previously been loaded as an array, then this method converts that array to a PageArray and returns it. 
	 *
	 * @return PageArray
	 *
	 */
	protected function getRoles() {

		if(is_null($this->_roles)) {
			return new PageArray();

		} else if($this->_roles instanceof PageArray) {
			return $this->_roles;
		
		} else if(is_array($this->_roles)) {
			$roles = new PageArray();
			if(count($this->_roles)) {
				$roles->import($this->pages->getById($this->_roles)); 
			}
			$this->_roles = $roles;
			return $this->_roles;
		} else {
			return new PageArray();
		}
	}

	/**
	 * Does this template have the given role name or page?
	 *
	 * @param string|Page $role Name of role or page object representing it. 
	 * @return bool
	 *
	 */
	public function hasRole($role) {
		$roles = $this->getRoles();
		if(is_string($role)) return $roles->has("name=$role"); 
		if($role instanceof Page) return $roles->has($role); 
		return false;
	}

	/**
	 * Given an array of page IDs or a PageArray, sets the roles for this template
	 *
	 * @param array|PageArray $value
	 *
	 */
	protected function setRoles($value) {
		if(is_array($value) || $value instanceof PageArray) {
			$this->_roles = $value;		
		}
	}

	/**
	 * Does this template have the given field?
	 *
	 * @param string|int|Field
	 * @return bool
	 *
	 */
	public function hasField($name) {
		return $this->fieldgroup->hasField($name);
	}

	/**
	 * Set a Template property
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return this
	 *
	 */
	public function set($key, $value) {

		if($key == 'flags') { 
			$this->setFlags($value); 

		} else if(isset($this->settings[$key])) { 

			if($key == 'id') $value = (int) $value; 
				else if($key == 'name') $value = $this->fuel('sanitizer')->name($value); 
				else if($key == 'fieldgroups_id') return $this->setFieldgroup($this->getFuel('fieldgroups')->get($value)); 
				else if($key == 'cache_time' || $key == 'cacheTime') $value = (int) $value; 
				else $value = '';

			if($this->settings[$key] != $value) {
				if($this->settings[$key] && ($this->settings['flags'] & Template::flagSystem) && in_array($key, array('id', 'name'))) {
					throw new WireException("Template '$this' has the system flag and you may not change it's 'id' or 'name' fields. "); 
				}
				$this->trackChange($key); 
			}
			$this->settings[$key] = $value; 

		} else if($key == 'fieldgroup' || $key == 'fields') {
			$this->setFieldgroup($value); 

		} else if($key == 'filename') {
			$this->setFilename($value); 

		} else if($key == 'roles') {
			$this->setRoles($value);

		} else if($key == 'childrenTemplatesID') { // this can eventaully be removed
			if($value < 0) {
				parent::set('noChildren', 1);
			} else if($value) {
				$v = $this->childTemplates; 
				$v[] = (int) $value; 
				parent::set('childTemplates', $v);
			}

		} else if($key == 'sortfield') {
			$value = $this->fuel('pages')->sortfields()->decode($value, '');	
			parent::set($key, $value); 

		} else if(in_array($key, array('addRoles', 'editRoles', 'createRoles', 'childTemplates', 'parentTemplates'))) {
			if(!is_array($value)) $value = array();
			foreach($value as $k => $v) $value[(int)$k] = (int) $v; 
			parent::set($key, $value); 

		} else if(in_array($key, array('noChildren', 'noParents'))) {
			$value = (int) $value;
			if(!$value) $value = null; // enforce null over 0
			parent::set($key, $value);

		} else {
			parent::set($key, $value); 
		}

		return $this; 
	}

	/**
	 * Set the flags property and prevent the system flag from being removed
	 *
	 * @param int $value
	 *
	 */
	protected function setFlags($value) {
		$value = (int) $value;
		$override = $this->settings['flags'] & Template::flagSystemOverride; 
		if($this->settings['flags'] & Template::flagSystem) {
			// prevent the system flag from being removed
			if(!$override) $value = $value | Template::flagSystem; 
		}
		$this->settings['flags'] = $value; 
	}


	/**
	 * Set this template's filename, with or without path
	 *
	 * @param string $value The filename with or without path
	 *
	 */
	protected function setFilename($value) {
		if(empty($value)) return; 

		if(strpos($value, '/') === false) {
			$value = $this->config->paths->templates . $value;

		} else if(strpos($value, $this->config->paths->root) !== 0) {
			$value = $this->config->paths->templates . basename($value); 
		}

		if(is_file($value)) {
			$this->filename = $value; 
			$this->filenameExists = true; 
		}
	}

	/**
	 * Set this Template's Fieldgroup
	 *
	 * @param Fieldgroup $fieldgroup
	 * @return this
	 *
	 */
	public function setFieldgroup(Fieldgroup $fieldgroup) {

		if(is_null($this->fieldgroup) || $fieldgroup->id != $this->fieldgroup->id) $this->trackChange('fieldgroup'); 

		if($this->fieldgroup && $fieldgroup->id != $this->fieldgroup->id) {
			// save record of the previous fieldgroup so that unused fields can be deleted during save()
			$this->fieldgroupPrevious = $this->fieldgroup; 

			if($this->flags & Template::flagSystem) 
				throw new WireException("Can't change fieldgroup for template '{$this}' because it is a system template."); 

			$hasPermanentFields = false;
			foreach($this->fieldgroup as $field) {
				if($field->flags & Field::flagPermanent) $hasPermanentFields = true; 
			}
			if($this->id && $hasPermanentFields) throw new WireException("Fieldgroup for template '{$this}' may not be changed because it has permanent fields."); 
		}

		$this->fieldgroup = $fieldgroup;
		$this->settings['fieldgroups_id'] = $fieldgroup->id; 
		return $this; 
	}

	/**
	 * Return the number of pages used by this template. 
	 *
	 * @return int
	 *
	 */
	public function getNumPages() {
		return Wire::getFuel('templates')->getNumPages($this); 	
	}

	/**
	 * Save the template to database
	 *
	 * @return this|bool Returns Template if successful, or false if not
	 *
	 */
	public function save() {

		$result = Wire::getFuel('templates')->save($this); 	

		return $result ? $this : false; 
	}

	/**
	 * Return corresponding template filename, including path
	 *
	 * @return string
	 *	
	 */
	public function filename() {

		if($this->filename) return $this->filename; 

		if(!$this->settings['name']) throw new WireException("Template must be assigned a name before 'filename' can be accessed"); 

		if($this->altFilename) {
			$altFilename = $this->fuel('templates')->path . basename($this->altFilename, "." . $this->config->templateExtension) . "." . $this->config->templateExtension; 
			$this->filename = $altFilename; 
		} else {
			$this->filename = $this->fuel('templates')->path . $this->settings['name'] . '.' . $this->config->templateExtension;
		}
		return $this->filename;
	}

	/**
	 * Does the template filename exist?
	 *
	 * @return string
	 *	
	 */
	public function filenameExists() {
		if(!is_null($this->filenameExists)) return $this->filenameExists; 
		$this->filenameExists = is_file($this->filename()); 
		return $this->filenameExists; 
	}

	/**
	 * Per Saveable interface, get an array of this table's data
	 *
	 * We override this so that we can add our roles array to it. 
	 *
	 */
	public function getArray() {
		$a = parent::getArray();

		if($this->useRoles) { 
			$a['roles'] = array();	
			foreach($this->getRoles() as $role) {
				$a['roles'][] = $role->id;
			}
		} else {
			unset($a['roles'], $a['editRoles'], $a['addRoles'], $a['createRoles']); 
		}

		return $a;
	}

	/**
	 * Per Saveable interface
	 *
	 */
	public function getTableData() {

		$tableData = $this->settings; 
		$data = $this->getArray();
		// ensure sortfield is a signed integer or native name, rather than a custom fieldname
		if(!empty($data['sortfield'])) $data['sortfield'] = $this->fuel('pages')->sortfields()->encode($data['sortfield'], ''); 
		$tableData['data'] = $data; 
		
		return $tableData; 
	}

	/**
	 * The string value of a Template is always it's name
	 *
	 */
	public function __toString() {
		return $this->name; 
	}



}



