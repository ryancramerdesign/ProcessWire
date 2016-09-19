<?php namespace ProcessWire;

/**
 * ProcessWire Languages (plural) Class
 * 
 * Class for managing Language-type pages.
 * Acts as the $wire->languages API variable. 
 *
 * ProcessWire 3.x, Copyright 2016 by Ryan Cramer
 * https://processwire.com
 * 
 * @property LanguageTabs|null $tabs Current LanguageTabs module instance, if installed
 * @property Language $default Get default language 
 * @property LanguageSupport $support Instance of LanguageSupport module
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
	 * @var Language|null
	 * 
	 */
	protected $defaultLanguage = null;

	/**
	 * Saved language from a setDefault() call
	 * 
	 * @var Language|null
	 * 
	 */
	protected $savedLanguage = null;

	/**
	 * Saved language from a setLanguage() call
	 * 
	 * @var Language|null
	 * 
	 */
	protected $savedLanguage2 = null;

	/**
	 * Language-specific page-edit permissions, if installed (i.e. page-edit-lang-es, page-edit-lang-default, etc.)
	 *
	 * @var null|array Becomes an array once its been populated
	 *
	 */
	protected $pageEditPermissions = null;

	/**
	 * Cached results from editable() method, indexed by user_id.language_id
	 * 
	 * @var array
	 * 
	 */
	protected $editableCache = array();
	
	public function __construct(ProcessWire $wire, $templates = array(), $parents = array()) {
		parent::__construct($wire, $templates, $parents);
		$this->wire('database')->addHookAfter('unknownColumnError', $this, 'hookUnknownColumnError');
	}

	/**
	 * Return the LanguageTranslator instance for the given language
	 * 
	 * @param Language $language
	 * @return LanguageTranslator
	 *
	 */
	public function translator(Language $language) {
		if(is_null($this->translator)) $this->translator = $this->wire(new LanguageTranslator($language)); 
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
		$selector = "parent_id=$parent_id, template=$template, include=all, sort=sort";
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
		$languages = $this->wire('pages')->newPageArray();
		foreach($this->getAll() as $language) { 
			if($language->hasStatus(Page::statusUnpublished) || $language->hasStatus(Page::statusHidden)) continue; 
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
	 * Set the current user language, remembering the previous setting for a later unsetLanguage() call
	 * 
	 * @param int|string|Language $language Language id, name or Language object
	 * @return bool Returns false if no change necessary, true if language was changed
	 * @throws WireException if given $language argument doesn't resolve
	 * 
	 */
	public function setLanguage($language) {
		if(is_int($language)) {
			$language = $this->get($language);
		} else if(is_string($language)) {
			$language = $this->get($this->wire('sanitizer')->pageNameUTF8($language));	
		} 
		if(!$language instanceof Language || !$language->id) throw new WireException("Unknown language");
		$user = $this->wire('user');
		$this->savedLanguage2 = null;
		if($user->language && $user->language->id) {
			if($language->id == $user->language->id) return false; // no change necessary
			$this->savedLanguage2 = $user->language;
		}
		$user->language = $language;
		return true;
	}

	/**
	 * Undo a previous setLanguage() call, restoring the previous user language
	 * 
	 * @return bool Returns true if language restored, false if no restore necessary
	 * 
	 */
	public function unsetLanguage() {
		$user = $this->wire('user');
		if(!$this->savedLanguage2) return false;
		if($user->language && $user->language->id == $this->savedLanguage2->id) return false;
		$user->language = $this->savedLanguage2;
		return true;
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
		return $this->wire('pages')->get($this->parent_id, array('loadOptions' => array('autojoin' => false)));
	}

	public function getParents() {
		if(count($this->parents)) {
			return $this->wire('pages')->getById($this->parents, array('autojoin' => false));
		} else {
			return parent::getParents();
		}
	}

	/**
	 * Get all language-specific page-edit permissions, or individually one of htem
	 *
	 * @param string $name Optionally specify a permission or language name to change return value.
	 * @return array|string|bool of Permission names indexed by language name, or:
	 *  - If given a language name, it will return permission name (if exists) or false if not. 
	 *  - If given a permission name, it will return the language name (if exists) or false if not. 
	 *
	 */
	public function getPageEditPermissions($name = '') {
		
		$prefix = "page-edit-lang-";
		
		if(!is_array($this->pageEditPermissions)) {
			$this->pageEditPermissions = array();
			$langNames = array();
			foreach($this->wire('languages') as $language) {
				$langNames[$language->name] = $language->name;
			}
			foreach($this->wire('permissions') as $permission) {
				if(strpos($permission->name, $prefix) !== 0) continue;
				if($permission->name === $prefix . 'none') {
					$this->pageEditPermissions['none'] = $permission->name;
					continue;
				}
				foreach($langNames as $langName) {
					$permissionName = $prefix . $langName;
					if($permission->name === $permissionName) {
						$this->pageEditPermissions[$langName] = $permissionName;
						break;
					}
				}
			}
		}
		
		if($name) {
			if(strpos($name, $prefix) === 0) {
				// permission name specified: will return language name or false
				return array_search($name, $this->pageEditPermissions);
			} else {
				// language name specified: will return permission name or false
				return isset($this->pageEditPermissions[$name]) ? $this->pageEditPermissions[$name] : false;
			}
			
		} else {
			return $this->pageEditPermissions;
		}
	}

	/**
	 * Return applicable page-edit permission name for given language
	 * 
	 * A blank string is returned if there is no applicable permission
	 * 
	 * @param int|string|Language $language
	 * @return string
	 * 
	 */
	public function getPageEditPermission($language) {
		$permissions = $this->getPageEditPermissions();
		if($language === 'none' && isset($permissions['none'])) return $permissions['none'];
		if(!$language instanceof Language) {
			$language = $this->get($this->wire('sanitizer')->pageNameUTF8($language));
		}
		if(!$language || !$language->id) return '';
		return isset($permissions[$language->name]) ? $permissions[$language->name] : '';
	}

	/**
	 * Does current user have edit access for page fields in given language?
	 * 
	 * @param Language|int|string $language
	 * @return bool
	 * 
	 */
	public function editable($language) {
		
		$user = $this->wire('user');
		if($user->isSuperuser()) return true; 
		if(empty($language)) return false;
		$cacheKey = "$user->id.$language";
		
		if(array_key_exists($cacheKey, $this->editableCache)) {
			// accounts for 'none', or language ID
			return $this->editableCache[$cacheKey];
		}
		
		if($language === 'none') {
			// page-edit-lang-none permission applies to non-multilanguage fields, if present
			$permissions = $this->getPageEditPermissions();
			if(isset($permissions['none'])) {
				// if the 'none' permission exists, then the user must have it in order to edit non-multilanguage fields
				$has = $user->hasPermission('page-edit') && $user->hasPermission($permissions['none']); 
			} else {
				// if the page-edit-lang-none permission doesn't exist, then it's not applicable
				$has = $user->hasPermission('page-edit');
			}
			$this->editableCache[$cacheKey] = $has;
			
		} else {
			
			if(!$language instanceof Language) $language = $this->get($this->wire('sanitizer')->pageNameUTF8($language));
			if(!$language || !$language->id) return false;
		
			$cacheKey = "$user->id.$language->id";
			
			if(array_key_exists($cacheKey, $this->editableCache)) {
				return $this->editableCache[$cacheKey];
			}
			
			if(!$user->hasPermission('page-edit')) {
				// page-edit is a pre-requisite permission
				$has = false;
			} else {
				$permissionName = $this->getPageEditPermission($language);
				// if a language-specific page-edit permission doesn't exist, then fallback to regular page-edit permission
				if(!$permissionName) {
					$has = true;
				} else {
					$has = $user->hasPermission($permissionName);
				}
			}
			
			$this->editableCache[$cacheKey] = $has; 
		}
		
		return $has; 
	}
	
	public function __get($key) {
		if($key == 'tabs') return $this->wire('modules')->get('LanguageSupport')->getLanguageTabs();
		if($key == 'default') return $this->getDefault();
		if($key == 'support') return $this->wire('modules')->get('LanguageSupport');
		return parent::__get($key);
	}

	/**
	 * Hook to WireDatabasePDO::unknownColumnError
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
	public function hookUnknownColumnError(HookEvent $event) {
		
		$column = $event->arguments(0);
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

