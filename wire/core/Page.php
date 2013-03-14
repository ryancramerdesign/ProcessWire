<?php

/**
 * ProcessWire Page
 *
 * Page is the class used by all instantiated pages and it provides functionality for:
 *
 * 1. Providing get/set access to the Page's properties
 * 2. Accessing the related hierarchy of pages (i.e. parents, children, sibling pages)
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 * @link http://processwire.com/api/variables/page/ Offical $page Documentation
 * @link http://processwire.com/api/selectors/ Official Selectors Documentation
 *
 * @property int $id The numbered ID of the current page
 * @property string $name The name assigned to the page, as it appears in the URL
 * @property string $title The page's title (headline) text
 * @property string $path The page's URL path from the homepage (i.e. /about/staff/ryan/)
 * @property string $url The page's URL path from the server's document root (may be the same as the $page->path)
 * @property string $httpUrl Same as $page->url, except includes protocol (http or https) and hostname.
 * @property Page $parent The parent Page object or a NullPage if there is no parent.
 * @property int $parent_id The numbered ID of the parent page or 0 if homepage or NullPage.
 * @property PageArray $parents All the parent pages down to the root (homepage). Returns a PageArray.
 * @property Page $rootParent The parent page closest to the homepage (typically used for identifying a section)
 * @property Template $template The Template object this page is using
 * @property FieldsArray $fields All the Fields assigned to this page (via it's template, same as $page->template->fields). Returns a FieldsArray.
 * @property int $numChildren The number of children (subpages) this page has, much faster than count($page->children). 
 * @property PageArray $children All the children (subpages) of this page. Returns a PageArray. See also $page->children($selector).
 * @property Page $child The first child of this page. Returns a Page. See also $page->child($selector).
 * @property PageArray $siblings All the sibling pages of this page. Returns a PageArray. See also $page->siblings($selector).
 * @property Page $next This page's next sibling page, or NullPage if it is the last sibling. See also $page->next($pageArray).
 * @property Page $prev This page's previous sibling page, or NullPage if it is the first sibling. See also $page->prev($pageArray).
 * @property string $created Unix timestamp of when the page was created
 * @property string $modified Unix timestamp of when the page was last modified
 * @property User $createdUser The user that created this page. Returns a User or a NullUser.
 * @property User $modifiedUser The user that last modified this page. Returns a User or a NullUser.
 * 
 * @method string render() Returns rendered page markup. echo $page->render();
 * @method bool viewable() Returns true if the page is viewable by the current user, false if not. 
 * @method bool editable($field) Returns true if the page is editable by the current user, false if not. Optionally specify a field to see if that field is editable.
 * @method bool publishable() Returns true if the page is publishable by the current user, false if not. 
 * @method bool listable() Returns true if the page is listable by the current user, false if not. 
 * @method bool deleteable() Returns true if the page is deleteable by the current user, false if not. 
 * @method bool addable($pageToAdd) Returns true if the current user can add children to the page, false if not. Optionally specify the page to be added for additional access checking. 
 * @method bool moveable($newParent) Returns true if the current user can move this page. Optionally specify the new parent to check if the page is moveable to that parent. 
 * @method bool sortable() Returns true if the current user can change the sort order of the current page (within the same parent). 
 *
 */

class Page extends WireData {

	/*
	 * The following constant flags are specific to a Page's 'status' field. A page can have 1 or more flags using bitwise logic. 
	 * Status levels 1024 and above are excluded from search by the core. Status levels 16384 and above are runtime only and not 
	 * stored in the DB unless for logging or page history.
	 *
	 * If the under 1024 status flags are expanded in the future, it must be ensured that the combined value of the searchable flags 
	 * never exceeds 1024, otherwise issues in Pages::find() will need to be considered. 
	 *
	 * The status levels 16384 and above can safely be changed as needed as they are runtime only. 
	 *
	 */
	const statusOn = 1; 			// base status for all pages
	const statusLocked = 4; 		// page locked for changes. Not enforced by the core, but checked by Process modules. 
	const statusSystemID = 8; 		// page is for the system and may not be deleted or have it's id changed (everything else, okay)
	const statusSystem = 16; 		// page is for the system and may not be deleted or have it's id, name, template or parent changed
	const statusHidden = 1024;		// page is excluded selector methods like $pages->find() and $page->children() unless status is specified, like "status&1"
	const statusUnpublished = 2048; 	// page is not published and is not renderable. 
	const statusTrash = 8192; 		// page is in the trash
	const statusDeleted = 16384; 		// page is deleted (runtime only)
	const statusSystemOverride = 32768; 	// page is in a state where system flags may be overridden
	const statusCorrupted = 131072; 	// page was corrupted at runtime and is NOT saveable: see setFieldValue() and $outputFormatting. (runtime)
	const statusMax = 9999999;		// number to use for max status comparisons, runtime only

	/**
	 * The Template this page is using (object)
	 *
	 */
	protected $template; 

	/**
	 * The previous template used by the page, if it was changed during runtime. 	
	 *
	 * Allows Pages::save() to delete data that's no longer used. 
	 *
	 */
	private $templatePrevious; 

	/**
	 * Parent Page - Instance of Page
	 *
	 */
	protected $parent = null;

	/**
	 * The previous parent used by the page, if it was changed during runtime. 	
	 *
	 * Allows Pages::save() to identify when the parent has changed
	 *
	 */
	private $parentPrevious; 

	/**
	 * The previous name used by this page, if it changed during runtime.
	 *
	 */
	private $namePrevious; 

	/**
	 * Reference to the Page's template file, used for output. Instantiated only when asked for. 
	 *
	 */
	private $output; 

	/**
	 * Instance of PageFilesManager, which manages and migrates file versions for this page
	 *
	 * Only instantiated upon request, so access only from filesManager() method in Page class. 
	 * Outside API can use $page->filesManager.
	 *
	 */
	private $filesManager = null;

