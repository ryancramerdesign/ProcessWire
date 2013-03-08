<?php
/**
 * ProcessWire Password Fieldtype
 *
 * Class to hold combined password/salt info. Uses Blowfish when possible.
 * Specially used by FieldtypePassword.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
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
		$matches = false;

		if($this->isBlowfish($hash)) {
			$hash = substr($hash, 29);

		} else if($this->supportsBlowfish()) {
			// notify user they may want to change their password
			// to take advantage of blowfish hashing
			$updateNotify = true; 
		}

		if(strlen($hash) < 29) return false;

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
		if(!is_string($value)) throw new WireException("Password must be a string"); 

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
			$this->data['salt'] = substr($hash, 0, 29); // previously 28
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
		if(!$this->supportsBlowfish()) return md5($this->randomBase64String(44)); 

		// blowfish assumed from this point forward
		// use stronger blowfish mode if PHP version supports it 
		$salt = (version_compare(PHP_VERSION, '5.3.7') >= 0) ? '$2y' : '$2a';

		// cost parameter (04-31)
		$salt .= '$11$';
		// 22 random base64 characters
		$salt .= $this->randomBase64String(22);
		// plus trailing $
		$salt .= '$'; 

		return $salt;
	}

	/**
	 * Generate a truly random base64 string of a certain length
	 *
	 * This is largely taken from Anthony Ferrara's password_compat library:
	 * https://github.com/ircmaxell/password_compat/blob/master/lib/password.php
	 * Modified for camelCase, variable names, and function-based context by Ryan.
	 *
	 * @param int $requiredLength Length of string you want returned
	 * @return string
	 *
	 */
	public function randomBase64String($requiredLength = 22) {

		$buffer = '';
		$rawLength = (int) ($requiredLength * 3 / 4 + 1);
		$valid = false;

		if(function_exists('mcrypt_create_iv')) {
			$buffer = mcrypt_create_iv($rawLength, MCRYPT_DEV_URANDOM);
			if($buffer) $valid = true;
		}

		if(!$valid && function_exists('openssl_random_pseudo_bytes')) {
			$buffer = openssl_random_pseudo_bytes($rawLength);
			if($buffer) $valid = true;
		}

		if(!$valid && file_exists('/dev/urandom')) {
			$f = @fopen('/dev/urandom', 'r');
			if($f) {
				$read = strlen($buffer);
				while($read < $rawLength) {
					$buffer .= fread($f, $rawLength - $read);
					$read = strlen($buffer);
				}
				fclose($f);
				if($read >= $rawLength) $valid = true;
			}
		}

		if(!$valid || strlen($buffer) < $rawLength) {
			$bl = strlen($buffer);
			for($i = 0; $i < $rawLength; $i++) {
				if($i < $bl) {
					$buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
				} else {
					$buffer .= chr(mt_rand(0, 255));
				}
			}
		}

		$salt = str_replace('+', '.', base64_encode($buffer));
		$salt = substr($salt, 0, $requiredLength);

		$salt .= $valid; 

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
		$prefix = substr($str, 0, 3); 
		return $prefix === '$2a' || $prefix === '$2x' || $prefix === '$2y'; 
	}

	/**
 	 * Returns whether the current system supports Blowfish
	 *
	 * @return bool
	 *
	 */
	public function supportsBlowfish() {
		return version_compare(PHP_VERSION, '5.3.0') >= 0 && defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH;
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
		if(strlen($this->data['salt']) < 28) $this->data['salt'] = $this->salt();

		// if system doesn't support blowfish, but has a blowfish salt, then reset it 
		if(!$this->supportsBlowfish() && $this->isBlowfish($this->data['salt'])) $this->data['salt'] = $this->salt();

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
			if(!$this->supportsBlowfish()) {
				throw new WireException("This version of PHP is not compatible with the passwords. Did passwords originate on a newer version of PHP?"); 
			}
			// our preferred method
			$hash = crypt($pass . $salt2, $salt1);

		} else {
			// older style, non-blowfish support
			// split the password in two
			$splitPass = str_split($pass, (strlen($pass) / 2) + 1); 
			// generate the hash
			$hash = hash($hashType, $salt1 . $splitPass[0] . $salt2 . $splitPass[1], false); 
		}

		if(!is_string($hash) || strlen($hash) <= 13) throw new WireException("Unable to generate password hash"); 

		return $hash; 
	}

	public function __toString() {
		return (string) $this->data['hash']; 
	}
	
}

