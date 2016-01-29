<?php namespace ProcessWire;

/**
 * ProcessWire Pages ($pages API variable)
 *
 * Manages Page instances, providing find, load, save and delete capabilities, most of 
 * which are delegated to other classes but this provides the common interface to them.
 *
 * This is the most used object in the ProcessWire API. 
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 *
 * @link http://processwire.com/api/variables/pages/ Offical $pages Documentation
 * @link http://processwire.com/api/selectors/ Official Selectors Documentation
 * 
 * PROPERTIES
 * ==========
 * @property bool cloning Whether or not a clone() operation is currently active
 * @property bool outputFormatting Current default output formatting mode.
 * @property bool autojoin Whether or not autojoin is allowed (typically true)
 * 
 * HOOKABLE METHODS
 * ================
 * @method PageArray find() find($selectorString, array $options = array()) Find and return all pages matching the given selector string. Returns a PageArray.
 * @method bool save() save(Page $page) Save any changes made to the given $page. Same as : $page->save() Returns true on success
 * @method bool saveField() saveField(Page $page, $field) Save just the named field from $page. Same as : $page->save('field')
 * @method bool trash() trash(Page $page, $save = true) Move a page to the trash. If you have already set the parent to somewhere in the trash, then this method won't attempt to set it again.
 * @method bool restore(Page $page, $save = true) Restore a trashed page to its original location. 
 * @method int emptyTrash() Empty the trash and return number of pages deleted. 
 * @method bool delete() delete(Page $page, $recursive = false) Permanently delete a page and it's fields. Unlike trash(), pages deleted here are not restorable. If you attempt to delete a page with children, and don't specifically set the $recursive param to True, then this method will throw an exception. If a recursive delete fails for any reason, an exception will be thrown.
 * @method Page|NullPage clone(Page $page, Page $parent = null, $recursive = true, $options = array()) Clone an entire page, it's assets and children and return it.
 * @method Page|NullPage add($template, $parent, $name = '', array $values = array())
 * @method setupNew(Page $page) Setup new page that does not yet exist by populating some fields to it. 
 * @method string setupPageName(Page $page, array $options = array()) Determine and populate a name for the given page.
 * 
 * METHODS PURELY FOR HOOKS
 * ========================
 * You can hook these methods, but you should not call them directly. 
 * See the phpdoc in the actual methods for more details about arguments and additional properties that can be accessed.
 * 
 * @method saveReady(Page $page) Hook called just before a page is saved. 
 * @method saved(Page $page, array $changes = array(), $values = array()) Hook called after a page is successfully saved. 
 * @method added(Page $page) Hook called when a new page has been added. 
 * @method moved(Page $page) Hook called when a page has been moved from one parent to another. 
 * @method templateChanged(Page $page) Hook called when a page template has been changed. 
 * @method trashed(Page $page) Hook called when a page has been moved to the trash. 
 * @method restored(Page $page) Hook called when a page has been moved OUT of the trash. 
 * @method deleteReady(Page $page) Hook called just before a page is deleted. 
 * @method deleted(Page $page) Hook called after a page has been deleted. 
 * @method cloneReady(Page $page, Page $copy) Hook called just before a page is cloned. 
 * @method cloned(Page $page, Page $copy) Hook called after a page has been successfully cloned. 
 * @method renamed(Page $page) Hook called after a page has been successfully renamed. 
 * @method statusChangeReady(Page $page) Hook called when a page's status has changed and is about to be saved.
 * @method statusChanged(Page $page) Hook called after a page status has been changed and saved. 
 * @method publishReady(Page $page) Hook called just before an unpublished page is published. 
 * @method published(Page $page) Hook called after an unpublished page has just been published. 
 * @method unpublishReady(Page $page) Hook called just before a pubished page is unpublished. 
 * @method unpublished(Page $page) Hook called after a published page has just been unpublished. 
 * @method saveFieldReady(Page $page, Field $field) Hook called just before a saveField() method saves a page fied. 
 * @method savedField(Page $page, Field $field) Hook called after saveField() method successfully executes. 
 * @method found(PageArray $pages, array $details) Hook called at the end of a $pages->find().
 *
 * TO-DO
 * =====
 * @todo Add a getCopy method that does a getById($id, array('cache' => false) ?
 * @todo Update saveField to accept array of field names as an option. 
 *
 */

class Pages extends Wire {

	/**
	 * Max length for page name
	 * 
	 */
	const nameMaxLength = 128;

	/**
	 * Default name for the root/home page
	 * 
	 */
	const defaultRootName = 'home';

	/**
	 * Instance of PagesSortfields
	 *
	 */
	protected $sortfields;

	/**
	 * Runtime debug log of Pages class activities, see getDebugLog()
	 *
	 */
	protected $debugLog = array();

	/**
	 * @var PagesLoader
	 * 
	 */
	protected $loader;

	/**
	 * @var PagesEditor
	 * 
	 */
	protected $editor;

	/**
	 * @var PagesLoaderCache
	 * 
	 */
	protected $cacher;

