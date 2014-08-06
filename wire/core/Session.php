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
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
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

		if($userID = $this->get('_user', 'id')) {
			if($this->isValidSession()) {
				$user = $this->wire('users')->get($userID); 
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
			if(empty($_COOKIE[$sessionName . "_challenge"]) || ($this->get('_user', 'challenge') != $_COOKIE[$sessionName . "_challenge"])) {
				$valid = false; 
			}
		}	

		if($this->config->sessionFingerprint) {
			if(md5(($this->getIP(true) . $_SERVER['HTTP_USER_AGENT'])) != $this->get('_user', 'fingerprint')) {
				$valid = false; 
			}
		}

		if($this->config->sessionExpireSeconds) {
			$ts = (int) $this->get('_user', 'ts');
			if($ts < (time() - $this->config->sessionExpireSeconds)) {
				// session time expired
				$valid = false;
				$this->error($this->_('Session timed out'));
			}
		}

		if($valid) $this->set('_user', 'ts', time());


		return $valid; 
	}


	/**
	 * Get a session variable
	 *
	 * @param string|object $key Key to get, or object if namespace
	 * @param string $_key Key to get if first argument is namespace, omit otherwise
	 * @return mixed
	 *
	 */
	public function get($key, $_key = null) {
		if($key == 'CSRF') {
			if(is_null($this->CSRF)) $this->CSRF = new SessionCSRF();
			return $this->CSRF; 
		} else if(!is_null($_key)) {
			// namespace
			return $this->getFor($key, $_key);
		}
		$className = $this->className();
		$value = isset($_SESSION[$className][$key]) ? $_SESSION[$className][$key] : null;
		
		if(is_null($value) && is_null($_key) && strpos($key, '_user_') === 0) {
			// for backwards compatiblity with non-core modules or templates that may be checking _user_[property]
			// not currently aware of any instances, but this is just a precaution
			return $this->get('_user', str_replace('_user_', '', $key)); 
		}
		
		return $value; 
	}

	/**
	 * Get all session variables
 	 *
	 * @param $ns Optional namespace
	 * @return array
	 *
	 */
	public function getAll($ns = null) {
		if(!is_null($ns)) return $this->getFor($ns, ''); 
		return $_SESSION[$this->className()]; 
	}

	/**
	 * Set a session variable
	 * 
	 * @param string|object $key Key to set OR object for namespace
	 * @param string|mixed $value Value to set OR key if first argument is namespace
	 * @param mixed $_value Value to set if first argument is namespace. Omit otherwise.
 	 * @return $this
	 *
	 */
	public function set($key, $value, $_value = null) {
		if(!is_null($_value)) return $this->setFor($key, $value, $_value); 
		$className = $this->className();
		$oldValue = $this->get($key); 
		if($value !== $oldValue) $this->trackChange($key, $oldValue, $value); 
		$_SESSION[$className][$key] = $value; 
		return $this; 
	}

	/**
	 * Get a session variable within a given namespace
	 *
	 * @param string|object $ns namespace string or object
	 * @param string $key Specify blank string to return all vars in the namespace
	 * @return mixed
	 *
	 */
	public function getFor($ns, $key) {
		$ns = $this->getNamespace($ns); 
		$data = $this->get($ns); 
		if(!is_array($data)) $data = array();
		if($key === '') return $data;
		return isset($data[$key]) ? $data[$key] : null;
	}

	/**
	 * Set a session variable within a given namespace
	 * 
	 * To remove a namespace, call $session->remove(namespace)
	 *
	 * @param string|object $ns namespace string or object
	 * @param string $key
	 * @param mixed $value Specify null to unset key
	 * @return $this
	 *
	 */
	public function setFor($ns, $key, $value) {
		$ns = $this->getNamespace($ns); 
		$data = $this->get($ns); 
		if(!is_array($data)) $data = array();
		if(is_null($value)) unset($data[$key]); 
			else $data[$key] = $value; 
		return $this->set($ns, $data); 
	}

	/**
	 * Unsets a session variable
	 *
 	 * @param string|object $key or namespace string/object
	 * @param string|bool|null $_key Omit this argument unless first argument is a namespace. 
	 * 	If first argument is namespace and you want to remove a property from the namespace, provide key here. 
	 * 	If first argument is namespace and you want to remove all properties from the namespace, provide boolean TRUE. 
	 * @return $this
	 *
	 */
	public function remove($key, $_key = null) {
		if(is_null($_key)) {	
			unset($_SESSION[$this->className()][$key]); 
		} else if(is_bool($_key)) {
			unset($_SESSION[$this->className()][$this->getNamespace($key)]); 
		} else {
			unset($_SESSION[$this->className()][$this->getNamespace($key)][$_key]); 
		}
		return $this; 
	}

	/**
	 * Given a namespace object or string, return the namespace string
	 * 
	 * @param object|string $ns
	 * @return string
	 * @throws WireException if given invalid namespace type
	 *
	 */
	protected function getNamespace($ns) {
		if(is_object($ns)) {
			if($ns instanceof Wire) $ns = $ns->className();
				else $ns = get_class($ns);
		} else if(is_string($ns)) {
			// good
		} else {
			throw new WireException("Session namespace must be string or object"); 
		}
		return $ns; 
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
	 * @param bool $int Return as a long integer for DB storage? (default=false)
	 * @param bool $useClient Give preference to client headers for IP? HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR (default=false)
	 * @return string|int Returns string by default, or integer if $int argument indicates to.
	 *
	 */
	public function getIP($int = false, $useClient = false) {

		if($useClient) { 
			if(!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP']; 
				else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				else if(!empty($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR']; 
				else $ip = '0.0.0.0';
			// It's possible for X_FORWARDED_FOR to have more than one CSV separated IP address, per @tuomassalo
			if(strpos($ip, ',') !== false) list($ip) = explode(',', $ip); 

		} else {
			$ip = $_SERVER['REMOTE_ADDR']; 
		}

		// sanitize by converting to and from integer
		$ip = ip2long($ip);
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

		$name = $this->wire('sanitizer')->username($name); 
		$user = $this->wire('users')->get("name=$name"); 

		if($user->id && $this->authenticate($user, $pass)) { 

			$this->trackChange('login', $this->wire('user'), $user); 
			session_regenerate_id(true);
			$this->set('_user', 'id', $user->id); 
			$this->set('_user', 'ts', time());

			if($this->config->sessionChallenge) {
				// create new challenge
				$pass = new Password();
				$challenge = $pass->randomBase64String(32);
				$this->set('_user', 'challenge', $challenge); 
				// set challenge cookie to last 30 days (should be longer than any session would feasibly last)
				setcookie(session_name() . '_challenge', $challenge, time()+60*60*24*30, '/', null, false, true); 
			}

			if($this->config->sessionFingerprint) {
				// remember a fingerprint that tracks the user's IP and user agent
				$this->set('_user', 'fingerprint', md5($this->getIP(true) . $_SERVER['HTTP_USER_AGENT'])); 
			}

			$this->setFuel('user', $user); 
			$this->get('CSRF')->resetAll();
			$this->loginSuccess($user); 

			return $user; 
		}

		return null; 
	}

	/**
	 * Login success method for hooks
	 *
	 * @param User $user
	 *
	 */
	protected function ___loginSuccess(User $user) { }

	/**
	 * Allow the user $name to login?
	 *
	 * Provided for use by hooks. 
	 * 
	 * @param string $name
	 * @return bool
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
	 * @return $this
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
		$user = $this->wire('user'); 
		$guest = $this->wire('users')->getGuestUser();
		$this->wire('users')->setCurrentUser($guest); 
		$this->trackChange('logout', $user, $guest); 
		if($user) $this->logoutSuccess($user); 
		return $this; 
	}

	/**
	 * Logout success method for hooks
	 *
	 * @param User $user
	 *
	 */
	protected function ___logoutSuccess(User $user) { }

	/**
	 * Redirect this session to another URL.
	 * 
	 * Execution halts within this function after redirect has been issued. 
	 * 
	 * @param string $url URL to redirect to
	 * @param bool $http301 Should this be a permanent (301) redirect? (default=true). If false, it is a 302 temporary redirect.
	 *
	 */
	public function ___redirect($url, $http301 = true) {

		// if there are notices, then queue them so that they aren't lost
		$notices = $this->fuel('notices'); 
		if(count($notices)) foreach($notices as $notice) {
			$this->queueNotice($notice->text, $notice instanceof NoticeError ? 'error' : 'message', $notice->flags); 
		}

		// perform the redirect
		if($this->wire('page')) {
			$process = $this->wire('modules')->get('ProcessPageView'); 
			$process->setResponseType(ProcessPageView::responseTypeRedirect); 
			$process->finished();
		}
		if($http301) header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		exit(0);
	}

	/**
	 * Manually close the session, before program execution is done
	 * 
	 */
	public function close() {
		session_write_close();
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
	 * @param string $text
	 * @param int $flags See Notice::flags
	 * @return $this
	 *
	 */
	public function message($text, $flags = 0) {
		$this->queueNotice($text, 'message', $flags); 
		return $this;
	}

	/**
	 * Queue an error to appear the next time session is instantiated
	 *
	 * @param string $text
	 * @param int $flags See Notice::flags
	 * @return $this
	 * 
	 */
	public function error($text, $flags = 0) {
		$this->queueNotice($text, 'error', $flags); 
		return $this; 
	}


}