	/**
	 * Field data that queues while the page is loading. 
	 *
	 * Once setIsLoaded(true) is called, this data is processed and instantiated into the Page and the fieldDataQueue is emptied (and no longer relevant)	
	 *
	 */
	protected $fieldDataQueue = array();

	/**
	 * Is this a new page (not yet existing in the database)?
	 *
	 */
	protected $isNew = true; 

	/**
	 * Is this Page finished loading from the DB (i.e. Pages::getById)?
	 *
	 * When false, it is assumed that any values set need to be woken up. 
	 * When false, it also assumes that built-in properties (like name) don't need to be sanitized. 
	 *
	 * Note: must be kept in the 'true' state. Pages::getById sets it to false before populating data and then back to true when done.
	 *
	 */
	protected $isLoaded = true; 

	/**
	 * Is this page allowing it's output to be formatted?
	 *
	 * If so, the page may not be saveable because calls to $page->get(field) are returning versions of 
	 * variables that may have been formatted at runtime for output. An exception will be thrown if you
	 * attempt to set the value of a formatted field when $outputFormatting is on. 
	 *
	 * Output formatting should be turned off for pages that you are manipulating and saving. 
	 * Whereas it should be turned on for pages that are being used for output on a public site. 
	 * Having it on means that Textformatters and any other output formatters will be executed
	 * on any values returned by this page. Likewise, any values you set to the page while outputFormatting
	 * is set to true are considered potentially corrupt. 
	 *
	 */
	protected $outputFormatting = false; 

	/**
	 * A unique instance ID assigned to the page at the time it's loaded (for debugging purposes only)
	 *
	 */
	protected $instanceID = 0; 

	/**
	 * IDs for all the instances of pages, used for debugging and testing.
	 *
	 * Indexed by $instanceID => $pageID
	 *
	 */
	static public $instanceIDs = array();

	/**
	 * Stack of ID indexed Page objects that are currently in the loading process. 
	 *
	 * Used to avoid possible circular references when multiple pages referencing each other are being populated at the same time.
	 *
	 */
	static public $loadingStack = array();

	/**
	 * Controls the behavior of Page::__isset function
	 *
	 * false = isset($page->var) returns true if 'var' is a valid field for page AND IS LOADED. (default behavior)
	 * true = isset($page->var) returns true if 'var' is a valid field for page, same has $page->has('var'); 
	 *
	 * This is a static setting affecting all pages. It is provided for template engines that use isset() or empty() 
	 * to verify the validity of a property name for an object (i.e. Twig).
	 * 
	 */
	static public $issetHas = false; 

	/**
	 * The current page number, starting from 1
	 *
	 * @deprecated, use $input->pageNum instead. 
	 *
	 */
	protected $pageNum = 1; 

	/**
	 * Reference to main config, optimization so that get() method doesn't get called
	 *
	 */
	protected $config = null; 

	/**
	 * When true, exceptions won't be thrown when values are set before templates
	 *
	 */
	protected $quietMode = false; 

	/**
	 * Page-specific settings which are either saved in pages table, or generated at runtime.
	 *
	 */
	protected $settings = array(
		'id' => 0, 
		'name' => '', 
		'status' => 1, 
		'numChildren' => 0, 
		'sort' => -1, 
		'sortfield' => 'sort', 
		'modified_users_id' => 0, 
		'created_users_id' => 0,
		); 

	/**
	 * Create a new page in memory. 
	 *
	 * @param Template $tpl Template object this page should use. 
	 *
	 */
	public function __construct(Template $tpl = null) {

		if(!is_null($tpl)) $this->template = $tpl;
		$this->useFuel(false); // prevent fuel from being in local scope
		$this->parentPrevious = null;
		$this->templatePrevious = null;
	}

	/**
	 * Destruct this page instance
	 *
	 */
	public function __destruct() {
		if($this->instanceID) {
			// remove from the record of instanceID, so that we have record of page's that HAVEN'T been destructed. 
			unset(self::$instanceIDs[$this->instanceID]); 
		}
	}

	/**
	 * Clone this page instance
	 *
	 */
	public function __clone() {
		$track = $this->trackChanges();
		$this->setTrackChanges(false); 
		if($this->filesManager) {
			$this->filesManager = clone $this->filesManager; 
			$this->filesManager->setPage($this);
		}
		foreach($this->template->fieldgroup as $field) {
			$name = $field->name; 
			if(!$field->type->isAutoload() && !isset($this->data[$name])) continue; // important for draft loading
			$value = $this->get($name); 
			// no need to clone non-objects, as they've already been cloned
			if(!is_object($value)) continue;
			// if value doesn't resolve to a page, then create a clone of it
			if(!$value instanceof Page) $this->set($name, clone $value); // attempt re-commit
			// if value is Pagefiles, then tell it the new page
			if($value instanceof Pagefiles) $this->get($name)->setPage($this); 

		}
		$this->instanceID .= ".clone";
		if($track) $this->setTrackChanges(true); 
	}