	/**
	 * @var PagesTrash
	 * 
	 */
	protected $trasher; 

	/**
	 * Create the Pages object
	 * 
	 * @param ProcessWire $wire
	 *
	 */
	public function __construct(ProcessWire $wire) {
		$this->setWire($wire);
		$this->sortfields = $this->wire(new PagesSortfields());
		$this->loader = $this->wire(new PagesLoader($this));
		$this->cacher = $this->wire(new PagesLoaderCache($this));
		$this->trasher = null;
		$this->editor = null;
	}
	
	/**
	 * Initialize $pages API var by preloading some pages
	 *
	 */
	public function init() {
		$this->loader->getById($this->wire('config')->preloadPageIDs);
	}

	/****************************************************************************************************************
	 * BASIC PUBLIC PAGES API METHODS
	 * 
	 */

	/**
	 * Count and return how many pages will match the given selector string
	 *
	 * @param string $selectorString Specify selector string, or omit to retrieve a site-wide count.
	 * @param array|string $options See $options in Pages::find
	 * @return int
	 *
	 */
	public function count($selectorString = '', $options = array()) {
		return $this->loader->count($selectorString, $options);
	}

	/**
	 * Given a Selector string, return the Page objects that match in a PageArray. 
	 * 
	 * Non-visible pages are excluded unless an include=hidden|unpublished|all mode is specified in the selector string, 
	 * or in the $options array. If 'all' mode is specified, then non-accessible pages (via access control) can also be included. 
	 *
	 * @param string|int|array $selectorString Specify selector string (standard usage), but can also accept page ID or array of page IDs.
	 * @param array|string $options Optional one or more options that can modify certain behaviors. May be assoc array or key=value string.
	 *	- findOne: boolean - apply optimizations for finding a single page 
	 *  - findAll: boolean - find all pages with no exculsions (same as include=all option)
	 *	- getTotal: boolean - whether to set returning PageArray's "total" property (default: true except when findOne=true)
	 *	- loadPages: boolean - whether to populate the returned PageArray with found pages (default: true). 
	 *		The only reason why you'd want to change this to false would be if you only needed the count details from 
	 *		the PageArray: getTotal(), getStart(), getLimit, etc. This is intended as an optimization for Pages::count().
	 * 		Does not apply if $selectorString argument is an array. 
	 *  - caller: string - optional name of calling function, for debugging purposes, i.e. pages.count
	 * 	- include: string - Optional inclusion mode of 'hidden', 'unpublished' or 'all'. Default=none. Typically you would specify this 
	 * 		directly in the selector string, so the option is mainly useful if your first argument is not a string. 
	 * 	- loadOptions: array - Optional assoc array of options to pass to getById() load options.
	 * @return PageArray
	 *
	 */
	public function ___find($selectorString, $options = array()) {
		return $this->loader->find($selectorString, $options);
	}

	/**
	 * Like find() but returns only the first match as a Page object (not PageArray)
	 * 
	 * This is functionally similar to the get() method except that its default behavior is to
	 * filter for access control and hidden/unpublished/etc. states, in the same way that the
	 * find() method does. You can add an "include=..." to your selector string to bypass. 
	 * This method also accepts an $options arrray, whereas get() does not. 
	 *
	 * @param string $selectorString
	 * @param array|string $options See $options for Pages::find
	 * @return Page|NullPage
	 *
	 */
	public function findOne($selectorString, $options = array()) {
		return $this->loader->findOne($selectorString, $options);
	}

	/**
	 * Returns the first page matching the given selector with no exclusions
	 *
	 * @param string $selectorString
	 * @return Page|NullPage Always returns a Page object, but will return NullPage (with id=0) when no match found
	 * 
	 */
	public function get($selectorString) {
		return $this->loader->get($selectorString); 
	}
	
	/**
	 * Save a page object and it's fields to database.
	 *
	 * If the page is new, it will be inserted. If existing, it will be updated.
	 *
	 * This is the same as calling $page->save()
	 *
	 * If you want to just save a particular field in a Page, use $page->save($fieldName) instead.
	 *
	 * @param Page $page
	 * @param array $options Optional array with the following optional elements:
	 * 		'uncacheAll' => boolean - Whether the memory cache should be cleared (default=true)
	 * 		'resetTrackChanges' => boolean - Whether the page's change tracking should be reset (default=true)
	 * 		'quiet' => boolean - When true, modified date and modified_users_id won't be updated (default=false)
	 *		'adjustName' => boolean - Adjust page name to ensure it is unique within its parent (default=false)
	 * 		'forceID' => integer - use this ID instead of an auto-assigned on (new page) or current ID (existing page)
	 * 		'ignoreFamily' => boolean - Bypass check of allowed family/parent settings when saving (default=false)
	 * @return bool True on success, false on failure
	 * @throws WireException
	 *
	 */
	public function ___save(Page $page, $options = array()) {
		return $this->editor()->save($page, $options);
	}

