<?php

/**
 * ProcessWire ModulePlaceholder
 *
 * Holds the place for a Module until it is included and instantiated.
 * As used by the Modules class. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://.processwire.com
 * 
 * @property bool $autoload
 * @property bool $singular
 * @property string $file
 * @property string $className
 * @property string $class alias of className
 * @property string $name alias of className
 *
 */

class ModulePlaceholder extends WireData implements Module {

	protected $class = '';
	protected $moduleInfo = array();

	public function __construct() {
		$this->set('autoload', false); 
		$this->set('singular', true); 
		$this->set('file', ''); 
	}

	static public function getModuleInfo() {
		return array(
			'title' => 'ModulePlaceholder: call $modules->get(class) to replace this placeholder.',  
			'version' => 0, 
			'summary' => '', 
			);
	}

	public function init() { }
	public function ___install() { }
	public function ___uninstall() { }

	public function setClass($class) {
		$this->class = $class; 
	}

	public function get($key) {
		if($key == 'className' || $key == 'class' || $key == 'name') return $this->class;
		return parent::get($key); 
	}

	public function isSingular() {
		return $this->singular; 
	}

	public function isAutoload() {
		return false; 
	}

	public function className($options = null) {
		return $this->class; 
	}

}

