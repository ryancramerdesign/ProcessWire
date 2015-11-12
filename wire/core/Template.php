<?php

/**
 * ProcessWire Template
 *
 * A template is a Page's connection to fields (via a Fieldgroup) and output TemplateFile.
 *
 * Templates also maintain several properties which can affect the render behavior of pages using it. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 * @todo add multi-language option for redirectLogin setting
 * 
 * @property int $id Get or set the template's numbered database ID.
 * @property string $name Get or set the template's name.
 * @property string $filename Get or set a template's filename, including path (this is auto-generated from the name, though you may modify it at runtime if it suits your need).
 * @property string $label Optional short text label to describe Template.
 * @property int $fieldgroups_id ID of Fieldgroup assigned to this template. 
 * @property int $flags Flags assigned to this template: see the flag* constants in Template class.
 * @property int $cache_time Number of seconds pages using this template should cache for, or 0 for no cache. Negative values indicates setting used for external caching engine like ProCache.
 * @property int $cacheTime Alias for $cache_time for case consistency, can be used interchangeably with cache_time.
 * @property Fieldgroup $fieldgroup Get or set a template's Fieldgroup. Can also be used to iterate a template's fields.
 * @property Fieldgroup $fields Syntactical alias for $template->fieldgroup. Use whatever makes more sense for your code readability.
 * @property Fieldgroup|null $fieldgroupPrevious Previous fieldgroup, if it was changed. Null if not. 
 * @property int|bool $useRoles Whether or not this template defines access. 
 * @property PageArray $roles Roles assigned to this template for general/view access. 
 * @property array $editRoles Array of Role IDs representing roles that may edit pages using this template.
 * @property array $addRoles Array of Role IDs representing roles that may add pages using this template.
 * @property array $createRoles Array of Role IDs representing roles that may create pages using this template.
 * @property array $rolesPermissions Override permissions: Array indexed by role ID with values as permission ID (add) or negative permission ID (revoke)
 * @property int $noInherit Specify 1 to prevent edit/create/add access from inheriting to children, or 0 for default inherit behavior.
 * @property int $childrenTemplatesID Template ID for child pages, or -1 if no children allowed. DEPRECATED
 * @property string $sortfield Field that children of templates using this page should sort by. blank=page decides or sort=manual drag-n-drop
 * @property int $noChildren Set to 1 to cancel use of childTemplates
 * @property int $noParents Set to 1 to cancel use of parentTemplates, set to -1 to only allow one page using this template to exist.
 * @property array $childTemplates Array of template IDs that are allowed for children. blank array = any. 
 * @property array $parentTemplates Array of template IDs that are allowed for parents. blank array = any.
 * @property int $allowPageNum Allow page numbers in URLs? (0=no, 1=yes)
 * @property int $allowChangeUser Allow the createdUser/created_users_id field of pages to be changed? (with API or in admin w/superuser only). 0=no, 1=yes
 * @property int $redirectLogin Redirect when no access: 0 = 404, 1 = login page, url = URL to redirect to.
 * @property int|string $urlSegments Allow URL segments on pages? (0=no, 1=yes (all), string=space separted list of segments to allow)
 * @property int $https Use https? 0 = http or https, 1 = https only, -1 = http only
 * @property int $slashUrls Page URLs should have a trailing slash? 1 = yes, 0 = no	
 * @property string|int $slashPageNum Should PageNum segments have a trailing slash? (blank=either, 1=yes, 0=no) applies only if allowPageNum!=0
 * @property string|int $slashUrlSegments Should last URL segment have a trailing slash? (blank=either, 1=yes, 0=no) applies only if urlSegments!=0
 * @property string $altFilename Alternate filename for template file, if not based on template name.
 * @property int $guestSearchable Pages appear in search results even when user doesnt have access? (0=no, 1=yes)
 * @property string $pageClass Class for instantiated page objects. Page assumed if blank, or specify class name. 
 * @property string $childNameFormat Name format for child pages. when specified, the page-add UI step can be skipped when adding chilcren. Counter appended till unique. Date format assumed if any non-pageName chars present. Use 'title' to pull from title field.
 * @property string $pageLabelField CSV or space separated string of field names to be displayed by ProcessPageList (overrides those set with ProcessPageList config).
 * @property int $noGlobal Template should ignore the global option of fields? (0=no, 1=yes)
 * @property int $noMove Pages using this template are not moveable? (0=moveable, 1=not movable)
 * @property int $noTrash Pages using this template may not go in trash? (i.e. they will be deleted not trashed) (0=trashable, 1=not trashable)
 * @property int $noSettings Don't show a settings tab on pages using this template? (0=use settings tab, 1=no settings tab)
 * @property int $noChangeTemplate Don't allow pages using this template to change their template? (0=template change allowed, 1=template change not allowed)
 * @property int $noUnpublish Don't allow pages using this template to ever exist in an unpublished state - if page exists, it must be published. (0=page may be unpublished, 1=page may not be unpublished)
 * @property int $noShortcut Don't allow pages using this template to appear in shortcut "add new page" menu
 * @property int $nameContentTab Pages should display the name field on the content tab? (0=no, 1=yes)
 * @property string $noCacheGetVars GET vars that trigger disabling the cache (only when cache_time > 0)
 * @property string $noCachePostVars POST vars that trigger disabling the cache (only when cache_time > 0)
 * @property int $useCacheForUsers Use cache for: 0 = only guest users, 1 = guests and logged in users
 * @property int $cacheExpire Expire the cache for all pages when page using this template is saved? (1 = yes, 0 = no- only current page)
 * @property array $cacheExpirePages Array of Page IDs that should be expired, when cacheExpire == Template::cacheExpireSpecific
 * @property string $cacheExpireSelector Selector string matching pages that should be expired, when cacheExpire == Template::cacheExpireSelector
 * @property string $tags Optional tags that can group this template with others in the admin templates list 
 * @property string $tabContent Optional replacement for default "Content" label
 * @property string $tabChildren Optional replacement for default "Children" label
 * @property string $nameLabel Optional replacement for the default "Name" label on pages using this template
 * @property string $contentType Content-type header or index (extension) of content type header from $config->contentTypes
 *
 */

