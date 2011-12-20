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
	 * Construct this Languages PagesType
	 *
	 */
	public function __construct(Template $template, $parent_id) {
		parent::__construct($template, $parent_id); 
	}

	/**
	 * Return the LanguageTranslator instance for the given language
	 *
	 */
	public function translator(Language $language) {
		if(is_null($this->translator)) $this->translator = new LanguageTranslator($language); 
			else $this->translator->setCurrentLanguage($language);
		return $this->translator; 
	}

	/**
	 * Returns ALL languages, including those in the trash or unpublished, etc. (inactive)
	 *
	 * Note: to get all active languages, just iterate the $languages API var. 
	 *
	 */
	public function getAll() {
		return $this->pages->find("template={$this->template->name}, include=all"); 
	}

	/**
	 * Hook called when new language added
	 *
	 */
	protected function ___added(Page $language) { 
	}

	/**
	 * Hook called when language deleted
	 *
	 */
	protected function ___deleted(Page $language) { 
	}


	public function getIterator() {
		return $this->find("id>0, sort=sort");
	}


}

