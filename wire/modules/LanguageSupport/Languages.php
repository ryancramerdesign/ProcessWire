<?php

/**
 * ProcessWire Languages (plural) Class
 *
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com

/**
 * Class for managing Language-type pages
 *
 * Acts as the $this->languages API variable. 
 *
 */
class Languages extends PagesType {

	/**
	 * Reference to LanguageTranslator instance
	 *
	 */
	protected $translator = null;

	/**
	 * Integer ID of the default language page
	 *
	protected $defaultLanguagePageID; 
	 */

	/**
	 * Integer ID of the system language page
	 *
	protected $systemLanguagePageID;
	 */

	/**
	 * Set to true when languages have been loaded
	 *
	 * This is used to avoid potential circular references with autojoin fields. 
	 *
	protected $isReady = false;
	 */


	/**
	 * Construct this Languages PagesType
	 *
	 */
	public function __construct(Template $template, $parent_id) {
		parent::__construct($template, $parent_id); 
		//$this->getIterator();
		//$this->isReady = true; 
	}

	public function translator(Language $language) {
		if(is_null($this->translator)) $this->translator = new LanguageTranslator($language); 
			else $this->translator->setCurrentLanguage($language);
		return $this->translator; 
	}

	/**
	 * Get/find operations call loaded() for each page loaded
	 *
	 * We're piggybacking on this to determine when the languages have been loaded
	 *
	protected function loaded(Page $page) {
		if($page->id == $this->defaultLanguagePageID) $page->setIsDefaultLanguage();
		if($page->id == $this->systemLanguagePageID) $page->setIsSystemLanguage();
	}
	 */

	/**
	 * Hook called when new language added
	 *
	 */
	protected function ___added(Language $language) { 
	}

	/**
	 * Hook called when language deleted
	 *
	 */
	protected function ___deleted(Language $language) { 
	}

	/**
	 * Returns true when languages have been loaded
	 *
	 * This is used to avoid potential circular references with autojoin fields. 
	 *
	 * @return bool
	 *
	public function isReady() { return $this->isReady; }
	 */

	//public function setDefaultLanguagePageID($id) { $this->defaultLanguagePageID = $id; }
	//public function setSystemLanguagePageID($id) { $this->systemLanguagePageID = $id; }
}

