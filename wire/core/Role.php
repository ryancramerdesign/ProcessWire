<?php

/**
 * ProcessWire Role Page
 *
 * A type of Page used for storing an individual Role
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Role extends Page { 

	/**
	 * Does this role have the given permission name or object?
	 *
	 * @param string|Permission
	 * @return bool
	 *
	 */
	public function hasPermission($name) {

		if($name instanceof Page) {
			$permission = $name; 
		} else {
			$permission = $this->fuel('permissions')->get("name=$name"); 
		}

		return $this->permissions->has($permission); 
	}

}

