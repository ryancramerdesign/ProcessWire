<?php

/**
 * ProcessWire PageRole
 *
 * A PageRole represents a single Page to Role relation, and the collection of 
 * PageRoles are manged by the PagesRoles class.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class PageRole extends WireData implements Saveable {

	/**
	 * Instance of Page
	 *
	 */
	protected $page; 

	/**
	 * Instance of Role
	 *
	 */
	protected $role; 

	/**
	 * Construct the PageRole
	 *
	 */
	public function __construct() {
		$this->set('id', 0); 
		$this->set('pages_id', 0); 
		$this->set('roles_id', 0); 
		$this->set('action', '-'); 
	}

	/**
	 * Per the Saveable interface
	 *
	 */
	public function getTableData() {
		return array(
			'id' => $this->id, 
			'pages_id' => $this->pages_id, 
			'roles_id' => $this->roles_id, 
			'action' => $this->action, 
			); 
	}

	/**
	 * Save this PageRole to disk
	 *
	 */
	public function save() {
		return $this->fuel('pagesRoles')->save($this); 
	}

	public function set($key, $value) {

		if($key == 'action') { 
			if(!in_array($value, array('+', '-'))) throw new WireException("PageRole::action must be either '+' or '-'"); 

		} else if($key == 'pages_id') { 
			$value = (int) $value;
			$this->page = null;
			
		} else if($key == 'roles_id') { 
			$value = (int) $value; 
			$this->role = null;

		} else if($key == 'page' && $value instanceof Page) {
			if(!$this->page || !$this->page->id || $value->id != $this->page->id) $this->trackChange('page'); 
			$this->page = $value; 
			return $this; 

		} else if($key == 'role' && $value instanceof Role) {
			if(!$this->role || !$this->role->id || $value->id != $this->role->id) $this->trackChange('role'); 
			$this->role = $value;
			return $this; 
		}

		parent::set($key, $value); 
	}

	public function get($key) {
		if($key === 'role') return $this->role ? $this->role : $this->getFuel('roles')->get($this->roles_id); 
			else if($key === 'page') return $this->page ? $this->page : $this->getFuel('pages')->get($this->pages_id); 
			else if($key === 'pages_id' && $this->page) return $this->get('page')->id; 
			else if($key === 'roles_id' && $this->role) return $this->get('role')->id; 
			else return parent::get($key); 
	}

	/**
	 * Does this PageRole specifically remove it's role?
	 *
	 */
	public function removesRole() {
		return $this->action == '-';
	}

	/**
	 *  Does this PageRole specifically add it's role?
	 *
	 */
	public function addsRole() {
		return $this->action == '+';
	}

	/**
	 * Is this PageRole redundant, i.e. no longer necessary?
	 *
	 */
	public function isRedundant() {

		$page = $this->get('page'); 
		$role = $this->get('role'); 

		// if page doesn't have a parent (i.e. homepage) then any role removals are redundant
		// since there is nothing earlier to add them

		if(!$page->parent) {
			if($this->removesRole()) return true; 
			// role additions are never redundant at the root (homepage) level
			return false; 
		}

		$redundant = false; 

		// parent already has role, so this one doesn't need it?
		if($this->addsRole() && $page->parent->hasRole($role)) {
			$redundant = true; 
		}

		// parent already removes role, so we don't need to remove it?
		if($this->removesRole() && !$page->parent->hasRole($role)) {
			$redundant = true; 
		}

		return $redundant; 
	}

	public function __toString() {
		return $this->action . $this->get('role') . ":" . $this->get('page')->path();
	}

}

