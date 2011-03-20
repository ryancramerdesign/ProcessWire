<?php

/**
 * ProcessWire Fieldtypes
 *
 * Maintains a collection of Fieldtype modules.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
class Fieldtypes extends WireArray {

	/**
	 * Instance of Modules class
	 *
	 */
	protected $modules; 

	/**
	 * Construct this Fieldtypes object and load all Fieldtype modules 
	 *
 	 */
	public function init() {
		$this->modules = $this->getFuel('modules'); 
		$fieldtypes = $this->modules->find('className^=Fieldtype'); 
		foreach($fieldtypes as $fieldtype) $this->add($fieldtype); 
	}

	/**
	 * Per WireArray interface, items added to Fieldtypes must be Fieldtype instances
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Fieldtype || $item instanceof ModulePlaceholder; 
	}

	/**
	 * Per the WireArray interface, keys must be strings (field names)
	 *
	 */
	public function isValidKey($key) {
		return is_string($key); 
	}

	/**
	 * Per the WireArray interface, Fields are indxed by their name
	 *
	 */
	public function getItemKey($item) {
		return $item->className();
	}

	/**
	 * Does this WireArray use numeric keys only? 
	 *
	 * @return bool
	 *
	 */
	protected function usesNumericKeys() {
		return false;
	}


	/**
	 * Per the WireArray interface, return a blank copy
	 *
	 * Since Fieldtype is abstract, there is nothing but NULL to return here
	 *
	 */
	public function makeBlankItem() {
		return null; 
	}

	/**
	 * Given a Fieldtype name (or class name) return the instantiated Fieldtype module. 
	 *
	 * If the requested Fieldtype is not already installed, it will be installed here automatically. 
	 *
	 * @param string $key Fieldtype name or class name, or dynamic property of Fieldtypes
	 * @return Fieldtype|null 
	 *
	 */
	public function get($key) {

		if(strpos($key, 'Fieldtype') !== 0) $key = "Fieldtype" . ucfirst($key); 

		if(!$fieldtype = parent::get($key)) {
			$fieldtype = $this->modules->get($key); 
		}

		if($fieldtype instanceof ModulePlaceholder) {
			$fieldtype = $this->modules->get($fieldtype->className()); 			
			$this->set($key, $fieldtype); 
		}

		return $fieldtype; 
	}
}