	/**
	 * Save just a field from the given page as used by Page::save($field)
	 *
	 * This function is public, but the preferred manner to call it is with $page->save($field)
	 *
	 * @param Page $page
	 * @param string|Field $field Field object or name (string)
	 * @param array|string $options Specify option 'quiet' => true, to bypass updating of modified_users_id and modified time.
	 * @return bool True on success
	 * @throws WireException
	 *
	 */
	public function ___saveField(Page $page, $field, $options = array()) {
		return $this->editor()->saveField($page, $field, $options);
	}

	/**
	 * Add a new page using the given template to the given parent
	 *
	 * If no name is specified one will be assigned based on the current timestamp.
	 *
	 * @param string|Template $template Template name or Template object
	 * @param string|int|Page $parent Parent path, ID or Page object
	 * @param string $name Optional name or title of page. If none provided, one will be automatically assigned based on microtime stamp.
	 * 	If you want to specify a different name and title then specify the $name argument, and $values['title'].
	 * @param array $values Field values to assign to page (optional). If $name is ommitted, this may also be 3rd param.
	 * @return Page Returned page has output formatting off.
	 * @throws WireException When some criteria prevents the page from being saved.
	 *
	 */
	public function ___add($template, $parent, $name = '', array $values = array()) {
		return $this->editor()->add($template, $parent, $name, $values);
	}
	
	/**
	 * Clone an entire page, it's assets and children and return it.
	 *
	 * @param Page $page Page that you want to clone
	 * @param Page $parent New parent, if different (default=same parent)
	 * @param bool $recursive Clone the children too? (default=true)
	 * @param array|string $options Optional options that can be passed to clone or save
	 * 	- forceID (int): force a specific ID
	 * 	- set (array): Array of properties to set to the clone (you can also do this later)
	 * 	- recursionLevel (int): recursion level, for internal use only.
	 * @return Page the newly cloned page or a NullPage() with id=0 if unsuccessful.
	 * @throws WireException|\Exception on fatal error
	 *
	 */
	public function ___clone(Page $page, Page $parent = null, $recursive = true, $options = array()) {
		return $this->editor()->_clone($page, $parent, $recursive, $options);
	}

	/**
	 * Permanently delete a page and it's fields.
	 *
	 * Unlike trash(), pages deleted here are not restorable.
	 *
	 * If you attempt to delete a page with children, and don't specifically set the $recursive param to True, then
	 * this method will throw an exception. If a recursive delete fails for any reason, an exception will be thrown.
	 *
	 * @param Page $page
	 * @param bool $recursive If set to true, then this will attempt to delete all children too.
	 * @param array $options Optional settings to change behavior (for the future)
	 * @return bool|int Returns true (success), or integer of quantity deleted if recursive mode requested.
	 * @throws WireException on fatal error
	 *
	 */
	public function ___delete(Page $page, $recursive = false, array $options = array()) {
		return $this->editor()->delete($page, $recursive, $options);
	}

	/**
	 * Move a page to the trash
	 *
	 * If you have already set the parent to somewhere in the trash, then this method won't attempt to set it again.
	 *
	 * @param Page $page
	 * @param bool $save Set to false if you will perform the save() call, as is the case when called from the Pages::save() method.
	 * @return bool
	 * @throws WireException
	 *
	 */
	public function ___trash(Page $page, $save = true) {
		return $this->trasher()->trash($page, $save);
	}

	/**
	 * Restore a page from the trash back to a non-trash state
	 *
	 * Note that this method assumes already have set a new parent, but have not yet saved.
	 * If you do not set a new parent, then it will restore to the original parent, when possible.
	 *
	 * @param Page $page
	 * @param bool $save Set to false if you only want to prep the page for restore (i.e. being saved elsewhere)
	 * @return bool
	 *
	 */
	protected function ___restore(Page $page, $save = true) {
		return $this->trasher()->restore($page, $save);
	}

	/****************************************************************************************************************
	 * ADVANCED PAGES API METHODS (more for internal use)
	 *
	 */
	
	/**
	 * Delete all pages in the trash
	 *
	 * Populates error notices when there are errors deleting specific pages.
	 *
	 * @return int Returns total number of pages deleted from trash.
	 * 	This number is negative or 0 if not all pages could be deleted and error notices may be present.
	 *
	 */
	public function ___emptyTrash() {
		return $this->trasher()->emptyTrash();
	}
	
