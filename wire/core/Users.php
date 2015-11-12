<?php

/**
 * The Users class serves as the $users API variable. 
 *
 * @method PageArray find() find($selectorString) Return the user(s) matching the the given selector query.
 * 
 * ProcessWire 2.x
 * Copyright 2015 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 */

class Users extends PagesType {

	protected $currentUser = null; 
	protected $guestUser = null;
	
	public function __construct($templates = array(), $parents = array()) {
		parent::__construct($templates, $parents);
		$this->setPageClass('User'); 
	}
	
	/**
	 * Like find() but returns only the first match as a Page object (not PageArray)
	 *
	 * This is an alias of the findOne() method for syntactic convenience and consistency.
	 *
	 * @param string $selectorString
	 * @return Page|null
	 */
	public function get($selectorString) {
		$user = parent::get($selectorString);
		return $user; 
	}

	/**
	 * Set the current system user (the $user API variable)
	 *
	 * @param User $user
	 *
	 */
	public function setCurrentUser(User $user) {
		
		$hasGuest = false;
		$guestRoleID = $this->wire('config')->guestUserRolePageID; 
		
		if($user->roles) foreach($user->roles as $role) {
			if($role->id == $guestRoleID) {
				$hasGuest = true; 	
				break;
			}
		}
		
		if(!$hasGuest && $user->roles) {
			$guestRole = $this->wire('roles')->getGuestRole();
			$user->roles->add($guestRole);
		}
		
		$this->currentUser = $user; 
		$this->wire('user', $user); 
	}

	/**
	 * Ensure that every user loaded has at least the 'guest' role
	 *
	 */
	protected function loaded(Page $page) {
		static $guestID = null;
		if(is_null($guestID)) $guestID = $this->wire('config')->guestUserRolePageID; 
		$roles = $page->get('roles'); 
		if(!$roles->has("id=$guestID")) $page->get('roles')->add($this->wire('roles')->getGuestRole());
	}

	/**
	 * Returns the current user object
	 *
	 * @return User
	 *
	 */
	public function getCurrentUser() {
		if($this->currentUser) return $this->currentUser; 
		return $this->getGuestUser();
	}

	/**
	 * Get the 'guest' user account
	 *
	 * @return User
	 *
	 */
	public function getGuestUser() {
		if($this->guestUser) return $this->guestUser; 
		$this->guestUser = $this->get($this->config->guestUserPageID); 
		if(defined("PROCESSWIRE_UPGRADE") && !$this->guestUser || !$this->guestUser->id) $this->guestUser = new User(); // needed during upgrade
		return $this->guestUser; 
	}

}
