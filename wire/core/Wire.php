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
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
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
 * 
 * @method changed(string $what) See Wire::___changed()
 * @method log($str = '', array $options = array()) See Wire::___log()
 * @method callUnknown($method, $arguments) See Wire::___callUnknown()
 * @method trackException(Exception $e, $severe = true, $text = null)
 * 
 * @todo Move all hooks implementation to separate WireHooks class
 *
 */

abstract class Wire implements WireTranslatable, WireHookable, WireFuelable, WireTrackable {

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
	 * Note that we store the API variables statically so that descending classes can share the same
	 * API variables without repetitive calls. PW 3.0 will instead have the fuel injected to each
	 * object instance so that it's possible for multiple PW instances to run in the same request. 
	 *
	 */
	
	/**
	 * Fuel holds references to other ProcessWire system objects. It is an instance of the Fuel class.
	 * 
	 * @var Fuel|null
	 * @deprecated
	 *
	 */
	protected static $fuel = null;

	/**
	 * Whether this class may use fuel variables in local scope, like $this->item
	 * 
	 * @var bool
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
	 * @deprecated Use $this->wire($name, $value, $lock) instead.
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
	 * @deprecated
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
	 * @deprecated
	 *
	 */
	public function fuel($name) {
		return self::$fuel->$name;
	}
	
	/**
	 * Should fuel vars be scoped locally to this class instance?
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
	 * @internal
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
	 * Cached name of this class from the className(false) method
	 *
	 */
	private $className = '';
	
	/**
	 * Cached results from class name calls with options
	 *
	 */
	private $classNameOptions = array();