	/**
	 * Given an array or CSV string of Page IDs, return a PageArray 
	 *
	 * Optionally specify an $options array rather than a template for argument 2. When present, the 'template' and 'parent_id' arguments may be provided
	 * in the given $options array. These options may be specified: 
	 * 
	 * LOAD OPTIONS (argument 2 array): 
	 * - cache: boolean, default=true. place loaded pages in memory cache?
	 * - getFromCache: boolean, default=true. Allow use of previously cached pages in memory (rather than re-loading it from DB)?
	 * - template: instance of Template (see $template argument)
	 * - parent_id: integer (see $parent_id argument)
	 * - getNumChildren: boolean, default=true. Specify false to disable retrieval and population of 'numChildren' Page property. 
	 * - getOne: boolean, default=false. Specify true to return just one Page object, rather than a PageArray.
	 * - autojoin: boolean, default=true. Allow use of autojoin option?
	 * - joinFields: array, default=empty. Autojoin the field names specified in this array, regardless of field settings (requires autojoin=true).
	 * - joinSortfield: boolean, default=true. Whether the 'sortfield' property will be joined to the page.
	 * - findTemplates: boolean, default=true. Determine which templates will be used (when no template specified) for more specific autojoins.
	 * - pageClass: string, default=auto-detect. Class to instantiate Page objects with. Leave blank to determine from template. 
	 * - pageArrayClass: string, default=PageArray. PageArray-derived class to store pages in (when 'getOne' is false). 
	 * 
	 * Use the $options array for potential speed optimizations:
	 * - Specify a 'template' with your call, when possible, so that this method doesn't have to determine it separately. 
	 * - Specify false for 'getNumChildren' for potential speed optimization when you know for certain pages will not have children. 
	 * - Specify false for 'autojoin' for potential speed optimization in certain scenarios (can also be a bottleneck, so be sure to test). 
	 * - Specify false for 'joinSortfield' for potential speed optimization when you know the Page will not have children or won't need to know the order.
	 * - Specify false for 'findTemplates' so this method doesn't have to look them up. Potential speed optimization if you have few autojoin fields globally.
	 * - Note that if you specify false for 'findTemplates' the pageClass is assumed to be 'Page' unless you specify something different for the 'pageClass' option.
	 *
	 * @param array|WireArray|string $_ids Array of IDs or CSV string of IDs
	 * @param Template|array|null $template Specify a template to make the load faster, because it won't have to attempt to join all possible fields... just those used by the template. 
	 *	Optionally specify an $options array instead, see the method notes above. 
	 * @param int|null $parent_id Specify a parent to make the load faster, as it reduces the possibility for full table scans. 
	 *	This argument is ignored when an options array is supplied for the $template. 
	 * @return PageArray|Page Returns Page only if the 'getOne' option is specified, otherwise always returns a PageArray.
	 * @throws WireException
	 *
	 */
	public function getById($_ids, $template = null, $parent_id = null) {
		return $this->loader->getById($_ids, $template, $parent_id);
	}
	
	/**
	 * Given an ID return a path to a page, without loading the actual page
	 *
	 * Please note
	 * ===========
	 * 1) Always returns path in default language, unless a language argument/option is specified.
	 * 2) Path may be different from 'url' as it doesn't include $config->urls->root at the beginning.
	 * 3) In most cases, it's preferable to use $page->path() rather than this method. This method is
	 *    here just for cases where a path is needed without loading the page.
	 * 4) It's possible for there to be Page::path() hooks, and this method completely bypasses them,
	 *    which is another reason not to use it unless you know such hooks aren't applicable to you.
	 *
	 * @param int|Page $id ID of the page you want the path to
	 * @param null|array|Language|int|string $options Specify $options array or Language object, id or name. Allowed options:
	 *  - language (int|string|anguage): To retrieve in non-default language, specify language object, ID or name (default=null)
	 *  - useCache (bool): Allow pulling paths from already loaded pages? (default=true)
	 *  - usePagePaths (bool): Allow pulling paths from PagePaths module, if installed? (default=true)
	 * @return string Path to page or blank on error/not-found
	 * @since 3.0.6
	 *
	 */
	public function getPath($id, $options = array()) {
		return $this->loader->getPath($id, $options);
	}

	/**
	 * Alias of getPath method for backwards compatibility
	 *
	 * @param int $id
	 * @return string
	 *
	 */
	public function _path($id) {
		return $this->loader->getPath($id);
	}

	/**
	 * Get a page by its path, similar to $pages->get('/path/to/page/') but with more options
	 *
	 * Please note
	 * ===========
	 * 1) There are no exclusions for page status or access. If needed, you should validate access
	 *    on any page returned from this method.
	 * 2) In a multi-language environment, you must specify the $useLanguages option to be true, if you
	 *    want a result for a $path that is (or might be) a multi-language path. Otherwise, multi-language
	 *    paths will make this method return a NullPage (or 0 if getID option is true).
	 *
	 * @param $path
	 * @param array|bool $options array of options (below), or specify boolean for $useLanguages option only.
	 *  - getID: Specify true to just return the page ID (default=false)
	 *  - useLanguages: Specify true to allow retrieval by language-specific paths (default=false)
	 *  - useHistory: Allow use of previous paths used by the page, if PagePathHistory module is installed (default=false)
	 * @return Page|int
	 * @since 3.0.6
	 *
	 */
	public function getByPath($path, $options = array()) {
		return $this->loader->getByPath($path, $options);
	}
	
	/**
	 * Auto-populate some fields for a new page that does not yet exist
	 *
	 * Currently it does this: 
	 * - Sets up a unique page->name based on the format or title if one isn't provided already. 
	 * - Assigns a 'sort' value'. 
	 * 
	 * @param Page $page
	 *
	 */
	public function ___setupNew(Page $page) {
		return $this->editor()->setupNew($page);
	}

