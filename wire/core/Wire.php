<?php namespace ProcessWire;

/**
 * ProcessWire Base Class "Wire"
 *
 * Classes that descend from this have access to a wire() method containing all of ProcessWire's API variables. 
 * Descending classes can specify which methods should be "hookable" by precending the method name with 3 underscores: "___". 
 * This class provides the interface for tracking changes to object properties. 
 * 
 * This file is licensed under the MIT license
 * https://processwire.com/about/license/mit/
 *
 * ProcessWire 3.x (development), Copyright 2015 by Ryan Cramer
 * https://processwire.com
 * 
 * @property string $className
 * @property ProcessWire $wire
 * @property Database $db
 * @property WireDatabasePDO $database
 * @property Session $session 
 * @property Notices $notices
 * @property Sanitizer $sanitizer
 * @property Fields $fields
 * @property Fieldtypes $fieldtypes
 * @property Fieldgroups $fieldgroups
 * @property Templates $templates
 * @property Pages $pages
 * @property Page $page
 * @property Process $process
 * @property Modules $modules
 * @property Permissions $permissions
 * @property Roles $roles
 * @property Users $users
 * @property User $user
 * @property WireCache $cache
 * @property WireInput $input
 * @property Languages $languages If LanguageSupport installed
 * @property Config $config
 * @property Fuel $fuel
 * @property WireHooks $hooks
 * @property WireDateTime $datetime
 * @property WireMailTools $mail
 * @property WireFileTools $files
 * 
 * @method changed(string $what) See Wire::___changed()
 * @method log($str = '', array $options = array()) See Wire::___log()
 * @method callUnknown($method, $arguments) See Wire::___callUnknown()
 * @method Wire trackException(\Exception $e, $severe = true, $text = null)
 * 
 */

abstract class Wire implements WireTranslatable, WireFuelable, WireTrackable {

	/*******************************************************************************************************
	 * API VARIABLE/FUEL INJECTION AND ACCESS
	 * 
	 * PLEASE NOTE: All the following fuel related variables/methods will be going away in PW 3.0.
	 * You should use the $this->wire() method instead for compatibility with PW 3.0. The only methods
	 * and variables sticking around for PW 3.0 are:
	 * 
	 * $this->wire(...);
	 * $this->useFuel(bool);
	 * $this->useFuel
	 * 
	 */

	/**
	 * Whether this class may use fuel variables in local scope, like $this->item
	 * 
	 * @var bool
	 *
	 */
	protected $useFuel = true;
	
	/**
	 * Total number of Wire class instances
	 *
	 * @var int
	 *
	 */
	static private $_instanceTotal = 0;

	/**
	 * ID of this Wire class instance
	 *
	 * @var int
	 *
	 */
	private $_instanceNum = 0;

	public function __construct() {}
	
	public function __clone() {
		$this->_instanceNum = 0;
		$this->getInstanceNum();
	}
	
	/**
	 * Get this Wire object's instance number
	 * 
	 * If this instance ID has not yet been set, this will set it. 
	 * Note: this is different from the ProcessWire instance ID. 
	 *
	 * @param bool $getTotal Specify true to get the total quantity of Wire instances rather than this instance num
	 * @return int
	 *
	 */
	public function getInstanceNum($getTotal = false) {
		if(!$this->_instanceNum) {
			self::$_instanceTotal++;
			$this->_instanceNum = self::$_instanceTotal;
		}
		if($getTotal) return self::$_instanceTotal;
		return $this->_instanceNum;
	}

	/**
	 * Add fuel to all classes descending from Wire
	 *
	 * @param string $name 
	 * @param mixed $value 
	 * @param bool $lock Whether the API value should be locked (non-overwritable)
	 * @internal Fuel is an internal-only keyword.
	 * 	Unless static needed, use $this->wire($name, $value) instead.
	 * @deprecated Use $this->wire($name, $value, $lock) instead.
	 *
	 */
	public static function setFuel($name, $value, $lock = false) {
		$wire = ProcessWire::getCurrentInstance();
		if($wire->wire('log')) $wire->wire('log')->deprecatedCall();
		$wire->fuel()->set($name, $value, $lock);
	}

	/**
	 * Get the Fuel specified by $name or NULL if it doesn't exist
	 *
	 * @param string $name
	 * @return mixed|null
	 * @internal Fuel is an internal-only keyword.  
	 * 	Use $this->wire(name) or $this->wire()->name instead, unless static is required.
	 * @deprecated
	 *
	 */
	public static function getFuel($name = '') {
		$wire = ProcessWire::getCurrentInstance();
		if($wire->wire('log')) $wire->wire('log')->deprecatedCall();
		if(empty($name)) return $wire->fuel();	
		return $wire->fuel()->$name;
	}

	/**
	 * Returns an iterable Fuel object of all Fuel currently loaded
	 *
	 * @return Fuel
	 * @deprecated This method will be going away. 
	 * 	Use $this->wire() instead, or if static required use: Wire::getFuel() with no arguments
	 *
	 */
	public static function getAllFuel() {
		$wire = ProcessWire::getCurrentInstance();
		if($wire->wire('log')) $wire->wire('log')->deprecatedCall();
		return $wire->fuel();	
	}