	/**
	 * Set the value of a page property
	 *
	 * @param string $key Property to set
	 * @param mixed $value
	 * @return Page Reference to this Page
	 * @see __set
	 *
	 */
	public function set($key, $value) {

		if(($key == 'id' || $key == 'name') && $this->settings[$key] && $value != $this->settings[$key]) 
			if(	($key == 'id' && (($this->settings['status'] & Page::statusSystem) || ($this->settings['status'] & Page::statusSystemID))) ||
				($key == 'name' && (($this->settings['status'] & Page::statusSystem)))) {
					throw new WireException("You may not modify '$key' on page '{$this->path}' because it is a system page"); 
		}

		switch($key) {
			case 'id':
				if(!$this->isLoaded) Page::$loadingStack[(int) $value] = $this;
			case 'sort': 
			case 'numChildren': 
			case 'num_children':
				if($key == 'num_children') $key = 'numChildren';
				if($this->settings[$key] !== $value) $this->trackChange($key); 
				$this->settings[$key] = (int) $value; 
				break;
			case 'status':
				$this->setStatus($value); 
				break;
			case 'name':
				if($this->isLoaded) {
					$beautify = empty($this->settings[$key]); 
					$value = $this->fuel('sanitizer')->pageName($value, $beautify); 
					if($this->settings[$key] !== $value) {
						if($this->settings[$key] && empty($this->namePrevious)) $this->namePrevious = $this->settings[$key];
						$this->trackChange($key); 
					}
				}
				$this->settings[$key] = $value; 
				break;
			case 'parent': 
			case 'parent_id':
				if(($key == 'parent_id' || is_int($value)) && $value) $value = $this->fuel('pages')->get((int)$value); 
					else if(is_string($value)) $value = $this->fuel('pages')->get($value); 
				if($value) $this->setParent($value);
				break;
			case 'parentPrevious':
				if(is_null($value) || $value instanceof Page) $this->parentPrevious = $value; 
				break;
			case 'template': 
			case 'templates_id':
				if($key == 'templates_id' && $this->template && $this->template->id == $value) break;
				if($key == 'templates_id') $value = $this->fuel('templates')->get((int)$value); 
				$this->setTemplate($value); 
				break;
			case 'created': 
			case 'modified':
				if(!ctype_digit("$value")) $value = strtotime($value); 
				$this->settings[$key] = (int) $value; 
				break;
			case 'created_users_id':
			case 'modified_users_id': 
				$this->settings[$key] = (int) $value; 
				break;
			case 'createdUser':
			case 'modifiedUser':
				$this->setUser($value, strpos($key, 'created') === 0 ? 'created' : 'modified'); 
				break;
			case 'sortfield':
				if($this->template && $this->template->sortfield) break;
				$value = $this->fuel('pages')->sortfields()->decode($value); 
				if($this->settings[$key] != $value) $this->trackChange($key); 
				$this->settings[$key] = $value; 
				break;
			case 'isLoaded': 
				$this->setIsLoaded($value); 
				break;
			case 'pageNum':
				$this->pageNum = ((int) $value) > 1 ? (int) $value : 1; 
				break;
			case 'instanceID': 
				$this->instanceID = $value; 
				self::$instanceIDs[$value] = $this->settings['id']; 
				break;
			default:
				if($this->quietMode && !$this->template) return parent::set($key, $value); 

				$this->setFieldValue($key, $value, $this->isLoaded); 

		}
		return $this; 
	}

	/**
	 * Set a value to a page without tracking changes and without exceptions
	 *
	 * Otherwise same as set()
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return this
	 *
	 */
	public function setQuietly($key, $value) {
		$this->quietMode = true; 
		return parent::setQuietly($key, $value);
		$this->quietMode = false;
		return $this; 
	}


	/**
	 * Set the value of a field that is defined in the page's Fieldgroup
	 *
	 * This may not be called when outputFormatting is on. 
	 *
	 * This is for internal use. API should generally use the set() method, but this is kept public for the minority of instances where it's useful.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param bool $load Should the existing value be loaded for change comparisons? (applicable only to non-autoload fields)
	 *
	 */
	public function setFieldValue($key, $value, $load = true) {

		if(!$this->template) throw new WireException("You must assign a template to the page before setting custom field values."); 

		// if the page is not yet loaded and a '__' field was set, then we queue it so that the loaded() method can 
		// instantiate all those fields knowing that all parts of them are present for wakeup. 
		if(!$this->isLoaded && strpos($key, '__')) {
			list($key, $subKey) = explode('__', $key); 
			if(!isset($this->fieldDataQueue[$key])) $this->fieldDataQueue[$key] = array();
			$this->fieldDataQueue[$key][$subKey] = $value; 
			return;
		}

		if(!$field = $this->template->fieldgroup->getField($key)) {
			// not a known/saveable field, let them use it for runtime storage
			return parent::set($key, $value); 
		}

		// if a null value is set, then ensure the proper blank type is set to the field
		if(is_null($value)) {
			return parent::set($key, $field->type->getBlankValue($this, $field)); 
		}

		// if the page is currently loading from the database, we assume that any set values are 'raw' and need to be woken up
		if(!$this->isLoaded) {

			// send the value to the Fieldtype to be woken up for storage in the page
			$value = $field->type->wakeupValue($this, $field, $value); 

			// page is currently loading, so we don't need to continue any further
			return parent::set($key, $value); 
		}

		// check if the field hasn't been already loaded
		if(is_null(parent::get($key))) {
			// this field is not currently loaded. if the $load param is true, then ...
			// retrieve old value first in case it's not autojoined so that change comparisons and save's work 
			if($load && $this->isLoaded) $this->get($key); 

		} else if($this->outputFormatting && $field->type->formatValue($this, $field, $value) != $value) {
			// The field has been loaded or dereferenced from the API, and this field changes when formatters are applied to it. 
			// There is a good chance they are trying to set a formatted value, and we don't allow this situation because the 
			// possibility of data corruption is high. We set the Page::statusCorrupted status so that Pages::save() can abort.
			$this->set('status', $this->status | self::statusCorrupted); 
		}

		// ensure that the value is in a safe format and set it 
		$value = $field->type->sanitizeValue($this, $field, $value); 

		parent::set($key, $value); 
	}

