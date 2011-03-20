<?php

/**
 * ProcessWire PagesRoles
 *
 * Manages instances of PageRole objects which form relations between Pages and Roles.
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
 * WireArray of PageRoles
 *
 */
class PagesRolesArray extends WireArray {

	/**
	 * Per WireArray interface
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof PageRole; 
	}

	/**
	 * Per WireArray interface
	 *
	 */
	public function getItemKey($item) {
		return $item->id; 
	}

	/**
	 * Per WireArray interface, return a blank PageRole
	 *
	 */
	public function makeBlankItem() {
		return new PageRole();
	}

	/**
	 * Given a Page and a Role, return the existing PageRole, or NULL if it doesn't already exist. 
	 *
	 */
	public function getPageRole(Page $page, Role $role) {
		$match = null;
		foreach($this as $pageRole) {
			if($pageRole->pages_id == $page->id && $pageRole->roles_id == $role->id) {
				$match = $pageRole;
				break;
			}
		}
		return $match; 
	}

	
}

/**
 * Manages instances of PageRole objects which form relations between Pages and Roles.
 *
 */
class PagesRoles extends WireSaveableItems {

	/**
	 * Instance of PagesRolesArray
	 *
	 */
	protected $pagesRolesArray; 

	public function __construct() {
		$this->pagesRolesArray = new PagesRolesArray();
		$this->load($this->pagesRolesArray); 
	}

	/**
	 * Per WireSaveableItems interface
	 *
	 */
	public function getAll() {
		return $this->pagesRolesArray; 
	}

	/**
	 * Per WireSaveableItems interface
	 *
	 */
	public function makeBlankItem() {
		return new PageRole();
	}

	/**
	 * Per WireSaveableItems interface
	 *
	 */
	public function getTable() {
		return "pages_roles";
	}


	/**
	 * Find all PageRoles that contain Page, optionally filtered by a + or - action.
	 *
	 * @param Page $page
	 * @param string $action Must be blank, "+" or "-"
	 * @return PagesRolesArray
	 *
	 */
	public function findPageRolesByPage(Page $page, $action = '') {

		$matches = new PagesRolesArray();

		foreach($this->pagesRolesArray as $pageRole) { 
			if($pageRole->pages_id === $page->id) {
				if(!$action || $action === $pageRole->action) 
					$matches->add($pageRole); 
			}
		}

		return $matches->setTrackChanges(); 
	}

	/**
	 * Find all PageRoles that contain Role, optionally filtered by a + or - action.
	 *
	 * @param Role $role
	 * @param string $action Must be blank, "+" or "-"
	 * @return PagesRolesArray
	 *
	 */
	protected function findPageRolesByRole(Role $role, $action = '') {

		$matches = new PagesRolesArray();

		foreach($this->pagesRolesArray as $pageRole) { 
			if($pageRole->roles_id === $role->id) {
				if(!$action || $action === $pageRole->action) 
					$matches->add($pageRole); 
			}
		}

		return $matches->setTrackChanges(); 
	}

	/**
	 * Find all Roles that are specifically added by the given Page
	 *
	 * "Specifically added" means the page that adds the role, not one that inherits it. 
	 *
	 * @param Page $page
	 * @return RolesArray 
	 *
	 */
	public function findRolesAddedByPage(Page $page) {
		$pageRoles = $this->findPageRolesByPage($page);
		$roles = new RolesArray();
		foreach($pageRoles as $pageRole) if($pageRole->addsRole()) $roles->add($pageRole->role); 
		return $roles->setTrackChanges(); 
	}

	/**
	 * Find all Roles that are specifically removed by the given Page
	 *
	 * @param Page $page
	 * @return RolesArray
	 *
	 */
	public function findRolesRemovedByPage(Page $page) {
		$pageRoles = $this->findPageRolesByPage($page);
		$roles = new RolesArray();
		foreach($pageRoles as $pageRole) if($pageRole->removesRole()) $roles->add($pageRole->role); 
		return $roles->setTrackChanges(); 
	}

	/**
	 * Find all roles that are active for this page.
	 *
	 * This includes roles inherited from parent pages. 
	 * This includes only roles that are active for this page: if a PageRole removes a role from the page, it's not included. 
	 * This is the beginning of determining whether a Role is allowed to access a Page. 
	 *
	 * @param Page $page
	 * @return RolesArray
	 *
	 */
	public function findRolesByPage(Page $page) {

		$parents = $page->parents()->append($page); 
		$roles = new RolesArray();

		foreach($parents as $p) {

			$pageRoles = $this->findPageRolesByPage($p); 			

			foreach($pageRoles as $pageRole) {

				if($pageRole->addsRole()) {
					$roles->add($pageRole->role); 

				} else if($pageRole->removesRole()) {
					$roles->remove($pageRole->role); 
				}
				
			}
		}

		// if the current user is the one that created the page, then give them the special "owner" role
		/*
		if($this->fuel('user')->id == $page->createdUser->id) {
			$roles->add($this->fuel('roles')->get(Role::ownerRoleID); 
		}
		*/ 

		$roles->setTrackChanges(); 


		return $roles; 
	}

