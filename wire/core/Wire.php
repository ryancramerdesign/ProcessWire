<?php

/**
 * ProcessWire Base Class "Wire"
 *
 * Classes that descend from this have access to a $fuel property and fuel() method containing all of ProcessWire's objects. 
 * Descending classes can specify which methods should be "hookable" by precending the method name with 3 underscores: "___". 
 * This class provides the interface for tracking changes to object properties. 
 * message() and error() methods are provided for this class to provide any text notices. 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 * 
 * @property string $className
 * @property ProcessWire $wire
 * @property Database $db
 * @property Session $session 
 * @property Notices $notices
 * @property Sanitizer $sanitizer
 * @property Fields $fields
 * @property Fieldtypes $fieldtypes
 * @property Fieldgroups $fieldgroups
 * @property Templates $templates
 * @property Pages $pages
 * @property Page $page
 * @property Modules $modules
 * @property Permissions $permissions
 * @property Roles $roles
 * @property Users $users
 * @property User $user
 * @property WireInput $input
 * @property Config $config
 * @property Fuel $fuel
 * 
 * @method changed(string $what) See Wire::___changed()
 *
 */

abstract class Wire implements WireTranslatable, WireHookable, WireFuelable, WireTrackable {

	/*******************************************************************************************************
	 * API VARIABLE/FUEL INJECTION AND ACCESS
	 * 
	 * Note that we store the API variables statically so that descending classes can share the same
	 * API variables without repetitive calls.
	 *
	 */
	
	/**
	 * Fuel holds references to other ProcessWire system objects. It is an instance of the Fuel class.
	 *
	 */
	protected static $fuel = null;
	
	/**
	 * Whether this class may use fuel variables in local scope, like $this->item
	 *
	 */
	protected $useFuel = true;

	/**
	 * Add fuel to all classes descending from Wire
	 *
	 * @param string $name 
	 * @param mixed $value 
	 * @param bool $lock Whether the API value should be locked (non-overwritable)
	 * @internal Fuel is an internal-only keyword.
	 * 	Unless static needed, use $this->wire($name, $value) instead.
	 *
	 */
	public static function setFuel($name, $value, $lock = false) {
		if(is_null(self::$fuel)) self::$fuel = new Fuel();
		self::$fuel->set($name, $value, $lock); 
	}

	/**
	 * Get the Fuel specified by $name or NULL if it doesn't exist
	 *
	 * @param string $name
	 * @return mixed|null
	 * @internal Fuel is an internal-only keyword.  
	 * 	Use $this->wire(name) or $this->wire()->name instead, unless static is required.
	 *
	 */
	public static function getFuel($name = '') {
		if(empty($name)) return self::$fuel;
		return self::$fuel->$name;
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
		return self::$fuel;
	}

	/**
	 * Get the Fuel specified by $name or NULL if it doesn't exist
	 *
	 * @deprecated Fuel is now an internal-only keyword and this method will be going away. 
	 * 	Use $this->wire(name) or $this->wire()->name instead
	 * @param string $name
	 * @return mixed|null
	 *
	 */
	public function fuel($name) {
		return self::$fuel->$name;
	}

	/**
	 * Get or inject a ProcessWire API variable
	 * 
	 * 1. As a getter (option 1): 
	 * ==========================
	 * Usage: $this->wire('name'); // name is an API variable name
	 * If 'name' does not exist, a WireException will be thrown.
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
	 * @param string $name Name of API variable to retrieve, set, or omit to retrieve the master ProcessWire object
	 * @param null|mixed $value Value to set if using this as a setter, otherwise omit.
	 * @param bool $lock When using as a setter, specify true if you want to lock the value from future changes (default=false)
	 * @return mixed|ProcessWire
	 * @throws WireException
	 * 
	 *
	 */
	public function wire($name = '', $value = null, $lock = false) {
		if(is_null(self::$fuel)) self::$fuel = new Fuel();
		if(!is_null($value)) return self::$fuel->set($name, $value, $lock);
		if(empty($name)) return self::$fuel->wire;
		$value = self::$fuel->$name;
		//if(is_null($value)) throw new WireException("Unknown API variable: $name"); 
		return $value;
	}

