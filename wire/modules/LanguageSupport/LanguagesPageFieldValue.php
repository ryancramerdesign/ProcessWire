<?php

/**
 * Serves as a multi-language value placeholder for field values that contain a value in more than one language. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class LanguagesPageFieldValue extends Wire {

	/**
	 * Values per language indexed by language ID
	 *
	 */
	protected $data = array();

	/**
	 * Cached ID of default language page
	 *
	 */
	protected $defaultLanguagePageID = 0;

	/**
	 * Construct the multi language value
	 *
 	 * @param array|string $values
	 *
	 */
	public function __construct($values = null) { // #98

		$languageSupport = wire('modules')->get('LanguageSupport');
		$this->defaultLanguagePageID = $languageSupport->defaultLanguagePageID; 

		if(!is_array($values)) $values = array('data' => $values); 

		if(array_key_exists('data', $values)) {
			$this->data[$this->defaultLanguagePageID] = $values['data']; 
		}

		foreach($languageSupport->otherLanguagePageIDs as $id) {
			$key = 'data' . $id; 	
			$value = empty($values[$key]) ? '' : $values[$key]; 
			$this->data[$id] = $value; 
		}
	}

	/**
	 * Sets the value for a given language
	 *
	 * @param int|Language $languageID
	 * @param mixed $value
	 *
	 */
	public function setLanguageValue($languageID, $value) {
		if(is_object($languageID) && $languageID instanceof Language) $languageID = $languageID->id; 
		$existingValue = isset($this->data[$languageID]) ? $this->data[$languageID] : '';
		if($value instanceof LanguagesPageFieldValue) {
			// to avoid potential recursion 
			$value = $value->getLanguageValue($languageID); 
		}
		if($value !== $existingValue) $this->trackChange('data'); 
		$this->data[(int)$languageID] = $value;
	}

	/**
	 * Given an Inputfield with multi language values, this grabs and populates the language values from it
	 *
	 * @param Inptufield $inputfield
	 *
	 */
	public function setFromInputfield(Inputfield $inputfield) {

		foreach(wire('languages') as $language) {
			if($language->isDefault) {
				$key = 'value';
			} else {
				$key = 'value' . $language->id; 
			}
			$this->setLanguageValue($language->id, $inputfield->$key); 
		}
	}

	/**
	 * Given a language, returns the value in that language
	 *
	 * @param Language|int
	 * @return int
	 *
	 */
	public function getLanguageValue($languageID) {
		if(is_object($languageID) && $languageID instanceof Language) $languageID = $languageID->id; 
		return isset($this->data[$languageID]) ? $this->data[$languageID] : '';
	}

	/**
	 * Returns the value in the default language
	 *
	 */
	public function getDefaultValue() {
		return $this->data[$this->defaultLanguagePageID];
	}

	/**
	 * The string value is the value in the current user's language
	 *
	 */
	public function __toString() {
		$language = wire('user')->language; 	
		if($language && $language->id && !empty($this->data[$language->id])) {
			return (string) $this->data[$language->id]; 
		} else {
			return (string) $this->data[$this->defaultLanguagePageID];
		}
	}
}

