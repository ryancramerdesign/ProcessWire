<?php

/**
 * ProcessWire UserPage
 *
 * A type of Page used for storing an individual User
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class User extends Page { 

	/**
	 * Create a new User page in memory. 
	 *
	 * @param Template $tpl Template object this page should use. 
	 *
	 */
	public function __construct(Template $tpl = null) {
		if(is_null($tpl)) $tpl = $this->fuel('templates')->get('user'); 
		$this->parent = $this->fuel('pages')->get($this->fuel('config')->usersPageID); 
		parent::__construct($tpl); 
	}

	/**
	 * Does this user have the given role?
	 *
	 * @param string|Role|int
	 * @return bool
	 *
	 */
	public function hasRole($role) {
		if(is_object($role) && $role instanceof Role) return $this->roles->has($role); 
		if(ctype_digit("$role")) return $this->roles->has("id=$role"); 
		if(is_string($role)) return $this->roles->has("name=$role"); 
		return false;
	}

	/**
	 * Does the user have the given permission, OR the given permission in the given context?
	 *
	 * Context may be a Page or a Template. 
	 * This method serves as the public interface to the hasPagePermission and hasTemplatePermission methods.
	 *
	 * @param string|Permission $name Permission name
	 * @param Page|Template $context Page or Template
	 * @return bool
	 *
	 */
	public function hasPermission($name, $context = null) {
		if(is_null($context) || $context instanceof Page) return $this->hasPagePermission($name, $context); 
		if($context instanceof Template) return $this->hasTemplatePermission($name, $context); 
		return false;
	}

	/**
	 * Does this user have the given permission name?
	 *
 	 * This only indicates that the user has the permission, and not where they have the permission.
	 *
	 * @param string|Permission
	 * @param Page $page Optional page to check against
	 * @return bool
	 *
	 */
	protected function hasPagePermission($name, Page $page = null) {

		if($this->isSuperuser()) return true; 

		if($name instanceof Page) {
			$permission = $name; 
		} else {
			$permission = $this->fuel('permissions')->get("name=$name"); 
		}

		$has = false; 

		foreach($this->roles as $key => $role) {
			if(!$role || !$role->id) continue; 
			if(!is_null($page)) {
				if(!$page->id || !$page->hasAccessRole($role)) continue; 
			}
			if($role->hasPermission($permission)) { 
				$has = true;
				break;
			}
		}

		return $has; 
	}

	/**
	 * Does this user have the given permission on the given template?
	 *
	 * @param string $name Permission name
	 * @param Template|int|string Template object, name or ID
	 * @return bool
	 *
	 */
	protected function hasTemplatePermission($name, $template) {

		if($name instanceof Template) $name = $name->name; 
		if(is_object($name)) throw new WireException("Invalid type"); 

		if($this->isSuperuser()) return true; 

		if($template instanceof Template) {
			// fantastic then
		} else if(is_string($template) || is_int($template)) {
			$template = $this->templates->get($this->sanitizer->name($template)); 
			if(!$template) return false;
		} else {
			return false;
		}

		// if the template is not defining roles, we have to say 'no' to permission
		// because we don't have any page context to inherit from at this point
		if(!$template->useRoles) return false; 

		$has = false;

		foreach($this->roles as $role) {
			if(!$template->hasRole($role)) continue; 
			if($role->hasPermission($name)) {
				$has = true;
				break;
			}
		}

		return $has; 
	}

	/**
	 * Does this user have page-add permission to the given page?
	 *
	 * This is a special case for the 'page-add' permission, because it has to 
	 * check that the permission is on the access template's addRoles field.
	 *
	 * @param Page $page
	 * @return bool
	 *	
	public function hasPageAddPermission(Page $page) {

		$has = false;
		$template = $page->getAccessTemplate();	

		$role_ids = array();
		foreach($this->roles as $role) $role_ids[] = $role->id;

		foreach($template->addRoles as $role_id) {
			if(in_array($role_id, $role_ids)) {
				$has = true;
				break;
			}
		}

		return $has;
	}
	 */

	/**
	 * Get this user's permissions, optionally within the context of a Page
	 *
	 * @param Page $page Optional page to check against
	 * @return bool
	 *
	 */
	public function getPermissions(Page $page = null) {
		if($this->isSuperuser()) return $this->fuel('permissions'); 
		$permissions = new PageArray();
		foreach($this->roles as $key => $role) {
			if($page && !$page->hasAccessRole($role)) continue; 
			foreach($role->permissions as $permission) { 
				$permissions->add($permission); 
			}
		}
		return $permissions; 
	}

	public function isSuperuser() {
		$config = $this->fuel('config');
		if($this->id === $config->superUserPageID) return true; 
		if($this->id === $config->guestUserPageID) return false;
		$superuserRoleID = $config->superUserRolePageID; 
		$is = false;
		foreach($this->roles as $role) if($role->id == $superuserRoleID) {
			$is = true;
			break;
		}
		return $is;
	}

	public function isGuest() {
		return $this->id === $this->fuel('config')->guestUserPageID; 
	}

	public function isLoggedin() {
		return !$this->isGuest();
	}

}