	/**
	 * Should fuel vars be scoped locally to this class instance?
	 *
	 * If so, you can do things like $this->fuelItem.
	 * If not, then you'd have to do $this->fuel('fuelItem').
	 *
	 * If you specify a value, it will set the value of useFuel to true or false. 
	 * If you don't specify a value, the current value will be returned. 
	 *
	 * Local fuel scope should be disabled in classes where it might cause any conflict with class vars. 
	 *
	 * @param bool $useFuel Optional boolean to turn it on or off. 
	 * @return bool Current value of $useFuel
	 * @internal
	 *
	 */
	public function useFuel($useFuel = null) {
		if(!is_null($useFuel)) $this->useFuel = $useFuel ? true : false; 
		return $this->useFuel;
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

		if($name == 'wire' || $name == 'fuel') return self::$fuel;
		if($name == 'className') return $this->className();
		
		if($this->useFuel()) {
			if(!is_null(self::$fuel) && !is_null(self::$fuel->$name)) return self::$fuel->$name; 
		}

		if(self::isHooked($name)) { // potential property hook
			$result = $this->runHooks($name, array(), 'property'); 
			return $result['return'];
		}

		return null;
	}


	/*******************************************************************************************************
	 * IDENTIFICATION
	 *
	 */
	
	/**
	 * Cached name of this class from the className() method
	 *
	 */
	private $className = '';

	/**
	 * Return this object's class name
	 *
	 * Note that it caches the class name in the $className object property to reduce overhead from calls to get_class().
	 *
	 * @return string
	 *
	 */
	public function className() {
		if(!$this->className) $this->className = get_class($this);
		return $this->className;
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
	 * Debug hooks
	 *
	 */
	const ___debug = false;

	/**
	 * When a hook is specified, there are a few options which can be overridden: This array outlines those options and the defaults.
	 *
	 * - type: may be either 'method' or 'property'. If property, then it will respond to $obj->property rather than $obj->method().
	 * - before: execute the hook before the method call? Not applicable if 'type' is 'property'.
	 * - after: execute the hook after the method call? (allows modification of return value). Not applicable if 'type' is 'property'.
	 * - priority: a number determining the priority of a hook, where lower numbers are executed before higher numbers.
	 * - allInstances: attach the hook to all instances of this object? (store in staticHooks rather than localHooks). Set automatically, but you may still use in some instances.
	 * - fromClass: the name of the class containing the hooked method, if not the object where addHook was executed. Set automatically, but you may still use in some instances.
	 *
	 */
	protected static $defaultHookOptions = array(
		'type' => 'method',
		'before' => false,
		'after' => true,
		'priority' => 100,
		'allInstances' => false,
		'fromClass' => '',
	);

	/**
	 * Static hooks are applicable to all instances of the descending class.
	 *
	 * This array holds references to those static hooks, and is shared among all classes descending from Wire.
	 * It is for internal use only. See also self::$defaultHookOptions[allInstances].
	 *
	 */
	protected static $staticHooks = array();

	/**
	 * Hooks that are local to this instance of the class only.
	 *
	 */
	protected $localHooks = array();

	/**
	 * Cache of all local hooks combined, for debugging purposes
	 *
	 */
	public static $allLocalHooks = array();

	/**
	 * A static cache of all hook method/property names for an optimization.
	 *
	 * Hooked methods end with '()' while hooked properties don't.
	 *
	 * This does not distinguish which instance it was added to or whether it was removed.
	 * But will use keys in the form 'fromClass::method' (with value 'method') in cases where a fromClass was specified.
	 * This cache exists primarily to gain some speed in our __get and __call methods.
	 *
	 */
	protected static $hookMethodCache = array();

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
		if(self::___debug) {
			static $timers = array();
			$timerName = get_class($this) . "::$method";
			$notes = array();
			foreach($arguments as $argument) {
				if(is_object($argument)) $notes[] = get_class($argument);
				else if(is_array($argument)) $notes[] = "array(" . count($argument) . ")";
				else if(strlen($argument) > 20) $notes[] = substr($argument, 0, 20) . '...';
			}
			$timerName .= "(" . implode(', ', $notes) . ")";
			if(isset($timers[$timerName])) {
				$timers[$timerName]++;
				$timerName .= " #" . $timers[$timerName];
			} else {
				$timers[$timerName] = 1;
			}
			Debug::timer($timerName);
			$result = $this->runHooks($method, $arguments);
			Debug::saveTimer($timerName); 
			
		} else {
			$result = $this->runHooks($method, $arguments); 
		}
		
		if(!$result['methodExists'] && !$result['numHooksRun']) {
			if($this->wire('config')->disableUnknownMethodException) return null;
			throw new WireException("Method " . $this->className() . "::$method does not exist or is not callable in this context"); 
		}
		
		return $result['return'];
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

		$result = array(
			'return' => null, 
			'numHooksRun' => 0, 
			'methodExists' => false,
			'replace' => false,
			);

		$realMethod = "___$method";
		if($type == 'method') $result['methodExists'] = method_exists($this, $realMethod);
		if(!$result['methodExists'] && !self::isHooked($method . ($type == 'method' ? '()' : ''))) return $result; // exit quickly when we can

		$hooks = $this->getHooks();

		foreach(array('before', 'after') as $when) {

			if($type === 'method' && $when === 'after' && $result['replace'] !== true) {
				if($result['methodExists']) $result['return'] = call_user_func_array(array($this, $realMethod), $arguments); 
					else $result['return'] = null;
			}

			foreach($hooks as $priority => $hook) {

				if($hook['method'] !== $method) continue; 
				if(!$hook['options'][$when]) continue; 

				$event = new HookEvent(); 
				$event->object = $this;
				$event->method = $method;
				$event->arguments = $arguments;  
				$event->when = $when; 
				$event->return = $result['return']; 
				$event->id = $hook['id']; 
				$event->options = $hook['options']; 

				$toObject = $hook['toObject'];		
				$toMethod = $hook['toMethod']; 

				if(is_null($toObject)) $toMethod($event); 
					else $toObject->$toMethod($event); 

				$result['numHooksRun']++;

				if($when == 'before') {
					$arguments = $event->arguments; 
					$result['replace'] = $event->replace === true || $result['replace'] === true; 
					if($result['replace']) $result['return'] = $event->return;
				}

				if($when == 'after') $result['return'] = $event->return; 
			}	

		}

		return $result;
	}