class Template extends WireData implements Saveable, Exportable {

	/**
	 * Flag used to indicate the field is a system-only field and thus can't be deleted or have it's name changed
	 *
	 */
	const flagSystem = 8; 

	/**
	 * Flag set if you need to override the system flag - set this first, then remove system flag in 2 operations. 
	 *
	 */
	const flagSystemOverride = 32768; 

	/**
	 * Cache expiration options: expire only page cache
	 *
	 */
	const cacheExpirePage = 0;

	/**
	 * Cache expiration options: expire entire site cache
	 *
	 */
	const cacheExpireSite = 1; 

	/**
	 * Cache expiration options: expire page and parents
	 *
	 */
	const cacheExpireParents = 2; 

	/**
	 * Cache expiration options: expire page and other specific pages (stored in cacheExpirePages)
	 *
	 */
	const cacheExpireSpecific = 3;

	/**
	 * Cache expiration options: expire pages matching a selector
	 * 
	 */
	const cacheExpireSelector = 4; 

	/**
	 * Cache expiration options: don't expire anything
	 *
	 */
	const cacheExpireNone = -1; 

	/**
	 * The PHP output filename used by this Template
	 *
	 */
	protected $filename;

	/**
	 * Does the PHP template file exist?
	 *
	 */
	protected $filenameExists = null; 
	 
	/**
	 * The Fieldgroup instance assigned to this Template
	 *
	 */
	protected $fieldgroup; 

	/**
	 * The previous Fieldgroup instance assigned to this template, if changed during runtime
	 *
	 */
	protected $fieldgroupPrevious = null; 

	/**
	 * Roles that pages using this template support
	 *
	 */
	protected $_roles = null;

	/**
	 * The template's settings, as they relate to database schema
	 *
	 */
	protected $settings = array(
		'id' => 0, 
		'name' => '', 
		'fieldgroups_id' => 0, 
		'flags' => 0,
		'cache_time' => 0, 
		); 

