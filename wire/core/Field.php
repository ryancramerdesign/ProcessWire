<?php

/**
 * ProcessWire Field
 *
 * The Field class corresponds to a record in the fields database table 
 * and is managed by the 'Fields' class.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 * @property int $id
 * @property string $name
 * @property string $table
 * @property string $prevTable
 * @property Fieldtype|null $type
 * @property Fieldtype $prevFieldtype
 * @property int $flags
 * @property string $label
 * @property string $description
 * @property string $notes
 * @property string $icon
 * @property bool $useRoles Whether or not access control is enabled (same as $field->flags & Field::flagAccess)
 * @property array $editRoles Role IDs with edit access, applicable only if $field->useRoles is true.
 * @property array $viewRoles Role IDs with view access, applicable only if $field->useRoles is true.
 * 
 * @method bool viewable(Page $page = null, User $user = null)
 * @method bool editable(Page $page = null, User $user = null)
 * @method Inputfield getInputfield(Page $page, $contextStr = '')
 * @method InputfieldWrapper getConfigInputfields()
 * 
 * @todo add modified date property
 *
 */
class Field extends WireData implements Saveable, Exportable {

	/**
	 * Field should be automatically joined to the page at page load time
	 *
	 */
	const flagAutojoin = 1;

	/**
	 * Field used by all fieldgroups - all fieldgroups required to contain this field
	 *
	 */
	const flagGlobal = 4;

	/**
	 * Field is a system field and may not be deleted, have it's name changed, or be converted to non-system
	 *
	 */
	const flagSystem = 8;

	/**
	 * Field is permanent in any fieldgroups/templates where it exists - it may not be removed from them
	 *
	 */
	const flagPermanent = 16;

	/**
	 * Field is access controlled
	 *
	 */
	const flagAccess = 32;

	/**
	 * If field is access controlled, this flag says that values are still front-end API accessible
	 * 
	 * Without this flag, non-viewable values are made blank when output formatting is ON. 
	 * 
	 */
	const flagAccessAPI = 64;

	/**
	 * If field is access controlled and user has no edit access, they can still view in the editor (if they have view permission)
	 * 
	 * Without this flag, non-editable values are simply not shown in the editor at all. 
	 * 
	 */
	const flagAccessEditor = 128; 

	/**
	 * Field has been placed in a runtime state where it is contextual to a specific fieldgroup and is no longer saveable
	 *
	 */
	const flagFieldgroupContext = 2048;

	/**
	 * Set this flag to override system/permanent flags if necessary - once set, system/permanent flags can be removed, but not in the same set().
	 *
	 */
	const flagSystemOverride = 32768;

	/**
	 * Permanent/native settings to an individual Field
	 *
	 * id: Numeric ID corresponding with id in the fields table.
	 * type: Fieldtype object or NULL if no Fieldtype assigned.
	 * label: String text label corresponding to the <label> field during input.
	 * flags:
	 * - autojoin: True if the field is automatically joined with the page, or False if it's value is loaded separately.
	 * - global: Is this field required by all Fieldgroups?
	 *
	 */
	protected $settings = array(
		'id'    => 0,
		'type'  => null,
		'flags' => 0,
		'name'  => '',
		'label' => '',
	);

	/**
	 * If the field name changed, this is the name of the previous table so that it can be renamed at save time
	 *
	 */
	protected $prevTable;

	/**
	 * If the field type changed, this is the previous fieldtype so that it can be changed at save time
	 *
	 */
	protected $prevFieldtype;

	/**
	 * Accessed properties, becomes array when set to true, null when set to false
	 *
	 * Used for keeping track of which properties are accessed during a request, to help determine which
	 * $data properties might no longer be in use.
	 *
	 * @var null|array
	 *
	 */
	protected $trackGets = null;

	/**
	 * Array of Role IDs referring to roles that are allowed to view contents of this field (on pages)
	 *
	 * Applicable only if the flagAccess flag is set
	 *
	 * @var array
	 *
	 */
	protected $viewRoles = array();