	/**
	 * Auto-assign a page name to the given page
	 * 
	 * Typically this would be used only if page had no name or if it had a temporary untitled name.
	 * 
	 * Page will be populated with the name given. This method will not populate names to pages that
	 * already have a name, unless the name is "untitled"
	 * 
	 * @param Page $page
	 * @param array $options 
	 * 	- format: Optionally specify the format to use, or leave blank to auto-determine.
	 * @return string If a name was generated it is returned. If no name was generated blank is returned. 
	 * 
	 */
	public function ___setupPageName(Page $page, array $options = array()) {
		return $this->editor()->setupPageName($page, $options);
	}

	/**
	 * Update page modification time to now (or the given modification time)
	 *
	 * @param Page|PageArray|array $pages May be Page, PageArray or array of page IDs (integers)
	 * @param null|int|string $modified Omit to update to now, or specify unix timestamp or strtotime() recognized time string
	 * @throws WireException if given invalid format for $modified argument or failed database query
	 * @return bool True on success, false on fail
	 *
	 */
	public function ___touch($pages, $modified = null) {
		return $this->editor()->touch($pages, $modified);
	}
	
	/**
	 * Is the given page in a state where it can be saved from the API?
	 *
	 * Note: this does not account for user permission checking.
	 * It only checks if the page is in a state to be saveable via the API. 
	 * 
	 * @param Page $page
	 * @param string $reason Text containing the reason why it can't be saved (assuming it's not saveable)
	 * @param string|Field $fieldName Optional fieldname to limit check to.
	 * @param array $options Options array given to the original save method (optional)
	 * @return bool True if saveable, False if not
	 *
	 */
	public function isSaveable(Page $page, &$reason, $fieldName = '', array $options = array()) {
		return $this->editor()->isSaveable($page, $reason, $fieldName, $options);
	}
	
	/**
	 * Is the given page deleteable from the API?
	 *
	 * Note: this does not account for user permission checking. 
	 * It only checks if the page is in a state to be deleteable via the API. 
	 *
	 * @param Page $page
	 * @return bool True if deleteable, False if not
	 *
	 */
	public function isDeleteable(Page $page) {
		return $this->editor()->isDeleteable($page);
	}

	/**
	 * Given a Page ID, return it if it's cached, or NULL of it's not. 
	 *
	 * If no ID is provided, then this will return an array copy of the full cache.
	 *
	 * You may also pass in the string "id=123", where 123 is the page_id
	 *
	 * @param int|string|null $id 
	 * @return Page|array|null
	 *
	 */
	public function getCache($id = null) {
		return $this->cacher->getCache($id);
	}

	/**
	 * Cache the given page. 
	 *
	 * @param Page $page
	 *
	 */
	public function cache(Page $page) {
		return $this->cacher->cache($page);
	}

	/**
	 * Remove the given page(s) from the cache, or uncache all by omitting $page argument
	 *
	 * Note: When any $page argument is given, this does not remove pages from selectorCache.
	 * When no $page argument is given, this method behaves the same as $pages->uncacheAll().
	 *
	 * @param Page|PageArray|null $page Page to uncache, or omit to uncache all.
	 * @param array $options Additional options to modify behavior: 
	 * 	- shallow (bool): By default, this method also calls $page->uncache(). 
	 * 	  To prevent call to $page->uncache(), set 'shallow' => true. 
	 * @return int Number of pages uncached
	 *
	 */
	public function uncache($page = null, array $options = array()) {
		$cnt = 0;
		if(is_null($page)) {
			$cnt = $this->cacher->uncacheAll();
		} else if($page instanceof Page) {
			if($this->cacher->uncache($page, $options)) $cnt++;
		} else if($page instanceof PageArray) {
			foreach($page as $p) {
				if($this->cacher->uncache($p, $options)) $cnt++;
			}
		}
		return $cnt;
	}

	/**
	 * Remove all pages from the cache, same as $pages->uncache() with no arguments
	 * 
	 * @param Page $page Optional Page that initiated the uncacheAll
	 * @return int Number of pages uncached
	 *
	 */
	public function uncacheAll(Page $page = null) {
		return $this->cacher->uncacheAll($page);
	}

	/**
	 * For internal Page instance access, return the Pages sortfields property
	 *
	 * @param bool $reset Specify boolean true to reset the Sortfields instance
	 * @return PagesSortFields
	 *
	 */
	public function sortfields($reset = false) {
		if($reset) {
			unset($this->sortfields);
			$this->sortfields = $this->wire(new PagesSortfields());
		}
		return $this->sortfields; 
	}

