<?php namespace ProcessWire;

/**
 * ProcessWire Language (single) Page Class
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 *
 */

class Language extends Page {

	/**
	 * Whether this Language represents the default
	 *
	 */ 
	protected $isDefaultLanguage = false;

	/**
	 * Construct a new Language instance
	 *
	 */
	public function __construct(Template $tpl = null) {
		parent::__construct($tpl);
		if(is_null($tpl)) {
			$this->template = $this->wire('templates')->get('language');
		}
	}

	/**
	 * Get a value from the language page (intercepting translator and isDefault)
	 *
	 */
	public function get($key) {
		if($key == 'translator') return $this->translator();
		if($key == 'isDefault' || $key == 'isDefaultLanguage') return $this->isDefaultLanguage;
		if($key == 'isCurrent') return $this->isCurrent();
		return parent::get($key); 
	}

	/**
	 * Return an instance of the translator prepared for this language
	 *
	 */
	public function translator() {
		return $this->wire('languages')->translator($this); 
	}	

	/**
	 * Targets this as the default language
	 *
	 */
	public function setIsDefaultLanguage() { 
		$this->isDefaultLanguage = true; 
	}

	/**
	 * Returns whether this is the default language
	 * 
	 * @return bool
	 *
	 */
	public function isDefault() {
		return $this->isDefaultLanguage || $this->name == 'default'; 
	}

	/**
	 * Returns whether this is the current language
	 * 
	 * @return bool
	 * 
	 */
	public function isCurrent() {
		return $this->id == $this->wire('user')->language->id;
	}

	/**
	 * Return the API variable used for managing pages of this type
	 *
	 * @return Pages|PagesType
	 *
	 */
	public function getPagesManager() {
		return $this->wire('languages');
	}
}

