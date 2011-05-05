<?php

/**
 * ProcessWire ProcessController
 *
 * Loads and executes Process Module instance and determines access.
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
 * Exception thrown when a requested Process or Process method is requested that doesn't exist
 *
 */
class ProcessController404Exception extends Wire404Exception { }

/**
 * Exception thrown when the user doesn't have access to execute the requested Process
 *
 */
class ProcessControllerPermissionException extends WirePermissionException { } 

/**
 * A Controller for Process* Modules
 *
 * Intended to be used by templates that call upon Process objects
 *
 */
class ProcessController extends Wire {

	/**
	 * The default method called upon when no method is specified in the request
	 *
	 */
	 const defaultProcessMethodName = 'execute';

	/**
	 * The Process instance to execute
	 *
	 */
	protected $process; 

	/**
	 * The name of the Process to execute (string)
	 *
	 */
	protected $processName; 

	/**
	 * The name of the method to execute in this process
	 *
	 */ 
	protected $processMethodName; 

	/**
	 * The prefix to apply to the Process name
	 *
	 * All related Processes would use the same prefix, i.e. "Admin"
	 *
	 */
	protected $prefix; 

	/**
	 * Construct the ProcessController
	 *
	 */
	public function __construct() {
		$this->prefix = 'Process';
		$this->processMethodName = ''; // blank indicates default/index method
	}

	/**
	 * Set the Process to execute. 
	 *
	 */
	public function setProcess(Process $process) {
		$this->process = $process; 
	}

	/**
	 * Set the name of the Process to execute. 
	 *
	 * No need to call this unless you want to override the one auto-determined from the URL.
	 *
	 * If overridden, then make sure the name includes the prefix, and don't bother calling the setPrefix() method. 
	 *
	 */
	public function setProcessName($processName) {
		$this->processName = $this->sanitizer->name($processName); 
	}

	/**
	 * Set the name of the method to execute in the Process
	 *
	 * It is only necessary to call this if you want to override the default behavior. 
	 * The default behavior is to execute a method called "execute()" OR "executeSegment()" where "Segment" is the last URL segment in the request URL. 
	 *
	 */
	public function setProcessMethodName($processMethod) {
		$this->processMethod = $this->sanitizer->name($processMethod); 
	}

	/**
	 * Set the class name prefix used by all related Processes
	 *
	 * This is prepended to the class name determined from the URL. 
	 * For example, if the URL indicates a process name is "PageEdit", then we would need a prefix of "Admin" to fully resolve the class name. 
	 *
	 */
	public function setPrefix($prefix) {
		$this->prefix = $this->sanitizer->name($prefix); 
	}

	/**
	 * Determine and return the Process to execute
	 *
	 */
	public function getProcess() {

		if($this->process) $processName = $this->process->className();
			else if($this->processName) $processName = $this->processName; 
			else return null; 

		// verify that there is adequate permission to execute the Process
		$permissionName = '';
		$info = $this->modules->getModuleInfo($processName); 
		if(!empty($info['permission'])) $permissionName = $info['permission']; 

		$this->hasPermission($permissionName, true); // throws exception if no permission
		if(!$this->process) $this->process = $this->modules->get($processName); 

		// set a proces fuel, primarily so that certain Processes can determine if they are the root Process 
		// example: PageList when in PageEdit
		$this->setFuel('process', $this->process); 

		return $this->process; 
	}

	/**
	 * Does the current user have permission to execute the given process name?
	 *
	 * Note: an empty permission name is accessible only by the superuser
	 *
	 * @param string $processName
	 * @param bool $throw Whether to throw an Exception if the user does not have permission
	 * @return bool
	 *
	 */
	protected function hasPermission($permissionName, $throw = true) {
		$user = $this->fuel('user'); 
		if($user->isSuperuser()) return true; 
		if($permissionName && $user->hasPermission($permissionName)) return true; 
		if($throw) throw new ProcessControllerPermissionException("You don't have $permissionName permission"); 
		return false; 
	}

	/**
	 * Get the name of the method to execute with the Process
	 *
	 */
	public function getProcessMethodName(Process $process) {

		$method = $this->processMethodName;

		if(!$method) {
			$method = self::defaultProcessMethodName; 
			// urlSegment as given by ProcessPageView 
			if($this->input->urlSegment1 && !$this->user->isGuest()) $method .= ucfirst($this->input->urlSegment1); 
		}

		$hookedMethod = "___$method";
		
		if(method_exists($process, $method) || method_exists($process, $hookedMethod)) return $method; 
			else return '';

	}

	/**
	 * Execute the process and return the resulting content generated by the process
	 *	
	 */
	public function ___execute() {

		$content = '';

		if($process = $this->getProcess()) { 
			if($method = $this->getProcessMethodName($this->process)) {
				$content = $this->process->$method();
			} else {
				throw new ProcessController404Exception("Unrecognized path");
			}

		} else throw new ProcessController404Exception("The requested process does not exist");

		return $content; 
	}

	/**
	 * Generate a message in JSON format, for use with AJAX output
	 *
	 */
	public function jsonMessage($msg, $error = false) {
		return json_encode(array(
			'error' => $error, 
			'message' => $msg
		)); 
	}

	/**
	 * Is this an AJAX request?
	 *
	 */
	public function isAjax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
	}

}	



