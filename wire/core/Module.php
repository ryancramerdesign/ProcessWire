<?php

/**
 * ProcessWire Module Interface
 *
 * Provides the base interfaces required by modules. 
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
 * ConfigurableModule is an interface that indicates the module is configurable by providing 
 * a getModuleConfigInputfields method and config properties may be get/set directly like: 
 * 
 * $val = $obj->property, and $obj->property = $val
 *
 * When you use this as an interface, you MUST also use 'Module' as an interface, 
 * i.e. "class Something implements Module, ConfigurableModule"
 * 
 * Hint: Make your ConfigurableModule classes inherit from WireData, which already has 
 * the get/set required methods.
 *
 * You may optionally specify a handler method for configuration data: setConfigData().
 * See commented function reference in the interface below. 
 *
 */
interface ConfigurableModule {

	/** 
	 * Return an InputfieldsWrapper of Inputfields used to configure the class
	 *
	 * @param array $data Array of config values indexed by field name
	 * @return InputfieldsWrapper
	 *
	 */ 
	public static function getModuleConfigInputfields(array $data); 

	public function __get($key);

	public function __set($key, $value); 

	/**
	 * An optional method you may include in your ConfigurableModule to have ProcessWire 
	 * send the configuration data to it rather than populating the properties individually. 
	 *
	 * @param array $data Array of data in $key => $value format.
	 * 
	public function setConfigData(array $data);
	 *
	 */
	 	
}

/**
 * Base interface for all ProcessWire Modules
 *
 */
interface Module {

	/**
	 * Method to initialize the module. 
	 *
	 * While the method is required, if you don't need it, then just leave the implementation blank.
	 *
	 * This is called after ProcessWire's API is fully ready for use and hooks
	 *
	 */
	public function init();

	/**
	 * Return an array of module information
	 *
	 * Array is associative with the following fields: 
	 * - title: An alternate title, if you don't want to use the class name.
	 * - version: an integer that indicates the version number, 101 = 1.0.1
	 * - summary: a summary of the module (1 sentence to 1 paragraph max)
	 * - permanent: boolean, true only if the module is permanent and uninstallable
	 * - href: URL to more information about the module
	 * - singular: is only one instance of this module allowed? return boolean true or false.
	 * 	If specified, this overrides the isSingular() method, if that method exists in your class.
	 * 	See the information for the isSingular() method for more about the 'singular' property.
	 * - autoload: should this module load automatically at application start? return boolean true or false. 
	 *	If specified, this overrides the isAutoload() method, if that method exists in your class. 
	 *	See the information for the isAutoload() method for more about the 'autoload' property.
	 * This method is OPTIONAL, which is why it's commented out below. 
	 *
	 * @return array
	 *
	 */
	public static function getModuleInfo(); 

	/**
	 * Returns the class name of this Module instance
	 *
	 * If your Module descends from Wire, or any of it's derivatives (as would usually be the case), 
	 * then you don't need to implement this method as it's already present. 
	 *
	 * If you do need to implement this, then it can be just: "return get_class($this);"
	 *
	 * This method is required in this interface because ProcessWire may sometimes substitute placeholders
	 * for a module that need to respond with the non-placeholder classname. PHP's get_class won't work,
	 * which is why this method is required instead. 
	 *
	 * DEPRECATED / NO LONGER REQUIRED, EXCEPT BY ModulePlaceholder
	 *
	public function className();
	 */

	/**
	 * Perform any installation procedures specific to this module, if needed. 
	 *
	 * Note that the Modules class handles install/uninstall into the modules table. 
	 * The Modules class calls this install method right before performing the install. 
	 *
	 * This method is OPTIONAL, which is why it's commented out below. 
	 *
	public function ___install();
	 */

	/**
	 * Perform any uninstall procedures specific to this module, if needed. 
	 *
	 * Note that the Modules class handles the uninstall from the modules table. 
	 * It calls this uninstall method right before completing the uninstall. 
	 *
	 * This method is OPTIONAL, which is why it's commented out below. 
	 *
	public function ___uninstall();
	 */


	/**
	 * Is this module intended to be only a single instance?
	 *
	 * This method is OPTIONAL and therefore commented out in this interface. If ommitted, it is assumed that
	 * you are providing this inforamtion in the getModuleInfo() method. 
	 *
	 * Using this method is useful if you have multiple descending classes that you want to provide a default
	 * value for, rather than having each descending class specify it individually in the getModuleInfo(). 
	 * For example, Fieldtype or Inputfield modules. 
	 *
	 * Return true if this module is a single reusable instance, returning the same instance on every call from Modules. 
	 * Return false if this module should return a new instance on every call from Modules.
	 *
	 * Modules that attach hooks should be singular. 
	 * Modules that may have multiple instances (like Inputfields) should not be singular. 
	 *
	 * @return bool
	 *
	public function isSingular();
	 */

	/**
	 * Is this module automatically loaded at runtime?
	 * 
	 * This method is OPTIONAL and therefore commented out in this interface. If ommitted, it is assumed that
	 * you are providing this inforamtion in the getModuleInfo() method. 
	 *
	 * Using this method is useful if you have multiple descending classes that you want to provide a default
	 * value for, rather than having each descending class specify it individually in the getModuleInfo(). 
	 * For example, Fieldtype or Inputfield modules. 
	 *
	 * Return true if this module is automatically instantiated (and it's init method called) at runtime.
	 * Return false if this module must be requested before it is loaded, instantiated, and init'd.  
	 *
	 * Modules that are intended to attach hooks in the application typically should be autoloaded because
	 * they listen in to classes rather than have classes call upon them. If they weren't autoloaded, then
	 * they might never get to attach their hooks. 
	 *
	 * Modules that shouldn't be autoloaded are those that may or may not be needed at runtime, for example
	 * Fieldtypes and Inputfields.
	 *	
	public function isAutoload(); 
	 */ 


}

