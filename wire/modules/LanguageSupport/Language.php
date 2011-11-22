<?php

/**
 * ProcessWire Language (single) Class
 *
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * Language Page type
 *
 */
class Language extends Page {
	
	protected $isDefaultLanguage = false;
	protected $isSystemLanguage = false;

	public function __construct(Template $tpl = null) {
		if(is_null($tpl)) $tpl = wire('templates')->get('language');
		// $this->parent = $this->fuel('pages')->get($this->fuel('config')->rolesPageID);
		parent::__construct($tpl);
	}

	public function get($key) {
		if($key == 'translator') return $this->translator();
		if($key == 'isDefault' || $key == 'isDefaultLanguage') return $this->isDefaultLanguage; 
		if($key == 'isSystem' || $key == 'isSystemLanguage') return $this->isSystemLanguage; 
		return parent::get($key); 
	}

	public function translator() {
		return wire('languages')->translator($this); 
	}	

	public function setIsDefaultLanguage() { $this->isDefaultLanguage = true; }
	public function setIsSystemLanguage() { $this->isSystemLanguage = true; }
}