	/**	
 	 * Return a fuel or other property set to the Pages instance
	 * 
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function __get($key) {
		if($key == 'outputFormatting') return $this->loader->getOutputFormatting(); 
		if($key == 'cloning') return $this->editor()->isCloning(); 
		if($key == 'autojoin') return $this->loader->getAutojoin();
		return parent::__get($key); 
	}

	/**
	 * Set whether loaded pages have their outputFormatting turn on or off
	 *
	 * By default, it is turned on. 
	 * 
	 * @param bool $outputFormatting
	 *
	 */
	public function setOutputFormatting($outputFormatting = true) {
		$this->loader->setOutputFormatting($outputFormatting);
	}

	/**
	 * Log a Pages class event
	 *
	 * Only active in debug mode. 
	 *
	 * @param string $action Name of action/function that occurred.
	 * @param string $details Additional details, like a selector string. 
	 * @param string|object The value that was returned.
	 *
	 */
	public function debugLog($action = '', $details = '', $result = '') {
		if(!$this->wire('config')->debug) return;
		$this->debugLog[] = array(
			'time' => microtime(),
			'action' => (string) $action, 
			'details' => (string) $details, 
			'result' => (string) $result
			);
	}

	/**
	 * Get the Pages class debug log
	 *
	 * Only active in debug mode
	 *
	 * @param string $action Optional action within the debug log to find
	 * @return array
	 *
	 */
	public function getDebugLog($action = '') {
		if(!$this->wire('config')->debug) return array();
		if(!$action) return $this->debugLog; 
		$debugLog = array();
		foreach($this->debugLog as $item) if($item['action'] == $action) $debugLog[] = $item; 
		return $debugLog; 
	}

	/**
	 * Return a PageFinder object, ready to use
	 *
	 * @return PageFinder
	 *
	 */
	public function getPageFinder() {
		return $this->wire(new PageFinder());
	}

	/**
	 * Enable or disable use of autojoin for all queries
	 * 
	 * Default should always be true, and you may use this to turn it off temporarily, but
	 * you should remember to turn it back on
	 * 
	 * @param bool $autojoin
	 * 
	 */
	public function setAutojoin($autojoin = true) {
		$this->loader->setAutojoin($autojoin);
	}	

	/**
	 * Return a new/blank PageArray
	 * 
	 * @param array $options Optionally specify array('pageArrayClass' => 'YourPageArrayClass')
	 * @return PageArray
	 * 
	 */
	public function newPageArray(array $options = array()) {
		$class = 'PageArray';
		if(!empty($options['pageArrayClass'])) $class = $options['pageArrayClass'];
		if($this->wire('config')->compat2x && strpos($class, "\\") === false) {
			if(class_exists("\\$class")) $class = "\\$class";
		}
		$class = wireClassName($class, true);
		$pageArray = $this->wire(new $class());
		if(!$pageArray instanceof PageArray) $pageArray = $this->wire(new PageArray());
		return $pageArray;
	}

	/**
	 * Return a new/blank Page object (in memory only)
	 *
	 * @param array $options Optionally specify array('pageClass' => 'YourPageClass')
	 * @return Page
	 *
	 */
	public function newPage(array $options = array()) {
		$class = 'Page';
		if(!empty($options['pageClass'])) $class = $options['pageClass'];
		if($this->wire('config')->compat2x && strpos($class, "\\") === false) {
			if(class_exists("\\$class")) $class = "\\$class";
		}
		$class = wireClassName($class, true);
		if(isset($options['template'])) {
			$template = $options['template'];
			if(!is_object($template)) {
				$template = empty($template) ? null : $this->wire('templates')->get($template);
			}
		} else {
			$template = null;
		}
		$page = $this->wire(new $class($template));
		if(!$page instanceof Page) $page = $this->wire(new Page($template));
		return $page;
	}

	/**
	 * Return a new NullPage
	 * 
	 * @return NullPage
	 * 
	 */
	public function newNullPage() {
		if($this->wire('config')->compat2x && class_exists("\\NullPage")) {
			$page = new \NullPage();
		} else {
			$page = new NullPage();
		}
		$this->wire($page);
		return $page;
	}

	/**
	 * Execute a PDO statement, with retry and error handling (deprecated)
	 *
	 * @param \PDOStatement $query
	 * @param bool $throw Whether or not to throw exception on query error (default=true)
	 * @param int $maxTries Max number of times it will attempt to retry query on error
	 * @return bool
	 * @throws \PDOException
	 * @deprecated Use $database->execute() instead
	 *
	 */
	public function executeQuery(\PDOStatement $query, $throw = true, $maxTries = 3) {
		$this->wire('database')->execute($query, $throw, $maxTries);
	}

	/**
	 * Enables use of $pages(123), $pages('/path/') or $pages('selector string')
	 * 
	 * When given an integer or page path string, it calls $pages->get(key); 
	 * When given a string, it calls $pages->find($key);
	 * When given an array, it calls $pages->getById($key);
	 * 
	 * @param string|int|array $key
	 * @return Page|PageArray
	 *
	 */
	public function __invoke($key) {
		if(empty($key)) return $this;
		if(is_int($key)) return $this->get($key); 
		if(is_array($key)) return $this->getById($key); 
		if(strpos($key, '/') === 0 && ctype_alnum(str_replace(array('/', '-', '_', '.'), '', $key))) return $this->get($key);
		return $this->find($key);
	}

