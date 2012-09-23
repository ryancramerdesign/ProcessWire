<?php
/**
 * ProcessWire Password Fieldtype
 *
 * Class to hold combined password/salt info. Uses Blowfish when possible.
 * Specially used by FieldtypePassword.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class Password extends Wire {

	protected $data = array(
		'salt' => '', 
		'hash' => '',
		);

	/**
	 * Does this PasswordItem match the given pass?
	 *
	 * @param string $pass Password to compare
	 * @return string
	 *
	 */
	public function matches($pass) {

		if(!strlen($pass)) return false;
		$hash = $this->hash($pass); 
		if(!strlen($hash)) return false;
		$updateNotify = false;

		if($this->isBlowfish($hash)) {
			$hash = substr($hash, 29);

		} else if($this->supportsBlowfish()) {
			// notify user they may want to change their password
			// to take advantage of blowfish hashing
			$updateNotify = true; 
		}

		$matches = ($hash === $this->data['hash']);

		if($matches && $updateNotify) $this->message($this->_('The password system has recently been updated. Please change your password to complete the update for your account.')); 

		return $matches; 
	}

	/**
	 * Get a property via direct access
	 *
	 */
	public function __get($key) {
		if($key == 'salt' && !$this->data['salt']) $this->data['salt'] = $this->salt();
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * Set a property via direct access
	 *
	 */
	public function __set($key, $value) {

		if($key == 'pass') {
			// setting the password
			return $this->setPass($value);

		} else if(array_key_exists($key, $this->data)) { 
			// something other than pass
			$this->data[$key] = $value; 
		}
	}

	/**
	 * Set the 'pass' to the given $value
	 *
	 */
	protected function ___setPass($value) {

		// if nothing supplied, then don't continue
		if(!strlen($value)) return;

		// first check to see if it actually changed
		if($this->data['salt'] && $this->data['hash']) {
			$hash = $this->hash($value);
			if($this->isBlowfish($hash)) $hash = substr($hash, 29);
			// if no change then return now
			if($hash === $this->data['hash']) return; 
		}

		// password has changed
		$this->trackChange('pass');

		// force reset by clearing out the salt, hash() will gen a new salt
		$this->data['salt'] = ''; 

		// generate the new hash
		$hash = $this->hash($value);

		// if it's a blowfish hash, separate the salt from the hash
		if($this->isBlowfish($hash)) {
			$this->data['salt'] = substr($hash, 0, 28);
			$this->data['hash'] = substr($hash, 29);
		} else {
			$this->data['hash'] = $hash;
		}
	}

	/**
	 * Generate a random salt for the given hashType
	 *
	 * @param string $hashType Typically 'blowfish' or 'sha1' in ProcessWire. Provide blank string to auto-detect.
	 * @return string
	 *
	 */
	protected function salt() {

		// if system doesn't support blowfish, return old style salt
		if(!$this->supportsBlowfish()) return md5(mt_rand() . microtime()); 

		// blowfish assumed from this point forward
		// use stronger blowfish mode if PHP version supports it 
		$salt = (version_compare(PHP_VERSION, '5.3.7') >= 0) ? '$2y' : '$2a';

		// cost parameter (04-31)
		$salt .= '$11$'; 

		// base64 characters allowed for blowfish salt
		$chars = './abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$len = strlen($chars)-1;

		// generate a 21 character random blowfish salt
		for($n = 0; $n < 21; $n++) $salt .= $chars[mt_rand(0, $len)]; 
		$salt .= '$'; // plus trailing $

		return $salt;
	}

	/**
 	 * Returns whether the given string is blowfish hashed
	 *
	 * @param string $str
	 * @return bool
	 *
	 */
	public function isBlowfish($str = '') {
		if(!strlen($str)) $str = $this->data['salt'];
		$prefix = substr($str, 0, 2); 
		return $prefix === '$2';
	}

	/**
 	 * Returns whether the current system supports Blowfish
	 *
	 * @return bool
	 *
	 */
	public function supportsBlowfish() {
		return defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH;
	}

	/**
	 * Given an unhashed password, generate a hash of the password for database storage and comparison
	 *
	 * Note: When bowfish, returns the entire blowfish string which has the salt as the first 28 characters. 
	 *
	 * @param string $pass Raw password
	 * @param string $hashType Typically 'blowfish' or 'sha1' in ProcessWire
	 *
	 */
	protected function hash($pass) {

		// if there is no salt yet, make one (for new pass or reset pass)
		if(!strlen($this->data['salt'])) $this->data['salt'] = $this->salt();

		// salt we made (the one ultimately stored in DB)
		$salt1 = $this->data['salt'];

		// static salt stored in config.php
		$salt2 = (string) wire('config')->userAuthSalt; 

		// auto-detect the hash type based on the format of the salt
		$hashType = $this->isBlowfish($salt1) ? 'blowfish' : wire('config')->userAuthHashType;

		if(!$hashType) {
			// If there is no defined hash type, and the system doesn't support blowfish, then just use md5 (ancient backwards compatibility)
			$hash = md5($pass); 

		} else if($hashType == 'blowfish') {
			// our preferred method
			$hash = crypt($pass . $salt2, $salt1);

		} else {
			// older style, non-blowfish support
			// split the password in two
			$splitPass = str_split($pass, (strlen($pass) / 2) + 1); 
			// generate the hash
			$hash = hash($hashType, $salt1 . $splitPass[0] . $salt2 . $splitPass[1], false); 
		}

		return $hash; 
	}

	public function __toString() {
		return (string) $this->data['hash']; 
	}
	
}