	/**
	 * Return all hooks associated with this class instance or method (if specified)
	 *
	 * @param string $method Optional method that hooks will be limited to. Or specify '*' to return all hooks everywhere.
	 * @param int $type Type of hooks to return: 0=all, 1=local only, 2=static only
	 * @return array
	 *
	 */
	public function getHooks($method = '', $type = 0) {

		$hooks = $type === 2 ? array() : $this->localHooks; 

		if($type !== 1) {
			// static hooks
			foreach(self::$staticHooks as $className => $staticHooks) {
				// join in any related static hooks to the instance hooks
				if($this instanceof $className || $method == '*') {
					// TODO determine if the local vs static priority level may be damaged by the array_merge
					$hooks = array_merge($hooks, $staticHooks); 
				}
			}
		}

		if($method && $method != '*') {
			$methodHooks = array();
			foreach($hooks as $priority => $hook) {
				if($hook['method'] == $method) $methodHooks[$priority] = $hook;
			}
			$hooks = $methodHooks;
		}

		return $hooks;
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
	 * @param Wire|null $instance Optional instance to check against (see isThisHooked method for details)
	 * 	Note that if specifying an $instance, you may not use the Class::method() or Class::property options for $method argument.
	 * @return bool
	 *
	 */
	static public function isHooked($method, Wire $instance = null) {
		if($instance) return $instance->hasHook($method); 
		$hooked = false;
		if(strpos($method, ':') !== false) {
			if(array_key_exists($method, self::$hookMethodCache)) $hooked = true; // fromClass::method() or fromClass::property
		} else {
			if(in_array($method, self::$hookMethodCache)) $hooked = true; // method() or property
		}
		return $hooked; 
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
		
		$hooked = false;
		if(strpos($method, '::') !== false) {
			throw new WireException("You may only specify a 'method()' or 'property', not 'Class::something'.");
		}
		
		// quick exit when possible
		if(!in_array($method, self::$hookMethodCache)) return false; 

		// first check local hooks attached to this instance
		$_method = rtrim($method, '()'); 
		foreach($this->localHooks as $hook) {
			if($hook['method'] != $_method) continue; 
			// @todo differentiate between hooked property and method
			$hooked = true;
			break;
		}

		// then check static hooks with this class and parents
		if(!$hooked) { 
			$classes = class_parents($this, false);
			array_unshift($classes, get_class($this));
			foreach($classes as $class) {
				if(array_key_exists("$class::$method", self::$hookMethodCache)) {
					$hooked = true;
					break;
				}
			}
		}
		
		return $hooked;	
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
	 * @param string $method Method name to hook into, NOT including the three preceding underscores. May also be Class::Method for same result as using the fromClass option.
	 * @param object|null $toObject Object to call $toMethod from, or null if $toMethod is a function outside of an object
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event
	 * @param array $options See self::$defaultHookOptions at the beginning of this class
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 * @throws WireException
	 *
	 */
	public function addHook($method, $toObject, $toMethod = null, $options = array()) {
		
		if(is_array($toMethod)) {
			// $options array specified as 3rd argument
			if(count($options)) {
				// combine $options from addHookBefore/After and user specified options
				$options = array_merge($toMethod, $options); 
			} else {
				$options = $toMethod;
			}
			$toMethod = null;
		}
		
		if(is_null($toMethod)) {
			// $toObject has been ommitted and a procedural function specified instead
			// $toObject may also be a closure
			$toMethod = $toObject; 
			$toObject = null;
		}

		if(is_null($toMethod)) throw new WireException("Method to call is required and was not specified (toMethod)"); 
		if(substr($method, 0, 3) == '___') throw new WireException("You must specify hookable methods without the 3 preceding underscores"); 
		if(method_exists($this, $method)) throw new WireException("Method " . $this->className() . "::$method is not hookable"); 

		$options = array_merge(self::$defaultHookOptions, $options);
		if(strpos($method, '::')) list($options['fromClass'], $method) = explode('::', $method); 

		if($options['allInstances'] || $options['fromClass']) {
			// hook all instances of this class
			$hookClass = $options['fromClass'] ? $options['fromClass'] : $this->className();
			if(!isset(self::$staticHooks[$hookClass])) self::$staticHooks[$hookClass] = array();
			$hooks =& self::$staticHooks[$hookClass]; 

		} else {
			// hook only this instance
			$hookClass = '';
			$hooks =& $this->localHooks;
		}

		$priority = (int) $options['priority']; 
		while(isset($hooks[$priority])) $priority++;
		$id = "$hookClass:$priority";
		$options['priority'] = $priority; 

		$hooks[$priority] = array(
			'id' => $id, 
			'method' => $method, 
			'toObject' => $toObject, 
			'toMethod' => $toMethod, 
			'options' => $options, 
			); 
	
		// cacheValue is just the method() or property, cacheKey includes optional fromClass::
		$cacheValue = $options['type'] == 'method' ? "$method()" : "$method";
		$cacheKey = ($options['fromClass'] ? $options['fromClass'] . '::' : '') . $cacheValue;
		self::$hookMethodCache[$cacheKey] = $cacheValue;
		
		// keep track of all local hooks combined when debug mode is on
		if($this->wire('config')->debug && $hooks === $this->localHooks) {
			$debugClass = $this->className();
			$debugID = $debugClass . $id;
			while(isset(self::$allLocalHooks[$debugID])) $debugID .= "_";
			$debugHook = $hooks[$priority];
			$debugHook['method'] = $debugClass . "->" . $debugHook['method'];
			self::$allLocalHooks[$debugID] = $debugHook;
		}

		ksort($hooks); // sort by priority
		return $id;
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
	 * @param object|null $toObject Object to call $toMethod from, or null if $toMethod is a function outside of an object
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event
	 * @param array $options See self::$defaultHookOptions at the beginning of this class
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 *
	 */
	public function addHookBefore($method, $toObject, $toMethod = null, $options = array()) {
		$options['before'] = true; 
		if(!isset($options['after'])) $options['after'] = false; 
		return $this->addHook($method, $toObject, $toMethod, $options); 
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
	 * @param object|null $toObject Object to call $toMethod from, or null if $toMethod is a function outside of an object
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event
	 * @param array $options See self::$defaultHookOptions at the beginning of this class
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 *
	 */
	public function addHookAfter($method, $toObject, $toMethod = null, $options = array()) {
		$options['after'] = true; 
		if(!isset($options['before'])) $options['before'] = false; 
		return $this->addHook($method, $toObject, $toMethod, $options); 
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
	 * @param object|null $toObject Object to call $toMethod from, or null if $toMethod is a function outside of an object
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event
	 * @param array $options See self::$defaultHookOptions at the beginning of this class
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 *
	 */
	public function addHookProperty($property, $toObject, $toMethod = null, $options = array()) {
		$options['type'] = 'property'; 
		return $this->addHook($property, $toObject, $toMethod, $options); 
	}

	/**
	 * Given a Hook ID provided by addHook() this removes the hook
	 *
	 * @param string $hookId
	 * @return $this
	 *
	 */
	public function removeHook($hookId) {
		list($hookClass, $priority) = explode(':', $hookId); 
		if(!$hookClass) unset($this->localHooks[$priority]); 
			else unset(self::$staticHooks[$hookClass][$priority]); 
		return $this;
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
	
	protected $_notices = array('errors' => null, 'messages' => null);

	/**
	 * Record an informational or 'success' message in the system-wide notices. 
	 *
	 * This method automatically identifies the message as coming from this class. 
	 *
	 * @param string $text
	 * @param int|bool $flags See Notices::flags or specify TRUE to have the message also logged to messages.txt
	 * @return $this
	 *
	 */
	public function message($text, $flags = 0) {
		if($flags === true) $flags = Notice::log; 
		$notice = new NoticeMessage($text, $flags); 
		$notice->class = $this->className();
		if(is_null($this->_notices['messages'])) $this->_notices['messages'] = new Notices();
		$this->wire('notices')->add($notice);
		$this->_notices['messages']->add($notice);
		return $this; 
	}

	/**
	 * Record an non-fatal error message in the system-wide notices. 
	 *
	 * This method automatically identifies the error as coming from this class. 
	 *
	 * Fatal errors should still throw a WireException (or class derived from it)
	 *
	 * @param string $text
	 * @param int|bool $flags See Notices::flags or specify TRUE to have the error also logged to errors.txt
	 * @return $this
	 *
	 */
	public function error($text, $flags = 0) {
		if($flags === true) $flags = Notice::log; 
		$notice = new NoticeError($text, $flags); 
		$notice->class = $this->className();
		if(is_null($this->_notices['errors'])) $this->_notices['errors'] = new Notices();
		$this->wire('notices')->add($notice);
		$this->_notices['errors']->add($notice);
		return $this; 
	}

	/**
	 * Return errors recorded by this object
	 * 
	 * @param string|array $options One or more of array elements or space separated string of:
	 * 	first: only first item will be returned (string)
	 * 	last: only last item will be returned (string)
	 * 	all: include all items of type (messages or errors) beyond the scope of this object
	 * 	clear: clear out all items that are returned from this method (includes both local and global)
	 * @return Notices|string Array of NoticeError error messages or string if last, first or str option was specified.
	 * 
	 */
	public function errors($options = array()) {
		if(!is_array($options)) $options = explode(' ', strtolower($options)); 
		$options[] = 'errors';
		return $this->messages($options); 
	}

	/**
	 * Return messages recorded by this object
	 *
	 * @param string|array $options One or more of array elements or space separated string of:
	 * 	first: only first item will be returned (string)
	 * 	last: only last item will be returned (string)
	 * 	all: include all items of type (messages or errors) beyond the scope of this object
	 * 	clear: clear out all items that are returned from this method (includes both local and global)
	 * 	errors: returns errors rather than messages.
	 * @return Notices|string Array of NoticeError error messages or string if last, first or str option was specified.
	 *
	 */
	public function messages($options = array()) {
		if(!is_array($options)) $options = explode(' ', strtolower($options)); 
		$type = in_array('errors', $options) ? 'errors' : 'messages';
		$clear = in_array('clear', $options); 
		if(in_array('all', $options)) {
			// get all of either messages or errors (either in or out of this object instance)
			$value = new Notices();
			foreach($this->wire('notices') as $notice) {
				if($notice->getName() != $type) continue;
				$value->add($notice);
				if($clear) $this->wire('notices')->remove($notice); // clear global
			}
			if($clear) $this->_notices[$type] = null; // clear local
		} else {
			// get messages or errors specific to this object instance
			$value = is_null($this->_notices[$type]) ? new Notices() : $this->_notices[$type];
			if(in_array('first', $options)) $value = $clear ? $value->shift() : $value->first();
				else if(in_array('last', $options)) $value = $clear ? $value->pop() : $value->last(); 
				else if($clear) $this->_notices[$type] = null;
			if($clear && $value) $this->wire('notices')->removeItems($value); // clear from global notices
		}
		return $value; 
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


}