	/**
	 * Get the value of a requested Page property
	 *
	 * @param string $key
	 * @return mixed
	 * @see __get
	 *
	 */
	public function get($key) {
		$value = null;
		switch($key) {
			case 'parent_id':
			case 'parentID': 
				$value = $this->parent ? $this->parent->id : 0; 
				break;
			case 'child':
				$value = $this->child(); 
				break;
			case 'children':
			case 'subpages': // PW1 
				$value = $this->children();
				break;
			case 'parent':
			case 'parents':
			case 'rootParent':
			case 'siblings':
			case 'next':
			case 'prev':
			case 'url':
			case 'path':
			case 'outputFormatting': 
			case 'isTrash': 
				$value = $this->{$key}(); 
				break;
			case 'httpUrl': 
			case 'httpURL': 
				$value = $this->httpUrl();
				break;
			case 'fieldgroup': 
			case 'fields':
				$value = $this->template->fieldgroup; 
				break; 
			case 'template':
			case 'templatePrevious':
			case 'parentPrevious':
			case 'namePrevious':
			case 'isLoaded':
			case 'isNew':
			case 'pageNum':
			case 'instanceID': 
				$value = $this->$key; 
				break;
			case 'out':
			case 'output':
				$value = $this->getTemplateFile();
				break;
			case 'filesManager':
				$value = $this->filesManager();
				break;
			case 'name':
				$value = $this->settings['name'];
				break;
			case 'modified_users_id': 
			case 'modifiedUsersID':
			case 'modifiedUserID':
				$value = $this->settings['modified_users_id']; 
				break;
			case 'created_users_id':
			case 'createdUsersID':
			case 'createdUserID': 
				$value = $this->settings['created_users_id'];
				break;
			case 'modifiedUser':
				if(!$value = $this->fuel('users')->get($this->settings['modified_users_id'])) $value = new NullUser(); 
				break;
			case 'createdUser':
				if(!$value = $this->fuel('users')->get($this->settings['created_users_id'])) $value = new NullUser(); 
				break;
			case 'urlSegment':
				$value = $this->fuel('input')->urlSegment1; // deprecated, but kept for backwards compatibility
				break;
			case 'accessTemplate': 
				$value = $this->getAccessTemplate();
				break;
			case 'numVisibleChildren':
				$value = $this->numChildren(true);
				break;
			default:
				if($key && isset($this->settings[(string)$key])) return $this->settings[$key]; 

				if(($value = $this->getFieldFirstValue($key)) !== null) return $value; 
				if(($value = $this->getFieldValue($key)) !== null) return $value;

				// if there is a selector, we'll assume they are using the get() method to get a child
				if(Selectors::stringHasOperator($key)) return $this->child($key);

				// check if it's a field.subfield property, but only if output formatting is off
				if(!$this->outputFormatting() && strpos($key, '.') !== false && ($value = $this->getDot($key)) !== null) return $value;

				// optionally let a hook look at it
				if(self::isHooked('Page::getUnknown()')) return $this->getUnknown($key);
		}

		return $value; 
	}

	/**
	 * Hookable method called when a request to a field was made that didn't match anything
	 *
	 * Hooks that want to inject something here should hook after and modify the $event->return.
	 *
	 * @param string $key Name of property.
	 * @return null|mixed Returns null if property not known, or a value if it is.
	 *
	 */
	public function ___getUnknown($key) {
		return null;
	}

	/**
	 * Handles get() method requests for properties that include a period like "field.subfield"
	 *
	 * Typically these resolve to objects, and the subfield is pulled from the object.
	 * Currently we only allow this dot syntax when output formatting is off. This limitation may be removed
	 * but we have to consider potential security implications before doing so.
	 *
	 * @param string $key Property name in field.subfield format
	 * @return null|mixed Returns null if not found or invalid. Returns property value on success.
	 *
	 */
	public function getDot($key) {
		if(strpos($key, '.') === false) return $this->get($key);
		$of = $this->outputFormatting();
		if($of) $this->setOutputFormatting(false);
		$value = self::_getDot($key, $this);
		if($of) $this->setOutputFormatting(true);
		return $value; 
	}

	/**
	 * Given a Multi Key, determine if there are multiple keys requested and return the first non-empty value
	 *
	 * A Multi Key is a string with multiple field names split by pipes, i.e. headline|title
	 *
	 * Example: browser_title|headline|title - Return the value of the first field that is non-empty
	 *
	 * @param string $key
	 * @return null|mixed Returns null if no values match, or if there aren't multiple keys split by "|" chars
	 *
	 */
	protected function getFieldFirstValue($multiKey) {

		// looking multiple keys split by "|" chars, and not an '=' selector
		if(strpos($multiKey, '|') === false || strpos($multiKey, '=') !== false) return null;

		$value = null;
		$keys = explode('|', $multiKey); 

		foreach($keys as $key) {
			$value = $this->getFieldValue($key);
			if(is_string($value)) $value = trim($value); 
			if($value) break;
		}

		return $value;
	}

	/**
	 * Get the value for a non-native page field, and call upon Fieldtype to join it if not autojoined
	 *
	 * @param string $key
	 * @return null|mixed
	 *
	 */
	protected function getFieldValue($key) {
		if(!$this->template) return null;
		$field = $this->template->fieldgroup->getField($key); 
		$value = parent::get($key); 
		if(!$field) return $value;  // likely a runtime field, not part of our data

		// if the value is already loaded, return it 
		if(!is_null($value)) return $this->outputFormatting ? $field->type->formatValue($this, $field, $value) : $value; 
		$track = $this->trackChanges();
		$this->setTrackChanges(false); 
		$value = $field->type->loadPageField($this, $field); 
		if(is_null($value)) $value = $field->type->getDefaultValue($this, $field); 
			else $value = $field->type->wakeupValue($this, $field, $value); 

		// if outputFormatting is being used, turn it off because it's not necessary here and may throw an exception
		$outputFormatting = $this->outputFormatting; 
		if($outputFormatting) $this->setOutputFormatting(false); 
		$this->setFieldValue($key, $value, false); 
		if($outputFormatting) $this->setOutputFormatting(true); 
		
		$value = parent::get($key); 	
		if(is_object($value) && $value instanceof Wire) $value->resetTrackChanges(true);
		if($track) $this->setTrackChanges(true); 
		return $this->outputFormatting ? $field->type->formatValue($this, $field, $value) : $value; 
	}

	/**
	 * Get the raw/unformatted value of a field, regardless of what $this->outputFormatting is set at
	 *
	 */
	public function getUnformatted($key) {
		$outputFormatting = $this->outputFormatting; 
		if($outputFormatting) $this->setOutputFormatting(false); 
		$value = $this->get($key); 
		if($outputFormatting) $this->setOutputFormatting(true); 
		return $value; 
	}


	/**
	 * @see get
	 *
	 */
	public function __get($key) {
		return $this->get($key); 
	}