	/**
	 * Array where get/set properties are stored
	 *
	 */
	protected $data = array(
		'useRoles' => 0, 		// does this template define access?
		'editRoles' => array(),		// IDs of roles that may edit pages using this template
		'addRoles' => array(),		// IDs of roles that may add children to pages using this template
		'createRoles' => array(),	// IDs of roles that may create pages using this template
		'rolesPermissions' => array(), 	// Permission overrides by role: Array keys are role IDs, values are permission ID (add) or negative permission ID (revoke)
		'noInherit' => 0, 			// Specify 1 to prevent edit/add/create access from inheriting to non-access controlled children, or 0 for default inherit behavior.
		'childrenTemplatesID' => 0, 	// template ID for child pages, or -1 if no children allowed. DEPRECATED
		'sortfield' => '',		// Field that children of templates using this page should sort by. blank=page decides or 'sort'=manual drag-n-drop
		'noChildren' => '', 		// set to 1 to cancel use of childTemplates
		'noParents' => '', 		// set to 1 to cancel use of parentTemplates
		'childTemplates' => array(),	// array of template IDs that are allowed for children. blank array = any. 
		'parentTemplates' => array(),	// array of template IDs that are allowed for parents. blank array = any.
		'allowPageNum' => 0, 		// allow page numbers in URLs?
		'allowChangeUser' => 0,		// allow the createdUser/created_users_id field of pages to be changed? (with API or in admin w/superuser only)
		'redirectLogin' => 0, 		// redirect when no access: 0 = 404, 1 = login page, 'url' = URL to redirec to
		'urlSegments' => 0,		// allow URL segments on pages? (0=no, 1=yes any, string=only these segments)
		'https' => 0, 			// use https? 0 = http or https, 1 = https only, -1 = http only
		'slashUrls' => 1, 		// page URLs should have a trailing slash? 1 = yes, 0 = no	
		'slashPageNum' => 0,	// should page number segments end with a slash? 0=either, 1=yes, -1=no (applies only if allowPageNum=1)
		'slashUrlSegments' => 0,	// should URL segments end with a slash? 0=either, 1=yes, -1=no (applies only if urlSegments!=0)
		'altFilename' => '',		// alternate filename for template file, if not based on template name
		'guestSearchable' => 0, 	// pages appear in search results even when user doesn't have access?
		'pageClass' => '', 		// class for instantiated page objects. 'Page' assumed if blank, or specify class name. 
		'childNameFormat' => '',	// Name format for child pages. when specified, the page-add UI step can be skipped when adding chilcren. Counter appended till unique. Date format assumed if any non-pageName chars present. Use 'title' to pull from title field. 
		'pageLabelField' => '',		// CSV or space separated string of field names to be displayed by ProcessPageList (overrides those set with ProcessPageList config). May also be a markup {tag} format string. 
		'noGlobal' => 0, 		// template should ignore the 'global' option of fields?
		'noMove' => 0,			// pages using this template are not moveable?
		'noTrash' => 0,			// pages using thsi template may not go in trash? (i.e. they will be deleted not trashed)
		'noSettings' => 0, 		// don't show a 'settings' tab on pages using this template?
		'noChangeTemplate' => 0, 	// don't allow pages using this template to change their template?
		'noShortcut' => 0, 		// don't allow pages using this template to appear in shortcut "add new page" menu
		'noUnpublish' => 0,		// don't allow pages using this template to ever exist in an unpublished state - if page exists, it must be published 
		'nameContentTab' => 0, 		// pages should display the 'name' field on the content tab?	
		'noCacheGetVars' => '',		// GET vars that trigger disabling the cache (only when cache_time > 0)
		'noCachePostVars' => '',	// POST vars that trigger disabling the cache (only when cache_time > 0)
		'useCacheForUsers' => 0, 	// use cache for: 0 = only guest users, 1 = guests and logged in users
		'cacheExpire' => 0, 		// expire the cache for all pages when page using this template is saved? (1 = yes, 0 = no- only current page)
		'cacheExpirePages' => array(),	// array of Page IDs that should be expired, when cacheExpire == Template::cacheExpireSpecific
		'cacheExpireSelector' => '', // selector string that matches pages to expire when cacheExpire == Template::cacheExpireSelector
		'label' => '',			// label that describes what this template is for (optional)
		'tags' => '',			// optional tags that can group this template with others in the admin templates list 
		'modified' => 0, 		// last modified time for template or template file
		'titleNames' => 0, 		// future page title changes re-create the page names too? (recommend only if PagePathHistory is installed)
		'noPrependTemplateFile' => 0, // disable automatic inclusion of $config->prependTemplateFile 
		'noAppendTemplateFile' => 0, // disable automatic inclusion of $config->appendTemplateFile
		'prependFile' => '', // file to prepend (relative to /site/templates/)
		'appendFile' => '', // file to append (relative to /site/templates/)
		'tabContent' => '', 	// label for the Content tab (if different from 'Content')
		'tabChildren' => '', 	// label for the Children tab (if different from 'Children')
		'nameLabel' => '', // label for the "name" property of the page (if something other than "Name")
		'contentType' => '', // Content-type header or index of header from $config->contentTypes
		); 


