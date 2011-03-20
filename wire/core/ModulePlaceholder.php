<?php

/**
 * ProcessWire ModulePlaceholder
 *
 * Holds the place for a Module until it is included and instantiated.
 * As used by the Modules class. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class ModulePlaceholder extends WireData implements Module {

	protected $class = '';
	protected $moduleInfo = array();

	public function __construct() {
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

	public function className() {
		return $this->class; 
	}

}

