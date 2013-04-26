<?php

/**
 * ProcessWire Page Access
 *
 * Provides implementation for Page access functions.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class PageAccess {

	/**
	 * Returns the parent page that has the template from which we get our role/access settings from
	 *
	 * @param Page $page
	 * @return Page|NullPage Returns NullPage if none found
	 *
	 */
	public function getAccessParent(Page $page) {
		if($page->template->useRoles || $page->id === 1) return $page;
		$parent = $page->parent();	
		if($parent->id) return $this->getAccessParent($parent); 
		return new NullPage();
	}

	/**
	 * Returns the template from which we get our role/access settings from
	 *
	 * @param Page $page
	 * @return Template|null Returns null if none	
	 *
	 */
	public function getAccessTemplate(Page $page) {
		$parent = $this->getAccessParent($page);
		if(!$parent->id) return null;
		return $parent->template; 
	}
	
	/**
	 * Return the PageArray of roles that have access to this page
	 *
	 * This is determined from the page's template. If the page's template has roles turned off, 
	 * then it will go down the tree till it finds usable roles to use. 
	 *
	 * @param Page $page
	 * @return PageArray
	 *
	 */
	public function getAccessRoles(Page $page) {
		$template = $this->getAccessTemplate($page);
		if($template) return $template->roles; 
		return new PageArray();
	}

	/**
	 * Returns whether this page has the given access role
	 *
	 * Given access role may be a role name, role ID or Role object
	 *
	 * @param Page $page
	 * @param string|int|Role $role 
	 * @return bool
	 *
	 */
	public function hasAccessRole(Page $page, $role) {
		$roles = $this->getAccessRoles($page);
		if(is_string($role)) return $roles->has("name=$role"); 
		if($role instanceof Role) return $roles->has($role); 
		if(is_int($role)) return $roles->has("id=$role"); 
		return false;
	}
}