	/**
	 * Get the Fuel specified by $name or NULL if it doesn't exist (DEPRECATED)
	 * 
	 * DO NOT USE THIS METHOD: It is deprecated and only used by the ProcessWire class. 
	 * It is here in the Wire class for legacy support only. Use the wire() method instead.
	 *
	 * @param string $name
	 * @return mixed|null
	 *
	 */
	public function fuel($name = '') {
		$wire = $this->wire();
		if($wire->wire('log')) $wire->wire('log')->deprecatedCall();
		return $wire->fuel($name);
	}
	
	/**
	 * Should fuel vars be scoped locally to this class instance? (internal use only)
	 *
	 * If so, you can do things like $this->apivar.
	 * If not, then you'd have to do $this->wire('apivar').
	 *
	 * If you specify a value, it will set the value of useFuel to true or false.
	 * If you don't specify a value, the current value will be returned.
	 *
	 * Local fuel scope should be disabled in classes where it might cause any conflict with class vars.
	 *
	 * @param bool $useFuel Optional boolean to turn it on or off.
	 * @return bool Current value of $useFuel
	 *
	 */
	public function useFuel($useFuel = null) {
		if(!is_null($useFuel)) $this->useFuel = $useFuel ? true : false;
		return $this->useFuel;
	}


	/*******************************************************************************************************
	 * IDENTIFICATION
	 *
	 */
	
	/**
	 * Return this object's class name
	 *
	 * @param array|bool|null $options Optionally an option or boolean for 'namespace' option: 
	 * 	- lowercase (bool): Specify true to make it return hyphenated lowercase version of class name
	 * 	- namespace (bool): Specify false to omit namespace from returned class name. Default=true. 
	 * 	Note: when lowercase=true option is specified, the namespace=false option is required.
	 * @return string
	 *
	 */
	public function className($options = null) {
		
		if(is_bool($options)) {
			$options = array('namespace' => $options);
		} else if(is_array($options)) {
			if(!empty($options['lowercase'])) $options['namespace'] = false;
		} else {
			$options = array();
		}

		if(isset($options['namespace']) && $options['namespace'] === true) {
			$className = get_class($this);
		} else {
			$className = wireClassName($this, false);
		}

		if(!empty($options['lowercase'])) {
			static $cache = array();
			if(isset($cache[$className])) {
				$className = $cache[$className];
			} else {
				$_className = $className;
				$part = substr($className, 1);
				if(strtolower($part) != $part) {
					// contains more than 1 uppercase character, convert to hyphenated lowercase
					$className = substr($className, 0, 1) . preg_replace('/([A-Z])/', '-$1', $part);
				}
				$className = strtolower($className);
				$cache[$_className] = $className;
			}
		}
		
		return $className;
	}


	/**
	 * Unless overridden, classes descending from Wire return their class name when typecast as a string
	 *
	 */
	public function __toString() {
		return $this->className();
	}


	/*******************************************************************************************************
	 * HOOKS
	 *
	 */
	
	/**
	 * Hooks that are local to this instance of the class only.
	 *
	 */
	protected $localHooks = array();

	/**
	 * Return all local hooks for this instance
	 * 
	 * @return array
	 * 
	 */
	public function getLocalHooks() {
		return $this->localHooks;
	}

	/**
	 * Set local hooks for this instance
	 * 
	 * @param array $hooks
	 * 
	 */
	public function setLocalHooks(array $hooks) {
		$this->localHooks = $hooks;
	}

	/**
	 * Call a method in this object, for use by WireHooks
	 * 
	 * @param $method
	 * @param $arguments
	 * @return mixed
	 * @internal
	 * 
	 */
	public function _callMethod($method, $arguments) {
		return call_user_func_array(array($this, $method), $arguments);
	}

	/**
	 * Provides the gateway for calling hooks in ProcessWire
	 * 
	 * When a non-existant method is called, this checks to see if any hooks have been defined and sends the call to them. 
	 * 
	 * Hooks are defined by preceding the "hookable" method in a descending class with 3 underscores, like __myMethod().
	 * When the API calls $myObject->myMethod(), it gets sent to $myObject->___myMethod() after any 'before' hooks have been called. 
	 * Then after the ___myMethod() call, any "after" hooks are then called. "after" hooks have the opportunity to change the return value.
	 *
	 * Hooks can also be added for methods that don't actually exist in the class, allowing another class to add methods to this class. 
	 *
	 * See the Wire::runHooks() method for the full implementation of hook calls.
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 * @throws WireException
	 *
	 */ 
	public function __call($method, $arguments) {
		if(!$this->wire('hooks')) throw new WireException('gotcha');
		$result = $this->wire('hooks')->runHooks($this, $method, $arguments); 
		if(!$result['methodExists'] && !$result['numHooksRun']) return $this->callUnknown($method, $arguments);
		return $result['return'];
	}