	/**
	 * @see set
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value); 
	}

	/**
	 * Set the 'status' setting, with some built-in protections
	 *
	 */
	protected function setStatus($value) {
		$value = (int) $value; 
		$override = $this->settings['status'] & Page::statusSystemOverride; 
		if(!$override) { 
			if($this->settings['status'] & Page::statusSystemID) $value = $value | Page::statusSystemID;
			if($this->settings['status'] & Page::statusSystem) $value = $value | Page::statusSystem; 
		}
		if($this->settings['status'] != $value) $this->trackChange('status');
		$this->settings['status'] = $value;
		if($value & Page::statusDeleted) {
			// disable any instantiated filesManagers after page has been marked deleted
			// example: uncache method polls filesManager
			$this->filesManager = null; 
		}
	}

	/**
	 * Set this Page's Template object
	 *
	 */
	protected function setTemplate($tpl) {
		if(!is_object($tpl)) $tpl = $this->fuel('templates')->get($tpl); 
		if(!$tpl instanceof Template) throw new WireException("Invalid value sent to Page::setTemplate"); 
		if($this->template && $this->template->id != $tpl->id) {
			if($this->settings['status'] & Page::statusSystem) throw new WireException("Template changes are disallowed on this page"); 
			if(is_null($this->templatePrevious)) $this->templatePrevious = $this->template; 
			$this->trackChange('template'); 
		}
		if($tpl->sortfield) $this->settings['sortfield'] = $tpl->sortfield; 
		$this->template = $tpl; 
		return $this;
	}


	/**
	 * Set this page's parent Page
	 *
	 */
	protected function setParent(Page $parent) {
		if($this->parent && $this->parent->id == $parent->id) return $this; 
		$this->trackChange('parent');
		if(($this->parent && $this->parent->id) && $this->parent->id != $parent->id) {
			if($this->settings['status'] & Page::statusSystem) throw new WireException("Parent changes are disallowed on this page"); 
			$this->parentPrevious = $this->parent; 
		}
		$this->parent = $parent; 
		return $this; 
	}


	/**
	 * Set either the createdUser or the modifiedUser 
	 *
	 * @param User|int|string User object or integer/string representation of User
	 * @param string $userType Must be either 'created' or 'modified' 
	 * @return this
	 *
	 */
	protected function setUser($user, $userType) {

		if(!$user instanceof User) $user = $this->fuel('users')->get($user); 

		// if they are setting an invalid user or unknown user, then the Page defaults to the super user
		if(!$user || !$user->id) $user = $this->fuel('users')->get(User::superUserID); 

		if($userType == 'created') $field = 'createdUser';
			else if($userType == 'modified') $field = 'modifiedUser';
			else throw new WireException("Unknown user type in Page::setUser(user, type)"); 

		$existingUser = $this->$field; 
		if($existingUser && $existingUser->id != $user->id) $this->trackChange($field); 
		$this->$field = $user; 
		return $this; 	
	}

	/**
	 * Find Pages in the descendent hierarchy
	 *
	 * Same as Pages::find() except that the results are limited to descendents of this Page
	 *
	 * @param string $selector
	 *
	 */
	public function find($selector = '', $options = array()) {
		if(!$this->numChildren) return new PageArray();
		$selector = "has_parent={$this->id}, $selector"; 
		return $this->fuel('pages')->find(trim($selector, ", "), $options); 
	}

	/**
	 * Return this page's children pages, optionally filtered by a selector
	 *
	 * @param string $selector Selector to use, or blank to return all children
	 * @return PageArray
	 *
	 */
	public function children($selector = '', $options = array()) {
		return PageTraversal::children($this, $selector, $options); 
	}

	/**
	 * Return number of children, optionally limiting to visible pages. 
	 *
	 * @param bool $onlyVisible When true, number includes only visible children (excludes unpublished, hidden, no-access, etc.)
	 *
	 */
	public function numChildren($onlyVisible = false) {
		if(!$onlyViewable) return $this->settings['numChildren'];
		return $this->children('limit=2')->getTotal();
	}

	/**
	 * Return the page's first single child that matches the given selector. 
	 *
	 * Same as children() but returns a Page object or NullPage (with id=0) rather than a PageArray
	 *
	 * @param string $selector Selector to use, or blank to return the first child. 
	 * @return Page|NullPage
	 *
	 */
	public function child($selector = '', $options = array()) {
		return PageTraversal::child($this, $selector, $options); 
	}

	/**
	 * Return this page's parent Page, or the closest parent matching the given selector.
	 *
	 * @param string $selector Optional selector string. When used, it returns the closest parent matching the selector. 
	 * @return Page|NullPage Returns a Page or a NullPage when there is no parent or the selector string did not match any parents.
	 *
	 */
	public function parent($selector = '') {
		if(!$this->parent) return new NullPage();
		if(!strlen($selector)) return $this->parent; 
		if($this->parent->matches($selector)) return $this->parent; 
		if($this->parent->parent_id) return $this->parent->parent($selector); // recursive, in a way
		return new NullPage();
	}

	/**
	 * Return this page's parent pages, or the parent pages matching the given selector.
	 *
	 * @param sting $selector Optional selector string to filter parents by
	 * @return PageArray
	 *
	 */
	public function parents($selector = '') {
		return PageTraversal::parents($this, $selector); 
	}

	/**
	 * Return all parents from current till the one matched by $selector
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @return PageArray
	 *
	 */
	public function parentsUntil($selector = '', $filter = '') {
		return PageTraversal::parentsUntil($this, $selector, $filter); 
	}

	/**
	 * Like parent() but includes the current Page in the possible pages that can be matched
 	 *
	 * Note also that unlike parent() a $selector is required.
	 *
	 * @param string $selector Selector string to match. 
	 * @return Page|NullPage $selector Returns the current Page or closest parent matching the selector. Returns NullPage when no match.
	 *
	 */
	public function closest($selector) {
		if(!strlen($selector) || $this->matches($selector)) return $this; 
		return $this->parent($selector); 
	}