	/**
	 * Return this object's class name
	 *
	 * Note that it caches the class name in the $className object property to reduce overhead from calls to get_class().
	 *
	 * @param array|null $options Optionally an option: 
	 * 	- lowercase (bool): Specify true to make it return hyphenated lowercase version of class name
	 * @return string
	 *
	 */
	public function className($options = null) {
		
		if(!$this->className) $this->className = get_class($this);
		if($options === null || !is_array($options)) return $this->className; 
		
		if(!empty($options['lowercase'])) {
			if(!empty($this->classNameOptions['lowercase'])) return $this->classNameOptions['lowercase']; 
			$name = $this->className;
			$part = substr($name, 1);
			if(strtolower($part) != $part) {
				// contains more than 1 uppercase character, convert to hyphenated lowercase
				$name = substr($name, 0, 1) . preg_replace('/([A-Z])/', '-$1', $part);
			}
			$name = strtolower($name); 
			$this->classNameOptions['lowercase'] = $name;
			return $name; 
		}
		
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
	 * - argMatch: array of Selectors objects where the indexed argument (n) to the hooked method must match, order to execute hook.
	 * - objMatch: Selectors object that the current object must match in order to execute hook
	 *
	 */
	protected static $defaultHookOptions = array(
		'type' => 'method',
		'before' => false,
		'after' => true,
		'priority' => 100,
		'allInstances' => false,
		'fromClass' => '',
		'argMatch' => null, 
		'objMatch' => null, 
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
			return $this->callUnknown($method, $arguments);
		}
		
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

		$result = array(
			'return' => null, 
			'numHooksRun' => 0, 
			'methodExists' => false,
			'replace' => false,
			);

		$realMethod = "___$method";
		if($type == 'method') $result['methodExists'] = method_exists($this, $realMethod);
		if(!$result['methodExists'] && !self::isHooked($method . ($type == 'method' ? '()' : ''))) {
			return $result; // exit quickly when we can
		}

		$hooks = $this->getHooks($method);

		foreach(array('before', 'after') as $when) {

			if($type === 'method' && $when === 'after' && $result['replace'] !== true) {
				if($result['methodExists']) {
					$result['return'] = call_user_func_array(array($this, $realMethod), $arguments);
				} else {
					$result['return'] = null;
				}
			}

			foreach($hooks as $priority => $hook) {

				if(!$hook['options'][$when]) continue;

				if(!empty($hook['options']['objMatch'])) {
					$objMatch = $hook['options']['objMatch'];
					// object match comparison to determine at runtime whether to execute the hook
					if(is_object($objMatch)) {
						if(!$objMatch->matches($this)) continue;
					} else {
						if(((string) $this) != $objMatch) continue;
					}
				}
				
				if($type == 'method' && !empty($hook['options']['argMatch'])) {
					// argument comparison to determine at runtime whether to execute the hook
					$argMatches = $hook['options']['argMatch'];
					$matches = true;
					foreach($argMatches as $argKey => $argMatch) {
						$argVal = isset($arguments[$argKey]) ? $arguments[$argKey] : null;
						if(is_object($argMatch)) {
							// Selectors object
							if(is_object($argVal)) {
								$matches = $argMatch->matches($argVal);
							} else {
								// we don't work with non-object here
								$matches = false;
							}
						} else {
							if(is_array($argVal)) {
								// match any array element
								$matches = in_array($argMatch, $argVal);
							} else {
								// exact string match
								$matches = $argMatch == $argVal;
							}
						}
						if(!$matches) break;
					}
					if(!$matches) continue; // don't run hook
				}

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

		$hooks = array();
	
		// first determine which local hooks when should include
		if($type !== 2) {
			if($method && $method !== '*') {
				// populate all local hooks for given method
				if(isset($this->localHooks[$method])) $hooks = $this->localHooks[$method];
			} else {
				// populate all local hooks, regardless of method
				// note: sort of return hooks is no longer priority based
				// @todo account for '*' method, which should return all hooks regardless of instance
				foreach($this->localHooks as $method => $methodHooks) {
					$hooks = array_merge(array_values($hooks), array_values($methodHooks));
				}
			}
		}
	
		// if only local hooks requested, we can return them now
		if($type === 1) return $hooks;
		
		$needSort = false;
		
		// join in static hooks
		foreach(self::$staticHooks as $className => $staticHooks) {
			if(!$this instanceof $className && $method !== '*') continue;
			// join in any related static hooks to the local hooks
			if($method && $method !== '*') {
				// retrieve all static hooks for method
				if(!empty($staticHooks[$method])) {
					if(count($hooks)) {
						$collisions = array_intersect_key($hooks, $staticHooks[$method]);
						$hooks = array_merge($hooks, $staticHooks[$method]);
						if(count($collisions)) {
							// identify and resolve priority collisions
							foreach($collisions as $priority => $hook) {
								$n = 0;
								while(isset($hooks["$priority.$n"])) $n++;
								$hooks["$priority.$n"] = $hook;
							}
						}
						$needSort = true;
					} else {
						$hooks = $staticHooks[$method];
					}
				}
			} else {
				// no method specified, retrieve all for class
				// note: priority-based array indexes are no longer in tact
				$hooks = array_values($hooks);
				foreach($staticHooks as $_method => $methodHooks) {
					$hooks = array_merge($hooks, array_values($methodHooks));
				}
			}
		}
		
		if($needSort && count($hooks) > 1) {
			defined("SORT_NATURAL") ? ksort($hooks, SORT_NATURAL) : uksort($hooks, "strnatcmp");
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
	 * @param Wire|null $instance Optional instance to check against (see hasHook method for details)
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
	 * @todo differentiate between "method()" and "property"
	 *
	 */
	public function hasHook($method) {
		
		$hooked = false;
		if(strpos($method, '::') !== false) {
			throw new WireException("You may only specify a 'method()' or 'property', not 'Class::something'.");
		}
		
		// quick exit when possible
		if(!in_array($method, self::$hookMethodCache)) return false; 

		$_method = rtrim($method, '()');
		
		if(!empty($this->localHooks[$_method])) {
			// first check local hooks attached to this instance
			$hooked = true;
		} else if(!empty(self::$staticHooks[get_class($this)][$_method])) {
			// now check if hooked in this class
			$hooked = true;
		} else {
			// check parent classes and interfaces
			$classes = class_parents($this, false);
			$interfaces = class_implements($this);
			if(is_array($interfaces)) $classes = array_merge($interfaces, $classes);
			foreach($classes as $class) {
				if(!empty(self::$staticHooks[$class][$_method])) {
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
		if(strpos($method, '::')) {
			list($fromClass, $method) = explode('::', $method, 2);
			if(strpos($fromClass, '(') !== false) {
				// extract object selector match string
				list($fromClass, $objMatch) = explode('(', $fromClass, 2);
				$objMatch = trim($objMatch, ') ');
				if(Selectors::stringHasSelector($objMatch)) $objMatch = new Selectors($objMatch);
				if($objMatch) $options['objMatch'] = $objMatch;
			}
			$options['fromClass'] = $fromClass;
		}
	
		$argOpen = strpos($method, '('); 
		if($argOpen && strpos($method, ')') > $argOpen+1) {
			// extract argument selector match string(s), arg 0: Something::something(selector_string)
			// or: Something::something(1:selector_string, 3:selector_string) matches arg 1 and 3. 
			list($method, $argMatch) = explode('(', $method, 2); 
			$argMatch = trim($argMatch, ') ');
			if(strpos($argMatch, ':') !== false) {
				// zero-based argument indexes specified, i.e. 0:template=product, 1:order_status
				$args = preg_split('/\b([0-9]):/', trim($argMatch), -1, PREG_SPLIT_DELIM_CAPTURE);
				if(count($args)) {
					$argMatch = array();
					array_shift($args); // blank
					while(count($args)) {
						$argKey = (int) trim(array_shift($args));
						$argVal = trim(array_shift($args), ', ');
						$argMatch[$argKey] = $argVal;
					}
				}
			} else {
				// just single argument specified, so argument 0 is assumed
			}
			if(is_string($argMatch)) $argMatch = array(0 => $argMatch);
			foreach($argMatch as $argKey => $argVal) {
				if(Selectors::stringHasSelector($argVal)) $argMatch[$argKey] = new Selectors($argVal);
			}
			if(count($argMatch)) $options['argMatch'] = $argMatch; 
		}

		if($options['allInstances'] || $options['fromClass']) {
			// hook all instances of this class
			$hookClass = $options['fromClass'] ? $options['fromClass'] : $this->className();
			if(!isset(self::$staticHooks[$hookClass])) self::$staticHooks[$hookClass] = array();
			$hooks =& self::$staticHooks[$hookClass]; 
			$options['allInstances'] = true; 
			$local = 0;

		} else {
			// hook only this instance
			$hookClass = '';
			$hooks =& $this->localHooks;
			$local = 1;
		}

		$priority = (string) $options['priority']; 
		if(!isset($hooks[$method])) {
			if(ctype_digit($priority)) $priority = "$priority.0";
			$hooks[$method] = array();
		} else {
			if(strpos($priority, '.')) {
				// priority already specifies a sub value: extract it
				list($priority, $n) = explode('.', $priority);
				$options['priority'] = $priority; // without $n
				$priority .= ".$n";
			} else {
				$n = 0;
				$priority .= ".0";
			}
			// come up with a priority that is unique for this class/method across both local and static hooks
			while(($hookClass && isset(self::$staticHooks[$hookClass][$method][$priority])) 
				|| isset($this->localHooks[$method][$priority])) {
				$n++;
				$priority = "$options[priority].$n";
			}
		}
	
		// Note hookClass is always blank when this is a local hook
		$id = "$hookClass:$priority:$method";
		$options['priority'] = $priority;

		$hooks[$method][$priority] = array(
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
			$debugID = ($local ? $debugClass : '') . $id;
			while(isset(self::$allLocalHooks[$debugID])) $debugID .= "_";
			$debugHook = $hooks[$method][$priority];
			$debugHook['method'] = $debugClass . "->" . $debugHook['method'];
			self::$allLocalHooks[$debugID] = $debugHook;
		}

		// sort by priority, if more than one hook for the method
		if(count($hooks[$method]) > 1) {
			defined("SORT_NATURAL") ? ksort($hooks[$method], SORT_NATURAL) : uksort($hooks[$method], "strnatcmp");
		}
		
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
		return $this->addHook($property, $toObject, $toMethod, $options); 
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
		if(!empty($hookId) && strpos($hookId, ':')) {
			list($hookClass, $priority, $method) = explode(':', $hookId); 
			if(empty($hookClass)) {
				unset($this->localHooks[$method][$priority]);	
			} else {
				unset(self::$staticHooks[$hookClass][$method][$priority]);
			}
		}
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
		$notice = new $class($text, $flags);
		$notice->class = $this->className();
		if(is_null($this->_notices[$name])) $this->_notices[$name] = new Notices();
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
	 * @param Exception $e Exception object that was thrown
	 * @param bool|int $severe Whether or not it should be considered severe (default=true)
	 * @param string|array|object|true $text Additional details (optional). 
	 * 	When provided, it will be sent to $this->error($text) if $severe==true, or $this->warning($text) if $severe==false.
	 * 	Specify boolean true to just sent the $e->getMessage() to $this->error() or $this->warning(). 
	 * @return $this
	 * @throws Exception If $severe==true and $config->allowExceptions==true
	 * 
	 */
	public function ___trackException(Exception $e, $severe = true, $text = null) {
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
			$value = new Notices();
			foreach($this->wire('notices') as $notice) {
				if($notice->getName() != $type) continue;
				$value->add($notice);
				if($clear) $this->wire('notices')->remove($notice); // clear global
			}
			if($clear) $this->_notices[$type] = null; // clear local
		} else {
			// get messages, warnings or errors specific to this object instance
			$value = is_null($this->_notices[$type]) ? new Notices() : $this->_notices[$type];
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
	protected $_wire = null;
	 */

	/**
	 * Set the current ProcessWire instance for this object (PW 3.0)
	 *
	 * Specify no arguments to get, or specify a ProcessWire instance to set.
	 *
	 * @param ProcessWire $wire
	 * @return this
	 *
	public function setWire($wire) {
		$this->_wire = $wire;
		return $this;
	}
	 */

	/**
	 * Get the current ProcessWire instance (PW 3.0)
	 * 
	 * You can also use the wire() method with no arguments. 
	 *
	 * @return null|ProcessWire
	 *
	public function getWire() {
		return $this->_wire;
	}
	 */
	
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
	 * @return ProcessWire|Wire|Session|Page|Pages|Modules|User|Users|Roles|Permissions|Templates|Fields|Fieldtypes|Sanitizer|Config|Notices|WireDatabasePDO|WireInput|string|mixed
	 * @throws WireException
	 *
	 *
	 */
	public function wire($name = '', $value = null, $lock = false) {
		
		if(is_null(self::$fuel)) self::$fuel = new Fuel();
		
		if($value !== null) {
			// setting a fuel value
			return self::$fuel->set($name, $value, $lock);
		}
		
		if(empty($name)) {
			// return ProcessWire instance
			return self::$fuel->wire;
		} else if($name === '*' || $name === 'all') {
			// return Fuel instance
			return self::$fuel;
		}
	
		/* TBA PW3
		if(is_object($name)) {
			// injecting ProcessWire instance to object
			if($name instanceof Wire) return $name->setWire($this->_wire); // inject fuel, PW 3.0 
			throw new WireException("Expected Wire instance");
		}
		*/

		// get API variable
		$value = self::$fuel->$name;
		
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
		if(is_null($debugInfo)) $debugInfo = new WireDebugInfo();
		return $debugInfo->getDebugInfo($this);
	}


}

