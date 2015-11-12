<?php

/**
 * ProcessWire UserPage
 *
 * A type of Page used for storing an individual User
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 *
 * @link https://processwire.com/api/variables/user/ Offical $user API variable Documentation
 *
 * @property string email Get or set email address for this user.
 * @property string pass Set the user's password. Note that when getting, this returns a hashed version of the password, so it is not typically useful to get this property. However, it is useful to set this property if you want to change the password. When you change a password, it is assumed to be the non-hashed/non-encrypted version. ProcessWire will hash it automatically when the user is saved.
 * @property PageArray roles Get roles this user has. Returns PageArray.
 * @property Language $language User language, applicable only if LanguageSupport installed.
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
		if(is_null($tpl)) $tpl = $this->wire('templates')->get('user'); 
		if(!$this->parent_id) $this->set('parent_id', $this->wire('config')->usersPageID); 
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
		
		$roles = $this->get('roles');
		$has = false; 
		
		if(empty($roles)) {
			// do nothing
			
		} else if(is_object($role) && $role instanceof Page) {
			$has = $roles->has($role); 
			
		} else if(ctype_digit("$role")) {
			$role = (int) $role; 
			foreach($roles as $r) {
				if(((int) $r->id) === $role) {
					$has = true; 
					break;
				}
			}
			
		} else if(is_string($role)) {
			foreach($roles as $r) {
				if($r->name === $role) {
					$has = true;
					break;
				}
			}
		}
		
		return $has;
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
			$this->get('roles')->add($role); 
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
			$this->get('roles')->remove($role); 
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
		if(is_null($context) || $context instanceof Page) {
			return $this->isHooked('hasPagePermission()') ? $this->hasPagePermission($name, $context) : $this->___hasPagePermission($name, $context);
		}
		if($context instanceof Template) {
			return $this->isHooked('hasTemplatePermission()') ? $this->hasTemplatePermission($name, $context) : $this->___hasTemplatePermission($name, $context); 
		}
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
	protected function ___hasPagePermission($name, Page $page = null) {

		if($this->isSuperuser()) return true; 
		$permissions = $this->wire('permissions');

		// convert $name to a Permission object (if it isn't already)
		if($name instanceof Page) {
			$permission = $name;
		} else if(ctype_digit("$name")) {
			$permission = $permissions->get((int) $name);
		} else if($name == 'page-rename') {
			// optional permission that, if not installed, page-edit is substituted for
			if($this->wire('permissions')->has('page-rename')) {
				$permission = $permissions->get('page-rename');
			} else {
				$permission = $permissions->get('page-edit');
			}
		} else {
			if($name == 'page-add' || $name == 'page-create') {
				// page-add and page-create don't actually exist in the DB, so we substitute page-edit for them 
				// code later on will make sure they exist in the template's addRoles/createRoles
				$p = 'page-edit';
			} else if(!$permissions->has($name)) {
				$delegated = $permissions->getDelegatedPermissions();
				$p = isset($delegated[$name]) ? $delegated[$name] : $name;
			} else {
				$p = $name;
			}
			$permission = $permissions->get($p); 
		}

		if(!$permission || !$permission->id) return false;

		$roles = $this->get('roles'); 
		if(empty($roles) || !$roles instanceof PageArray) return false; 
		$has = false; 
		$accessTemplate = is_null($page) ? false : $page->getAccessTemplate($permission->name);
		if(is_null($accessTemplate)) return false;

		foreach($roles as $key => $role) {

			if(!$role || !$role->id) continue; 
			$context = null;

			if(!is_null($page)) {
				// @todo some of this logic has been duplicated in Role::hasPermission, so code within this if() may be partially redundant
				if(!$page->id) continue;  

				// if page doesn't have the 'view' role, then no access
				if(!$page->hasAccessRole($role, $name)) continue; 

				// all page- permissions except page-view and page-add require page-edit access on $page, so check against that
				if(strpos($name, 'page-') === 0 && $name != 'page-view' && $name != 'page-add') {
					if($accessTemplate && !in_array($role->id, $accessTemplate->editRoles)) continue; 
				}

				// check against addRoles, createRoles if the permission requires it
				if($name == 'page-add') {
					if($accessTemplate && !in_array($role->id, $accessTemplate->addRoles)) continue;
				} else if($name == 'page-create') {
					if($accessTemplate && !in_array($role->id, $accessTemplate->createRoles)) continue;
				} else {
					// some other page-* permission, check against context of access template
					$context = $accessTemplate ? $accessTemplate : $page;
				}
			}

			if($role->hasPermission($permission, $context)) { 
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
	 * @param Template|int|string $template Template object, name or ID
	 * @return bool
	 * @throws WireException
	 *
	 */
	protected function ___hasTemplatePermission($name, $template) {
		
		if($name instanceof Template) $name = $name->name; 
		if(is_object($name)) throw new WireException("Invalid type"); 

		if($this->isSuperuser()) return true; 

		if($template instanceof Template) {
			// fantastic then
		} else if(is_string($template) || is_int($template)) {
			$template = $this->templates->get($this->wire('sanitizer')->name($template)); 
			if(!$template) return false;
		} else {
			return false;
		}

		// if the template is not defining roles, we have to say 'no' to permission
		// because we don't have any page context to inherit from at this point
		// if(!$template->useRoles) return false; 

		$roles = $this->get('roles'); 
		if(empty($roles)) return false; 
		$has = false;

		foreach($roles as $role) {
			// @todo much of this logic has been duplicated in Role::hasPermission, so code within this foreach() may be partially redundant

			if(!$template->hasRole($role)) continue; 

			if($name == 'page-create') { 
				if(!in_array($role->id, $template->createRoles)) continue; 
				$name = 'page-edit'; // swap permission to page-edit since create managed at template and requires page-edit
			}
			
			if($name == 'page-edit' && !in_array($role->id, $template->editRoles)) {
				continue;
			}

			if($name == 'page-add') {
				if(!in_array($role->id, $template->addRoles)) continue;
				$name = 'page-edit';
			}

			$context = null;
			if($name != 'page-edit' && $name != 'page-add' && $name != 'page-create' && $name != 'page-view') {
				if(strpos($name, "page-") === 0) $context = $template;
			}

			if($role->hasPermission($name, $context)) {
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
		if($this->isSuperuser()) return $this->wire('permissions')->getIterator(); // all permissions
		$permissions = new PageArray();
		$roles = $this->get('roles'); 
		if(empty($roles)) return $permissions; 
		foreach($roles as $key => $role) {
			if($page && !$page->hasAccessRole($role)) continue; 
			foreach($role->permissions as $permission) { 
				if($page && $permission->name == 'page-edit') {
					$accessTemplate = $page->getAccessTemplate('edit');
					if(!$accessTemplate) continue;
					if(!in_array($role->id, $accessTemplate->editRoles)) continue; 
				}
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
		$config = $this->wire('config');
		if($this->id === $config->superUserPageID) return true; 
		if($this->id === $config->guestUserPageID) return false;
		$superuserRoleID = (int) $config->superUserRolePageID; 
		$roles = $this->get('roles');
		if(empty($roles)) return false;
		$is = false;
		foreach($roles as $role) if(((int) $role->id) === $superuserRoleID) {
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
		return $this->id === $this->wire('config')->guestUserPageID; 
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

	/**
	 * Get the value for a non-native User field
	 *
	 * @param string $key
	 * @return null|mixed
	 *
	 */
	protected function getFieldValue($key) {
		$value = parent::getFieldValue($key);
		if(!$value && $key == 'language') {
			$languages = $this->wire('languages');
			if($languages) $value = $languages->getDefault();
		}
		return $value;
	}

	/**
	 * Returns the URL where this page can be edited 
	 * 
	 * In this case we adjust the default page editor URL to ensure users are edited
	 * only from the Access section. 
	 * 
	 * @return string
	 * 
	 */
	public function editUrl() {
		return str_replace('/page/edit/', '/access/users/edit/', parent::editUrl());
	}

	/**
	 * Set the Process module (WirePageEditor) that is editing this User
	 * 
	 * We use this to detect when the User is being edited somewhere outside of /access/users/
	 * 
	 * @param WirePageEditor $editor
	 * 
	 */
	public function ___setEditor(WirePageEditor $editor) {
		parent::___setEditor($editor); 
		if(!$editor instanceof ProcessUser) $this->wire('session')->redirect($this->editUrl());
	}

	/**
	 * Return the API variable used for managing pages of this type
	 *
	 * @return Pages|PagesType
	 *
	 */
	public function getPagesManager() {
		return $this->wire('users');
	}

}