	/**
	 * Get the lowest-level, non-homepage parent of this page
	 *
	 * rootParents typically comprise the first level of navigation on a site. 
	 *
	 * @return Page 
	 *
	 */
	public function ___rootParent() {
		return PageTraversal::rootParent($this); 
	}

	/**
	 * Return this Page's sibling pages, optionally filtered by a selector. 
	 *
	 * Note that the siblings include the current page. To exclude the current page, specify "id!=$page". 
	 *
	 * @param string $selector Optional selector to filter siblings by.
	 * @return PageArray
	 *
	 */
	public function siblings($selector = '') {
		return PageTraversal::siblings($this, $selector); 
	}

	/**
	 * Return the next sibling page
	 *
	 * If given a PageArray of siblings (containing the current) it will return the next sibling relative to the provided PageArray.
	 *
	 * Be careful with this function when the page has a lot of siblings. It has to load them all, so this function is best
	 * avoided at large scale, unless you provide your own already-reduced siblings list (like from pagination)
	 *
	 * When using a selector, note that this method operates only on visible children. If you want something like "include=all"
	 * or "include=hidden", they will not work in the selector. Instead, you should provide the siblings already retrieved with
	 * one of those modifiers, and provide those siblings as the second argument to this function.
	 *
	 * @param string $selector Optional selector string. When specified, will find nearest next sibling that matches. 
	 * @param PageArray $siblings Optional siblings to use instead of the default. May also be specified as first argument when no selector needed.
	 * @return Page|NullPage Returns the next sibling page, or a NullPage if none found. 
	 *
	 */
	public function next($selector = '', PageArray $siblings = null) {
		return PageTraversal::next($this, $selector, $siblings); 
	}

	/**
	 * Return all sibling pages after this one, optionally matching a selector
	 *
	 * @param string $selector Optional selector string. When specified, will filter the found siblings.
	 * @param PageArray $siblings Optional siblings to use instead of the default. 
	 * @return Page|NullPage Returns all matching pages after this one.
	 *
	 */
	public function nextAll($selector = '', PageArray $siblings = null) {
		return PageTraversal::nextAll($this, $selector, $siblings); 
	}

	/**
	 * Return all sibling pages after this one until matching the one specified 
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @param PageArray Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	public function nextUntil($selector = '', $filter = '', PageArray $siblings = null) {
		return PageTraversal::nextUntil($this, $selector, $filter, $siblings); 
	}

	/**
	 * Return the previous sibling page
	 *
	 * If given a PageArray of siblings (containing the current) it will return the previous sibling relative to the provided PageArray.
	 *
	 * Be careful with this function when the page has a lot of siblings. It has to load them all, so this function is best
	 * avoided at large scale, unless you provide your own already-reduced siblings list (like from pagination)
	 *
	 * When using a selector, note that this method operates only on visible children. If you want something like "include=all"
	 * or "include=hidden", they will not work in the selector. Instead, you should provide the siblings already retrieved with
	 * one of those modifiers, and provide those siblings as the second argument to this function.
	 *
	 * @param string $selector Optional selector string. When specified, will find nearest previous sibling that matches. 
	 * @param PageArray $siblings Optional siblings to use instead of the default. May also be specified as first argument when no selector needed.
	 * @return Page|NullPage Returns the previous sibling page, or a NullPage if none found. 
	 *
	 */
	public function prev($selector = '', PageArray $siblings = null) {
		return PageTraversal::prev($this, $selector, $siblings); 
	}

	/**
	 * Return all sibling pages before this one, optionally matching a selector
	 *
	 * @param string $selector Optional selector string. When specified, will filter the found siblings.
	 * @param PageArray $siblings Optional siblings to use instead of the default. 
	 * @return Page|NullPage Returns all matching pages before this one.
	 *
	 */
	public function prevAll($selector = '', PageArray $siblings = null) {
		return PageTraversal::prevAll($this, $selector, $siblings); 
	}

	/**
	 * Return all sibling pages before this one until matching the one specified 
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @param PageArray Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	public function prevUntil($selector = '', $filter = '', PageArray $siblings = null) {
		return PageTraversal::prevUntil($this, $selector, $filter, $siblings); 
	}


	/**
	 * Save this page to the database. 
	 *
	 * To hook into this (___save), use 'Pages::save' 
	 * To hook into a field-only save, use 'Pages::saveField'
	 *
	 * @param Field|string $field Optional field to save (name of field or Field object)
	 *
	 */
	public function save($field = null) {
		if(!is_null($field)) return $this->fuel('pages')->saveField($this, $field);
		return $this->fuel('pages')->save($this);
	}

	/**
	 * Delete this page from the Database
	 *
	 * Throws WireException if action not allowed. 
	 * See Pages::delete for a hookable version. 
	 *
	 * @return bool True on success
	 *
	 */
	public function delete() {
		return $this->fuel('pages')->delete($this); 
	}

	/**
	 * Move this page to the trash
	 *
	 * Throws WireException if action is not allowed. 
	 * See Pages::trash for a hookable version. 
	 *
	 * @return bool True on success
	 *
	 */
	public function trash() {
		return $this->fuel('pages')->trash($this); 
	}

	/**
	 * Allow iteration of the page's properties with foreach(), fulfilling IteratorAggregate interface.
	 *
	 */
	public function getIterator() {
		$a = $this->settings; 
		foreach($this->template->fieldgroup as $field) {
			$a[$field->name] = $this->get($field->name); 
		}
		return new ArrayObject($a); 	
	}


	/**
	 * Has the Page (or optionally one of it's fields) changed since it was loaded?
	 *
	 * Assumes that Pages has turned on this Page's change tracking with a call to setTrackChanges(). 
	 * Pages that are new (i.e. don't yet exist in the DB) always return true. 
	 * 
	 * @param string $what If specified, only checks the given property for changes rather than the whole page. 
	 * @return bool 
	 *
	 */
	public function isChanged($what = '') {
		if($this->isNew()) return true; 
		if(parent::isChanged($what)) return true; 
		$changed = false;
		if($what) {
			$value = $this->get($what); 
			if(is_object($value) && $value instanceof Wire) 
				$changed = $value->isChanged(); 
		} else {
			foreach($this->data as $key => $value) {
				if(is_object($value) && $value instanceof Wire)
					$changed = $value->isChanged();
				if($changed) break;
			}
		}

		return $changed; 	
	}


