<?php

/**
 * ProcessWire Interfaces
 *
 * Interfaces used throughout ProcessWire's core.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

/** 
 * For classes that are saved to a database or disk.
 *
 * Item must have a gettable/settable 'id' property for this interface as well
 *
 */
interface Saveable {

	/**
	 * Save the object's current state to database.
	 *
	 */
	public function save(); 

	/**
	 * Get an array of this item's saveable data, should match exact with the table it saves in
	 *
	 */
	public function getTableData(); 
}


/**
 * Class HasRoles
 * 
 * @deprecated
 * 
 */
interface HasRoles {
	// To be deleted
}


/**
 * For classes that contain lookup items, as used by WireSaveableItemsLookup
 *
 */
interface HasLookupItems {

	/**
	 * Get all lookup items, usually in a WireArray derived type, but specified by class
	 *
	 */
	public function getLookupItems(); 

	/**
	 * Add a lookup item to this instance
	 *
	 * @param int $item The ID of the item to add 
	 * @param array $row The row from which it was retrieved (in case you want to retrieve or modify other details)
	 *
	 */
	public function addLookupItem($item, array &$row); 
	
}


/**
 * For classes that need to track changes made by other objects. 
 *
 */
interface WireTrackable {

	/**
	 * Turn change tracking on (or off). 
	 *
	 * By default change tracking is off until turned on. 
	 *
	 */
	public function setTrackChanges($trackChanges = true); 


	/**
	 * Track a change to the name of the variable provided.	
	 *
	 * @param string $what The name of the variable that changed. 
	 *
	 */
	public function trackChange($what); 	

	
	/**
	 * Has this object changed since tracking was turned on?
	 *
	 * @param string $what Optional indictator of variable name 
	 * @return bool
	 *
	 */
	public function isChanged($what = ''); 

	/**
	 * Get array of all changes
	 *
	 * @return array
	 *
	 */
	public function getChanges();

}


/**
 * Indicates that a given Fieldtype may be used for page titles
 *
 */
interface FieldtypePageTitleCompatible { }


/**
 * Indicates that an Inputfield provides tree selection capabilities
 *
 * In such Inputfields a parent_id refers to the root of the tree rather than an immediate parent.
 *
 */
interface InputfieldPageListSelection { }

/**
 * Indicates that an Inputfield renders a list of items
 *
 */
interface InputfieldItemList { }

/**
 * Interface that indicates a class contains gettext() like translation methods 
 *
 */
interface WireTranslatable {
	/**
	 * Translate the given text string into the current language if available.
	 *
	 * If not available, or if the current language is the native language, then it returns the text as is.
	 *
	 * @param string $text Text string to translate
	 * @return string
	 *
	 */
	public function _($text);

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
	public function _x($text, $context);

	/**
	 * Perform a language translation with singular and plural versions
	 *
	 * @param string $textSingular Singular version of text (when there is 1 item)
	 * @param string $textPlural Plural version of text (when there are multiple items or 0 items)
	 * @param int $count Quantity used to determine whether singular or plural.
	 * @return string Translated text or original text if translation not available.
	 *
	 */
	public function _n($textSingular, $textPlural, $count);
}


/**
 * Interface that indicates the required methods for a class to be hookable.
 * 
 * See the Wire class that provides an example implementation of all these.
 * 
 */
interface WireHookable {
	
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
	public function __call($method, $arguments);

	/**
	 * Provides the implementation for dealing with hook properties, added via the addHookProperty method
	 * 
	 * @param $name
	 * @return mixed
	 * 
	public function __get($name);
	 */
	
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
	public function runHooks($method, $arguments, $type = 'method');

	/**
	 * Return all hooks associated with this class instance or method (if specified)
	 *
	 * @param string $method Optional method that hooks will be limited to. Or specify '*' to return all hooks everywhere.
	 * @return array
	 *
	 */
	public function getHooks($method = '');

