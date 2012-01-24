<?php

/**
 * ProcessWire HookEvent
 *
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * Instances of HookEvent are passed to Hook handlers when their requested method has been called.
 *
 * HookEvents have the following properties available: 
 *
 * 	$hookEvent->object
 *	 	Instance of the object where the Hook event originated. 
 *
 * 	$hookEvent->method
 * 		The name of the method that was called to generate the Hook event. 
 * 
 * 	$hookEvent->arguments
 *	 	A numerically indexed array of the arguments sent to the above mentioned method. 
 * 
 * 	$hookEvent->return
 *	 	Applicable only for 'after' or ('replace' + 'before' hooks), contains the value returned by the 
 * 		above mentioned method. The hook handling method may modify this return value. 
 *
 * 	$hookEvent->replace
 *		Set to boolean TRUE in a 'before' hook if you want to prevent execution of the original hooked function.
 *		In such a case, your hook is replacing the function entirely. Not recommended, so be careful with this.
 *
 * 	$hookEvent->options
 *	 	An optional array of user-specified data that gets sent to the hooked function
 *		The hook handling method may access it from $event->data
 * 		This array also includes all of the Wire:defaultHookOptions
 * 
 * 	$hookEvent->id
 *	 	An unique identifier string that may be used with a call to Wire::removeHook()
 *
 */
class HookEvent extends WireData {

	/**
	 * Cached argument names indexed by "className.method"
	 *
	 */
	static protected $argumentNames = array();

	/**
	 * Construct the HookEvent and establish default values
	 *
	 */
	public function __construct() {
		$this->set('object', null); 
		$this->set('method', '');
		$this->set('arguments', array()); 
		$this->set('return', null); 
		$this->set('replace', false); 
		$this->set('options', array()); 
		$this->set('id', ''); 
	}

	/**
	 * Retrieve or set a hooked function argument
	 *
	 * @param int $n Zero based number of the argument you want to retrieve, where 0 is the first. Omit to return array of all arguments. 
	 *	May also be the string 'name' of the argument. 
	 * @param mixed $value Value that you want to set to this argument, or omit to only return the argument.
	 * @return array|null|mixed 
	 *
	 */
	public function arguments($n = null, $value = null) {
		if(is_null($n)) return $this->arguments; 
		if(!is_null($value)) return $this->setArgument($n, $value); 
		if(is_string($n)) return $this->argumentsByName($n);
		$arguments = $this->arguments; 
		return isset($arguments[$n]) ? $arguments[$n] : null; 
	}

	/**
	 * Returns an array of all arguments indexed by name, or the value of a single specified argument
	 *
	 * Note: arguments('name') can also be used as a shorter synonym for argumentsByName('name');
	 *
	 * @param string $n Optional name of argument value to return. If not specified, array of all argument values returned.
	 * @return mixed|array Depending on whether you specify $n
	 *
	 */
	public function argumentsByName($n = '') {

		$names = $this->getArgumentNames();
		$arguments = $this->arguments();

		if($n) {
			$key = array_search($n, $names); 
			if($key === false) return null;	
			return array_key_exists($key, $arguments) ? $arguments[$key] : null;
		}

		$value = null;
		$argumentsByName = array();

		foreach($names as $key => $name) {
			$value = null;
			if(isset($arguments[$key])) $value = $arguments[$key];
			$argumentsByName[$name] = $value;
		}

		return $argumentsByName;
		
	}


	/**
	 * Sets an argument value, handles the implementation of setting for the above arguments() function
	 *
	 * Only useful with 'before' hooks, where the argument can be manipulated before being sent to the hooked function.
	 *
	 * @param int|string Argument name or key
	 * @param mixed $value
	 *
	 */
	public function setArgument($n, $value) {

		if(is_string($n) && !ctype_digit($n)) {
			// convert argument name to position
			$names = $this->getArgumentNames();	
			$n = array_search($n, $names); 
			if($n === false) throw new WireException("Unknown argument name: $n"); 
		}

		$arguments = $this->arguments; 
		$arguments[(int)$n] = $value; 
		$this->set('arguments', $arguments); 
	}

	/**
	 * Return an array of all argument names, indexed by their position
	 *
	 * @return array
	 *
	 */
	protected function getArgumentNames() {

		$o = $this->get('object'); 
		$m = $this->get('method');
		$key = get_class($o) . '.' . $m; 

		if(isset(self::$argumentNames[$key])) return self::$argumentNames[$key];

		$argumentNames = array();
		$argumentDefaults = array();
		$method = new ReflectionMethod($o, '___' . $m); 
		$arguments = $method->getParameters();

		foreach($arguments as $a) {
			$pos = $a->getPosition();
			$argumentNames[$pos] = $a->getName();
		}

		self::$argumentNames[$key] = $argumentNames; 

		return $argumentNames; 
	}

	/**
	 * Return a string representing the HookEvent
	 *
	 */
	public function __toString() {
		$s = $this->object->className() . '::' . $this->method . '(';
		foreach($this->arguments as $a) $s .= is_string($a) ? '"' . $a . '", ' : "$a, ";
		$s = rtrim($s, ", ") . ")";
		return $s; 	
	}


}