	/**
	 * If the above __call() resulted in no handler, this method is called. 
	 * 
	 * This standard implementation just throws an exception. This is a template method, so the reason it
	 * exists is so that other classes can override and provide their own handler. Classes that provide
	 * their own handler should not do a parent::__callUnknown() unless they also fail.
	 * 
	 * @param string $method Requested method name
	 * @param array $arguments Arguments provided
	 * @return null|mixed
	 * @throws WireException
	 * 
	 */
	protected function ___callUnknown($method, $arguments) {
		if($this->wire('config')->disableUnknownMethodException) return null;
		throw new WireException("Method " . $this->className() . "::$method does not exist or is not callable in this context"); 
	}

	/**
	 * Provides the implementation for calling hooks in ProcessWire
	 *
	 * Unlike __call, this method won't trigger an Exception if the hook and method don't exist. 
	 * Instead it returns a result array containing information about the call. 
	 *
	 * @param string $method Method or property to run hooks for.
	 * @param array $arguments Arguments passed to the method and hook. 
	 * @param string $type May be either 'method' or 'property', depending on the type of call. Default is 'method'.
	 * @return array Returns an array with the following information: 
	 * 	[return] => The value returned from the hook or NULL if no value returned or hook didn't exist. 
	 *	[numHooksRun] => The number of hooks that were actually run. 
	 *	[methodExists] => Did the hook method exist as a real method in the class? (i.e. with 3 underscores ___method).
	 *	[replace] => Set by the hook at runtime if it wants to prevent execution of the original hooked method.
	 *
	 */
	public function runHooks($method, $arguments, $type = 'method') {
		return $this->wire('hooks')->runHooks($this, $method, $arguments, $type);
	}

	/**
	 * Return all hooks associated with this class instance or method (if specified)
	 *
	 * @param string $method Optional method that hooks will be limited to. Or specify '*' to return all hooks everywhere.
	 * @param int $type Type of hooks to return, specify one of the following constants: 
	 * 	- WireHooks::getHooksAll returns all hooks (default)
	 * 	- WireHooks::getHooksLocal returns local hooks only 
	 * 	- WireHooks::getHooksStatic returns static hooks only
	 * @return array
	 *
	 */
	public function getHooks($method = '', $type = 0) {
		return $this->wire('hooks')->getHooks($this, $method, $type); 
	}
	
	/**
	 * Returns true if the method/property hooked, false if it isn't.
	 *
	 * This is for optimization use. It does not distinguish about class instance. 
	 * It only distinguishes about class if you provide a class with the $method argument (i.e. Class::).
	 * As a result, a true return value indicates something "might" be hooked, as opposed to be 
	 * being definitely hooked. 
	 *
	 * If checking for a hooked method, it should be in the form "Class::method()" or "method()". 
	 * If checking for a hooked property, it should be in the form "Class::property" or "property". 
	 * 
	 * @param string $method Method or property name in one of the following formats:
	 * 	Class::method()
	 * 	Class::property
	 * 	method()
	 * 	property
	 * @param Wire|null $instance Optional instance to check against (see hasHook method for details)
	 * 	Note that if specifying an $instance, you may not use the Class::method() or Class::property options for $method argument.
	 * @return bool
	 * @deprecated 
	 *
	 */
	static public function isHooked($method, Wire $instance = null) {
		$wire = $instance ? $instance->wire() : ProcessWire::getCurrentInstance();
		if($instance) return $instance->wire('hooks')->hasHook($instance, $method);
		return $wire->hooks->isHooked($method);
	}

	/**
	 * Similar to isHooked(), returns true if the method or property hooked, false if it isn't.
	 *
	 * Accomplishes the same thing as the static isHooked() method, but this is non-static, more accruate, 
	 * and potentially slower than isHooked(). Less for optimization use, more for accuracy use. 
	 * 
	 * It checks for both static hooks and local hooks, but only accepts a method() or property
	 * name as an argument (i.e. no Class::something) since the class context is assumed from the current 
	 * instance. Unlike isHooked() it also analyzes the instance's class parents for hooks, making it 
	 * more accurate. As a result, this method works well for more than just optimization use. 
	 *
	 * If checking for a hooked method, it should be in the form "method()".
	 * If checking for a hooked property, it should be in the form "property".
	 *
	 * @param string $method Method() or property name
	 * @return bool
	 * @throws WireException whe you try to call it with a Class::something() type method. 
	 *
	 */
	public function hasHook($method) {
		return $this->wire('hooks')->hasHook($this, $method);
	}

