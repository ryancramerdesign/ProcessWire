<?php

/**
 * ProcessWire Language (single) Page Class
 *
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
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
		if(is_null($tpl)) $tpl = wire('templates')->get('language');
		parent::__construct($tpl);
	}

	/**
	 * Get a value from the language page (intercepting translator and isDefault)
	 *
	 */
	public function get($key) {
		if($key == 'translator') return $this->translator();
		if($key == 'isDefault' || $key == 'isDefaultLanguage') return $this->isDefaultLanguage; 
		return parent::get($key); 
	}

	/**
	 * Return an instance of the translator prepared for this language
	 *
	 */
	public function translator() {
		return wire('languages')->translator($this); 
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
	 */
	public function isDefault() {
		return $this->isDefaultLanguage || $this->name == 'default'; 
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