	/**
	 * Get a Template property
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {

		if($key == 'filename') return $this->filename();
		if($key == 'fields') $key = 'fieldgroup';
		if($key == 'fieldgroup') return $this->fieldgroup; 
		if($key == 'fieldgroupPrevious') return $this->fieldgroupPrevious; 
		if($key == 'roles') return $this->getRoles();
		if($key == 'cacheTime') $key = 'cache_time'; // for camel case consistency
		if($key == 'icon') return $this->getIcon();
		if($key == 'urlSegments') return $this->urlSegments();

		return isset($this->settings[$key]) ? $this->settings[$key] : parent::get($key); 
	}

	/**
	 * Get the role pages that are part of this template
	 *
	 * This method returns a blank PageArray if roles haven't yet been loaded into the template. 
	 * If the roles have previously been loaded as an array, then this method converts that array to a PageArray and returns it. 
	 *
	 * @param string $type Default is 'view', but you may also specify 'edit', 'create' or 'add' to retrieve those
	 * @return PageArray
	 * @throws WireException if given an unknown roles type
	 *
	 */
	public function getRoles($type = 'view') {

		if(strpos($type, 'page-') === 0) $type = str_replace('page-', '', $type);
		
		if($type != 'view') {
			$roles = new PageArray();
			$roleIDs = null;
			if($type == 'edit') $roleIDs = $this->editRoles;	
				else if($type == 'create') $roleIDs = $this->createRoles;
				else if($type == 'add') $roleIDs = $this->addRoles;
				else throw new WireException("Unknown roles type: $type"); 
			if(empty($roleIDs)) return $roles;
			return $this->wire('pages')->getById($roleIDs);
		}

		// type=view assumed from this point forward
		
		if(is_null($this->_roles)) {
			return new PageArray();

		} else if($this->_roles instanceof PageArray) {
			return $this->_roles;
		
		} else if(is_array($this->_roles)) {
			$errors = array();
			$roles = new PageArray();
			if(count($this->_roles)) {
				$test = implode('0', $this->_roles); // test to see if it's all digits (IDs)
				if(ctype_digit("$test")) {
					$roles->import($this->pages->getById($this->_roles)); 
				} else {
					// role names
					foreach($this->_roles as $name) {
						$role = $this->wire('roles')->get($name); 
						if($role->id) {
							$roles->add($role); 
						} else {
							$errors[] = $name; 
						}
					}
				}
			}
			if(count($errors) && $this->useRoles) $this->error("Unable to load role(s): " . implode(', ', $errors)); 
			$this->_roles = $roles;
			return $this->_roles;
		} else {
			return new PageArray();
		}
	}

	/**
	 * Does this template have the given role name or page?
	 *
	 * @param string|Page $role Name of role or page object representing it. 
	 * @param string|Permission Which permission to check: 'page-view', 'page-edit', 'page-create' or 'page-add' (default is 'page-view')
	 * @return bool
	 *
	 */
	public function hasRole($role, $type = 'view') {
		$has = false;
		$roles = $this->getRoles();
		$rolePage = null;
		if(is_object($type) && $type instanceof Page) $type = $type->name;
		if(strpos($type, 'page-') === 0) $type = str_replace('page-', '', $type);
		if(is_string($role)) {
			$has = $roles->has("name=$role");
		} else if(is_int($role)) {
			$has = $roles->has("id=$role");
			$rolePage = $this->wire('roles')->get($role);
		} else if($role instanceof Page) {
			$has = $roles->has($role);
			$rolePage = $role;
		}
		if($type == 'view') return $has;
		if(!$has) return false; // page-view is a pre-requisite
		if(!$rolePage || !$rolePage->id) $rolePage = $this->wire('roles')->get($role);
		if(!$rolePage->id) return false;
		if($type === 'edit') {
			$has = in_array($rolePage->id, $this->editRoles);
		} else if($type === 'create') {
			$has = in_array($rolePage->id, $this->createRoles);
		} else if($tye == 'add') {
			$has = in_array($rolePage->id, $this->addRoles);
		}
		return $has;
	}