	/**
	 * Hook a function/method to a hookable method call in this object
	 *
	 * Hookable method calls are methods preceded by three underscores. 
	 * You may also specify a method that doesn't exist already in the class
	 * The hook method that you define may be part of a class or a globally scoped function. 
	 * 
	 * If you are hooking a procedural function, you may omit the $toObject and instead just call via:
	 * $this->addHook($method, 'function_name'); or $this->addHook($method, 'function_name', $options); 
	 *
	 * @param string $method Method name to hook into, NOT including the three preceding underscores. 
	 * 	May also be Class::Method for same result as using the fromClass option.
	 * @param object|null|callable $toObject Object to call $toMethod from,
	 * 	Or null if $toMethod is a function outside of an object,
	 * 	Or function|callable if $toObject is not applicable or function is provided as a closure.
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event. Optional.
	 * @param array $options See self::$defaultHookOptions at the beginning of this class. Optional.
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 * @throws WireException
	 *
	 */
	public function addHook($method, $toObject, $toMethod = null, $options = array()) {
		return $this->wire('hooks')->addHook($this, $method, $toObject, $toMethod, $options);
	}

	/**
	 * Shortcut to the addHook() method which adds a hook to be executed before the hooked method. 
	 *
	 * This is the same as calling addHook with the 'before' option set the $options array.
	 * 
	 * If you are hooking a procedural function, you may omit the $toObject and instead just call via:
	 * $this->addHookBefore($method, 'function_name'); 
	 *
	 * @param string $method Method name to hook into, NOT including the three preceding underscores
	 * 	May also be Class::Method for same result as using the fromClass option.
	 * @param object|null|callable $toObject Object to call $toMethod from,
	 * 	Or null if $toMethod is a function outside of an object,
	 * 	Or function|callable if $toObject is not applicable or function is provided as a closure. 
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event. Optional.
	 * @param array $options See self::$defaultHookOptions at the beginning of this class. Optional.
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 *
	 */
	public function addHookBefore($method, $toObject, $toMethod = null, $options = array()) {
		$options['before'] = true; 
		if(!isset($options['after'])) $options['after'] = false; 
		return $this->wire('hooks')->addHook($this, $method, $toObject, $toMethod, $options); 
	}

	/**
	 * Shortcut to the addHook() method which adds a hook to be executed after the hooked method. 
	 *
	 * This is the same as calling addHook with the 'after' option set the $options array.
	 * 
	 * If you are hooking a procedural function, you may omit the $toObject and instead just call via:
	 * $this->addHookAfter($method, 'function_name'); 
	 *
	 * @param string $method Method name to hook into, NOT including the three preceding underscores
	 * 	May also be Class::Method for same result as using the fromClass option.
	 * @param object|null|callable $toObject Object to call $toMethod from,
	 * 	Or null if $toMethod is a function outside of an object,
	 * 	Or function|callable if $toObject is not applicable or function is provided as a closure.
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event. Optional.
	 * @param array $options See self::$defaultHookOptions at the beginning of this class. Optional.
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 *
	 */
	public function addHookAfter($method, $toObject, $toMethod = null, $options = array()) {
		$options['after'] = true; 
		if(!isset($options['before'])) $options['before'] = false; 
		return $this->wire('hooks')->addHook($this, $method, $toObject, $toMethod, $options); 
	}

	/**
	 * Shortcut to the addHook() method which adds a hook to be executed as an object property. 
	 *
	 * i.e. $obj->property; in addition to $obj->property(); 
	 *
	 * This is the same as calling addHook with the 'type' option set to 'property' in the $options array. 
	 * Note that descending classes that override __get must call getHook($property) and/or runHook($property).
	 * 
	 * If you are hooking a procedural function, you may omit the $toObject and instead just call via:
	 * $this->addHookProperty($method, 'function_name'); 
	 *
	 * @param string $property Method name to hook into, NOT including the three preceding underscores
	 * 	May also be Class::Method for same result as using the fromClass option.
	 * @param object|null|callable $toObject Object to call $toMethod from,
	 * 	Or null if $toMethod is a function outside of an object,
	 * 	Or function|callable if $toObject is not applicable or function is provided as a closure.
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event. Optional.
	 * @param array $options See self::$defaultHookOptions at the beginning of this class. Optional.
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 *
	 */
	public function addHookProperty($property, $toObject, $toMethod = null, $options = array()) {
		$options['type'] = 'property'; 
		return $this->wire('hooks')->addHook($this, $property, $toObject, $toMethod, $options); 
	}

	/**
	 * Given a Hook ID provided by addHook() this removes the hook
	 * 
	 * To have a hook function remove itself within the hook function, say this is your hook function: 
	 * function(HookEvent $event) {
	 *   $event->removeHook(null); // remove self
	 * }
	 *
	 * @param string|null $hookId
	 * @return $this
	 *
	 */
	public function removeHook($hookId) {
		return $this->wire('hooks')->removeHook($this, $hookId);
	}

	
	/*******************************************************************************************************
	 * CHANGE TRACKING
	 *
	 */

	/**
	 * Predefined track change mode: track names only (default)
	 *
	 */
	const trackChangesOn = 2;
	const trackChangesValues = 4;
	
	/**
	 * Track changes mode
	 * 
	 * @var int Bitmask
	 *
	 */
	private $trackChanges = 0;