	/**
	 * Array of Role IDs referring to roles that are allowed to edit contents of this field (on pages)
	 *
	 * Applicable only if the flagAccess flag is set
	 *
	 * @var array
	 *
	 */
	protected $editRoles = array();

	/**
	 * True if lowercase tables should be enforce, false if not (null = unset). Cached from $config
	 *
	 */
	static protected $lowercaseTables = null;


	/**
	 * Set a native setting or a dynamic data property for this Field
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return this
	 *
	 */
	public function set($key, $value) {

		if($key == 'name') {
			return $this->setName($value);
		} else if($key == 'type' && $value) {
			return $this->setFieldtype($value);
		} else if($key == 'prevTable') {
			$this->prevTable = $value;
			return $this;
		} else if($key == 'prevFieldtype') {
			$this->prevFieldtype = $value;
			return $this;
		} else if($key == 'flags') {
			$this->setFlags($value);
			return $this;
		} else if($key == 'flagsAdd') {
			return $this->addFlag($value);
		} else if($key == 'flagsDel') {
			return $this->removeFlag($value);
		} else if($key == 'id') {
			$value = (int) $value;
		}

		if(isset($this->settings[$key])) {
			$this->settings[$key] = $value;
		} else if($key == 'icon') {
			$this->setIcon($value);
		} else if($key == 'editRoles') {
			$this->setRoles('edit', $value);
		} else if($key == 'viewRoles') {
			$this->setRoles('view', $value);
		} else if($key == 'useRoles') {
			$flags = $this->flags;
			if($value) {
				$flags = $flags | self::flagAccess; // add flag
			} else {
				$flags = $flags & ~self::flagAccess; // remove flag
			}
			$this->setFlags($flags);
		} else {
			return parent::set($key, $value);
		}

		return $this;
	}

	/**
	 * Set the flags field, ensuring a system flag remains set
	 *
	 * @param int $value
	 *
	 */
	protected function setFlags($value) {
		// ensure that the system flag stays set
		$value = (int) $value;
		$override = $this->settings['flags'] & Field::flagSystemOverride;
		if(!$override) {
			if($this->settings['flags'] & Field::flagSystem) $value = $value | Field::flagSystem;
			if($this->settings['flags'] & Field::flagPermanent) $value = $value | Field::flagPermanent;
		}
		$this->settings['flags'] = $value;
	}

	/**
	 * Add the given flag
	 * 
	 * @param int $flag
	 * @return $this
	 * 
	 */
	public function addFlag($flag) {
		$flag = (int) $flag;
		$this->setFlags($this->settings['flags'] | $flag);
		return $this;
	}

	/**
	 * Remove the given flag
	 * 
	 * @param $flag
	 * @return $this
	 * 
	 */
	public function removeFlag($flag) {
		$flag = (int) $flag;
		$this->setFlags($this->settings['flags'] & ~$flag);
		return $this;
	}

	/**
	 * Does this field have the given flag?
	 * 
	 * @param int $flag
	 * @return bool
	 * 
	 */
	public function hasFlag($flag) {
		$flag = (int) $flag;
		return ($this->settings['flags'] & $flag) ? true : false;
	}

	/**
	 * Get a Field setting or dynamic data property
	 *
	 * @param string $key
	 *
	 * @return mixed
	 *
	 */
	public function get($key) {
		if($key == 'viewRoles') return $this->viewRoles;
		else if($key == 'editRoles') return $this->editRoles;
		else if($key == 'table') return $this->getTable();
		else if($key == 'prevTable') return $this->prevTable;
		else if($key == 'prevFieldtype') return $this->prevFieldtype;
		else if(isset($this->settings[$key])) return $this->settings[$key];
		else if($key == 'icon') return $this->getIcon(true);
		else if($key == 'useRoles') return ($this->settings['flags'] & self::flagAccess) ? true : false;
		else if($key == 'flags') return $this->settings['flags'];

		$value = parent::get($key);
		if(is_array($this->trackGets)) $this->trackGets($key);
		return $value;
	}

