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
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 * @link https://processwire.com/api/variables/page/ Offical $page Documentation
 * @link https://processwire.com/api/selectors/ Official Selectors Documentation
 *
 * @property int $id The numbered ID of the current page
 * @property string $name The name assigned to the page, as it appears in the URL
 * @property string $title The page's title (headline) text
 * @property string $path The page's URL path from the homepage (i.e. /about/staff/ryan/)
 * @property string $url The page's URL path from the server's document root (may be the same as the $page->path)
 * @property string $httpUrl Same as $page->url, except includes protocol (http or https) and hostname.
 * @property Page|string|int $parent The parent Page object or a NullPage if there is no parent. For assignment, you may also use the parent path (string) or id (integer). 
 * @property int $parent_id The numbered ID of the parent page or 0 if homepage or NullPage.
 * @property PageArray $parents All the parent pages down to the root (homepage). Returns a PageArray.
 * @property Page $rootParent The parent page closest to the homepage (typically used for identifying a section)
 * @property Template|string $template The Template object this page is using. The template name (string) may also be used for assignment.
 * @property FieldsArray $fields All the Fields assigned to this page (via it's template, same as $page->template->fields). Returns a FieldsArray.
 * @property int $numChildren The number of children (subpages) this page has, with no exclusions (fast).
 * @property int $numVisibleChildren The number of visible children (subpages) this page has. Excludes unpublished, no-access, hidden, etc.
 * @property PageArray $children All the children (subpages) of this page. Returns a PageArray. See also $page->children($selector).
 * @property Page $child The first child of this page. Returns a Page. See also $page->child($selector).
 * @property PageArray $siblings All the sibling pages of this page. Returns a PageArray. See also $page->siblings($selector).
 * @property Page $next This page's next sibling page, or NullPage if it is the last sibling. See also $page->next($pageArray).
 * @property Page $prev This page's previous sibling page, or NullPage if it is the first sibling. See also $page->prev($pageArray).
 * @property int $created Unix timestamp of when the page was created
 * @property int $modified Unix timestamp of when the page was last modified
 * @property int $published Unix timestamp of when the page was last published
 * @property int $created_users_id ID of created user
 * @property User $createdUser The user that created this page. Returns a User or a NullUser.
 * @property int $modified_users_id ID of last modified user
 * @property User $modifiedUser The user that last modified this page. Returns a User or a NullUser.
 * @property PagefilesManager $filesManager
 * @property bool $outputFormatting Whether output formatting is enabled or not. 
 * @property Page|null $parentPrevious Previous parent, if changed. Null if not. 
 * @property Template|null $templatePrevious Previous template, if changed. Null if not. 
 * @property string $namePrevious Previous name, if changed. Blank if not. 
 * @property int $sort Sort order of this page relative to siblings (applicable when manual sorting is used).  
 * @property string $sortfield Field that a page is sorted by relative to its siblings (default=sort, which means drag/drop manual)
 * @property null|array _statusCorruptedFields Field names that caused the page to have Page::statusCorrupted status. 
 * @property int $status Page status flags
 * @property string statusStr Returns space-separated string of status names active on this page.
 * @property Fieldgroup $fieldgroup Shorter alias for $page->template->fieldgroup
 * @property string $editUrl
 * @property string $editURL
 * 
 * Methods added by PageRender.module: 
 * -----------------------------------
 * @method string render() Returns rendered page markup. echo $page->render();
 * 
 * Methods added by PagePermissions.module: 
 * ----------------------------------------
 * @method bool viewable($fieldName = '') Returns true if the page (and optionally field) is viewable by the current user, false if not. 
 * @method bool editable($fieldName = '') Returns true if the page (and optionally field) is editable by the current user, false if not. 
 * @method bool publishable() Returns true if the page is publishable by the current user, false if not. 
 * @method bool listable() Returns true if the page is listable by the current user, false if not. 
 * @method bool deleteable() Returns true if the page is deleteable by the current user, false if not. 
 * @method bool deletable() Alias of deleteable().
 * @method bool trashable() Returns true if the page is trashable by the current user, false if not. 
 * @method bool addable($pageToAdd = null) Returns true if the current user can add children to the page, false if not. Optionally specify the page to be added for additional access checking. 
 * @method bool moveable($newParent = null) Returns true if the current user can move this page. Optionally specify the new parent to check if the page is moveable to that parent. 
 * @method bool sortable() Returns true if the current user can change the sort order of the current page (within the same parent). 
 *
 * Methods added by LanguageSupport.module (not installed by default) 
 * ------------------------------------------------------------------
 * @method Page setLanguageValue($language, $fieldName, $value) Set value for field in language (requires LanguageSupport module). $language may be ID, language name or Language object.
 * @method Page getLanguageValue($language, $fieldName) Get value for field in language (requires LanguageSupport module). $language may be ID, language name or Language object. 
 * 
 * Methods added by ProDrafts.module (if installed)
 * ------------------------------------------------
 * @method ProDraft|int|string|Page|array draft($key = null, $value = null)
 * 
 * Hookable methods
 * ----------------
 * @method mixed getUnknown($key) Last stop to find a property that we haven't been able to locate.
 * @method Page rootParent() Get parent closest to homepage.
 * @method void loaded() Called when page is loaded.
 * @method void setEditor(WirePageEditor $editor)
 * @method string getIcon()
 * @method getMarkup($key) Return the markup value for a given field name or {tag} string.
 *
 */

class Page extends WireData implements Countable, WireMatchable {

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
	const statusDraft = 64; 		// page has pending draft changes
	const statusVersions = 128;		// page has version data available
	const statusTemp = 512;			// page is temporary and 1+ day old unpublished pages with this status may be automatically deleted
	const statusHidden = 1024;		// page is excluded selector methods like $pages->find() and $page->children() unless status is specified, like "status&1"
	const statusUnpublished = 2048; // page is not published and is not renderable. 
	const statusTrash = 8192; 		// page is in the trash
	const statusDeleted = 16384; 	// page is deleted (runtime only)
	const statusSystemOverride = 32768; // page is in a state where system flags may be overridden
	const statusCorrupted = 131072; // page was corrupted at runtime and is NOT saveable: see setFieldValue() and $outputFormatting. (runtime)
	const statusMax = 9999999;		// number to use for max status comparisons, runtime only
	
	/**
	 * Status string shortcuts, so that status can be specified as a word
	 * 
	 * See also: self::getStatuses() method. 
	 * 
	 * @var array
	 * 
	 */
	static protected $statuses = array(
		'locked' => self::statusLocked,
		'systemID' => self::statusSystemID,
		'system' => self::statusSystem,
		'draft' => self::statusDraft,
		'versions' => self::statusVersions,
		'temp' => self::statusTemp,
		'hidden' => self::statusHidden,
		'unpublished' => self::statusUnpublished,
		'trash' => self::statusTrash,
		'deleted' => self::statusDeleted,
		'systemOverride' => self::statusSystemOverride, 
		'corrupted' => self::statusCorrupted, 
		);

	/**
	 * The Template this page is using (object)
	 *
	 * @var Template|null
	 * 
	 */
	protected $template;

	/**
	 * The previous template used by the page, if it was changed during runtime. 	
	 *
	 * Allows Pages::save() to delete data that's no longer used. 
	 * 
	 * @var Template|null
	 *
	 */
	private $templatePrevious; 

	/**
	 * Parent Page - Instance of Page
	 * 
	 * @var Page|null
	 *
	 */
	protected $parent = null;

	/**
	 * The previous parent used by the page, if it was changed during runtime. 	
	 *
	 * Allows Pages::save() to identify when the parent has changed
	 * 
	 * @var Page|null
	 *
	 */
	private $parentPrevious; 

	/**
	 * The previous name used by this page, if it changed during runtime.
	 * 
	 * @var string
	 *
	 */
	private $namePrevious; 

	/**
	 * The previous status used by this page, if it changed during runtime.
	 * 
	 * @var int
	 *
	 */
	private $statusPrevious; 

	/**
	 * Reference to the Page's template file, used for output. Instantiated only when asked for. 
	 * 
	 * @var TemplateFile|null
	 *
	 */
	private $output; 

	/**
	 * Instance of PagefilesManager, which manages and migrates file versions for this page
	 *
	 * Only instantiated upon request, so access only from filesManager() method in Page class. 
	 * Outside API can use $page->filesManager.
	 * 
	 * @var PagefilesManager|null
	 *
	 */
	private $filesManager = null;

	/**
	 * Field data that queues while the page is loading. 
	 *
	 * Once setIsLoaded(true) is called, this data is processed and instantiated into the Page and the fieldDataQueue is emptied (and no longer relevant)	
	 * 
	 * @var array
	 *
	 */
	protected $fieldDataQueue = array();

	/**
	 * Is this a new page (not yet existing in the database)?
	 * 
	 * @var bool
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
	 * @var bool
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
	 * @var bool
	 *
	 */
	protected $outputFormatting = false; 

	/**
	 * A unique instance ID assigned to the page at the time it's loaded (for debugging purposes only)
	 * 
	 * @var int
	 *
	 */
	protected $instanceID = 0; 

	/**
	 * IDs for all the instances of pages, used for debugging and testing.
	 *
	 * Indexed by $instanceID => $pageID
	 * 
	 * @var array
	 *
	 */
	static public $instanceIDs = array();

	/**
	 * Stack of ID indexed Page objects that are currently in the loading process. 
	 *
	 * Used to avoid possible circular references when multiple pages referencing each other are being populated at the same time.
	 * 
	 * @var array
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
	 * @var bool
	 * 
	 */
	static public $issetHas = false; 

	/**
	 * The current page number, starting from 1
	 *
	 * @deprecated, use $input->pageNum instead. 
	 * 
	 * @var int
	 *
	 */
	protected $pageNum = 1; 

	/**
	 * Reference to main config, optimization so that get() method doesn't get called
	 * 
	 * @var Config|null
	 *
	 */
	protected $config = null; 

	/**
	 * When true, exceptions won't be thrown when values are set before templates
	 * 
	 * @var bool
	 *
	 */
	protected $quietMode = false;

	/**
	 * Cached User that created this page
	 * 
	 * @var User|null
	 * 
	 */
	protected $createdUser = null;

	/**
	 * Cached User that last modified the page
	 * 
	 * @var User|null
	 * 
	 */
	protected $modifiedUser = null;

	/**
	 * Page-specific settings which are either saved in pages table, or generated at runtime.
	 * 
	 * @var array
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
		'created' => 0,
		'modified' => 0,
		'published' => 0,
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
		$this->statusPrevious = null;
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
			// no need to clone Page objects as we still want to reference the original page
			if(!is_object($value) || $value instanceof Page) continue;
			$value2 = clone $value; 
			$this->set($name, $value2); // commit cloned value
			// if value is Pagefiles, then tell it the new page
			if($value2 instanceof Pagefiles) $value2->setPage($this); 

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
	 * @throws WireException
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
				// no break is intentional
			case 'sort': 
			case 'numChildren': 
			case 'num_children':
				$value = (int) $value; 
				if($key == 'num_children') $key = 'numChildren';
				if($this->settings[$key] !== $value) $this->trackChange($key, $this->settings[$key], $value); 
				$this->settings[$key] = $value; 
				break;
			case 'status':
				$this->setStatus($value); 
				break;
			case 'statusPrevious':
				$this->statusPrevious = is_null($value) ? null : (int) $value; 
				break;
			case 'name':
				if($this->isLoaded) {
					$beautify = empty($this->settings[$key]); 
					$value = $this->wire('sanitizer')->pageName($value, $beautify); 
					if($this->settings[$key] !== $value) {
						if($this->settings[$key] && empty($this->namePrevious)) $this->namePrevious = $this->settings[$key];
						$this->trackChange($key, $this->settings[$key], $value); 
					}
				}
				$this->settings[$key] = $value; 
				break;
			case 'parent': 
			case 'parent_id':
				if(($key == 'parent_id' || is_int($value)) && $value) $value = $this->wire('pages')->get((int)$value); 
					else if(is_string($value)) $value = $this->wire('pages')->get($value); 
				if($value) $this->setParent($value);
				break;
			case 'parentPrevious':
				if(is_null($value) || $value instanceof Page) $this->parentPrevious = $value; 
				break;
			case 'template': 
			case 'templates_id':
				if($key == 'templates_id' && $this->template && $this->template->id == $value) break;
				if($key == 'templates_id') $value = $this->wire('templates')->get((int)$value); 
				$this->setTemplate($value); 
				break;
			case 'created': 
			case 'modified':
			case 'published':
				if(is_null($value)) $value = 0;
				if(!ctype_digit("$value")) $value = strtotime($value); 
				$value = (int) $value; 
				if($this->settings[$key] !== $value) $this->trackChange($key, $this->settings[$key], $value); 
				$this->settings[$key] = $value;
				break;
			case 'created_users_id':
			case 'modified_users_id':
				$value = (int) $value;
				if($this->settings[$key] !== $value) $this->trackChange($key, $this->settings[$key], $value); 
				$this->settings[$key] = $value; 
				break;
			case 'createdUser':
			case 'modifiedUser':
				$this->setUser($value, str_replace('User', '', $key));
				break;
			case 'sortfield':
				if($this->template && $this->template->sortfield) break;
				$value = $this->wire('pages')->sortfields()->decode($value); 
				if($this->settings[$key] != $value) $this->trackChange($key, $this->settings[$key], $value); 
				$this->settings[$key] = $value; 
				break;
			case 'isLoaded': 
				$this->setIsLoaded($value); 
				break;
			case 'pageNum':
				// note: pageNum is deprecated, use $input->pageNum instead
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
	 * @return $this
	 *
	 */
	public function setQuietly($key, $value) {
		$this->quietMode = true; 
		parent::setQuietly($key, $value);
		$this->quietMode = false;
		return $this; 
	}

	/**
	 * Force setting a value, skipping over any checks or errors
	 * 
	 * Enables setting a value when page has no template assigned, for example. 
	 * 
	 * @param $key
	 * @param $value
	 * @return $this
	 * 
	 */
	public function setForced($key, $value) {
		return parent::set($key, $value); 
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
	 * @return $this
	 * @throws WireException
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
			return $this;
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
			$corruptedFields = $this->get('_statusCorruptedFields');
			if(!is_array($corruptedFields)) $corruptedFields = array();
			$corruptedFields[$field->name] = $field->name;
			$this->set('_statusCorruptedFields', $corruptedFields); 
		}

		// isLoaded so sanitizeValue can determine if it can perform a typecast rather than a full sanitization (when helpful)
		// we don't use setIsLoaded() so as to avoid triggering any other functions
		$isLoaded = $this->isLoaded;
		if(!$load) $this->isLoaded = false;
		// ensure that the value is in a safe format and set it 
		$value = $field->type->sanitizeValue($this, $field, $value); 
		// Silently restore isLoaded state
		if(!$load) $this->isLoaded = $isLoaded;

		return parent::set($key, $value); 
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
		if(is_array($key)) $key = implode('|', $key); 
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
			case 'has_parent':
			case 'hasParent': 
				$value = $this->parents();
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
			case 'template_id':
			case 'templates_id':
			case 'templateID':
			case 'templatesID':
				$value = $this->template ? $this->template->id : 0; 
				break;
			case 'template':
			case 'templatePrevious':
			case 'parentPrevious':
			case 'namePrevious':
			case 'statusPrevious':
			case 'isLoaded':
			case 'isNew':
			case 'pageNum':
			case 'instanceID': 
				$value = $this->$key; 
				break;
			case 'out':
			case 'output':
				$value = $this->output();
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
				$value = (int) $this->settings['modified_users_id']; 
				break;
			case 'created_users_id':
			case 'createdUsersID':
			case 'createdUserID': 
				$value = (int) $this->settings['created_users_id'];
				break;
			case 'modifiedUser':
			case 'createdUser':
				if(!$this->$key) {
					$_key = str_replace('User', '', $key) . '_users_id';
					$u = $this->wire('user');
					if($this->settings[$_key] == $u->id) {
						$this->set($key, $u); // prevent possible recursion loop
					} else {
						$u = $this->wire('users')->get((int) $this->settings[$_key]);
						$this->set($key, $u);
					}
				}
				$value = $this->$key; 
				if($value) $value->of($this->of());
				break;
			case 'urlSegment':
				$value = $this->wire('input')->urlSegment1; // deprecated, but kept for backwards compatibility
				break;
			case 'accessTemplate': 
				$value = $this->getAccessTemplate();
				break;
			case 'num_children': 
			case 'numChildren': 
				$value = $this->settings['numChildren'];
				break;
			case 'numChildrenVisible':
			case 'numVisibleChildren':
			case 'hasChildren': 
				$value = $this->numChildren(true);
				break;
			case 'editUrl':
			case 'editURL':
				$value = $this->editUrl();
				break;
			case 'statusStr':
				$value = implode(' ', $this->status(true)); 
				break;
			case 'modifiedStr':
			case 'createdStr':
			case 'publishedStr':
				$value = $this->settings[str_replace('Str', '', $key)];
				$value = $value ? wireDate($this->wire('config')->dateFormat, $value) : '';
				break;
			default:
				if($key && isset($this->settings[(string)$key])) return $this->settings[$key];
				
				// populate a formatted string with {tag} vars
				if(strpos($key, '{') !== false && strpos($key, '}')) return $this->getMarkup($key);

				if(($value = $this->getFieldFirstValue($key)) !== null) return $value; 
				if(($value = $this->getFieldValue($key)) !== null) return $value;
				
				// if there is a selector, we'll assume they are using the get() method to get a child
				if(Selectors::stringHasOperator($key)) return $this->child($key);

				// check if it's a field.subfield property
				if(strpos($key, '.') && ($value = $this->getFieldSubfieldValue($key)) !== null) return $value; 
				
				// optionally let a hook look at it
				if(self::isHooked('Page::getUnknown()')) $value = $this->getUnknown($key);
		}

		return $value; 
	}

	/**
	 * If given a field.subfield string, returns the associated value
	 * 
	 * This is like the getDot() method, but with additional protection during output formatting. 
	 * 
	 * @param $key
	 * @return mixed|null
	 * 
	 */
	protected function getFieldSubfieldValue($key) {
		$value = null;
		if(!strpos($key, '.')) return null;
		if($this->outputFormatting()) {
			// allow limited access to field.subfield properties when output formatting is on
			// we only allow known custom fields, and only 1 level of subfield
			list($key1, $key2) = explode('.', $key);
			$field = $this->template->fieldgroup->getField($key1); 
			if($field && !($field->flags & Field::flagSystem)) {
				// known custom field, non-system
				// if neither is an API var, then we'll allow it
				if(!$this->wire($key1) && !$this->wire($key2)) $value = $this->getDot("$key1.$key2");
			}
		} else {
			// we allow any field.subfield properties when output formatting is off
			$value = $this->getDot($key);
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
	 * @param string $multiKey
	 * @param bool $getKey Specify true to get the first matching key (name) rather than value
	 * @return null|mixed Returns null if no values match, or if there aren't multiple keys split by "|" chars
	 *
	 */
	protected function getFieldFirstValue($multiKey, $getKey = false) {

		// looking multiple keys split by "|" chars, and not an '=' selector
		if(strpos($multiKey, '|') === false || strpos($multiKey, '=') !== false) return null;

		$value = null;
		$keys = explode('|', $multiKey); 

		foreach($keys as $key) {
			$value = $this->get($key);
			
			if(is_object($value)) {
				// like LanguagesPageFieldValue or WireArray
				$str = trim((string) $value); 
				if(!strlen($str)) continue; 
				
			} else if(is_array($value)) {
				// array with no items
				if(!count($value)) continue;
				
			} else if(is_string($value)) {
				$value = trim($value); 
			}
			
			if($value) {
				if($getKey) $value = $key;
				break;
			}
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
		if(!$this->template) return parent::get($key); 
		$field = $this->template->fieldgroup->getField($key);
		if($field && $this->outputFormatting && $this->template->fieldgroup->hasFieldContext($field)) {
			// if field has context available and output formatting is on, get the field in context
			// @todo determine if we can retrieve it in context even when output formatting is off
			$field = $this->template->fieldgroup->getFieldContext($field->name);
		}
		$value = parent::get($key); 
		if(!$field) return $value;  // likely a runtime field, not part of our data
		
		if($field->useRoles && $this->outputFormatting) {
			// API access may be limited when output formatting is ON
			if($field->flags & Field::flagAccessAPI) {
				// API access always allowed because of flag
			} else if($this->viewable($field)) {
				// User has view permission for this field
			} else {
				// API access is denied when output formatting is ON
				// so just return a blank value as defined by the Fieldtype
				// note: we do not store this blank value in the Page, so that
				// the real value can potentially be loaded later without output formatting
				$value = $field->type->getBlankValue($this, $field); 
				return $field->type->formatValue($this, $field, $value);
			}
		}

		// if the value is already loaded, return it 
		if(!is_null($value)) return $this->outputFormatting ? $field->type->formatValue($this, $field, $value) : $value; 
		$track = $this->trackChanges();
		$this->setTrackChanges(false); 
		if(!$field->type) return null;
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
	 * Return the markup value for a given field name or {tag} string
	 *
	 * 1. If given a field name (or name.subname or name1|name2|name3) it will return the
	 * markup value as defined by the fieldtype.
	 *
	 * 2. If given a string with field names referenced in {tags}, it will populate those
	 * tags and return the populated string.
	 *
	 * @param string $key Field name or markup string with field {name} tags in it
	 * @return string
	 *
	 */
	public function ___getMarkup($key) {
		
		$value = '';
		
		if(strpos($key, '{') !== false && strpos($key, '}')) {
			// populate a string with {tags}
			// note that the wirePopulateStringTags() function calls back on this method
			// to retrieve the markup values for each of the found field names
			return wirePopulateStringTags($key, $this);
		}

		if(strpos($key, '|') !== false) {
			$key = $this->getFieldFirstValue($key, true);
			if(!$key) return '';
		}
		
		if($this->wire('sanitizer')->name($key) != $key) {
			// not a possible field name
			return '';
		}

		$parts = strpos($key, '.') ? explode('.', $key) : array($key);
		$value = $this;
		
		do {
			
			$name = array_shift($parts);
			$field = null;
			if($this->template && $this->template->fieldgroup) $field = $this->template->fieldgroup->getField($name);
			
			if(!$field && $this->wire($name)) {
				// disallow API vars
				$value = '';
				break;
			}
			
			if($value instanceof Page) {
				$value = $value->getFormatted($name);
			} else if($value instanceof Wire) {
				$value = $value->get($name);
			} else {
				$value = $value->$name;
			}
			
			if($field && count($parts) < 2) {
				// this is a field that will provide its own formatted value
				$subname = count($parts) == 1 ? array_shift($parts) : '';
				if(!$this->wire($subname)) $value = $field->type->markupValue($this, $field, $value, $subname);
			}
			
		} while(is_object($value) && count($parts));
		
		if(is_object($value)) {
			if($value instanceof Page) $value = $value->getFormatted('title|name');
			if($value instanceof PageArray) $value = $value->getMarkup();
		}
		
		if(!is_string($value)) $value = (string) $value;
		
		return $value;
	}


	/**
	 * Get the raw/unformatted value of a field, regardless of what $this->outputFormatting is set at
	 * 
	 * @param string $key Field or property name to retrieve
	 * @return mixed
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
	 * Get the formatted value of a field, regardless of what $this->outputFormatting is set at
	 *
	 * @param string $key Field or property name to retrieve
	 * @return mixed
	 *
	 */
	public function getFormatted($key) {
		$outputFormatting = $this->outputFormatting;
		if(!$outputFormatting) $this->setOutputFormatting(true);
		$value = $this->get($key);
		if(!$outputFormatting) $this->setOutputFormatting(false);
		return $value;
	}

	/**
	 * @see get
	 * 
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function __get($key) {
		return $this->get($key); 
	}

	/**
	 * @see set
	 * 
	 * @param string $key
	 * @param mixed $value
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value); 
	}


	/**
	 * Set the 'status' setting, with some built-in protections
	 * 
	 * @param int|array|string Status value, array of status names or values, or status name string
	 *
	 */
	protected function setStatus($value) {
		
		if(!is_int($value)) {
			// status provided as something other than integer
			if(is_string($value) && !ctype_digit($value)) {
				// string of one or more status names
				if(strpos($value, ',') !== false) $value = str_replace(array(', ', ','), ' ', $value);
				$value = explode(' ', strtolower($value));
			} 
			if(is_array($value)) {
				// array of status names or numbers
				$status = 0;
				foreach($value as $v) {
					if(is_int($v) || ctype_digit("$v")) { // integer
						$status = $status | ((int) $v);
					} else if(is_string($v) && isset(self::$statuses[$v])) { // string (status name)
						$status = $status | self::$statuses[$v];
					}
				}
				if($status) $value = $status; 
			}
			// note if $value started as an integer string, i.e. "123", it gets passed through to below
		}
		
		$value = (int) $value; 
		$override = $this->settings['status'] & Page::statusSystemOverride; 
		if(!$override) { 
			if($this->settings['status'] & Page::statusSystemID) $value = $value | Page::statusSystemID;
			if($this->settings['status'] & Page::statusSystem) $value = $value | Page::statusSystem; 
		}
		if($this->settings['status'] != $value) {
			$this->trackChange('status', $this->settings['status'], $value);
			$this->statusPrevious = $this->settings['status'];
		}
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
	 * @param Template|int|string $tpl
	 * @return $this
	 * @throws WireException if given invalid arguments or template not allowed for page
	 *
	 */
	protected function setTemplate($tpl) {
		if(!is_object($tpl)) $tpl = $this->wire('templates')->get($tpl); 
		if(!$tpl instanceof Template) throw new WireException("Invalid value sent to Page::setTemplate"); 
		if($this->template && $this->template->id != $tpl->id) {
			if($this->settings['status'] & Page::statusSystem) throw new WireException("Template changes are disallowed on this page"); 
			if(is_null($this->templatePrevious)) $this->templatePrevious = $this->template; 
			$this->trackChange('template', $this->template, $tpl); 
		}
		if($tpl->sortfield) $this->settings['sortfield'] = $tpl->sortfield; 
		$this->template = $tpl; 
		return $this;
	}


	/**
	 * Set this page's parent Page
	 * 
	 * @param Page $parent
	 * @return $this
	 * @throws WireException if given impossible $parent or parent changes aren't allowed
	 *
	 */
	protected function setParent(Page $parent) {
		if($this->parent && $this->parent->id == $parent->id) return $this; 
		if($parent->id && $this->id == $parent->id || $parent->parents->has($this)) {
			throw new WireException("Page cannot be its own parent");
		}
		$this->trackChange('parent', $this->parent, $parent);
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
	 * @param User|int|string $user User object or integer/string representation of User
	 * @param string $userType Must be either 'created' or 'modified' 
	 * @return $this
	 * @throws WireException
	 *
	 */
	protected function setUser($user, $userType) {

		if(!$user instanceof User) {
			if(is_object($user)) {
				$user = null;
			} else {
				$user = $this->wire('users')->get($user);
			}
		}

		// if they are setting an invalid user or unknown user, then the Page defaults to the super user
		if(!$user || !$user->id || !$user instanceof User) {
			$user = $this->wire('users')->get($this->wire('config')->superUserPageID);
		}

		if($userType == 'created') {
			$field = 'created_users_id';
			$this->createdUser = $user; 
		} else if($userType == 'modified') {
			$field = 'modified_users_id';
			$this->modifiedUser = $user;
		} else {
			throw new WireException("Unknown user type in Page::setUser(user, type)"); 
		}

		$existingUserID = $this->settings[$field]; 
		if($existingUserID != $user->id) $this->trackChange($field, $existingUserID, $user->id); 
		$this->settings[$field] = $user->id; 
		return $this; 	
	}

	/**
	 * Find Pages in the descendent hierarchy
	 *
	 * Same as Pages::find() except that the results are limited to descendents of this Page
	 *
	 * @param string $selector Selector string
	 * @param array $options Same as the $options array passed to $pages->find(). 
	 * @return PageArray
	 * @see Pages::find
	 *
	 */
	public function find($selector = '', $options = array()) {
		if(!$this->numChildren) return new PageArray();
		$selector = "has_parent={$this->id}, $selector"; 
		return $this->wire('pages')->find(trim($selector, ", "), $options); 
	}

	/**
	 * Return this page's children pages, optionally filtered by a selector
	 *
	 * @param string $selector Selector to use, or omit to return all children
	 * @param array $options Options per Pages::find
	 * @return PageArray
	 *
	 */
	public function children($selector = '', $options = array()) {
		return $this->traversal()->children($this, $selector, $options); 
	}

	/**
	 * Return number of children, optionally with conditions
	 *
	 * Use this over $page->numChildren property when you want to specify a selector, or when you want the result to
	 * include only visible children. See the options for the $selector argument. 
	 *
	 * @param bool|string $selector 
	 *	When not specified, result includes all children without conditions, same as $page->numChildren property.
	 *	When a string, a selector string is assumed and quantity will be counted based on selector.
	 * 	When boolean true, number includes only visible children (excludes unpublished, hidden, no-access, etc.)
	 *	When boolean false, number includes all children without conditions, including unpublished, hidden, no-access, etc.
	 * @return int Number of children
	 *
	 */
	public function numChildren($selector = null) {
		if(!$this->settings['numChildren'] && is_null($selector)) return $this->settings['numChildren']; 
		return $this->traversal()->numChildren($this, $selector); 
	}

	/**
	 * Similar to numChildren except that default behavior is to exclude non-visible children.
	 * 
	 * This method may be more convenient for front-end navigation use than the numChildren() method. 
	 * 
	 * @param bool|string $selector
	 *	When not specified, result is quantity of visible children (excludes unpublished, hidden, no-access, etc.)
	 *	When a string, a selector string is assumed and quantity will be counted based on selector.
	 * 	When boolean true, number includes only visible children (this is the default behavior, so no need to specify this value).
	 *	When boolean false, number includes all children without conditions, including unpublished, hidden, no-access, etc.
	 * @return int Number of children
	 * 
	 */
	public function hasChildren($selector = true) {
		return $this->numChildren($selector);
	}

	/**
	 * Return the page's first single child that matches the given selector. 
	 *
	 * Same as children() but returns a Page object or NullPage (with id=0) rather than a PageArray
	 *
	 * @param string $selector Selector to use, or blank to return the first child. 
	 * @param array $options Options per Pages::find
	 * @return Page|NullPage
	 *
	 */
	public function child($selector = '', $options = array()) {
		return $this->traversal()->child($this, $selector, $options); 
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
	 * @param string $selector Optional selector string to filter parents by
	 * @return PageArray
	 *
	 */
	public function parents($selector = '') {
		return $this->traversal()->parents($this, $selector); 
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
		return $this->traversal()->parentsUntil($this, $selector, $filter); 
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
		return $this->traversal()->rootParent($this); 
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
		return $this->traversal()->siblings($this, $selector); 
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
		return $this->traversal()->next($this, $selector, $siblings); 
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
		return $this->traversal()->nextAll($this, $selector, $siblings); 
	}

	/**
	 * Return all sibling pages after this one until matching the one specified 
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @param PageArray $siblings Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	public function nextUntil($selector = '', $filter = '', PageArray $siblings = null) {
		return $this->traversal()->nextUntil($this, $selector, $filter, $siblings); 
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
	 * @param PageArray|null $siblings Optional siblings to use instead of the default. May also be specified as first argument when no selector needed.
	 * @return Page|NullPage Returns the previous sibling page, or a NullPage if none found. 
	 *
	 */
	public function prev($selector = '', PageArray $siblings = null) {
		return $this->traversal()->prev($this, $selector, $siblings); 
	}

	/**
	 * Return all sibling pages before this one, optionally matching a selector
	 *
	 * @param string $selector Optional selector string. When specified, will filter the found siblings.
	 * @param PageArray|null $siblings Optional siblings to use instead of the default. 
	 * @return Page|NullPage Returns all matching pages before this one.
	 *
	 */
	public function prevAll($selector = '', PageArray $siblings = null) {
		return $this->traversal()->prevAll($this, $selector, $siblings); 
	}

	/**
	 * Return all sibling pages before this one until matching the one specified 
	 *
	 * @param string|Page $selector May either be a selector string or Page to stop at. Results will not include this. 
	 * @param string $filter Optional selector string to filter matched pages by
	 * @param PageArray|null $siblings Optional PageArray of siblings to use instead of all from the page.
	 * @return PageArray
	 *
	 */
	public function prevUntil($selector = '', $filter = '', PageArray $siblings = null) {
		return $this->traversal()->prevUntil($this, $selector, $filter, $siblings); 
	}


	/**
	 * Save this page to the database. 
	 *
	 * To hook into this (___save), use 'Pages::save' 
	 * To hook into a field-only save, use 'Pages::saveField'
	 *
	 * @param Field|string $field Optional field to save (name of field or Field object)
	 * @param array $options See Pages::save for options. You may also specify $options as the first argument if no $field is needed.
	 * @return bool true on success false on fail
	 * @throws WireException on database error
	 *
	 */
	public function save($field = null, array $options = array()) {
		if(is_array($field) && empty($options)) {
			$options = $field;
			$field = null;
		}
		if(!is_null($field) && $this->template->fieldgroup->hasField($field)) {
			return $this->wire('pages')->saveField($this, $field, $options);
		}
		return $this->wire('pages')->save($this, $options);
	}
	
	/**
	 * Set a field value (or array of fields and values) and save the page
	 *
	 * This method does not need output formatting to be turned off first, so make sure that whatever
	 * value(s) you set are not formatted values!
	 *
	 * @param array|string $key Field or property name to set, or array of (key => value)
	 * @param string|int|bool|object $value Value to set, or omit if you provided an array in first argument.
	 * @param array $options Additional options, as specified with Pages::save()
	 * @return bool
	 *
	 */
	public function setAndSave($key, $value = null, array $options = array()) {
		if(is_array($key)) {
			$values = $key;
			$property = count($values) == 1 ? key($values) : '';
		} else {
			$property = $key;
			$values = array($key => $value);
		}
		$of = $this->of();
		if($of) $this->of(false);
		foreach($values as $k => $v) {
			$this->set($k, $v);
		}
		if($property) {
			$result = $this->save($property, $options);
		} else {
			$result = $this->save($options);
		}
		if($of) $this->of(true);
		return $result;
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
		return $this->wire('pages')->delete($this); 
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
		return $this->wire('pages')->trash($this); 
	}
	
	/**
	 * Returns number of children page has, fulfilling Countable interface
	 *
	 * When output formatting is on, returns only number of visible children.
	 * When output formatting is off, returns number of all children.
	 *
	 * @return int
	 *
	 */
	public function count() {
		if($this->outputFormatting) return $this->numChildren(true);
		return $this->numChildren(false);
	}

	/**
	 * Allow iteration of the properties with foreach(), fulfilling IteratorAggregate interface.
	 *
	 */
	public function getIterator() {
		$a = $this->settings; 
		if($this->template && $this->template->fieldgroup) {
			foreach($this->template->fieldgroup as $field) {
				$a[$field->name] = $this->get($field->name); 
			}
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
	 * Clears out any tracked changes and turns change tracking ON or OFF
	 *
	 * @param bool $trackChanges True to turn change tracking ON, or false to turn OFF. Default of true is assumed. 
	 * @return $this
	 *
	 */
	public function resetTrackChanges($trackChanges = true) {
		parent::resetTrackChanges($trackChanges); 
		foreach($this->data as $key => $value) {
			if(is_object($value) && $value instanceof Wire && $value !== $this) $value->resetTrackChanges($trackChanges); 
		}
		return $this; 
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
		$url = rtrim($this->wire('config')->urls->root, "/") . $this->path(); 
		if($this->template->slashUrls === 0 && $this->settings['id'] > 1) $url = rtrim($url, '/'); 
		return $url;
	}

	/**
	 * Like URL, but includes the protocol and hostname
	 * 
	 * @return string
	 *
	 */
	public function httpUrl() {
		if(!$this->template) return '';

		switch($this->template->https) {
			case -1: $protocol = 'http'; break;
			case 1: $protocol = 'https'; break;
			default: $protocol = $this->wire('config')->https ? 'https' : 'http'; 
		}

		return "$protocol://" . $this->wire('config')->httpHost . $this->url();
	}

	/**
	 * Return the URL necessary to edit this page
	 * 
	 * @return string
	 * 
	 */
	public function editUrl() {
		$adminTemplate = $this->wire('templates')->get('admin');
		$https = $adminTemplate && ($adminTemplate->https > 0);
		$url = ($https && !$this->wire('config')->https) ? 'https://' . $this->wire('config')->httpHost : '';
		$url .= $this->wire('config')->urls->admin . "page/edit/?id=$this->id";
		return $url;
	}

	/**
	 * Get the output TemplateFile object for rendering this page
	 *
	 * You can retrieve the results of this by calling $page->out or $page->output
	 *
	 * @internal This method is intended for internal use only, not part of the public API. 
	 * @param bool $forceNew Forces it to return a new (non-cached) TemplateFile object (default=false)
	 * @return TemplateFile
	 *
	 */
	public function output($forceNew = false) {
		if($this->output && !$forceNew) return $this->output; 
		if(!$this->template) return null;
		$this->output = new TemplateFile();
		$this->output->setThrowExceptions(false); 
		$this->output->setFilename($this->template->filename); 
		$fuel = self::getAllFuel();
		$this->output->set('wire', $fuel); 
		foreach($fuel as $key => $value) $this->output->set($key, $value); 
		$this->output->set('page', $this); 
		return $this->output; 
	}

	/**
	 * Return a Inputfield object that contains all the custom Inputfield objects required to edit this page
	 * 
	 * @param string $fieldName Optional field to limit to, typically the name of a fieldset or tab
	 * @return null|InputfieldWrapper
	 *
	 */
	public function getInputfields($fieldName = '') {
		return $this->template ? $this->template->fieldgroup->getPageInputfields($this, '', $fieldName) : null;
	}

	/**
	 * Does this page have the given status?
	 * 
	 * @param int|string $statusFlag Status number of string representation (hidden, locked, unpublished)
	 * @return bool
	 * 
	 */
	public function hasStatus($statusFlag) {
		if(is_string($statusFlag) && isset(self::$statuses[$statusFlag])) $statusFlag = self::$statuses[$statusFlag]; 
		return (bool) ($this->status & $statusFlag);
	}

	/**
	 * Add the specified status flag to this page's status
	 *
	 * @param int|string $statusFlag Status number of string representation (hidden, locked, unpublished)
	 * @return $this
	 *
	 */
	public function addStatus($statusFlag) {
		if(is_string($statusFlag) && isset(self::$statuses[$statusFlag])) $statusFlag = self::$statuses[$statusFlag]; 
		$statusFlag = (int) $statusFlag; 
		$this->setStatus($this->status | $statusFlag); 
		return $this;
	}

	/** 
	 * Remove the specified status flag from this page's status
	 *
	 * @param int|string $statusFlag Status flag integer or string representation (hidden, unpublished, locked)
	 * @return $this
	 * @throws WireException
	 *
	 */
	public function removeStatus($statusFlag) {
		if(is_string($statusFlag) && isset(self::$statuses[$statusFlag])) $statusFlag = self::$statuses[$statusFlag]; 
		$statusFlag = (int) $statusFlag; 
		$override = $this->settings['status'] & Page::statusSystemOverride; 
		if($statusFlag == Page::statusSystem || $statusFlag == Page::statusSystemID) {
			if(!$override) throw new WireException(
				"You may not remove the 'system' status from a page unless it also has system override " . 
				"status (Page::statusSystemOverride)"
			); 
		}
		$this->status = $this->status & ~$statusFlag; 
		return $this;
	}
	
	/**
	 * Given a Selectors object or a selector string, return whether this Page matches it
	 * 
	 * Implements WireMatchable interface
	 *
	 * @param string|Selectors $s
	 * @return bool
	 *
	 */
	public function matches($s) {
		return $this->comparison()->matches($this, $s);
	}

	/**
	 * Does this page have the specified status number or template name?
	 *
	 * See status flag constants at top of Page class.
	 * You may also use status names: hidden, locked, unpublished, system, systemID
	 *
	 * @param int|string|Selectors $status Status number, status name, or Template name or selector string/object
	 * @return bool
	 *
	 */
	public function is($status) {
		if(is_string($status) && isset(self::$statuses[$status])) $status = self::$statuses[$status]; 
		return $this->comparison()->is($this, $status);
	}

	/**
	 * Does this page have a 'hidden' status?
	 *
	 * @return bool
	 *
	 */
	public function isHidden() {
		return $this->hasStatus(self::statusHidden); 
	}

	/**
	 * Does this page have a 'unpublished' status?
	 *
	 * @return bool
	 *
	 */
	public function isUnpublished() {
		return $this->hasStatus(self::statusUnpublished);
	}
	
	/**
	 * Does this page have a 'locked' status?
	 *
	 * @return bool
	 *
	 */
	public function isLocked() {
		return $this->hasStatus(self::statusLocked);
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
		if($this->hasStatus(self::statusTrash)) return true; 
		$trashPageID = $this->wire('config')->trashPageID; 
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
		return self::isHooked('Page::isPublic()') ? $this->__call('isPublic', array()) : $this->___isPublic();
	}

	/**
	 * Implementation for the above isPublic function
	 * 
	 * @return bool
	 * 
	 */
	protected function ___isPublic() {
		if($this->status >= Page::statusUnpublished) return false;	
		$template = $this->getAccessTemplate();
		if(!$template || !$template->hasRole('guest')) return false;
		return true; 
	}

	/**
	 * Get or set current status
	 * 
	 * @param bool|int $value Optionally specify one of the following:
	 * 	- boolean true: to return an array of status names (indexed by status number)
	 * 	- integer|string|array: status number(s) or status name(s) to set the current page status (same as $page->status = $value)
	 * @param int|null $status If you specified true for first arg, optionally specify status value you want to use (if not the current).
	 * @return int|array|$this If setting status, $this is returned. If getting status: current status or array of status names.
	 * 
	 */
	public function status($value = false, $status = null) {
		if(!is_bool($value)) {
			$this->setStatus($value);
			return $this;
		}
		if(is_null($status)) $status = $this->status; 
		if($value === false) return $status; 
		$names = array();
		foreach(self::$statuses as $name => $value) {
			if($status & $value) $names[$value] = $name; 
		}
		return $names; 
	}

	/**
	 * Set the value for isNew, i.e. doesn't exist in the DB
	 *
	 * @internal
	 * @param bool @isNew
	 * @return $this
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
	 * @internal
	 * @param bool $isLoaded
	 * @return $this
	 *
	 */
	public function setIsLoaded($isLoaded) {
		$isLoaded = !$isLoaded || $isLoaded === 'false' ? false : true; 
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
	 * @return $this
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
		if($this->hasStatus(Page::statusDeleted)) return null;
		if(is_null($this->filesManager)) $this->filesManager = new PagefilesManager($this); 
		return $this->filesManager; 
	}

	/**
	 * Prepare the page and it's fields for removal from runtime memory, called primarily by Pages::uncache()
	 *
	 */
	public function uncache() {
		$trackChanges = $this->trackChanges();
		if($trackChanges) $this->setTrackChanges(false); 
		if($this->template) {
			foreach($this->template->fieldgroup as $field) {
				$value = parent::get($field->name);
				if($value != null && is_object($value)) {
					if(method_exists($value, 'uncache') && $value !== $this) $value->uncache(); 
					parent::set($field->name, null); 
				}
			}
		}
		if($this->filesManager) $this->filesManager->uncache(); 
		$this->filesManager = null;
		if($trackChanges) $this->setTrackChanges(true); 
	}

	/**
	 * Ensures that isset() and empty() work for this classes properties. 
	 *
	 * See the Page::issetHas property which can be set to adjust the behavior of this function.
	 * 
	 * @param string $key
	 * @return bool
	 *
	 */
	public function __isset($key) {
		if(isset($this->settings[$key])) return true;
		$natives = array('template', 'parent', 'createdUser', 'modifiedUser');
		if(in_array($key, $natives)) return $this->$key ? true : false;
		if(self::$issetHas && $this->template && $this->template->fieldgroup->hasField($key)) return true;
		return parent::__isset($key); 
	}

	/**
	 * Returns the parent page that has the template from which we get our role/access settings from
	 *
	 * @param string $type Specify one of 'view', 'edit', 'add', or 'create' (default='view')
	 * @return Page|NullPage Returns NullPage if none found
	 *
	 */
	public function getAccessParent($type = 'view') {
		return $this->access()->getAccessParent($this, $type);
	}

	/**
	 * Returns the template from which we get our role/access settings from
	 *
	 * @param string $type Specify one of 'view', 'edit', 'add', or 'create' (default='view')
	 * @return Template|null Returns null if none	
	 *
	 */
	public function getAccessTemplate($type = 'view') {
		return $this->access()->getAccessTemplate($this, $type);
	}
	
	/**
	 * Return the PageArray of roles that have access to this page
	 *
	 * This is determined from the page's template. If the page's template has roles turned off, 
	 * then it will go down the tree till it finds usable roles to use. 
	 *
	 * @param string $type May be 'view', 'edit', 'create' or 'add' (default='view')
	 * @return PageArray
	 *
	 */
	public function getAccessRoles($type = 'view') {
		return $this->access()->getAccessRoles($this, $type);
	}

	/**
	 * Returns whether this page has the given access role
	 *
	 * Given access role may be a role name, role ID or Role object
	 *
	 * @param string|int|Role $role 
	 * @param string $type May be 'view', 'edit', 'create' or 'add' (default is 'view')
	 * @return bool
	 *
	 */
	public function hasAccessRole($role, $type = 'view') {
		return $this->access()->hasAccessRole($this, $role, $type); 
	}

	/**
	 * Export the page's data to an array
	 * 
	 * @return array
	 * 
	public function ___export() {
		$exporter = new PageExport();
		return $exporter->export($this);
	}
	 */

	/**
	 * Export the page's data from an array
	 *
	 * @param array $data Data to import, in the format from the export() function
	 * @return $this
	 *
	public function ___import(array $data) {
		$importer = new PageExport();
		return $importer->import($this, $data); 
	}
	 */

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
		
		$isEqual = $value1 === $value2;
		
		if(!$isEqual && $value1 instanceof WireArray && $value2 instanceof WireArray) {
			// ask WireArray to compare itself to another
			$isEqual = $value1->isIdentical($value2, true);
		}
		
		if($isEqual) {
			if(is_object($value1) && $value1 instanceof Wire && ($value1->isChanged() || $value2->isChanged())) {
				$this->trackChange($key, $value1, $value2);
			}
		}
		
		return $isEqual;
	}

	/**
	 * @return PageComparison
	 *
	 */
	protected function comparison() {
		static $comparison = null;
		if(is_null($comparison)) $comparison = new PageComparison();
		return $comparison;
	}

	/**
	 * @return PageAccess
	 *
	 */
	protected function access() {
		static $access = null;
		if(is_null($access)) $access = new PageAccess();
		return $access;
	}

	/**
	 * @return PageTraversal
	 *
	 */
	protected function traversal() {
		static $traversal = null;
		if(is_null($traversal)) $traversal = new PageTraversal();
		return $traversal;
	}

	/**
	 * Return a translation array of all: status name => status number
	 *
	 * This enables string shortcuts to be used for statuses elsewhere in ProcessWire
	 * 
	 * @return array
	 *
	 */
	static public function getStatuses() {
		return self::$statuses;
	}

	/**
	 * Tells the page what Process it is being edited by, or simply that it's being edited
	 * 
	 * @param WirePageEditor $editor
	 * 
	 */
	public function ___setEditor(WirePageEditor $editor) {
		// $this->setQuietly('_editor', $editor); // uncomment when/if needed
	}

	/**
	 * Get the icon name associated with this Page (if applicable)
	 * 
	 * @todo add recognized page icon field to core
	 * 
	 * @return string
	 * 
	 */
	public function ___getIcon() {
		if(!$this->template) return '';
		if($this->template->fieldgroup->hasField('process')) {
			$process = $this->getUnformatted('process'); 
			if($process) {
				$info = $this->wire('modules')->getModuleInfoVerbose($process);
				if(!empty($info['icon'])) return $info['icon'];
			}
		}
		return $this->template->getIcon();
	}

	/**
	 * Return the API variable used for managing pages of this type
	 * 
	 * @return Pages|PagesType
	 * 
	 */
	public function getPagesManager() {
		return $this->wire('pages');
	}
}