	/**
	 * Array containing the names of properties (as array keys) that were changed while change tracking was ON.
	 * 
	 * Array values are insignificant unless trackChangeMode is trackChangesValues (1), in which case the values are the previous values.
	 * 
	 * @var array
	 *
	 */
	private $changes = array();
	
	/**
	 * Has the given property changed? 
	 *
	 * Applicable only for properties you are tracking while $trackChanges is true. 
	 *
	 * @param string $what Name of property, or if left blank, check if any properties have changed. 
	 * @return bool
	 *
	 */
	public function isChanged($what = '') {
		if(!$what) return count($this->changes) > 0; 
		return array_key_exists($what, $this->changes); 
	}

	/**
	 * Hookable method that is called whenever a property has changed while self::$trackChanges is true
	 *
	 * Enables hooks to monitor changes to the object. 
	 * 
	 * Descending objects should trigger this via $this->changed('name') when a property they are tracking has changed.
	 * 
	 * @param string $what Name of property that changed
	 * @param mixed $old Previous value before change 
	 * @param mixed $new New value
	 *
	 */
	public function ___changed($what, $old = null, $new = null) {
		// for hooks to listen to 
	}

	/**
	 * Track a change to a property in this object
	 *
	 * The change will only be recorded if $this->trackChanges is a positive value. 
	 *
	 * @param string $what Name of property that changed
	 * @param mixed $old Previous value before change
	 * @param mixed $new New value
	 * @return $this
	 * 
	 */
	public function trackChange($what, $old = null, $new = null) {
		
		if($this->trackChanges & self::trackChangesOn) {
			
			// establish it as changed
			if(array_key_exists($what, $this->changes)) {
				// remember last value so we can avoid duplication in hooks or storage
				$lastValue = end($this->changes[$what]); 
			} else {
				$lastValue = null;
				$this->changes[$what] = array();
			}
		
			if(is_null($old) || is_null($new) || $lastValue !== $new) {
				$this->changed($what, $old, $new); // triggers ___changed hook
			}
			
			if($this->trackChanges & self::trackChangesValues) {
				// track changed values, but avoid successive duplication of same value
				if(is_object($old) && $old === $new) $old = clone $old; // keep separate copy of objects for old value
				if($lastValue !== $old || !count($this->changes[$what])) $this->changes[$what][] = $old; 
				
			} else {
				// don't track changed values, just names of fields
				$this->changes[$what][] = null;
			}
			
		}
		
		return $this; 
	}

	/**
	 * Untrack a change to a property in this object
	 *
	 * @param string $what Name of property that you want to remove it's change being tracked
	 * @return $this
	 * 
	 */
	public function untrackChange($what) {
		unset($this->changes[$what]); 
		return $this; 
	}

	/**
	 * Turn change tracking ON or OFF
	 *
	 * @param bool|int $trackChanges True to turn on, false to turn off. Integer to specify bitmask. 
	 * @return $this
	 *
	 */
	public function setTrackChanges($trackChanges = true) {
		if(is_bool($trackChanges) || !$trackChanges) {
			// turn change track on or off
			if($trackChanges) $this->trackChanges = $this->trackChanges | self::trackChangesOn; // add bit
				else $this->trackChanges = $this->trackChanges & ~self::trackChangesOn; // remove bit
		} else if(is_int($trackChanges)) {
			// set bitmask
			$allowed = array(self::trackChangesOn, self::trackChangesValues, self::trackChangesOn | self::trackChangesValues); 
			if(in_array($trackChanges, $allowed)) $this->trackChanges = $trackChanges; 
		}
		return $this; 
	}

	/**
	 * Returns true if change tracking is on, or false if it's not. 
	 *
	 * @param bool $getMode When true, the track changes mode bitmask will be returned rather than a boolean
	 * @return bool|int 
	 * 
	 */
	public function trackChanges($getMode = false) {
		if($getMode) return $this->trackChanges; 
		return $this->trackChanges & self::trackChangesOn;	
	}

	/**
	 * Clears out any tracked changes and turns change tracking ON or OFF
	 *
	 * @param bool $trackChanges True to turn change tracking ON, or false to turn OFF. Default of true is assumed.
	 * @return $this
	 *
	 */
	public function resetTrackChanges($trackChanges = true) {
		$this->changes = array();
		return $this->setTrackChanges($trackChanges); 
	}

	/**
	 * Return an array of properties that have changed while change tracking was on. 
	 *
	 * @param bool $getValues If true, then an associative array will be returned containing an array of previous values, oldest to newest. 
	 * @return array
	 *
	 */
	public function getChanges($getValues = false) {
		if($getValues) return $this->changes; 
		return array_keys($this->changes); 
	}

	
	/*******************************************************************************************************
	 * NOTICES AND LOGS
	 *
	 */
	
	protected $_notices = array(
		'errors' => null, 
		'warnings' => null, 
		'messages' => null
	);

