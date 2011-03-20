<?php

/**
 * ProcessWire Permission
 *
 * An individual Permission item for determining access in ProcessWire
 *
 * @TODO Documentation
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Permission extends WireData implements Saveable {

	public function __construct() {
		$this->set('id', 0); 
		$this->set('name', ''); 
		$this->set('summary', ''); 
		$this->set('modules_id', 0); 
	}

	public function set($key, $value) {
		if($key == 'id' || $key == 'modules_id') $value = (int) $value; 
			else if($key == 'name') $value = $this->fuel('sanitizer')->name($value); 
		return parent::set($key, $value); 
	}

	public function save() {
		$this->getFuel('permissions')->save($this); 
		return $this;
	}

	public function __toString() {
		return $this->name; 
	}

	public function getTableData() {
		return array(
			'id' => $this->id, 
			'name' => $this->name, 
			'summary' => $this->summary, 
			'modules_id' => $this->modules_id, 
			); 
	}

}