	/**
	 * Save to pages activity log, if enabled in config
	 * 
	 * @param $str
	 * @param Page|null Page to log
	 * @return WireLog
	 * 
	 */
	public function log($str, Page $page) {
		if(!in_array('pages', $this->wire('config')->logs)) return parent::___log();
		if($this->wire('process') != 'ProcessPageEdit') $str .= " [From URL: " . $this->wire('input')->url() . "]";
		$options = array('name' => 'pages', 'url' => $page->path); 
		return parent::___log($str, $options); 
	}

	/**
	 * @return PagesLoader
	 *
	 */
	public function loader() {
		return $this->loader;
	}

	/**
	 * @return PagesEditor
	 *
	 */
	public function editor() {
		if(!$this->editor) $this->editor = $this->wire(new PagesEditor($this));
		return $this->editor;
	}

	/**
	 * @return PagesLoaderCache
	 *
	 */
	public function cacher() {
		return $this->cacher;
	}
	
	/**
	 * @return PagesTrash
	 *
	 */
	public function trasher() {
		if(is_null($this->trasher)) $this->trasher = $this->wire(new PagesTrash($this));
		return $this->trasher;
	}

	/***********************************************************************************************************************
	 * COMMON PAGES HOOKS
	 * 
	 */

	/**
	 * Hook called after a page is successfully saved
	 *
	 * This is the same as Pages::save, except that it occurs before other save-related hooks (below),
	 * Whereas Pages::save occurs after. In most cases, the distinction does not matter. 
	 * 
	 * @param Page $page The page that was saved
	 * @param array $changes Array of field names that changed
	 * @param array $values Array of values that changed, if values were being recorded, see Wire::getChanges(true) for details.
	 *
	 */
	public function ___saved(Page $page, array $changes = array(), $values = array()) { 
		$str = "Saved page";
		if(count($changes)) $str .= " (Changes: " . implode(', ', $changes) . ")";
		$this->log($str, $page);
		$this->wire('cache')->maintenance($page);
		if($page->className() != 'Page') {
			$manager = $page->getPagesManager();
			if($manager instanceof PagesType) $manager->saved($page, $changes, $values);
		}
	}

	/**
	 * Hook called when a new page has been added
	 * 
	 * @param Page $page
	 *
	 */
	public function ___added(Page $page) { 
		$this->log("Added page", $page);
		if($page->className() != 'Page') {
			$manager = $page->getPagesManager();
			if($manager instanceof PagesType) $manager->added($page);
		}
	}

	/**
	 * Hook called when a page has been moved from one parent to another
	 *
	 * Note the previous parent is in $page->parentPrevious
	 * 
	 * @param Page $page
	 *
	 */
	public function ___moved(Page $page) { 
		if($page->parentPrevious) {
			$this->log("Moved page from {$page->parentPrevious->path}$page->name/", $page);
		} else {
			$this->log("Moved page", $page); 
		}
	}

	/**
	 * Hook called when a page's template has been changed
	 *
	 * Note the previous template is in $page->templatePrevious
	 * 
	 * @param Page $page
	 *
	 */
	public function ___templateChanged(Page $page) {
		if($page->templatePrevious) {
			$this->log("Changed template on page from '$page->templatePrevious' to '$page->template'", $page);
		} else {
			$this->log("Changed template on page to '$page->template'", $page);
		}
	}

	/**
	 * Hook called when a page has been moved to the trash
	 * 
	 * @param Page $page
	 *
	 */
	public function ___trashed(Page $page) { 
		$this->log("Trashed page", $page);
	}

	/**
	 * Hook called when a page has been moved OUT of the trash
	 * 
	 * @param Page $page
	 *
	 */
	public function ___restored(Page $page) { 
		$this->log("Restored page", $page); 
	}

	/**
	 * Hook called just before a page is saved
	 *
	 * May be preferable to a before(save) hook because you know for sure a save will 
	 * be executed immediately after this is called. Whereas you don't necessarily know
 	 * that when before(save) is called, as an error may prevent it. 
	 *
	 * @param Page $page The page about to be saved
	 * @return array Optional extra data to add to pages save query.
	 *
	 */
	public function ___saveReady(Page $page) {
		$data = array();
		if($page->className() != 'Page') {
			$manager = $page->getPagesManager();
			if($manager instanceof PagesType) $data = $manager->saveReady($page);
		}
		return $data;
	}

	/**
	 * Hook called when a page is about to be deleted, but before data has been touched
	 *
	 * This is different from a before(delete) hook because this hook is called once it has 
	 * been confirmed that the page is deleteable and WILL be deleted. 
	 * 
	 * @param Page $page
	 *
	 */
	public function ___deleteReady(Page $page) {
		if($page->className() != 'Page') {
			$manager = $page->getPagesManager();
			if($manager instanceof PagesType) $manager->deleteReady($page);
		}
	}

