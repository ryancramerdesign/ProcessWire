<?php

/**
 * ProcessWire Languages (plural) Class
 * 
 * Class for managing Language-type pages.
 * Acts as the $wire->languages API variable. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class Languages extends PagesType {

	/**
	 * Reference to LanguageTranslator instance
	 *
	 */
	protected $translator = null;

	/**
	 * Cached all published languages (for getIterator)
	 *
	 * We cache them so that the individual language pages persist through saves.
	 *
	 */
	protected $languages = null;

	/**
	 * Cached all languages including unpublished (for getAll)
	 *
	 */
	protected $languagesAll = null;

	/**
	 * Saved reference to default language
	 * 
	 */
	protected $defaultLanguage = null;

	/**
	 * Saved language from a setDefault() call
	 * 
	 */
	protected $savedLanguage = null;

	/**
	 * Return the LanguageTranslator instance for the given language
	 * 
	 * @param Language $language
	 * @return LanguageTranslator
	 *
	 */
	public function translator(Language $language) {
		if(is_null($this->translator)) $this->translator = new LanguageTranslator($language); 
			else $this->translator->setCurrentLanguage($language);
		return $this->translator; 
	}
	
	public function getPageClass() {
		return 'Language';
	}
	
	public function getLoadOptions(array $loadOptions = array()) {
		$loadOptions = parent::getLoadOptions($loadOptions);
		$loadOptions['autojoin'] = false;
		return $loadOptions; 
	}
	
	public function getJoinFieldNames() {
		return array();
	}

	/**
	 * Returns ALL languages, including those in the trash or unpublished, etc. (inactive)
	 *
	 * Note: to get all active languages, just iterate the $languages API var. 
	 *
	 */
	public function getAll() {
		if($this->languagesAll) return $this->languagesAll;
		$template = $this->getTemplate();
		$parent_id = $this->getParentID();
		$selector = "parent_id=$parent_id, template=$template, include=all";
		$languagesAll = $this->wire('pages')->find($selector, array(
				'loadOptions' => $this->getLoadOptions(), 
			)
		); 
		if(count($languagesAll)) $this->languagesAll = $languagesAll;
		return $languagesAll;
	}

	/**
	 * Enable iteration of this class
	 *
	 */
	public function getIterator() {
		if($this->languages && count($this->languages)) return $this->languages; 
		$languages = new PageArray();
		foreach($this->getAll() as $language) { 
			if($language->is(Page::statusUnpublished) || $language->is(Page::statusHidden)) continue; 
			$languages->add($language); 
		}
		if(count($languages)) $this->languages = $languages;
		return $languages; 
	}

	/**
	 * Get the default language
	 * 
	 * @return Language
	 * @throws WireException when default language hasn't been set
	 * 
	 */
	public function getDefault() {
		if(!$this->defaultLanguage) throw new WireException('Default language not yet set');
		return $this->defaultLanguage; 	
	}

	/**
	 * Set default language (if given a $language) OR set current user to have default language if no arguments given
	 * 
	 * If called with no arguments, it should later be followed up with an unsetDefault() call to restore language setting.
	 * 
	 * @param Language $language
	 * 
	 */
	public function setDefault(Language $language = null) {
		if(is_null($language)) {
			// save current user language setting and make current language default
			if(!$this->defaultLanguage) return;
			$user = $this->wire('user');
			if($user->language->id == $this->defaultLanguage->id) return; // already default
			$this->savedLanguage = $user->language;
			$user->language = $this->defaultLanguage; 
		} else {
			// set what language is the default
			$this->defaultLanguage = $language; 
		}
	}

	/**
	 * Switch back to previous language
	 * 
	 * Should only be called after a previous setDefault(null) call. 
	 * 
	 */
	public function unsetDefault() { 
		if(!$this->savedLanguage || !$this->defaultLanguage) return;
		$this->wire('user')->language = $this->savedLanguage; 
	}

	/**
	 * Hook called when a language is deleted
	 * 
	 * @param Page $language
	 *
	 */
	public function ___deleted(Page $language) {
		$this->updated($language, 'deleted'); 
	}

	/**
	 * Hook called when a language is added
	 * 
	 * @param Page $language
	 *
	 */
	public function ___added(Page $language) {
		$this->updated($language, 'added'); 
	}

	/**
	 * Hook called when a language is added or deleted
	 *
	 * @param Page $language
	 * @param string $what What occurred? ('added' or 'deleted')
	 *
	 */
	public function ___updated(Page $language, $what) {
		$this->reloadLanguages();
		$this->message("Updated language $language->name ($what)", Notice::debug); 
	}

	/**
	 * Reload all languages
	 *
	 */
	public function reloadLanguages() {
		$this->languages = null;
		$this->languagesAll = null;
	}

	public function getParent() {
		return $this->wire('pages')->findOne($this->parent_id, array('loadOptions' => array('autojoin' => false)));
	}

	public function getParents() {
		if(count($this->parents)) {
			return $this->wire('pages')->getById($this->parents, array('autojoin' => false));
		} else {
			return parent::getParents();
		}
	}

	/**
	 * Pages calls this when it catches an unknown column exception
	 *
	 * Provides QA to make sure any language-related columns are property setup in case
	 * something failed during the initial setup process.
	 *
	 * This is only here to repair existing installs that were missing a field for one reason or another.
	 * This method (and the call to it in Pages) can eventually be removed (?)
	 *
	 * @param $column
	 *
	 */
	public function ___unknownColumnError($column) {

		if(!preg_match('/^([^.]+)\.([^.\d]+)(\d+)$/', $column, $matches)) {
			return;
		}

		$table = $matches[1];
		$col = $matches[2];
		$languageID = (int) $matches[3];

		foreach($this->wire('languages') as $language) {
			if($language->id == $languageID) {
				$this->warning("language $language->name is missing column $column", Notice::debug);
				if($table == 'pages' && $this->wire('modules')->isInstalled('LanguageSupportPageNames')) {
					$module = $this->wire('modules')->get('LanguageSupportPageNames');
					$module->languageAdded($language);
				} else if(strpos($table, 'field_') === 0) {
					$fieldName = substr($table, strpos($table, '_')+1);
					$field = $this->wire('fields')->get($fieldName);
					if($field && $this->wire('modules')->isInstalled('LanguageSupportFields')) {
						$module = $this->wire('modules')->get('LanguageSupportFields');
						$module->fieldLanguageAdded($field, $language);
					}
				}
			}
		}
	}



}

