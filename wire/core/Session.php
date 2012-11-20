<?php

/**
 * ProcessWire Session
 *
 * Start a session with login/logout capability 
 *
 * This should be used instead of the $_SESSION superglobal, though the $_SESSION superglobal can still be 
 * used, but it's in a different namespace than this. A value set in $_SESSION won't appear in $session
 * and likewise a value set in $session won't appear in $_SESSION.  It's also good to use this class
 * over the $_SESSION superglobal just in case we ever need to replace PHP's session handling in the future.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 *
 * @see http://processwire.com/api/cheatsheet/#session Cheatsheet
 * @see http://processwire.com/api/variables/session/ Offical $session API variable Documentation
 *
 * @method User login() login($name, $pass) Login the user identified by $name and authenticated by $pass. Returns the user object on successful login or null on failure.
 * @method Session logout() logout() Logout the current user, and clear all session variables.
 * @method redirect() redirect($url, $http301 = true) Redirect this session to the specified URL. 
 *
 * Expected $config variables include: 
 * ===================================
 * int $config->sessionExpireSeconds Number of seconds of inactivity before session expires
 * bool $config->sessionChallenge True if a separate challenge cookie should be used for validating sessions
 * bool $config->sessionFingerprint True if a fingerprint should be kept of the user's IP & user agent to validate sessions
 *
 */

class Session extends Wire implements IteratorAggregate {

	/**
	 * Reference to ProcessWire $config object
	 *
 	 * For convenience, since our __get() does not reference the Fuel, unlike other Wire derived classes.
	 *
	 */
	protected $config; 

	/**
	 * Instance of the SessionCSRF protection class, instantiated when requested from $session->CSRF.
	 *
	 */
	protected $CSRF = null; 

	/**
	 * IP address of current session in integer format (used as cache by getIP function)
	 *
	 */
	private $ip = null;

	/**
	 * Start the session and set the current User if a session is active
	 *
	 * Assumes that you have already performed all session-specific ini_set() and session_name() calls 
	 *
	 */
	public function __construct() {

		$this->config = $this->fuel('config'); 
		$this->init();
		unregisterGLOBALS();
		$className = $this->className();
		$user = null;

		if(empty($_SESSION[$className])) $_SESSION[$className] = array();

		if($userID = $this->get('_user_id')) {
			if($this->isValidSession()) {
				$user = $this->fuel('users')->get($userID); 
			} else {
				$this->logout();
			}
		}

		if(!$user || !$user->id) $user = $this->fuel('users')->getGuestUser();
		$this->fuel('users')->setCurrentUser($user); 	

		foreach(array('message', 'error') as $type) {
			if($items = $this->get($type)) foreach($items as $item) {
				list($text, $flags) = $item;
				parent::$type($text, $flags); 
			}
			$this->remove($type);
		}

		$this->setTrackChanges(true);
	}

	/**
	 * Start the session
	 *
	 * Provided here in any case anything wants to hook in before session_start()
	 * is called to provide an alternate save handler.
	 *
	 */
	protected function ___init() {
		@session_start();
	}

	/**
	 * Checks if the session is valid based on a challenge cookie and fingerprint
	 *
	 * These items may be disabled at the config level, in which case this method always returns true
 	 *
	 * @return bool
	 *
	 */
	protected function ___isValidSession() {

		$valid = true; 
		$sessionName = session_name();

		if($this->config->sessionChallenge) {
			if(empty($_COOKIE[$sessionName . "_challenge"]) || ($this->get('_user_challenge') != $_COOKIE[$sessionName . "_challenge"])) {
				$valid = false; 
			}
		}	

		if($this->config->sessionFingerprint) {
			if(($this->getIP(true) . $_SERVER['HTTP_USER_AGENT']) != $this->get("_user_fingerprint")) {
				$valid = false; 
			}
		}

		if($this->config->sessionExpireSeconds) {
			$ts = (int) $this->get('_user_ts');
			if($ts < (time() - $this->config->sessionExpireSeconds)) {
				// session time expired
				$valid = false;
				$this->error($this->_('Session timed out'));
			}
		}

		if($valid) $this->set('_user_ts', time());


		return $valid; 
	}


	/**
	 * Get a session variable
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {
		if($key == 'CSRF') {
			if(is_null($this->CSRF)) $this->CSRF = new SessionCSRF();
			return $this->CSRF; 
		}
		$className = $this->className();
		return isset($_SESSION[$className][$key]) ? $_SESSION[$className][$key] : null; 
	}

	/**
	 * Get all session variables
 	 *
	 * @return array
	 *
	 */
	public function getAll() {
		return $_SESSION[$this->className()]; 
	}

	/**
	 * Set a session variable
	 *
	 * @param string $key
	 * @param mixed $value
 	 * @return this
	 *
	 */
	public function set($key, $value) {
		$className = $this->className();
		$oldValue = $this->get($key); 
		if($value !== $oldValue) $this->trackChange($key); 
		$_SESSION[$className][$key] = $value; 
		return $this; 
	}