	/**
	 * Record a Notice, internal use (contains the code for message, warning and error methods)
	 * 
	 * @param string $text|array|Wire Title of notice
	 * @param int $flags Flags bitmask
	 * @param string $name Name of container
	 * @param string $class Name of Notice class
	 * @return $this
	 * 
	 */
	protected function _notice($text, $flags, $name, $class) {
		if($flags === true) $flags = Notice::log;
		$class = wireClassName($class, true);
		$notice = $this->wire(new $class($text, $flags));
		$notice->class = $this->className();
		if(is_null($this->_notices[$name])) $this->_notices[$name] = $this->wire(new Notices());
		$this->wire('notices')->add($notice);
		if(!($notice->flags & Notice::logOnly)) $this->_notices[$name]->add($notice);
		return $this; 
	}

	/**
	 * Record an informational or 'success' message in the system-wide notices. 
	 *
	 * This method automatically identifies the message as coming from this class. 
	 *
	 * @param string|array|Wire $text
	 * @param int|bool $flags See Notices::flags or specify TRUE to have the message also logged to messages.txt
	 * @return $this
	 *
	 */
	public function message($text, $flags = 0) {
		return $this->_notice($text, $flags, 'messages', 'NoticeMessage'); 
	}
	
	/**
	 * Record a warning error message in the system-wide notices.
	 *
	 * This method automatically identifies the warning as coming from this class.
	 *
	 * @param string|array|Wire $text
	 * @param int|bool $flags See Notices::flags or specify TRUE to have the error also logged to errors.txt
	 * @return $this
	 *
	 */
	public function warning($text, $flags = 0) {
		return $this->_notice($text, $flags, 'warnings', 'NoticeWarning'); 
	}

	/**
	 * Record an non-fatal error message in the system-wide notices. 
	 *
	 * This method automatically identifies the error as coming from this class. 
	 * Fatal errors should still throw a WireException (or class derived from it)
	 *
	 * @param string|array|Wire $text
	 * @param int|bool $flags See Notices::flags or specify TRUE to have the error also logged to errors.txt
	 * @return $this
	 *
	 */
	public function error($text, $flags = 0) {
		return $this->_notice($text, $flags, 'errors', 'NoticeError'); 
	}

	/**
	 * Hookable method called when an Exception occurs
	 * 
	 * It will log Exception to exceptions.txt log if 'exceptions' is in $config->logs. 
	 * It will re-throw Exception if $config->allowExceptions == true. 
	 * If additioanl $text is provided, it will be sent to $this->error when $fatal or $this->warning otherwise. 
	 * 
	 * @param \Exception $e Exception object that was thrown
	 * @param bool|int $severe Whether or not it should be considered severe (default=true)
	 * @param string|array|object|true $text Additional details (optional). 
	 * 	When provided, it will be sent to $this->error($text) if $severe==true, or $this->warning($text) if $severe==false.
	 * 	Specify boolean true to just sent the $e->getMessage() to $this->error() or $this->warning(). 
	 * @return Wire (this)
	 * @throws \Exception If $severe==true and $config->allowExceptions==true
	 * 
	 */
	public function ___trackException(\Exception $e, $severe = true, $text = null) {
		$config = $this->wire('config');
		$log = $this->wire('log');
		$msg = $e->getMessage();
		if($text !== null) {
			if($text === true) $text = $msg;
			$severe ? $this->error($text) : $this->warning($text);
			if(strpos($text, $msg) === false) $msg = "$text - $msg";
		}
		if(in_array('exceptions', $config->logs) && $log) {
			$msg .= " (in " . str_replace($config->paths->root, '/', $e->getFile()) . " line " . $e->getLine() . ")";
			$log->save('exceptions', $msg);
		}
		if($severe && $this->wire('config')->allowExceptions) {
			throw $e; // re-throw, if requested
		}
		return $this;
	}

	/**
	 * Return errors recorded by this object
	 * 
	 * @param string|array $options One or more of array elements or space separated string of:
	 * 	first: only first item will be returned (string)
	 * 	last: only last item will be returned (string)
	 * 	all: include all errors, including those beyond the scope of this object
	 * 	clear: clear out all items that are returned from this method
	 * 	array: return an array of strings rather than series of Notice objects.
	 * 	string: return a newline separated string rather than array/Notice objects. 
	 * @return Notices|string Array of NoticeError error messages or string if last, first or str option was specified.
	 * 
	 */
	public function errors($options = array()) {
		if(!is_array($options)) $options = explode(' ', strtolower($options)); 
		$options[] = 'errors';
		return $this->messages($options); 
	}

	/**
	 * Return warnings recorded by this object
	 *
	 * @param string|array $options One or more of array elements or space separated string of:
	 * 	first: only first item will be returned (string)
	 * 	last: only last item will be returned (string)
	 * 	all: include all warnings, including those beyond the scope of this object
	 * 	clear: clear out all items that are returned from this method
	 * 	array: return an array of strings rather than series of Notice objects.
	 * 	string: return a newline separated string rather than array/Notice objects. 
	 * @return Notices|string Array of NoticeError error messages or string if last, first or str option was specified.
	 *
	 */
	public function warnings($options = array()) {
		if(!is_array($options)) $options = explode(' ', strtolower($options));
		$options[] = 'warnings';
		return $this->messages($options); 
	}