	/**
	 * Turn on tracking for accessed properties
	 *
	 * @param bool|string $key
	 *    Omit to retrieve current trackGets value.
	 *    Specify true to enable Get tracking.
	 *    Specify false to disable (and reset) Get tracking.
	 *    Specify string key to track.
	 *
	 * @return bool|array Returns current state of trackGets when no arguments provided.
	 *    Otherwise it just returns true.
	 *
	 */
	public function trackGets($key = null) {
		if(is_null($key)) {
			// return current value
			return array_keys($this->trackGets);
		} else if($key === true) {
			// enable tracking
			if(!is_array($this->trackGets)) $this->trackGets = array();
		} else if($key === false) {
			// disable tracking
			$this->trackGets = null;
		} else if(!is_int($key) && is_array($this->trackGets)) {
			// track a key
			$this->trackGets[$key] = 1;
		}
		return true;
	}


	/**
	 * Return a key=>value array of the data associated with the database table per Saveable interface
	 *
	 * @return array
	 *
	 */
	public function getTableData() {
		$a = $this->settings;
		$a['data'] = $this->data;
		foreach($a['data'] as $key => $value) {
			// remove runtime data (properties beginning with underscore)
			if(strpos($key, '_') === 0) unset($a['data'][$key]);
		}
		if($this->settings['flags'] & self::flagAccess) {
			$a['data']['editRoles'] = $this->editRoles;
			$a['data']['viewRoles'] = $this->viewRoles;
		} else {
			unset($a['data']['editRoles'], $a['data']['viewRoles']); // just in case
		}
		return $a;
	}

	/**
	 * Per Saveable interface: return data for external storage
	 *
	 */
	public function getExportData() {

		if($this->type) {
			$data = $this->getTableData();
			$data['type'] = $this->type->className();
		} else {
			$data['type'] = '';
		}

		if(isset($data['data'])) $data = array_merge($data, $data['data']); // flatten
		unset($data['data']);

		if($this->type) {
			$typeData = $this->type->exportConfigData($this, $data);
			$data = array_merge($data, $typeData);
		}

		// remove named flags from data since the 'flags' property already covers them
		$flagOptions = array('autojoin', 'global', 'system', 'permanent');
		foreach($flagOptions as $name) unset($data[$name]);

		$data['flags'] = $this->flags;

		foreach($data as $key => $value) {
			// exclude properties beginning with underscore as they are assumed to be for runtime use only
			if(strpos($key, '_') === 0) unset($data[$key]);
		}

		return $data;
	}

	/**
	 * Given an export data array, import it back to the class and return what happened
	 *
	 * @param array $data
	 *
	 * @return array Returns array(
	 *    [property_name] => array(
	 *
	 *        // old value (in string comparison format)
	 *        'old' => 'old value',
	 *
	 *        // new value (in string comparison format)
	 *        'new' => 'new value',
	 *
	 *        // error message (string) or messages (array)
	 *        'error' => 'error message or blank if no error' ,
	 *    )
	 *
	 */
	public function setImportData(array $data) {

		$changes = array();
		$data['errors'] = array();
		$_data = $this->getExportData();

		// compare old data to new data to determine what's changed
		foreach($data as $key => $value) {
			if($key == 'errors') continue;
			$data['errors'][$key] = '';
			$old = isset($_data[$key]) ? $_data[$key] : '';
			if(is_array($old)) $old = wireEncodeJSON($old, true);
			$new = is_array($value) ? wireEncodeJSON($value, true) : $value;
			if($old === $new || (empty($old) && empty($new)) || (((string) $old) === ((string) $new))) continue;
			$changes[$key] = array(
				'old'   => $old,
				'new'   => $new,
				'error' => '', // to be populated by Fieldtype::importConfigData when applicable
			);
		}

		// prep data for actual import
		if(!empty($data['type']) && ((string) $this->type) != $data['type']) {
			$this->type = $this->wire('fieldtypes')->get($data['type']);
		}

		if(!$this->type) $this->type = $this->wire('fieldtypes')->get('FieldtypeText');
		$data = $this->type->importConfigData($this, $data);

		// populate import data
		foreach($changes as $key => $change) {
			$this->errors('clear all');
			$this->set($key, $data[$key]);
			if(!empty($data['errors'][$key])) {
				$error = $data['errors'][$key];
				// just in case they switched it to an array of multiple errors, convert back to string
				if(is_array($error)) $error = implode(" \n", $error);
			} else {
				$error = $this->errors('last');
			}
			$changes[$key]['error'] = $error ? $error : '';
		}
		$this->errors('clear all');

		return $changes;
	}