	/**
	 * Unsets a session variable
	 *
 	 * @param string $value
	 * @return this
	 *
	 */
	public function remove($key) {
		unset($_SESSION[$this->className()][$key]); 
		return $this; 
	}

	/**
	 * Provide $session->variable get access
	 *
	 */
	public function __get($key) {
		return $this->get($key); 
	}

	/**
	 * Provide $session->variable = variable set access
	 *
	 */
	public function __set($key, $value) {
		return $this->set($key, $value); 
	}

	/**
	 * Allow iteration of session variables, i.e. foreach($session as $key => $var) {} 
	 *
	 */
	public function getIterator() {
		return new ArrayObject($_SESSION[$this->className()]); 
	}

	/**
	 * Get the IP address of the current user
	 *
	 */
	public function getIP($int = false) {
		if(is_null($this->ip)) { 
			if(!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP']; 
				else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				else if(!empty($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR']; 
				else $ip = '';
			$ip = ip2long($ip);
			$this->ip = $ip;
		} else {
			$ip = $this->ip; 
		}
		if(!$int) $ip = long2ip($ip);
		return $ip;
	}

	/**
	 * Login a user with the given name and password
	 *
	 * Also sets them to the current user
	 *
	 * @param string $name
	 * @param string $pass Raw, non-hashed password
	 * @return User Return the $user if the login was successful or null if not. 
	 *
	 */
	public function ___login($name, $pass) {

		if(!$this->allowLogin($name)) return null;

		$name = $this->fuel('sanitizer')->username($name); 
		$user = $this->fuel('users')->get("name=$name"); 

		if($user->id && $this->authenticate($user, $pass)) { 

			$this->trackChange('login'); 
			session_regenerate_id(true);
			$this->set('_user_id', $user->id); 
			$this->set('_user_ts', time());

			if($this->config->sessionChallenge) {
				// create new challenge
				$challenge = md5(mt_rand() . $this->get('_user_id') . microtime()); 
				$this->set('_user_challenge', $challenge); 
				// set challenge cookie to last 30 days (should be longer than any session would feasibly last)
				setcookie(session_name() . '_challenge', $challenge, time()+60*60*24*30, '/', null, false, true); 
			}

			if($this->config->sessionFingerprint) {
				// remember a fingerprint that tracks the user's IP and user agent
				$this->set('_user_fingerprint', $this->getIP(true) . $_SERVER['HTTP_USER_AGENT']); 
			}

			$this->setFuel('user', $user); 
			$this->get('CSRF')->resetToken();

			return $user; 
		}

		return null; 
	}

	/**
	 * Allow the user $name to login?
	 *
	 * Provided for use by hooks. 
	 *
	 */
	public function ___allowLogin($name) {
		return true; 
	}

	/**
	 * Return true or false whether the user authenticated with the supplied password
	 *
	 * @param User $user 
	 * @param string $pass
	 * @return bool
	 *
	 */
	public function ___authenticate(User $user, $pass) {
		return $user->pass->matches($pass);
	}

	/**
	 * Logout the current user, and clear all session variables
	 *
	 * @return this
	 *
	 */
	public function ___logout() {
		$sessionName = session_name();
		$_SESSION = array();
		if(isset($_COOKIE[$sessionName])) setcookie($sessionName, '', time()-42000, '/'); 
		if(isset($_COOKIE[$sessionName . "_challenge"])) setcookie($sessionName . "_challenge", '', time()-42000, '/'); 
		session_destroy();
		session_name($sessionName); 
		$this->init();
		session_regenerate_id(true);
		$_SESSION[$this->className()] = array();
		$guest = $this->fuel('users')->getGuestUser();
		$this->fuel('users')->setCurrentUser($guest); 
		$this->trackChange('logout'); 
		return $this; 
	}

	/**
	 * Redirect this session to another URL
	 *
	 */
	public function ___redirect($url, $http301 = true) {

		// if there are notices, then queue them so that they aren't lost
		$notices = $this->fuel('notices'); 
		if(count($notices)) foreach($notices as $notice) {
			$this->queueNotice($notice->text, $notice instanceof NoticeError ? 'error' : 'message', $notice->flags); 
		}

		// perform the redirect
		if($http301) header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		header("Connection: close"); 
		exit(0);
	}

	/**
	 * Queue a notice (message/error) to be shown the next time this ession class is instantiated
	 *
	 */
	protected function queueNotice($text, $type, $flags) {
		$items = $this->get($type);
		if(is_null($items)) $items = array();
		$item = array($text, $flags); 
		$items[] = $item;
		$this->set($type, $items); 
	}


	/**
	 * Queue a message to appear the next time session is instantiated
	 *
	 */
	public function message($text, $flags = 0) {
		$this->queueNotice($text, 'message', $flags); 
		return $this;
	}

	/**
	 * Queue an error to appear the next time session is instantiated
	 *
	 */
	public function error($text, $flags = 0) {
		$this->queueNotice($text, 'error', $flags); 
		return $this; 
	}


}