	/**
	 * Returns the Page's ID in a string
	 *
	 */
	public function __toString() {
		return "{$this->id}"; 
	}

	/**
	 * Returns the Page's path from the site root. 
	 *
	 */
	public function path() {
		return self::isHooked('Page::path()') ? $this->__call('path', array()) : $this->___path();
	}

	/**
	 * Provides the hookable implementation for the path() method.
	 *
	 * The method we're using here by having a real path() function above is slightly quicker than just letting 
	 * PW's hook handler handle it all. We're taking this approach since path() is a function that can feasibly
	 * be called hundreds or thousands of times in a request, so we want it as optimized as possible.
	 *
	 */
	protected function ___path() {
		if($this->id === 1) return '/';
		$path = '';
		$parents = $this->parents();
		foreach($parents as $parent) if($parent->id > 1) $path .= "/{$parent->name}";
		return $path . '/' . $this->name . '/'; 
	}

	/**
	 * Like path() but comes from server document root (which may or may not be different)
	 *
	 * Does not include urlSegment, if applicable. 
	 * Does not include protocol and hostname -- use httpUrl() for that.
	 *
	 * @see path
	 *
	 */
	public function url() {
		$url = rtrim($this->fuel('config')->urls->root, "/") . $this->path(); 
		if($this->template->slashUrls === 0 && $this->settings['id'] > 1) $url = rtrim($url, '/'); 
		return $url;
	}

	/**
	 * Like URL, but includes the protocol and hostname
	 *
	 */
	public function httpUrl() {

		switch($this->template->https) {
			case -1: $protocol = 'http'; break;
			case 1: $protocol = 'https'; break;
			default: $protocol = $this->fuel('config')->https ? 'https' : 'http'; 
		}

		return "$protocol://" . $this->fuel('config')->httpHost . $this->url();
	}

	/**
	 * Get the output TemplateFile object for rendering this page
	 *
	 * You can retrieve the results of this by calling $page->out or $page->output
	 *
	 * @return TemplateFile
	 *
	 */
	protected function getTemplateFile() {
		if($this->output) return $this->output; 
		if(!$this->template) return null;
		$this->output = new TemplateFile($this->template->filename); 
		$fuel = self::getAllFuel();
		$this->output->set('wire', $fuel); 
		foreach($fuel as $key => $value) $this->output->set($key, $value); 
		$this->output->set('page', $this); 
		return $this->output; 
	}


	/**
	 * Return a Inputfield object that contains all the custom Inputfield objects required to edit this page
	 *
	 */
	public function getInputfields() {
		return $this->template ? $this->template->fieldgroup->getPageInputfields($this) : null;
	}


	/** 
	 * Does this page have the specified status number or template name? 
 	 *
 	 * See status flag constants at top of Page class
	 *
	 * @param int|string|Selectors $status Status number or Template name or selector string/object
	 * @return bool
	 *
	 */
	public function is($status) {
		return PageComparison::is($this, $status);
	}

	/**
	 * Given a Selectors object or a selector string, return whether this Page matches it
	 *
	 * @param string|Selectors $s
	 * @return bool
	 *
	 */
	public function matches($s) {
		return PageComparison::matches($this, $s); 
	}

	/**
	 * Add the specified status flag to this page's status
	 *
	 * @param int $statusFlag
	 * @return this
	 *
	 */
	public function addStatus($statusFlag) {
		$statusFlag = (int) $statusFlag; 
		$this->status = $this->status | $statusFlag; 
		return $this;
	}

	/** 
	 * Remove the specified status flag from this page's status
	 *
	 * @param int $statusFlag
	 * @return this
	 *
	 */
	public function removeStatus($statusFlag) {
		$statusFlag = (int) $statusFlag; 
		$override = $this->settings['status'] & Page::statusSystemOverride; 
		if($statusFlag == Page::statusSystem || $statusFlag == Page::statusSystemID) {
			if(!$override) throw new WireException("You may not remove the 'system' status from a page"); 
		}
		$this->status = $this->status & ~$statusFlag; 
		return $this;
	}

	/**
	 * Does this page have a 'hidden' status?
	 *
	 * @return bool
	 *
	 */
	public function isHidden() {
		return $this->is(self::statusHidden); 
	}

	/**
	 * Is this Page new? (i.e. doesn't yet exist in DB)
	 *
	 */
	public function isNew() {
		return $this->isNew; 
	}

	/**
	 * Is the page fully loaded?
	 *
	 */
	public function isLoaded() {
		return $this->isLoaded; 
	}

	/**
	 * Is this Page in the trash?
	 *
	 * @return bool
	 *
 	 */ 
	public function isTrash() {
		if($this->is(self::statusTrash)) return true; 
		$trashPageID = $this->fuel('config')->trashPageID; 
		if($this->id == $trashPageID) return true; 
		// this is so that isTrash() still returns the correct result, even if the page was just trashed and not yet saved
		foreach($this->parents() as $parent) if($parent->id == $trashPageID) return true; 
		return false;
	}

	/**
	 * Is this page public and viewable by all?
	 *
	 * This is a state that persists regardless of user, so has nothing to do with the current user.
	 * To be public, the page must be published and have guest view access.
	 *
	 * @return bool True if public, false if not
	 *
	 */
	public function isPublic() {
		if($this->status >= Page::statusUnpublished) return false;	
		$template = $this->getAccessTemplate();
		if(!$template->hasRole('guest')) return false;
		return true; 
	}

	/**
	 * Set the value for isNew, i.e. doesn't exist in the DB
	 *
	 * @param bool @isNew
	 * @return this
	 *
	 */
	public function setIsNew($isNew) {
		$this->isNew = $isNew ? true : false; 
		return $this; 
	}

