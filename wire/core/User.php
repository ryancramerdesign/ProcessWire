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
 *
 * @link http://processwire.com/api/variables/user/ Offical $user API variable Documentation
 *
 * @property string email Get or set email address for this user.
 * @property string pass Set the user's password. Note that when getting, this returns a hashed version of the password, so it is not typically useful to get this property. However, it is useful to set this property if you want to change the password. When you change a password, it is assumed to be the non-hashed/non-encrypted version. ProcessWire will hash it automatically when the user is saved.
 * @property PageArray roles Get roles this user has. Returns PageArray.
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
	 * Does this user have the given role? (object, name or id)
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
	 * Add the given role string, id or object
	 *
	 * This is the same as $user->roles->add($role) except this one will accept ID or name.
	 *
	 * @param string|int|Role
	 * @return bool false if role not recognized, true otherwise
	 *
	 */
	public function addRole($role) {
		if(is_string($role) || is_int($role)) $role = $this->fuel('roles')->get($role); 
		if(is_object($role) && $role instanceof Role) {
			$this->roles->add($role); 
			return true; 
		}
		return false;
	}

	/**
	 * Remove the given role string, id or object
	 *
	 * This is the same as $user->roles->remove($role) except this one will accept ID or name.
	 *
	 * @param string|int|Role
	 * @return bool false if role not recognized, true otherwise
	 *
	 */
	public function removeRole($role) {
		if(is_string($role) || is_int($role)) $role = $this->fuel('roles')->get($role); 
		if(is_object($role) && $role instanceof Role) {
			$this->roles->remove($role); 
			return true; 
		}
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
	 * This is a basic permission check and it is recommended that you use those from the PagePermissions module instead. 
	 * You use the PagePermissions module by calling the editable(), addable(), etc., functions on a page object. 
	 * The PagePermissions does use this function for some of it's checking. 
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
			$p = $name; 
			// page-add and page-create don't actually exist in the DB, so we substitute page-edit for them 
			// code later on will make sure they exist in the template's addRoles/createRoles
			if(in_array($name, array('page-add', 'page-create'))) $p = 'page-edit';
			$permission = $this->fuel('permissions')->get("name=$p"); 
		}

		if(!$permission || !$permission->id) return false;

		$has = false; 
		$accessTemplate = is_null($page) ? null : $page->getAccessTemplate();

		foreach($this->roles as $key => $role) {

			if(!$role || !$role->id) continue; 

			if(!is_null($page)) {
				if(!$page->id) continue;  

				// if page doesn't have the 'view' role, then no access
				if(!$page->hasAccessRole($role)) continue; 

				// if permission is page-edit, we also check against the template's editRoles
				// if($name == 'page-edit' && !in_array($role->id, $page->getAccessTemplate()->editRoles)) continue; 

				// all page- permissions except page-view and page-add require page-edit access on $page, so check against that
				if(strpos($name, 'page-') === 0 && $name != 'page-view' && $name != 'page-add' && !in_array($role->id, $accessTemplate->editRoles)) continue;

				// check against addRoles, createRoles if the permission requires it
				if($name == 'page-add' && !in_array($role->id, $accessTemplate->addRoles)) continue;
					else if($name == 'page-create' && !in_array($role->id, $accessTemplate->createRoles)) continue;
					//else if($name == 'page-delete' && !in_array($role->id, $accessTemplate->deleteRoles)) continue;
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
		// if(!$template->useRoles) return false; 

		$has = false;

		foreach($this->roles as $role) {

			if(!$template->hasRole($role)) continue; 

			if($name == 'page-create') { 
				if(!in_array($role->id, $template->createRoles)) continue; 
				$name = 'page-edit'; // swap permission to page-edit since create managed at template and requires page-edit
			}

			if($name == 'page-edit' && !in_array($role->id, $template->editRoles)) continue; 

			if($name == 'page-add' && !in_array($role->id, $template->addRoles)) continue; 

			if($role->hasPermission($name)) {
				$has = true;
				break;
			}
		}

		return $has; 
	}

	/**
	 * Get this user's permissions, optionally within the context of a Page
	 *
	 * Does not currently include page-add or page-create permissions. 
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
				if($page && $permission->name == 'page-edit' && !in_array($role->id, $page->getAccessTemplate()->editRoles)) continue; 
				$permissions->add($permission); 
			}
		}
		return $permissions; 
	}

	/**
	 * Does this user have the superuser role?
	 *
	 * Same as $user->roles->has('name=superuser'); 
	 *
	 * @return bool
	 *
	 */
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

	/**
	 * Is this the non-logged in guest user? 
	 *
	 * @return bool
	 *
	 */ 
	public function isGuest() {
		return $this->id === $this->fuel('config')->guestUserPageID; 
	}

	/**
	 * Is the current user logged in?
	 *
	 * @return bool
	 *
	 */
	public function isLoggedin() {
		return !$this->isGuest();
	}

	public function get($key) {
		return parent::get($key); 
	}

	public function __get($key) {
		return parent::get($key); 
	}

}