	/**
	 * Set the field's name
	 *
	 * @param string $name
	 *
	 * @return Field $this
	 * @throws WireException
	 *
	 */
	public function setName($name) {
		$name = $this->fuel('sanitizer')->fieldName($name);

		if(Fields::isNativeName($name))
			throw new WireException("Field may not be named '$name' because it is a reserved word");

		if($this->fuel('fields') && ($f = $this->fuel('fields')->get($name)) && $f->id != $this->id)
			throw new WireException("Field may not be named '$name' because it is already used by another field");

		if(strpos($name, '__') !== false)
			throw new WireException("Field name '$name' may not have double underscores because this usage is reserved by the core");

		if($this->settings['name'] != $name) {
			if($this->settings['name'] && ($this->settings['flags'] & Field::flagSystem)) {
				throw new WireException("You may not change the name of field '{$this->settings['name']}' because it is a system field.");
			}
			$this->trackChange('name');
			if($this->settings['name']) $this->prevTable = $this->getTable(); // so that Fields can perform a table rename
		}

		$this->settings['name'] = $name;
		return $this;
	}

	/**
	 * Set what type of field this is.
	 *
	 * Type should be either a Fieldtype object or the string name of a Fieldtype object.
	 *
	 * @param string|Fieldtype $type
	 *
	 * @return Field $this
	 * @throws WireException
	 *
	 */
	public function setFieldtype($type) {

		if(is_object($type) && $type instanceof Fieldtype) {
			// good for you

		} else if(is_string($type)) {
			$typeStr = $type;
			$fieldtypes = $this->fuel('fieldtypes');
			if(!$type = $fieldtypes->get($type)) {
				$this->error("Fieldtype '$typeStr' does not exist");
				return $this;
			}
		} else {
			throw new WireException("Invalid field type in call to Field::setFieldType");
		}

		if(!$this->type || ($this->type->name != $type->name)) {
			$this->trackChange("type:{$type->name}");
			if($this->type) $this->prevFieldtype = $this->type;
		}
		$this->settings['type'] = $type;

		return $this;
	}

	/**
	 * Set the roles that are allowed to view or edit this field on pages
	 *
	 * Applicable only if the flagAccess is set to this field's flags.
	 *
	 * @param string $type Must be either "view" or "edit"
	 * @param PageArray|array|null $roles May be a PageArray of Role objects or an array of Role IDs
	 *
	 * @throws WireException if given invalid argument
	 *
	 */
	public function setRoles($type, $roles) {
		if(empty($roles)) $roles = array();
		if(!WireArray::iterable($roles)) {
			throw new WireException("setRoles expects PageArray or array of Role IDs");
		}
		$ids = array();
		foreach($roles as $role) {
			if(is_int($role) || (is_string($role) && ctype_digit("$role"))) {
				$ids[] = (int) $role;
			} else if($role instanceof Role) {
				$ids[] = (int) $role->id;
			}
		}
		if($type == 'view') {
			$guestID = $this->wire('config')->guestUserRolePageID;
			// if guest is present, then that's inclusive of all, no need to store others in viewRoles
			if(in_array($guestID, $ids)) $ids = array($guestID); 
			if($this->viewRoles != $ids) {
				$this->viewRoles = $ids;
				$this->trackChange('viewRoles');
			}
		} else if($type == 'edit') {
			if($this->editRoles != $ids) {
				$this->editRoles = $ids;
				$this->trackChange('editRoles');
			}
		} else {
			throw new WireException("setRoles expects either 'view' or 'edit' (arg 0)");
		}
	}

