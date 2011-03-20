<?php

/**
 * ProcessWire HookEvent
 *
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
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
 *	 	Applicable only for 'after' hooks, contains the value returned by the above mentioned method. 
 *		The hook handling method may modify this return value. 
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

	public function __construct() {
		$this->set('object', null); 
		$this->set('method', '');
		$this->set('arguments', array()); 
		$this->set('return', null); 
		$this->set('options', array()); 
		$this->set('id', ''); 
	}

	public function __toString() {
		$s = $this->object->className() . '::' . $this->method . '(';
		foreach($this->arguments as $a) $s .= is_string($a) ? '"' . $a . '", ' : "$a, ";
		$s = rtrim($s, ", ") . ")";
		return $s; 	
	}

	/**
	 * Retrieve or set a hooked function argument
	 *
	 * @param int $n Zero based number of the argument you want to retrieve, where 0 is the first. Omit to return array of all arguments. 
	 * @param mixed $value Value that you want to set to this argument, or omit to only return the argument.
	 * @return array|null|mixed 
	 *
	 */
	public function arguments($n = null, $value = null) {
		if(is_null($n)) return $this->arguments; 
		$arguments = $this->arguments; 
		if(is_null($value)) return isset($arguments[$n]) ? $arguments[$n] : null; 
		$arguments[$n] = $value;
		$this->set('arguments', $arguments); 
		return $value; 
	}

}