	/**
	 * Given an array of page IDs or a PageArray, sets the roles for this template
	 *
	 * @param array|PageArray $value
	 * @param string Default is 'view', but you may also specify 'edit', 'create', or 'add' to set that type
	 *
	 */
	public function setRoles($value, $type = 'view') {
		if(strpos($type, 'page-') === 0) $type = str_replace('page-', '', $type);
		
		if($type == 'view') {
			if(is_array($value) || $value instanceof PageArray) {
				$this->_roles = $value;
			}
			
		} else if(WireArray::iterable($value)) {
			$roleIDs = array();
			foreach($value as $v) {
				if(is_int($v)) $id = $v;
					else if(is_string($v) && ctype_digit($v)) $id = (int) $v;
					else if($v instanceof Page) $id = $v->id;
					else continue;
				$roleIDs[] = $id;	
			}
			if($type == 'edit') $this->set('editRoles', $roleIDs);
				else if($type == 'create') $this->set('createRoles', $roleIDs);
				else if($type == 'add') $this->set('addRoles', $roleIDs);
		}
	}

	/**
	 * Does this template have the given field?
	 *
	 * @param string|int|Field
	 * @return bool
	 *
	 */
	public function hasField($name) {
		return $this->fieldgroup->hasField($name);
	}

	/**
	 * Set a Template property
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 * @throws WireException
	 *
	 */
	public function set($key, $value) {

		if($key == 'flags') { 
			$this->setFlags($value); 

		} else if(isset($this->settings[$key])) { 

			if($key == 'id') {
				$value = (int) $value; 
			} else if($key == 'name') {
				$value = $this->wire('sanitizer')->name($value); 
			} else if($key == 'fieldgroups_id' && $value) {
				$fieldgroup = $this->wire('fieldgroups')->get($value); 
				if($fieldgroup) $this->setFieldgroup($fieldgroup); 
					else $this->error("Unable to load fieldgroup '$value' for template $this->name"); 
				return $this;
			} else if($key == 'cache_time' || $key == 'cacheTime') {
				$value = (int) $value; 
			} else {
				$value = '';
			}

			if($this->settings[$key] != $value) {
				if($this->settings[$key] && ($this->settings['flags'] & Template::flagSystem) && in_array($key, array('id', 'name'))) {
					throw new WireException("Template '$this' has the system flag and you may not change it's 'id' or 'name' fields. "); 
				}
				$this->trackChange($key, $this->settings[$key], $value); 
			}
			$this->settings[$key] = $value; 

		} else if($key == 'fieldgroup' || $key == 'fields') {
			$this->setFieldgroup($value); 
			
		} else if($key == 'filename') {
			$this->setFilename($value); 

		} else if($key == 'roles') {
			$this->setRoles($value);

		} else if($key == 'childrenTemplatesID') { // this can eventaully be removed
			if($value < 0) {
				parent::set('noChildren', 1);
			} else if($value) {
				$v = $this->childTemplates; 
				$v[] = (int) $value; 
				parent::set('childTemplates', $v);
			}

		} else if($key == 'sortfield') {
			$value = $this->wire('pages')->sortfields()->decode($value, '');	
			parent::set($key, $value); 

		} else if(in_array($key, array('addRoles', 'editRoles', 'createRoles'))) {
			if(!is_array($value)) $value = array();
			foreach($value as $k => $v) {
				if(!is_int($v)) {
					if(is_object($v)) {
						$v = $v->id;
					} else if(is_string($v) && !ctype_digit("$v")) {
						$role = $this->wire('roles')->get($v);
						if(!$role->id && $this->_importMode && $this->useRoles) $this->error("Unable to load role: $v");
						$v = $role->id;
					}
				}
				if($v) $value[(int) $k] = (int) $v;
			}
			parent::set($key, $value);

		} else if($key == 'rolesPermissions') {
			if(!is_array($value)) $value = array();
			$_value = array();
			foreach($value as $roleID => $permissionIDs) {
				// if any one of these happend to be a role name or permission name, convert to IDs
				if(!ctype_digit("$roleID")) $roleID = $this->wire('roles')->get("name=$roleID")->id;
				if(!$roleID) continue;
				foreach($permissionIDs as $permissionID) {
					if(!ctype_digit(ltrim($permissionID, '-'))) {
						$revoke = strpos($permissionID, '-') === 0;
						$permissionID = $this->wire('permissions')->get("name=" . ltrim($permissionID, '-'))->id;
						if(!$permissionID) continue;
						if($revoke) $permissionID = "-$permissionID";
					}
					// we force these as strings so that they can portable in JSON
					$roleID = (string) ((int) $roleID);
					$permissionID = (string) ((int) $permissionID);
					if(!isset($_value[$roleID])) $_value[$roleID] = array();
					$_value[$roleID][] = $permissionID;
				}
			}
			parent::set($key, $_value);
			
		} else if(in_array($key, array('childTemplates', 'parentTemplates'))) {
			if(!is_array($value)) $value = array();
			foreach($value as $k => $v) {
				if(!is_int($v)) {
					if(is_object($v)) {
						$v = $v->id; 
					} else if(!ctype_digit("$v")) {
						$t = $this->wire('templates')->get($v);
						if(!$t && $this->_importMode) {
							$this->error("Unable to load template '$v' for '$this->name.$key'"); 
						}
						$v = $t ? $t->id : 0; 
					}
				}
				if($v) $value[(int)$k] = (int) $v; 
			}
			parent::set($key, $value); 

		} else if(in_array($key, array('noChildren', 'noParents'))) {
			$value = (int) $value;
			if(!$value) $value = null; // enforce null over 0
			parent::set($key, $value);
			
		} else if($key == 'cacheExpirePages') {
			if(!is_array($value)) $value = array();
			foreach($value as $k => $v) {
				if(is_object($v)) {
					$v = $v->id;
				} else if(!ctype_digit("$v")) {
					$p = $this->wire('pages')->get($v);
					if(!$p->id) $this->error("Unable to load page: $v");
					$v = $p->id;
				}
				$value[(int) $k] = (int) $v;
			}
			parent::set($key, $value);

		} else if($key == 'icon') {
			$this->setIcon($value);

		} else if($key == 'urlSegments') {
			$this->urlSegments($value); 
			
		} else {
			parent::set($key, $value); 
		}

		return $this; 
	}

