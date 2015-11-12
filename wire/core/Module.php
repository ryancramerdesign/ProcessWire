<?php

/**
 * ProcessWire Module Interface
 *
 * Provides the base interfaces required by modules.
 * 
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 * https://processwire.com
 * 
 * 
 * About the Module interface
 * ==========================
 * This interface doesn't actually require any specific methods,
 * but is required as an interface to state your intention to 
 * ProcessWire that your class is to be used as a Module. 
 * 
 * Requirements for classes using Module interface
 * ===============================================
 * 1. Must provide a means of getting information about the module.
 *    This can be with a static getModuleInfo() method, or with
 *    a ModuleName.info.php file or a MdouleName.info.json file. 
 *    See implementation details in the notes within the interface.
 * 2. Must provide a className() method that returns the module 
 *    class name. All Wire derived objects already do this, so 
 *    you don't have to provide it unless your Module does not
 *    descend from a ProcessWire class. We recommend that your
 *    modules extend the WireData class. 
 * 3. If you have a __construct() method, it must not require any
 *    particular arguments. 
 * 4. If your module is configurable, it must also fulfill the
 *    ConfigurableModule interface. 
 * 
 * Optional methods for Module interface
 * =====================================
 * 1. __construct() - called before module config is populated.
 * 2. init() - called after module config is populated.
 * 3. ready() - called after init(), after API ready.
 *    Note that ready() applies to 'autoload' modules only. 
 * 4. ___install() - called when module is installed. 
 * 5. ___uninstall() - called when module is uninstalled. 
 * 6. ___upgrade($fromVersion, $toVersion) - called on version change.
 * 
 * For more details on these methods, see comments within the 
 * interface below.
 * 
 *
 */

interface Module {

	/**
	 * Return an array of module information
	 * 
	 * This array of information may be returned in 1 of 3 ways: 
	 * 	1. By a static method in your module class named getModuleInfo().
	 * 	2. By a YourModuleClass.info.php file that populates an $info array.
	 * 	3. By a YourModuleClass.info.json file that contains an info object. 
	 * 
	 * Each of these are demonstrated below: 
	 *
	 * 1. Using a static getModuleInfo method:  
	 * ---------------------------------------
	 * 
	 * 	public static function getModuleInfo() {
	 * 		return array(
	 * 			'title' => 'Your Module Title',
	 * 			'version' => 100,
	 *			'author' => 'Ryan Cramer',
	 * 			'summary' => 'Description of what this module does and who made it.',
	 * 			'href' => 'http://www.domain.com/info/about/this/module/', 
	 * 			'singular' => false,
	 *			'autoload' => false,
	 *			'requires' => array('HelloWorld>=1.0.1', 'PHP>=5.4.1', 'ProcessWire>=2.4.1'), 
	 *			'installs' => array('Module1', 'Module2', 'Module3'),
	 * 			);
	 * 	}
	 * 
	 * 
	 * 2. Using a YourModuleClass.info.php file: 
	 * -----------------------------------------
	 * 
	 * Your file should populate an $info variable with an array exactly like described for #1 above, i.e. 
	 * 
	 * 	$info = array(
	 * 		'title' => 'Your Module Title', 
	 * 		'version' => 100, 
	 * 		// and so on, like the static version above 
	 * 		);
	 * 
	 * 3. Using a YourModuleClass.info.json file: 
	 * ------------------------------------------
	 * 
	 * Your JSON file should contain nothing but an object/map of the module info: 
	 * 
	 * 	{
	 * 		title: 'Your Module title',
	 * 		version: '100,
	 * 		// and so on
	 * 	}
	 * 
	 * 
	 * More details about the data your module info should return
	 * ----------------------------------------------------------
	 *
	 * The retured array is associative with the following fields. Fields bulleted with a "+" are required and 
 	 * fields bulleted with a "-" are optional, and fields bulleted with a "*" may be optional (see details): 
	 * 
	 * 	+ title: The module's title. 
	 * 	+ version: an integer that indicates the version number, 101 = 1.0.1
	 * 	+ summary: a summary of the module (1 sentence to 1 paragraph recommended)
	 * 	- href: URL to more information about the module. 
	 *	- requires: array or CSV string of module class names that are required by this module in order to install.
	 *		--
	 *		Requires Module version: If a particular version of the module is required, then specify an operator
	 *		and version number after the module name, like this: HelloWorld>=1.0.1
	 *		--
	 *		Requires PHP version: If a particular version of PHP is required, then specify 'PHP' as the module name
	 *		followed by an operator and required version number, like this: PHP>=5.3.8
	 *		--
	 *		Requires ProcessWire version: If a particular version of ProcessWire is required, then specify 
	 *		ProcessWire followed by an operator and required version number, like this: ProcessWire>=2.4.1
	 *	
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
	 *  - permission: name of permission required of a user before PW will instantiate the module.
	 *      Note that PW will not install this permission if it doesn't yet exist. To have it installed automatically,
	 *      see the 'permissions' option below this. 
	 *  - permissions: array of permissions that PW will install (and uninstall) automatically.
	 *      Permissions should be in the format: array('permission-name' => 'Permission description')
	 *  - icon: optional icon name (string) to represent this module
	 * 		Currently uses font-awesome icon names as seen at http://fortawesome.github.io/Font-Awesome/
	 * 		Omit the "fa-" part, leaving just the icon name.
	 *
	 * @return array
	 *
	public static function getModuleInfo(); 
	 */

	/**
	 * Method to initialize the module. 
	 *
	 * While the method is required, if you don't need it, then just leave the implementation blank.
	 *
	 * This is called after ProcessWire's API is fully ready for use and hooks. It is called at the end of the 
	 * bootstrap process. This is before PW has started retrieving or rendering a page. If you need to have the
	 * API ready with the $page ready as well, then see the ready() method below this one. 
	 *
	public function init();
	 */

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
	 * Called when a version change is detected on the module
	 * 
	public function ___upgrade($fromVersion, $toVersion);
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

