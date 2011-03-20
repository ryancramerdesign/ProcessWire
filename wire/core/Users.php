<?php

/**
 * ProcessWire Users
 *
 * Manages User instances (getting/saving) with lookup to Roles.
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
 * WireArray of User instances
 *
 */
class UsersArray extends WireArray {

        public function isValidItem($item) {
                return $item instanceof User;
        }       

        public function isValidKey($key) {
                return is_int($key) || ctype_digit($key);
        }
        
        public function getItemKey($item) {
                return $item->id; 
        }  

	public function makeBlankItem() {
		return new User();
	}

	
}

/**
 * Manages User instances (getting/saving) with lookup to Roles
 *
 * TODO Users should not load all users at once, unless dealing with a DB with very few of them
 *
 */ 
class Users extends WireSaveableItemsLookup {

	/**
	 * Instance of UsersArray
	 *
	 */
	protected $usersArray;

	/**
	 * The current user, instance of User class
	 *
	 */
	protected $currentUser = null; 

	/**
	 * Construct the Users 
	 *
	 * Default user ID "1" is always the 'guest' user
	 *
	 */
	public function __construct($currentUserId = 0) {
		if(!$currentUserId) $currentUserId = User::guestUserID; 
		$this->usersArray = new UsersArray();
		$this->load($this->usersArray);
		$this->setCurrentUser($this->get($currentUserId)); 
		foreach($this->usersArray as $user) $user->setTrackChanges(true); 
	}

	/**
	 * Per WireSaveableItems interface, get all Users
	 *
	 */
	public function getAll() {
		return $this->usersArray;
	}

	/**
	 * Per WireSaveableItems interface, get a blank User instance
	 *
	 */
	public function makeBlankItem() {
		return new User(); 
	}

	/**
	 * Per WireSaveableItems interface, get the DB table where users are stored
	 *
	 */
	public function getTable() {
		return 'users';
	}

	/**
	 * Per the WireSaveableItemsLookup interface, get the table joined with users for lookup (roles)
	 *
	 */
	public function getLookupTable() {
		return 'users_roles'; 
	}

	/**
	 * Save the provided User item to the database
	 *
	 */
	public function ___save(Saveable $item) {

		if(!$item instanceof User || $item instanceof NullUser) 
			throw new WireException("Item passed Users::save is not a User"); 

		if($item->isChanged('pass') || !$item->id) {
			$item->set('salt', md5(mt_rand() . microtime())); 
			$item->set('pass', $this->passHash($item->get('pass'), $item)); 
		}

		// The owner role is for runtime use only, so users may not be saved with the User role
		if($item->hasRole(Role::ownerRoleID)) $item->removeRole(Role::ownerRoleID); 

		return parent::___save($item); 
	}

	/** 
	 * Delete the provided item from the database, only if it's not a permanent user
	 *
	 */
	public function ___delete(Saveable $item) {
		if(!$item instanceof User || $item instanceof NullUser) 
			throw new WireException("Item passed Users::delete is not a User"); 
		if($item->isPermanent()) throw new WireException("User ID {$item->id} is permanent and may not be deleted"); 
		return parent::___delete($item); 
	}

	/**
	 * Sets the current users
	 *
	 * @param User $user
	 * @return this
	 *
	 */
	public function setCurrentUser(User $user) {
		$this->currentUser = $user; 
		Wire::setFuel('user', $user); 
		return $this; 
	}

	/**
	 * Sets the current user to be the default unauthenticated guest user
	 *
	 * @return this
	 *
	 */
	public function setCurrentUserGuest() {
		return $this->setCurrentUser($this->get(User::guestUserID)); 
	}

	/**
	 * Get the current user
	 *
	 * @return User
	 *
	 */
	public function getCurrentUser() {
		return $this->currentUser; 
	}

	/**
	 * Given a username and password, return the resulting User instance of it authenticates or NULL if it doesn't
	 *
	 * If the user authenticates, then the current user is automatically set to the authenticated user. 
	 *
	 * @param string $user
	 * @param string $pass Param should not be MD5 encoded
	 * @return User|null
	 *
	 */
	public function ___authenticateUser($name, $pass) {
		if(($user = $this->get($name)) && $user->pass === $this->passHash($pass, $user)) {
			$this->setCurrentUser($user); 
			return $user; 
		}
		return null;
	}

	/**
	 * Given an unhashed password, generate a hash of the password for database storage and comparison
	 *
	 * @param string $pass
	 *
	 */
	public function passHash($pass, User $user) {

		$hashType = $this->fuel('config')->userAuthHashType; 
		$salt1 = (string) $user->salt; 
		$salt2 = (string) $this->fuel('config')->userAuthSalt; 

		if(!$hashType) return md5($pass); 

		$splitPass = str_split($pass, (strlen($pass) / 2) + 1); 
		$hash = hash($hashType, $salt1 . $splitPass[0] . $salt2 . $splitPass[1], false); 

		return $hash; 
	}

}