	/**
	 * Return messages recorded by this object
	 *
	 * @param string|array $options One or more of array elements or space separated string of:
	 * 	first: only first item will be returned (string)
	 * 	last: only last item will be returned (string)
	 * 	all: include all items of type (messages or errors) beyond the scope of this object
	 * 	clear: clear out all items that are returned from this method
	 * 	errors: returns errors rather than messages.
	 * 	warnings: returns warnings rather than messages. 
	 * 	array: return an array of strings rather than series of Notice objects. 
	 * 	string: return a newline separated string rather than array/Notice objects. 
	 * @return Notices|string Array of NoticeError error messages or string if last, first or str option was specified.
	 *
	 */
	public function messages($options = array()) {
		if(!is_array($options)) $options = explode(' ', strtolower($options)); 
		if(in_array('errors', $options)) $type = 'errors'; 
			else if(in_array('warnings', $options)) $type = 'warnings';
			else $type = 'messages';
		$clear = in_array('clear', $options); 
		if(in_array('all', $options)) {
			// get all of either messages, warnings or errors (either in or out of this object instance)
			$value = $this->wire(new Notices());
			foreach($this->wire('notices') as $notice) {
				if($notice->getName() != $type) continue;
				$value->add($notice);
				if($clear) $this->wire('notices')->remove($notice); // clear global
			}
			if($clear) $this->_notices[$type] = null; // clear local
		} else {
			// get messages, warnings or errors specific to this object instance
			$value = is_null($this->_notices[$type]) ? $this->wire(new Notices()) : $this->_notices[$type];
			if(in_array('first', $options)) $value = $clear ? $value->shift() : $value->first();
				else if(in_array('last', $options)) $value = $clear ? $value->pop() : $value->last(); 
				else if($clear) $this->_notices[$type] = null;
			if($clear && $value) $this->wire('notices')->removeItems($value); // clear from global notices
		}
		if(in_array('array', $options) || in_array('string', $options)) {
			if($value instanceof Notice) {
				$value = array($value->text);
			} else {
				$_value = array();
				foreach($value as $notice) $_value[] = $notice->text; 
				$value = $_value; 
			}
			if(in_array('string', $options)) {
				$value = implode("\n", $value); 
			}
		}
		return $value; 
	}

	/**
	 * Log a message for this class
	 * 
	 * Message is saved to a log file in ProcessWire's logs path to a file with 
	 * the same name as the class, converted to hyphenated lowercase.
	 * 
	 * @param string $str Text to log, or omit to just return the name of the log
	 * @param array $options Optional extras to include: 
	 * 	- url (string): URL to record the with the log entry (default=auto-detect)
	 * 	- name (string): Name of log to use (default=auto-detect)
	 * @return WireLog|null
	 *
	 */
	public function ___log($str = '', array $options = array()) {
		$log = $this->wire('log');
		if($log && strlen($str)) {
			if(isset($options['name'])) {
				$name = $options['name'];
				unset($options['name']);
			} else {
				$name = $this->className(array('lowercase' => true));
			}
			$log->save($name, $str, $options);
		}
		return $log; 
	}
	
	/*******************************************************************************************************
	 * TRANSLATION 
	 * 
	 */

	/**
	 * Translate the given text string into the current language if available. 
	 *
	 * If not available, or if the current language is the native language, then it returns the text as is. 
	 *
	 * @param string $text Text string to translate
	 * @return string
	 *
	 */
	public function _($text) {
		return __($text, $this); 
	}

	/**
	 * Perform a language translation in a specific context
	 * 
	 * Used when to text strings might be the same in English, but different in other languages. 
	 * 
	 * @param string $text Text for translation. 
	 * @param string $context Name of context
	 * @return string Translated text or original text if translation not available.
	 *
	 */
	public function _x($text, $context) {
		return _x($text, $context, $this); 
	}

	/**
	 * Perform a language translation with singular and plural versions
	 * 
	 * @param string $textSingular Singular version of text (when there is 1 item)
	 * @param string $textPlural Plural version of text (when there are multiple items or 0 items)
	 * @param int $count Quantity used to determine whether singular or plural.
	 * @return string Translated text or original text if translation not available.
	 *
	 */
	public function _n($textSingular, $textPlural, $count) {
		return _n($textSingular, $textPlural, $count, $this); 
	}
	
	/*******************************************************************************************************
	 * API VARIABLE MANAGEMENT
	 * 
	 * To replace fuel in PW 3.0
	 *
	 */

	/**
	 * ProcessWire instance
	 *
	 * This will replace static fuel in PW 3.0
	 *
	 * @var ProcessWire|null
	 *
	 */
	protected $_wire = null;

	/**
	 * Set the current ProcessWire instance for this object (PW 3.0)
	 *
	 * Specify no arguments to get, or specify a ProcessWire instance to set.
	 *
	 * @param ProcessWire $wire
	 * @return this
	 *
	 */
	public function setWire(ProcessWire $wire) {
		$this->_wire = $wire;
		$this->getInstanceNum();
	}