	/**
	 * Hook called when a page and it's data have been deleted
	 * 
	 * @param Page $page
	 *
	 */
	public function ___deleted(Page $page) { 
		$this->log("Deleted page", $page); 
		$this->wire('cache')->maintenance($page);
		if($page->className() != 'Page') {
			$manager = $page->getPagesManager();
			if($manager instanceof PagesType) $manager->deleted($page);
		}
	}

	/**
	 * Hook called when a page is about to be cloned, but before data has been touched
	 *
	 * @param Page $page The original page to be cloned
	 * @param Page $copy The actual clone about to be saved
	 *
	 */
	public function ___cloneReady(Page $page, Page $copy) { }

	/**
	 * Hook called when a page has been cloned
	 *
	 * @param Page $page The original page to be cloned
	 * @param Page $copy The completed cloned version of the page
	 *
	 */
	public function ___cloned(Page $page, Page $copy) { 
		$this->log("Cloned page to $copy->path", $page); 
	}

	/**
	 * Hook called when a page has been renamed (i.e. had it's name field change)
	 *
	 * The previous name can be accessed at $page->namePrevious;
	 * The new name can be accessed at $page->name
	 *
	 * This hook is only called when a page's name changes. It is not called when
	 * a page is moved unless the name was changed at the same time. 
	 *
	 * @param Page $page The $page that was renamed
	 *
	 */
	public function ___renamed(Page $page) { 
		$this->log("Renamed page from '$page->namePrevious' to '$page->name'", $page); 
	}

	/**
	 * Hook called when a page status has been changed and saved
	 *
	 * Previous status may be accessed at $page->statusPrevious
	 *
	 * @param Page $page 
	 *
	 */
	public function ___statusChanged(Page $page) {
		$status = $page->status; 
		$statusPrevious = $page->statusPrevious; 
		$isPublished = !$page->isUnpublished();
		$wasPublished = !($statusPrevious & Page::statusUnpublished);
		if($isPublished && !$wasPublished) $this->published($page);
		if(!$isPublished && $wasPublished) $this->unpublished($page);
	
		$from = array();
		$to = array();
		foreach(Page::getStatuses() as $name => $flag) {
			if($flag == Page::statusUnpublished) continue; // logged separately
			if($statusPrevious & $flag) $from[] = $name;
			if($status & $flag) $to[] = $name; 
		}
		if(count($from) || count($to)) {
			$added = array();
			$removed = array();
			foreach($from as $name) if(!in_array($name, $to)) $removed[] = $name;
			foreach($to as $name) if(!in_array($name, $from)) $added[] = $name;
			$str = '';
			if(count($added)) $str = "Added status '" . implode(', ', $added) . "'";
			if(count($removed)) {
				if($str) $str .= ". ";
				$str .= "Removed status '" . implode(', ', $removed) . "'";
			}
			if($str) $this->log($str, $page);
		}
	}

	/**
	 * Hook called when a page's status is about to be changed and saved
	 *
	 * Previous status may be accessed at $page->statusPrevious
	 *
	 * @param Page $page 
	 *
	 */
	public function ___statusChangeReady(Page $page) {
		$isPublished = !$page->isUnpublished();
		$wasPublished = !($page->statusPrevious & Page::statusUnpublished);
		if($isPublished && !$wasPublished) $this->publishReady($page);
		if(!$isPublished && $wasPublished) $this->unpublishReady($page);
	}

	/**
	 * Hook called after an unpublished page has just been published
	 *
	 * @param Page $page 
	 *
	 */
	public function ___published(Page $page) { 
		$this->log("Published page", $page); 
	}

	/**
	 * Hook called after published page has just been unpublished
	 *
	 * @param Page $page 
	 *
	 */
	public function ___unpublished(Page $page) { 
		$this->log("Unpublished page", $page); 
	}

	/**
	 * Hook called right before an unpublished page is published and saved
	 *
	 * @param Page $page 
	 *
	 */
	public function ___publishReady(Page $page) { }

	/**
	 * Hook called right before a published page is unpublished and saved
	 *
	 * @param Page $page 
	 *
	 */
	public function ___unpublishReady(Page $page) { }

	/**
	 * Hook called at the end of a $pages->find(), includes extra info not seen in the resulting PageArray
	 *
	 * @param PageArray $pages The pages that were found
	 * @param array $details Extra information on how the pages were found, including: 
	 * 	- PageFinder $pageFinder The PageFinder instance that was used
	 * 	- array $pagesInfo The array returned by PageFinder
	 * 	- array $options Options that were passed to $pages->find()
	 *
	 */
	public function ___found(PageArray $pages, array $details) { }

	/**
	 * Hook called when Pages::saveField is going to execute
	 * 
	 * @param Page $page
	 * @param Field $field
	 * 
	 */
	public function ___saveFieldReady(Page $page, Field $field) { }

	/**
	 * Hook called after Pages::saveField successfully executes
	 * 
	 * @param Page $page
	 * @param Field $field
	 * 
	 */
	public function ___savedField(Page $page, Field $field) { 
		$this->log("Saved page field '$field->name'", $page); 
	}

}


