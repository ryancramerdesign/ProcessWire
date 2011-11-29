<?php

class LanguagesPageFieldValue extends Wire {

	protected $data = array();
	protected $defaultLanguagePageID = 0;

	public function __construct(array $values = null) {

		if(!is_array($values)) $values = array($values); 
		$languages = wire('languages'); 

		foreach($languages as $language) {
			
			if($language->isDefault || $language->id == 1987) {
				$key = 'data';
				$this->defaultLanguagePageID = $language->id;
			} else {
				$key = 'data' . $language->id; 
			}

			$value = empty($values[$key]) ? '' : $values[$key]; 
			$this->data[$language->id] = $value; 
		} 
	}

	public function setLanguageValue($languageID, $value) {
		$existingValue = isset($this->data[$languageID]) ? $this->data[$languageID] : '';
		if($this->trackChanges() && $value !== $existingValue) $this->trackChange('data'); 
		$this->data[(int)$languageID] = $value;
	}

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

	public function getLanguageValue($languageID) {
		return isset($this->data[$languageID]) ? $this->data[$languageID] : '';
	}

	public function getDefaultValue() {
		return $this->data[$this->defaultLanguagePageID];
	}

	public function __toString() {
		$language = wire('user')->language; 	
		if($language && $language->id && !empty($this->data[$language->id])) {
			return $this->data[$language->id]; 
		} else {
			return $this->data[$this->defaultLanguagePageID];
		}
	}
}