	/**
	 * Is this field viewable?
	 *
	 * 1. To maximize efficiency check that $field->useRoles is true before calling this.  
	 * 2. If you have already verified that the page is viewable, omit or specify null for $page argument.
	 * 
	 * PLEASE NOTE: this does not check that the provided $page itself is viewable.
	 * If you want that check, then use $page->viewable($field) instead.
	 * 
	 * @param Page|null $page Optionally specify a Page for context
	 * @param User|null $user Optionally specify a different user (default = current user)
	 * @return bool
	 * 
	 */
	public function ___viewable(Page $page = null, User $user = null) {
		return $this->wire('fields')->_hasPermission($this, 'view', $page, $user);
	}

	/**
	 * Is this field editable?
	 * 
	 * 1. To maximize efficiency check that $field->useRoles is true before calling this.
	 * 2. If you have already verified that the page is viewable, omit or specify null for $page argument.
	 * 
	 * PLEASE NOTE: this does not check that the provided $page itself is editable.
	 * If you want that check, then use $page->editable($field) instead.
	 *
	 * @param Page|string|int|null $page Optionally specify a Page for context
	 * @param User|string|int|null $user Optionally specify a different user (default = current user)
	 * @return bool
	 *
	 */
	public function ___editable(Page $page = null, User $user = null) {
		return $this->wire('fields')->_hasPermission($this, 'edit', $page, $user);
	}
	
	/**
	 * Save this field's settings and data in the database. 
	 *
	 * To hook ___save, use Fields::save instead
	 *
	 */
	public function save() {
		$fields = $this->getFuel('fields'); 
		return $fields->save($this); 
	}


	/**
	 * Return the number of fieldsets this field is used in
	 *
	 * Primarily used to check if the Field is deleteable. 
	 *
	 */ 
	public function numFieldgroups() {
		return count($this->getFieldgroups()); 
	}

	/**
	 * Return a FieldgroupArray of Fieldgroups using this field
	 *
	 * @return FieldgroupsArray
	 *
	 */ 
	public function getFieldgroups() {
		$fieldgroups = new FieldgroupsArray();
		foreach($this->fuel('fieldgroups') as $fieldgroup) {
			foreach($fieldgroup as $field) {
				if($field->id == $this->id) {
					$fieldgroups->add($fieldgroup); 
					break;
				}
			}
		}
		return $fieldgroups; 
	}

	/**
	 * Return a TemplatesArray of Templates using this field
	 *
	 * @return TemplatesArray
	 *
	 */ 
	public function getTemplates() {
		$templates = new TemplatesArray();
		$fieldgroups = $this->getFieldgroups();
		foreach($this->templates as $template) {
			foreach($fieldgroups as $fieldgroup) {
				if($template->fieldgroups_id == $fieldgroup->id) {
					$templates->add($template);	
					break;
				}
			}
		}
		return $templates; 
	}


	/**
	 * Return the default value for this field (if set), or null otherwise. 
	 * 
	 * @deprecated Use $field->type->getDefaultValue($page, $field) instead. 
	 *
	 */
	public function getDefaultValue() {
		$value = $this->get('default'); 
		if($value) return $value; 
		return null;
		
	}

