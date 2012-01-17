<?php

/**
 * ProcessWire Module Interface
 *
 * Provides the base interfaces required by modules. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
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
	 * Return an array of module information
	 *
	 * Example:
	 * 
	 * 	public static function getModuleInfo() {
	 * 		return array(
	 * 			'title' => 'Your Module's Title',
	 * 			'version' => 100,
	 *			'author' => 'Ryan Cramer',
	 * 			'summary' => 'Description of what this module does and who made it.',
	 * 			'href' => 'http://www.domain.com/info/about/this/module/', 
	 * 			'singular' => false,
	 *			'autoload' => false,
	 *			'requires' => array('HelloWorld', 'LazyCron'), 
	 *			'installs' => array('Module1', 'Module2', 'Module3'),
	 * 			);
	 * 	}
	 * 
	 *
	 * The retured array is associative with the following fields. Fields bulleted with a "+" are required and 
 	 * fields bulleted with a "-" are optional, and fields bulleted with a "*" may be optional (see details): 
	 * 
	 * 	+ title: The module's title. 
	 * 	+ version: an integer that indicates the version number, 101 = 1.0.1
	 * 	+ summary: a summary of the module (1 sentence to 1 paragraph reommended)
	 * 	- href: URL to more information about the module. 
	 *	- requires: array of module class names that are required by this module in order to install.
	 * 		If just one module is required, then it can also be a string with just the module name.
	 *	- installs: array of module class names that this module will handle install/uninstall.
	 *		This causes PW's dependency checker to ignore them and it is assumed your module will handle them (optional).	
	 * 		If your module does not handle them, PW will automatically install/uninstall them immediately after your module.
	 *		Like requires, this may be a string if there's only one and must be an array if multiple.
	 * 	* singular: is only one instance of this module allowed? return boolean true or false.
	 * 		If specified, this overrides the isSingular() method, if that method exists in your class.
	 * 		See the information for the isSingular() method for more about the 'singular' property.
	 * 		If you don't provide an isSingular() method, then you should provide this property here. 
	 * 	* autoload: should this module load automatically at application start? return boolean true or false. 
	 *		If specified, this overrides the isAutoload() method, if that method exists in your class. 
	 *		See the information for the isAutoload() method for more about the 'autoload' property.
	 * 		If you don't provide an isAutoload() method, then you should provide this property here. 
	 * 	- permanent: boolean, for core only. True only if the module is permanent and uninstallable. 
	 *		This is true only of PW core modules, so leave this out elsewhere.
	 *
	 * @return array
	 *
	 */
	public static function getModuleInfo(); 

	/**
	 * Method to initialize the module. 
	 *
	 * While the method is required, if you don't need it, then just leave the implementation blank.
	 *
	 * This is called after ProcessWire's API is fully ready for use and hooks. It is called at the end of the 
	 * bootstrap process. This is before PW has started retrieving or rendering a page. If you need to have the
	 * API ready with the $page ready as well, then see the ready() method below this one. 
	 *
	 */
	public function init();

	/**
	 * Method called when API is fully ready and the $page is determined and set, but before a page is rendered.
	 *
	 * Optional and only called if it exists in the module. 
	 *
	public function ready();
	 */

	/**
	 * Returns the class name of this Module instance
	 *
	 * If your Module descends from Wire, or any of it's derivatives (as would usually be the case), 
	 * then you don't need to implement this method as it's already present. 
	 *
	 * If you do want to implement this, then it can be just: "return get_class($this);"
	 *
	 * ProcessWire may sometimes substitute placeholders for a module that need to respond with the 
	 * non-placeholder classname. PHP's get_class won't work, which is why this method is used instead. 
	 * This method is only required for PW internal classes. As a result, implementation is optional for others.
	 *
	public function className();
	 */

	/**
	 * Perform any installation procedures specific to this module, if needed. 
	 *
	 * The Modules class calls this install method right after performing the install. 
	 * 
	 * If this method throws an exception, PW will catch it, remove it from the installed module list, and
	 * report that the module installation failed. You may specify details about why with the exception, i.e.
	 * throw new WireException("Can't install because of ..."); 
	 *
	 * This method is OPTIONAL, which is why it's commented out below. 
	 *
	public function ___install();
	 */

	/**
	 * Perform any uninstall procedures specific to this module, if needed. 
	 *
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
	 * you are providing this inforamtion in the getModuleInfo() method, documented above.
	 *
	 * A module that returns TRUE is referred to as a "singular" module, because there will never be any more
	 * than a single instance of the module running. 
	 *
	 * Why you'd use this method over the property in the getModuleInfo() method: 
	 * Using this method is useful if you have multiple descending classes that you want to provide a default
	 * value for, rather than having each descending class specify it individually in the getModuleInfo(). 
	 * For example, Fieldtype or Inputfield modules. 
	 *
	 *
	 * Return TRUE if this module is a single reusable instance, returning the same instance on every call from Modules. 
	 * Return FALSE if this module should return a new instance on every call from Modules.
	 * 
	 * 	- Singular modules will have their instance active for the entire request after instantiated. 
	 * 	- Non-singular modules return a new instance on every $modules->get("YourModule") call. 
	 * 	- Modules that attach hooks are usually singular. 
	 * 	- Modules that may have multiple instances (like Inputfields) should NOT be singular. 
	 *
	 * If you are having trouble deciding whether to make your module singular or not, be sure to read the documentation
	 * below for the isAutoload() method, because if your module is 'autoload' then it's probably also 'singular'.
	 * 
	 * @return bool
	 * 
	 * This method may be optional, which is why it's commented out below. 
	 *
	public function isSingular();
	 */

	/**
	 * Is this module automatically loaded at runtime?
	 * 
	 * This method is OPTIONAL and therefore commented out in this interface. If ommitted, it is assumed that
	 * you are providing this information in the getModuleInfo() method. 
	 *
	 * A module that returns TRUE is referred to as an "autoload" module, because it automatically loads as
	 * part of ProcessWire's boot process. Autoload modules load before PW attempts to handle the web request.
	 *
	 * Return TRUE if this module is automatically loaded [and it's init() method called] at runtime.
	 * Return FALSE if this module must be requested [via the $modules->get('ModuleName') method] before it is loaded.
	 *
	 * Modules that are intended to attach hooks in the application typically should be autoloaded because
	 * they listen in to classes rather than have classes call upon them. If they weren't autoloaded, then
	 * they might never get to attach their hooks. 
	 *
	 * Modules that shouldn't be autoloaded are those that may or may not be needed at runtime, for example
	 * Fieldtypes and Inputfields.
	 *
	 * As a side note, I can't think of any reason why a non-singular module would ever be autoload. The fact that
	 * an autoload module is automatically loaded as part of PW's boot process implies it's probably going to be the
 	 * only instance running. So if you've decided to make your module 'autoload', then is safe to assume you've
	 * also decided your module will also be singular (if that helps anyone reading this). 
	 * 
	 * Why you'd use this method over the property in the getModuleInfo() method: 
	 * Using this method is useful if you have multiple descending classes that you want to provide a default
	 * value for, rather than having each descending class specify it individually in the getModuleInfo(). 
	 * For example, Fieldtype or Inputfield modules. 
	 *
	 *
	 * @return bool
	 *
	 * This method may be optional, which is why it's commented out below. 
	 *	
	public function isAutoload(); 
	 */ 


}