	/**
	 * Set that the Page is fully loaded
	 *
	 * Pages::getById sets this once it has completed loading the page
	 * This method also triggers the loaded() method that hooks may listen to
	 *
	 * @param bool $isLoaded
	 *
	 */
	public function setIsLoaded($isLoaded) {
		if($isLoaded) {
			$this->processFieldDataQueue();
			unset(Page::$loadingStack[$this->settings['id']]); 
		}
		$this->isLoaded = $isLoaded ? true : false; 
		if($isLoaded) $this->loaded();
		return $this; 
	}

	/**
	 * Process and instantiate any data in the fieldDataQueue
	 *
	 * This happens after setIsLoaded(true) is called
	 *
	 */
	protected function processFieldDataQueue() {

		foreach($this->fieldDataQueue as $key => $value) {

			$field = $this->fieldgroup->get($key); 
			if(!$field) continue;

			// check for autojoin multi fields, which may have multiple values bundled into one string
			// as a result of an sql group_concat() function
			if($field->type instanceof FieldtypeMulti && ($field->flags & Field::flagAutojoin)) {
				foreach($value as $k => $v) {	
					if(is_string($v) && strpos($v, FieldtypeMulti::multiValueSeparator) !== false) {
						$value[$k] = explode(FieldtypeMulti::multiValueSeparator, $v); 	
					}
				}
			}

			// if all there is in the array is 'data', then we make that the value rather than keeping an array
			// this is so that Fieldtypes that only need to interact with a single value don't have to receive an array of data
			if(count($value) == 1 && array_key_exists('data', $value)) $value = $value['data']; 

			$this->setFieldValue($key, $value, false); 
		}
		$this->fieldDataQueue = array(); // empty it out, no longer needed
	}


	/**
	 * For hooks to listen to, triggered when page is loaded and ready
	 *
	 */
	public function ___loaded() { }


	/**
	 * Set if this page's output is allowed to be filtered by runtime formatters. 
	 *
	 * Pages used for output should have it on. 
	 * Pages you intend to manipulate and save should have it off. 
	 *
	 * @param bool @outputFormatting Optional, default true
	 * @return this
	 *
	 */
	public function setOutputFormatting($outputFormatting = true) {
		$this->outputFormatting = $outputFormatting ? true : false; 
		return $this; 
	}

	/**
	 * Return true if outputFormatting is on, false if not. 
	 *
	 * @return bool
	 *
	 */
	public function outputFormatting() {
		return $this->outputFormatting; 
	}

	/**
	 * Shorter version of setOutputFormatting() and outputFormatting() function
	 *
	 * Always returns the current state of outputFormatting like the outputFormatting() function (and unlike setOutputFormatting())
	 * You may optionally specify a boolean value for $outputFormatting which will set the current state, like setOutputFormatting().
	 *
	 * @param bool $outputFormatting If specified, sets outputFormatting ON or OFF. If not specified, outputFormatting status does not change. 
	 * @return bool outputFormatting state (before this function call, if it was changed)
	 *
	 */
	public function of($outputFormatting = null) {
		$of = $this->outputFormatting; 
		if(!is_null($outputFormatting)) $this->outputFormatting = $outputFormatting ? true : false; 
		return $of; 
	}

	/**
	 * Return instance of PagefileManager specific to this Page
	 *
	 * @return PageFilesManager
	 *
	 */
	public function filesManager() {
		if($this->is(Page::statusDeleted)) return null;
		if(is_null($this->filesManager)) $this->filesManager = new PagefilesManager($this); 
		return $this->filesManager; 
	}

	/**
	 * Prepare the page and it's fields for removal from runtime memory, called primarily by Pages::uncache()
	 *
	 */
	public function uncache() {
		if($this->template) {
			foreach($this->template->fieldgroup as $field) {
				$value = parent::get($field->name);
				if($value != null && is_object($value)) {
					if(method_exists($value, 'uncache')) $value->uncache();
					parent::set($field->name, null); 
				}
			}
		}
		if($this->filesManager) $this->filesManager->uncache(); 
		$this->filesManager = null;
	}

	/**
	 * Ensures that isset() and empty() work for this classes properties. 
	 *
	 * See the Page::issetHas property which can be set to adjust the behavior of this function.
	 *
	 */
	public function __isset($key) {
		if(isset($this->settings[$key])) return true; 
		if(self::$issetHas && $this->template && $this->template->fieldgroup->hasField($key)) return true;
		return parent::__isset($key); 
	}

	/**
	 * Returns the parent page that has the template from which we get our role/access settings from
	 *
	 * @return Page|NullPage Returns NullPage if none found
	 *
	 */
	public function getAccessParent() {
		return PageAccess::getAccessParent($this);
	}

	/**
	 * Returns the template from which we get our role/access settings from
	 *
	 * @return Template|null Returns null if none	
	 *
	 */
	public function getAccessTemplate() {
		return PageAccess::getAccessTemplate($this);
	}
	
	/**
	 * Return the PageArray of roles that have access to this page
	 *
	 * This is determined from the page's template. If the page's template has roles turned off, 
	 * then it will go down the tree till it finds usable roles to use. 
	 *
	 * @return PageArray
	 *
	 */
	public function getAccessRoles() {
		return PageAccess::getAccessRoles($this);
	}

	/**
	 * Returns whether this page has the given access role
	 *
	 * Given access role may be a role name, role ID or Role object
	 *
	 * @param string|int|Role $role 
	 * @return bool
	 *
	 */
	public function hasAccessRole($role) {
		return PageAccess::hasAccessRole($this, $role); 
	}

	/**
	 * Is $value1 equal to $value2?
	 *
	 * @param string $key Name of the key that triggered the check (see WireData::set)
	 * @param mixed $value1
	 * @param mixed $value2
	 * @return bool
	 *
	 */
	protected function isEqual($key, $value1, $value2) {
		if($value1 === $value2) {
			if(is_object($value1) && $value1 instanceof Wire && $value1->isChanged()) $this->trackChange($key);
			return true; 
		} 
		return false;
	}

}