	/**
	 * Does the given Page have the given Role?
	 *
	 * @param Page $page
	 * @param Role $role
	 * @param bool $not Setting this to true reverses the boolean state to perform a NOT operation. By default it is false. 
	 * @return bool
	 *
	 */
	public function pageHasRole(Page $page, Role $role, $not = false) {
		$roles = $this->findRolesByPage($page); 
		return $roles->has($role) !== $not; 
	}

	/**
	 * Does thie given Page lack the given Role?
	 *
	 * @param Page $page
	 * @param Role $role
	 * @return bool
	 *
	 */
	public function pageDoesNotHaveRole(Page $page, Role $role) {
		return $this->pageHasRole($page, $role, true); 
	}


	/**
	 * Add the given Role to the given Page only if it (or it's parents) doesn't already have the Role
	 *
	 * A call to save() should eventually follow, which will save to disk
	 *
	 * @param Role $role
	 * @param Page $page
	 * @return this
	 *
	 */
	public function addRoleToPage(Role $role, Page $page) {

		if($this->pageHasRole($page, $role)) return $this;

		$pageRole = $this->pagesRolesArray->getPageRole($page, $role); 
		if(!$pageRole) $pageRole = new PageRole();

		$pageRole->setTrackChanges();
		$pageRole->page = $page;
		$pageRole->role = $role;
		$pageRole->action = '+';

		$this->pagesRolesArray->add($pageRole); 

		// we trackChange with the $page since PageRoles are saved by Pages::save
		// we don't track the owner role being added since that is for runtime use only
		if($role->id != Role::ownerRoleID) $page->trackChange("roles"); 

		return $this;
	}

	/**
	 * Remove the given Role from the given Page only if it (or it's parents) doesn't already remove the Role
	 *
	 * A call to save() should eventually follow, which will save to disk
	 *
	 * @param Role $role
	 * @param Page $page
	 * @return this
	 *
	 */
	public function removeRoleFromPage(Role $role, Page $page) {

		if($this->pageDoesNotHaveRole($page, $role)) {
			return $this;
		}

		$pageRole = $this->pagesRolesArray->getPageRole($page, $role); 
		if(!$pageRole) $pageRole = new PageRole();

		$pageRole->setTrackChanges();
		$pageRole->page = $page;
		$pageRole->role = $role;
		$pageRole->action = '-';
	
		//if(!$page->parent || $this->pageHasRole($page->parent, $role)) $pageRole->redundant = true; 
		$this->pagesRolesArray->add($pageRole); 

		// we trackChange with the $page since PageRoles are saved by Pages::save
		if($role->id != Role::ownerRoleID) $page->trackChange("roles"); 

		return $this; 
	}

	/**
	 * Delete all roles from the given Page
	 *
	 * @param Page $page
	 * @return this
	 *
	 */
	public function deleteRolesFromPage(Page $page) {
		foreach($this->findPageRolesByPage($page) as $pageRole) {
			$this->delete($pageRole); 
		}
		return $this;
	}

	/**
	 * Delete all pages from the given Role
	 *
	 * @param Role $role
	 * @return this
	 *
	 */
	public function deletePagesFromRole(Role $role) {
		foreach($this->findPageRolesByRole($role) as $pageRole) {
			$this->delete($pageRole); 
		}
		return $this;
	}

	/**
	 * Save all roles for the given Page
	 *
	 * @param Page $page
	 * @return this
	 *
	 */
	public function savePageRoles(Page $page) {

		$changed = false;
		$pageRoles = $this->findPageRolesByPage($page); 

		foreach($pageRoles as $pageRole) {
			if($pageRole->role->id == Role::ownerRoleID) continue; // this one doesn't count
			if(!$pageRole->id || $pageRole->isChanged() || $pageRole->isRedundant()) {
				$changed = true; 
				break;
			}
		}

		if($changed) {
			$this->deleteRolesFromPage($page); 
			foreach($pageRoles as $pageRole) {

				// we don't same the owner role since it's for runtime use only
				if($pageRole->role->id == Role::ownerRoleID) continue; 

				if($pageRole->isRedundant()) {
					if($this->config->debug) $this->message("Removed redundant role: {$pageRole->role}"); 
					continue; 
				}

				$pageRole->id = 0; 
				$this->save($pageRole); 
			}
			$this->message("Saved page roles"); 
		}

		return $this; 
	}

	
}