	/**
	 * Get the current ProcessWire instance (PW 3.0)
	 * 
	 * You can also use the wire() method with no arguments. 
	 *
	 * @return null|ProcessWire
	 *
	 */
	public function getWire() {
		return $this->_wire;
	}

	/**
	 * Is this object wired to a ProcessWire instance?
	 * 
	 * @return bool
	 * 
	 */
	public function isWired() {
		return $this->_wire ? true : false;
	}
	
	/**
	 * Get or inject a ProcessWire API variable
	 *
	 * 1. As a getter (option 1):
	 * ==========================
	 * Usage: $this->wire('name'); // name is an API variable name
	 * If 'name' does not exist, a WireException will be thrown.
	 * Specify '*' or 'all' for name to retrieve all API vars (as a Fuel object)
	 *
	 * 2. As a getter (option 2):
	 * ==========================
	 * Usage: $this->wire()->name; // name is an API variable name
	 * Null will be returned if API var does not exist (no Exception thrown).
	 *
	 * 3. As a setter:
	 * ===============
	 * $this->wire('name', $value);
	 * $this->wire('name', $value, true); // lock the API variable so nothing else can overwrite it
	 * $this->wire()->set('name', $value);
	 * $this->wire()->set('name', $value, true); // lock the API variable so nothing else can overwrite it
	 *
	 * 4. As a dependency injector (PW 3.0 only)
	 * =========================================
	 * $this->wire(new Page());
	 * When creating a new object, this makes it inject the current PW instance into that object.
	 *
	 * @param string|object $name Name of API variable to retrieve, set, or omit to retrieve the master ProcessWire object
	 * @param null|mixed $value Value to set if using this as a setter, otherwise omit.
	 * @param bool $lock When using as a setter, specify true if you want to lock the value from future changes (default=false)
	 * @return ProcessWire|Wire|Session|Page|Pages|Modules|User|Users|Roles|Permissions|Templates|Fields|Fieldtypes|Sanitizer|Config|Notices|WireDatabasePDO|WireHooks|WireDateTime|WireFileTools|WireMailTools|WireInput|string|mixed
	 * @throws WireException
	 *
	 *
	 */
	public function wire($name = '', $value = null, $lock = false) {

		if(is_null($this->_wire)) {
			// this object has not yet been wired! use last known current instance as fallback
			// note this condition is unsafe in multi-instance mode
			$wire = ProcessWire::getCurrentInstance();
			
			// For live hunting objects that are using the fallback, uncomment the following:
			// echo "<hr /><p>Non-wired object: '$name' in " . get_class($this) . ($value ? " (value=$value)" : "") . "</p>";
			// echo "<pre>" . print_r(debug_backtrace(), true) . "</pre>";
		} else {
			// this instance is wired
			$wire = $this->_wire;
		}

		if(is_object($name)) {
			// make an object wired (inject ProcessWire instance to object)
			if($name instanceof WireFuelable) {
				if($this->_wire) $name->setWire($wire); // inject fuel, PW 3.0 
				if(is_string($value) && $value) {
					// set as new API var if API var name specified in $value
					$wire->fuel()->set($value, $name, $lock);
				}
				$value = $name; // return the provided instance
			} else {
				throw new WireException("Wire::wire(\$o) expected WireFuelable for \$o and was given " . get_class($name));
			}

		} else if($value !== null) {
			// setting a API variable/fuel value, and make it wired
			if($value instanceof WireFuelable && $this->_wire) $value->setWire($wire);
			$wire->fuel()->set($name, $value, $lock);
			
		} else if(empty($name)) {
			// return ProcessWire instance
			$value = $wire;
			
		} else if($name === '*' || $name === 'all' || $name == 'fuel') {
			// return Fuel instance
			$value = $wire->fuel();
			
		} else {
			// get API variable
			$value = $wire->fuel()->$name;
		}
		
		return $value;
	}

	/**
	 * Get an object property by direct reference or NULL if it doesn't exist
	 *
	 * If not overridden, this is primarily used as a shortcut for the fuel() method.
	 *
	 * Descending classes may have their own __get() but must pass control to this one when they can't find something.
	 *
	 * @param string $name
	 * @return mixed|null
	 *
	 */
	public function __get($name) {

		if($name == 'wire') return $this->wire();
		if($name == 'fuel') return $this->wire('fuel');
		if($name == 'className') return $this->className();

		if($this->useFuel()) {
			$value = $this->wire($name);
			if($value !== null) return $value; 
		}

		$hooks = $this->wire('hooks');
		if($hooks && $hooks->isHooked($name)) { // potential property hook
			$result = $this->runHooks($name, array(), 'property');
			return $result['return'];
		}

		return null;
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
		static $debugInfo = null;
		if(is_null($debugInfo)) {
			$debugInfo = $this->wire(new WireDebugInfo());
		}
		return $debugInfo->getDebugInfo($this);
	}


}