	/**
	 * Returns true if the method/property hooked, false if it isn't.
	 *
	 * This is for optimization use. It does not distinguish about class or instance.
	 *
	 * If checking for a hooked method, it should be in the form "method()".
	 * If checking for a hooked property, it should be in the form "property".
	 *
	 */
	static function isHooked($method);

	/**
	 * Hook a function/method to a hookable method call in this object
	 *
	 * Hookable method calls are methods preceded by three underscores.
	 * You may also specify a method that doesn't exist already in the class
	 * The hook method that you define may be part of a class or a globally scoped function.
	 *
	 * If you are hooking a procedural function, you may omit the $toObject and instead just call via:
	 * $this->addHook($method, 'function_name');
	 *
	 * @param string $method Method name to hook into, NOT including the three preceding underscores. May also be Class::Method for same result as using the fromClass option.
	 * @param object|null $toObject Object to call $toMethod from, or null if $toMethod is a function outside of an object
	 * @param string $toMethod Method from $toObject, or function name to call on a hook event
	 * @param array $options See self::$defaultHookOptions at the beginning of this class
	 * @return string A special Hook ID that should be retained if you need to remove the hook later
	 * @throws WireException
	 *
	 */
	public function addHook($method, $toObject, $toMethod = null, $options = array());

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
	public function addHookAfter($method, $toObject, $toMethod = null, $options = array());
	
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
	public function addHookProperty($property, $toObject, $toMethod = null, $options = array());
	
	/**
	 * Given a Hook ID provided by addHook() this removes the hook
	 *
	 * @param string $hookId
	 * @return $this
	 *
	 */
	public function removeHook($hookId);
	
}

/**
 * Interface that indicates a class supports API variable dependency injection and retrieval
 * 
 */
interface WireFuelable {
	
	/**
	 * Get or inject a ProcessWire API variable
	 *
	 * 1. As a getter (option 1):
	 * Usage: $this->wire('name'); // name is an API variable name
	 * If 'name' does not exist, a WireException will be thrown.
	 *
	 * 2. As a getter (option 2):
	 * Usage: $this->wire()->name; // name is an API variable name
	 * Null will be returned if API var does not exist (no Exception thrown).
	 *
	 * 3. As a setter:
	 * $this->wire('name', $value);
	 * $this->wire('name', $value, true); // lock the API variable so nothing else can overwrite it
	 * $this->wire()->set('name', $value);
	 * $this->wire()->set('name', $value, true); // lock the API variable so nothing else can overwrite it
	 *
	 * @param string $name Name of API variable to retrieve, set, or omit to retrieve entire Fuel object.
	 * @param null|mixed $value Value to set if using this as a setter, otherwise omit.
	 * @param bool $lock When using as a setter, specify true if you want to lock the value from future changes (default=false)
	 * @return mixed|Fuel
	 * @throws WireException
	 *
	 */
	public function wire($name = '', $value = null, $lock = false);
}

/**
 * Interface that indicates the class supports Notice messaging
 * 
 */
interface WireNoticeable {
	/**
	 * Record an informational or 'success' message in the system-wide notices.
	 *
	 * This method automatically identifies the message as coming from this class.
	 *
	 * @param string $text
	 * @param int $flags See Notices::flags
	 * @return $this
	 *
	 */
	public function message($text, $flags = 0);

	/**
	 * Record an non-fatal error message in the system-wide notices.
	 *
	 * This method automatically identifies the error as coming from this class.
	 *
	 * Fatal errors should still throw a WireException (or class derived from it)
	 *
	 * @param string $text
	 * @param int $flags See Notices::flags
	 * @return $this
	 *
	 */
	public function error($text, $flags = 0);
}

/**
 * Interface for ProcessWire database layer
 * 
 */

interface WireDatabase {
	/**
	 * Is the given string a database comparison operator?
	 *
	 * @param string $str 1-2 character opreator to test
	 * @return bool
	 *
	 */
	public function isOperator($str);
}