	/**
	 * Get or set allowed URL segments
	 * 
	 * @param array|int|bool|string $value Omit to return current value, or to set value: 
	 * 	Specify array of allowed URL segments, may include 'segment', 'segment/path' or 'regex:your-regex'.
	 * 	Or specify true or 1 to enable all URL segments
	 * 	Or specify 0, false, or blank array to disable all URL segments
	 * @return array|int Returns array of allowed URL segments, or 0 if disabled, or 1 if any allowed
	 * 
	 */
	public function urlSegments($value = '~') {
		
		if($value === '~') {
			// return current only
			$value = $this->data['urlSegments'];
			if(empty($value)) return 0; 
			if(is_array($value)) return $value; 
			return 1; 
			
		} else if(is_array($value)) {
			// set array value
			if(count($value)) {
				// we'll take it
				foreach($value as $k => $v) {
					$v = trim($v); // trim whitespace
					$v = trim($v, '/'); // remove leading/trailing slashes
					if($v !== $value[$k]) $value[$k] = $v; 
				}
			} else {
				// blank array becomes 0
				$value = 0;
			}
			
		} else {
			// enforce 0 or 1
			$value = empty($value) ? 0 : 1;
		}
	
		if(empty($this->data['urlSegments']) && empty($value)) {
			// don't bother updating if both are considered empty
			return $value;
		}
		
		if($this->data['urlSegments'] !== $value) {
			// update current value
			$this->trackChange('urlSegments', $this->data['urlSegments'], $value); 
			$this->data['urlSegments'] = $value; 
		} 
		
		return $value; 
	}

	/**
	 * Set the flags property and prevent the system flag from being removed
	 *
	 * @param int $value
	 *
	 */
	protected function setFlags($value) {
		$value = (int) $value;
		$override = $this->settings['flags'] & Template::flagSystemOverride; 
		if($this->settings['flags'] & Template::flagSystem) {
			// prevent the system flag from being removed
			if(!$override) $value = $value | Template::flagSystem; 
		}
		$this->settings['flags'] = $value; 
	}


	/**
	 * Set this template's filename, with or without path
	 *
	 * @param string $value The filename with or without path
	 *
	 */
	protected function setFilename($value) {
		if(empty($value)) return; 

		if(strpos($value, '/') === false) {
			$value = $this->config->paths->templates . $value;

		} else if(strpos($value, $this->config->paths->root) !== 0) {
			$value = $this->config->paths->templates . basename($value); 
		}

		if(file_exists($value)) {
			$this->filename = $value; 
			$this->filenameExists = true; 
		}
	}