	/**
	 * Get the Inputfield object associated with this Field's Fieldtype
	 *
	 * @param Page $page
	 * @param string $contextStr Optional context string to append to the Inputfield's name/id
	 * @return Inputfield|null 
	 *
	 */
	public function ___getInputfield(Page $page, $contextStr = '') {

		if(!$this->type) return null;
		
		// check access control
		$locked = false;
		if($this->useRoles && !$this->editable($page)) {
			// $this->message("not editable: " . $this->name);
			if(($this->flags & self::flagAccessEditor) && $this->viewable($page)) {
				// Inputfield is viewable but not editable
				$locked = true;
			} else {
				// Inputfield is neither editable nor viewable
				$locked = 'hidden';
			}
		}
		
		$inputfield = $this->type->getInputfield($page, $this);
		if(!$inputfield) return null; 

		// predefined field settings
		$inputfield->attr('name', $this->name . $contextStr); 
		$inputfield->label = $this->label;

		// just in case an Inputfield needs to know it's Fieldtype context, or lack of it
		$inputfield->hasFieldtype = $this->type; 

		// custom field settings
		foreach($this->data as $key => $value) {
			if($inputfield->has($key)) {
				if(is_array($this->trackGets)) $this->trackGets($key); 
				$inputfield->set($key, $value); 
			}
		}

		if($locked && $locked === 'hidden') {
			// Inputfield should not be shown
			$inputfield->collapsed = Inputfield::collapsedHidden;
		} else if($locked) {
			// Inputfield is locked as a result of access control
			$collapsed = $inputfield->collapsed; 
			$ignoreCollapsed = array(Inputfield::collapsedNoLocked, Inputfield::collapsedYesLocked, Inputfield::collapsedHidden);
			if(!in_array($collapsed, $ignoreCollapsed)) {
				// Inputfield is not already locked or hidden, convert to locked equivalent
				if($collapsed == Inputfield::collapsedYes || $collapsed == Inputfield::collapsedBlank) {
					$collapsed = Inputfield::collapsedYesLocked;
				} else if($collapsed == Inputfield::collapsedNo) {
					$collapsed = Inputfield::collapsedNoLocked;
				} else {
					$collapsed = Inputfield::collapsedYesLocked;
				}
				$inputfield->collapsed = $collapsed;
			}
		}

		return $inputfield; 
	}

	/**
	 * Get any configuration fields associated with the Inputfield
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function ___getConfigInputfields() {

		$wrapper = new InputfieldWrapper();
		$fieldgroupContext = $this->flags & Field::flagFieldgroupContext; 
		
		if($fieldgroupContext) {
			$allowContext = $this->type->getConfigAllowContext($this); 
			if(!is_array($allowContext)) $allowContext = array();
		} else {
			$allowContext = array();
		}

		if(!$fieldgroupContext || count($allowContext)) {
			
			$inputfields = new InputfieldWrapper();
			if(!$fieldgroupContext) $inputfields->head = $this->_('Field type details');
			$inputfields->attr('title', $this->_('Details'));

			try {
				$fieldtypeInputfields = $this->type->getConfigInputfields($this); 
				if(!$fieldtypeInputfields) $fieldtypeInputfields = new InputfieldWrapper();
				$configArray = $this->type->getConfigArray($this); 
				if(count($configArray)) {
					$w = new InputfieldWrapper();
					$w->importArray($configArray);
					$w->populateValues($this);
					$fieldtypeInputfields->import($w);
				}
				foreach($fieldtypeInputfields as $inputfield) {
					if($fieldgroupContext && !in_array($inputfield->name, $allowContext)) continue;
					$inputfields->append($inputfield);
				}
			} catch(Exception $e) {
				$this->trackException($e, false, true); 
			}

			if(count($inputfields)) $wrapper->append($inputfields); 
		}

		$inputfields = new InputfieldWrapper();
		$dummyPage = $this->wire('pages')->get("/"); // only using this to satisfy param requirement 

		if($inputfield = $this->getInputfield($dummyPage)) {
			if($fieldgroupContext) {
				$allowContext = array('visibility', 'collapsed', 'columnWidth', 'required', 'requiredIf', 'showIf');
				$allowContext = array_merge($allowContext, $inputfield->getConfigAllowContext($this)); 
			} else {
				$allowContext = array();
				$inputfields->head = $this->_('Input field settings');
			}
			$inputfields->attr('title', $this->_('Input')); 
			$inputfieldInputfields = $inputfield->getConfigInputfields();
			if(!$inputfieldInputfields) $inputfieldInputfields = new InputfieldWrapper();
			$configArray = $inputfield->getConfigArray(); 
			if(count($configArray)) {
				$w = new InputfieldWrapper();
				$w->importArray($configArray);
				$w->populateValues($this);
				$inputfieldInputfields->import($w);
			}
			foreach($inputfieldInputfields as $i) { 
				if($fieldgroupContext && !in_array($i->name, $allowContext)) continue; 
				$inputfields->append($i); 
			}
		}

		$wrapper->append($inputfields); 

		return $wrapper; 
	}

	public function getTable() {
		if(is_null(self::$lowercaseTables)) self::$lowercaseTables = $this->config->dbLowercaseTables ? true : false;
		$name = $this->settings['name'];
		if(self::$lowercaseTables) $name = strtolower($name); 
		if(!strlen($name)) throw new WireException("Field 'name' is required"); 
		return "field_" . $name;
	}

	/**
	 * The string value of a Field is always it's name
	 *
	 */
	public function __toString() {
		return $this->settings['name']; 
	}

