<?php

/**
 * ProcessWire Template
 *
 * A template is a Page's connection to fields (via a Fieldgroup) and output TemplateFile.
 *
 * Templates also maintain several properties which can affect the render behavior of pages using it. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Template extends WireData implements Saveable {

	/**
	 * Flag used to indicate the field is a system-only field and thus can't be deleted or have it's name changed
	 *
	 */
	const flagSystem = 8; 

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
		if($this->settings['flags'] & Template::flagSystem) {
			// prevent the system flag from being removed
			$value = $value | Template::flagSystem; 
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

		if(is_file($value)) $this->filename = $value; 
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

		// remove reference to fields with empty values
		foreach($a as $k => $v) if(empty($v)) unset($a[$k]); 

		$a['roles'] = array();	
		foreach($this->getRoles() as $role) {
			$a['roles'][] = $role->id;
		}
		return $a;
	}

	/**
	 * Per Saveable interface
	 *
	 */
	public function getTableData() {

		$tableData = $this->settings; 
		$tableData['data'] = $this->getArray();
		
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