	/**
	 * Set this Template's Fieldgroup
	 *
	 * @param Fieldgroup $fieldgroup
	 * @return $this
	 * @throws WireException
	 *
	 */
	public function setFieldgroup(Fieldgroup $fieldgroup) {

		if(is_null($this->fieldgroup) || $fieldgroup->id != $this->fieldgroup->id) $this->trackChange('fieldgroup', $this->fieldgroup, $fieldgroup); 

		if($this->fieldgroup && $fieldgroup->id != $this->fieldgroup->id) {
			// save record of the previous fieldgroup so that unused fields can be deleted during save()
			$this->fieldgroupPrevious = $this->fieldgroup; 

			if($this->flags & Template::flagSystem) 
				throw new WireException("Can't change fieldgroup for template '{$this}' because it is a system template."); 

			$hasPermanentFields = false;
			foreach($this->fieldgroup as $field) {
				if($field->flags & Field::flagPermanent) $hasPermanentFields = true; 
			}
			if($this->id && $hasPermanentFields) throw new WireException("Fieldgroup for template '{$this}' may not be changed because it has permanent fields."); 
		}

		$this->fieldgroup = $fieldgroup;
		$this->settings['fieldgroups_id'] = $fieldgroup->id; 
		return $this; 
	}

	/**
	 * Return the number of pages used by this template. 
	 *
	 * @return int
	 *
	 */
	public function getNumPages() {
		return $this->wire('templates')->getNumPages($this); 	
	}

	/**
	 * Save the template to database
	 *
	 * @return $this|bool Returns Template if successful, or false if not
	 *
	 */
	public function save() {

		$result = $this->wire('templates')->save($this); 	

		return $result ? $this : false; 
	}

	/**
	 * Return corresponding template filename, including path
	 *
	 * @return string
	 * @throws WireException
	 *	
	 */
	public function filename() {

		if($this->filename) return $this->filename; 

		if(!$this->settings['name']) throw new WireException("Template must be assigned a name before 'filename' can be accessed"); 

		if($this->altFilename) {
			$altFilename = $this->wire('templates')->path . basename($this->altFilename, "." . $this->config->templateExtension) . "." . $this->config->templateExtension; 
			$this->filename = $altFilename; 
		} else {
			$this->filename = $this->wire('templates')->path . $this->settings['name'] . '.' . $this->config->templateExtension;
		}
	
		if($this->filenameExists()) {
			$modified = filemtime($this->filename);
			if($modified > $this->modified) {
				$this->modified = $modified;
				// tell it to save the template after the request is finished
				$this->addHookAfter('ProcessWire::finished', $this, 'hookFinished'); 
			}
		}
		
		return $this->filename;
	}

	/**
	 * Saves a template after the request is complete
	 * 
	 * @param HookEvent $e
	 * 
	 */
	public function hookFinished(HookEvent $e) {
		foreach($this->wire('templates') as $template) {
			if($template->isChanged('modified')) $template->save();
		}
	}

	/**
	 * Does the template filename exist?
	 *
	 * @return string
	 *	
	 */
	public function filenameExists() {
		if(!is_null($this->filenameExists)) return $this->filenameExists; 
		$this->filenameExists = file_exists($this->filename()); 
		return $this->filenameExists; 
	}

	/**
	 * Per Saveable interface, get an array of this table's data
	 *
	 * We override this so that we can add our roles array to it. 
	 *
	 */
	public function getArray() {
		$a = parent::getArray();

		if($this->useRoles) { 
			$a['roles'] = array();	
			foreach($this->getRoles() as $role) {
				$a['roles'][] = $role->id;
			}
		} else {
			unset($a['roles'], $a['editRoles'], $a['addRoles'], $a['createRoles'], $a['rolesPermissions']); 
		}

		return $a;
	}

	/**
	 * Per Saveable interface: return data for storage in table
	 *
	 */
	public function getTableData() {

		$tableData = $this->settings; 
		$data = $this->getArray();
		// ensure sortfield is a signed integer or native name, rather than a custom fieldname
		if(!empty($data['sortfield'])) $data['sortfield'] = $this->fuel('pages')->sortfields()->encode($data['sortfield'], ''); 
		$tableData['data'] = $data; 
		
		return $tableData; 
	}

	/**
	 * Per Saveable interface: return data for external storage
	 * 
	 */
	public function getExportData() {
		return $this->wire('templates')->getExportData($this); 	
	}

	/**
	 * Given an array of export data, import it
	 * 
	 * @param array $data
	 * @return bool True if successful, false if not
	 * @return array Returns array(
	 * 	[property_name] => array(
	 * 		'old' => 'old value', // old value (in string comparison format)
	 * 		'new' => 'new value', // new value (in string comparison format)
	 * 		'error' => 'error message or blank if no error'  // error message (string) or messages (array)
	 * 		)
	 * 
	 */
	public function setImportData(array $data) {
		return $this->wire('templates')->setImportData($this, $data); 
	}
	
	/**
	 * The string value of a Template is always it's name
	 *
	 */
	public function __toString() {
		return $this->name; 
	}