	public function __isset($key) {
		if(parent::__isset($key)) return true; 
		return isset($this->settings[$key]); 
	}
	
	/**
	 * Return field label, description or notes for current language
	 *
	 * @param string $property Specify either label, description or notes
	 * @param Page|Language $language Optionally specify a language. If not specified user's current language is used.
	 * @return string
	 *
	 */
	protected function getText($property, $language = null) {
		if(is_null($language)) $language = $this->wire('languages') ? $this->wire('user')->language : null;
		if($language) {
			$value = $this->get("$property$language");
			if(!strlen($value)) $value = $this->$property;
		} else {
			$value = $this->$property;
		}
		if($property == 'label' && !strlen($value)) $value = $this->name;
		return $value;
	}

	/**
	 * Return field label for current language
	 *
	 * This is different from $this->label in that it knows about languages (when installed).
	 *
	 * @param Page|Language $language Optionally specify a language. If not specified user's current language is used.
	 * @return string
	 *
	 */
	public function getLabel($language = null) {
		return $this->getText('label', $language);
	}

	/**
	 * Return field description for current language
	 *
	 * This is different from $this->description in that it knows about languages (when installed).
	 *
	 * @param Page|Language $language Optionally specify a language. If not specified user's current language is used.
	 * @return string
	 *
	 */
	public function getDescription($language = null) {
		return $this->getText('description', $language);
	}

	/**
	 * Return field notes for current language
	 *
	 * This is different from $this->notes in that it knows about languages (when installed).
	 *
	 * @param Page|Language $language Optionally specify a language. If not specified user's current language is used.
	 * @return string
	 *
	 */
	public function getNotes($language = null) {
		return $this->getText('notes', $language);
	}

	/**
	 * Return the icon used by this field, or blank if none
	 * 
	 * @param bool $prefix Whether or not you want the fa- prefix included
	 * @return mixed|string
	 * 
	 */
	public function getIcon($prefix = false) {
		$icon = parent::get('icon'); 
		if(empty($icon)) return '';
		if(strpos($icon, 'fa-') === 0) $icon = str_replace('fa-', '', $icon);
		if(strpos($icon, 'icon-') === 0) $icon = str_replace('icon-', '', $icon); 
		return $prefix ? "fa-$icon" : $icon;
	}

	/**
	 * Set the icon for this field
	 * 
	 * @param string $icon Icon name
	 * @return $this
	 * 
	 */
	public function setIcon($icon) {
		// store the non-prefixed version
		if(strpos($icon, 'icon-') === 0) $icon = str_replace('icon-', '', $icon);
		if(strpos($icon, 'fa-') === 0) $icon = str_replace('fa-', '', $icon); 
		$icon = $this->wire('sanitizer')->pageName($icon); 
		parent::set('icon', $icon); 
		return $this; 
	}

	/**
	 * debugInfo PHP 5.6+ magic method
	 *
	 * This is used when you print_r() an object instance.
	 *
	 * @return array
	 *
	 */
	public function __debugInfo() {
		$info = parent::__debugInfo();
		$info['settings'] = $this->settings; 
		if($this->prevTable) $info['prevTable'] = $this->prevTable;
		if($this->prevFieldtype) $info['prevFieldtype'] = (string) $this->prevFieldtype;
		if(!empty($this->trackGets)) $info['trackGets'] = $this->trackGets;
		if($this->useRoles) {
			$info['viewRoles'] = $this->viewRoles;
			$info['editRoles'] = $this->editRoles; 
		}
		return $info; 
	}
	
}