	/**
	 * Return the parent page that this template assumes new pages are added to 
	 *
	 * This is based on family settings, when applicable. 
	 * It also takes into account user access, if requested (see arg 1). 
	 *
	 * If there is no shortcut parent, NULL is returned. 
	 * If there are multiple possible shortcut parents, a NullPage is returned.
	 *
	 * @param bool $checkAccess Whether or not to check for user access to do this (default=false).
	 * @return Page|NullPage|null
	 *
	 */
	public function getParentPage($checkAccess = false) {
		return $this->wire('templates')->getParentPage($this, $checkAccess); 
	}

	/**
	 * Return all possible parent pages for this template
	 * 
	 * @param bool $checkAccess Specify true to exclude parents that user doesn't have access to add children to (default=false)
	 * @return PageArray
	 * 
	 */
	public function getParentPages($checkAccess = false) {
		return $this->wire('templates')->getParentPages($this, $checkAccess);
	}

	/**
	 * Return template label for current language, or specified language if provided
	 * 
	 * If no template label, return template name.
	 * This is different from $this->label in that it knows about languages (when installed)
	 * and it will always return something. If there's no label, you'll still get the name. 
	 * 
	 * @param Page|Language $language Optional, if not used then user's current language is used
	 * @return string
	 * 
	 */
	public function getLabel($language = null) {
		if(is_null($language)) $language = $this->wire('languages') ? $this->wire('user')->language : null;
		if($language) {
			$label = $this->get("label$language"); 
			if(!strlen($label)) $label = $this->label;
		} else {
			$label = $this->label;
		}
		if(!strlen($label)) $label = $this->name;
		return $label;
	}
	
	/**
	 * Return tab label for current language (or specified language if provided)
	 *
	 * @param string $tab Which tab? 'content' or 'children'
	 * @param Page|Language $language Optional, if not used then user's current language is used
	 * @return string Returns blank if default tab label not overridden
	 *
	 */
	public function getTabLabel($tab, $language = null) {
		$tab = ucfirst(strtolower($tab)); 
		if(is_null($language)) $language = $this->wire('languages') ? $this->wire('user')->language : null;
		if(!$language || $language->isDefault()) $language = '';
		$label = $this->get("tab$tab$language");
		return $label;
	}

	/**
	 * Return the overriden "page name" label, or blank if not overridden
	 * 
	 * @param Language|null $language
	 * @return string
	 * 
	 */
	public function getNameLabel($language = null) {
		if(is_null($language)) $language = $this->wire('languages') ? $this->wire('user')->language : null;
		if(!$language || $language->isDefault()) $language = '';
		return $this->get("nameLabel$language");
	}

	/**
	 * Return the icon name used by this template (if specified in pageLabeField)
	 * 
	 * @param bool $prefix Specify true if you want the icon prefix (icon- or fa-) to be included (default=false).
	 * @return string
	 * 
	 */
	public function getIcon($prefix = false) {
		$label = $this->pageLabelField; 
		$icon = '';
		if(strpos($label, 'icon-') !== false || strpos($label, 'fa-') !== false) {
			if(preg_match('/\b(icon-|fa-)([^\s,]+)/', $label, $matches)) {
				if($matches[1] == 'icon-') $matches[1] = 'fa-';
				$icon = $prefix ? $matches[1] . $matches[2] : $matches[2];
			}
		}
		return $icon;
	}

	/**
	 * Set the icon to use with this template
	 * 
	 * This manipulates the pageLabelField property, since there isn't actually an icon property. 
	 * 
	 * @param $icon 
	 * @return $this
	 * 
	 */
	public function setIcon($icon) {
		$icon = $this->wire('sanitizer')->pageName($icon); 
		$current = $this->getIcon(false); 	
		$label = $this->pageLabelField;
		if(strpos($icon, "icon-") === 0) $icon = str_replace("icon-", "fa-", $icon); // convert icon-str to fa-str
		if($icon && strpos($icon, "fa-") !== 0) $icon = "fa-$icon"; // convert anon icon to fa-icon
		if($current) {
			// replace icon currently in pageLabelField with new one
			$label = str_replace(array("fa-$current", "icon-$current"), $icon, $label);
		} else if($icon) {
			// add icon to pageLabelField where there wasn't one already
			if(empty($label)) $label = $this->fieldgroup->hasField('title') ? 'title' : '';
			$label = trim("$icon $label");
		}
		$this->pageLabelField = $label;
		return $this;
	}

}


