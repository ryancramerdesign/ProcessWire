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
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 *
 * @see https://processwire.com/api/cheatsheet/#session Cheatsheet
 * @see https://processwire.com/api/variables/session/ Offical $session API variable Documentation
 *
 * @method User login() login($name, $pass) Login the user identified by $name and authenticated by $pass. Returns the user object on successful login or null on failure.
 * @method Session logout() logout() Logout the current user, and clear all session variables.
 * @method redirect() redirect($url, $http301 = true) Redirect this session to the specified URL. 
 *
 * Expected $config variables include: 
 * ===================================
 * string $config->sessionName Name of session on http
 * string $config->sessionNameSecure Name of session on https
 * int $config->sessionExpireSeconds Number of seconds of inactivity before session expires
 * bool $config->sessionChallenge True if a separate challenge cookie should be used for validating sessions
 * bool $config->sessionFingerprint True if a fingerprint should be kept of the user's IP & user agent to validate sessions
 * bool $config->sessionCookieSecure Use secure cookies or session? (default=true)
 * 
 * @todo enable login/forceLogin to recognize non-HTTP use of login, when no session needs to be maintained
 * @todo add a default $config->apiUser to be used when non-HTTP/bootstrap usage
 *
 */

class Session extends Wire implements IteratorAggregate {

	/**
	 * Fingerprint bitmask: Use remote addr (recommended)
	 * 
	 */
	const fingerprintRemoteAddr = 2;
	
	/**
	 * Fingerprint bitmask: Use client provided addr
	 *
	 */
	const fingerprintClientAddr = 4;
	
	/**
	 * Fingerprint bitmask: Use user agent (recommended)
	 *
	 */
	const fingerprintUseragent = 8; 
	

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
	 * Set to true when maintenance should be skipped
	 * 
	 * @var bool
	 * 
	 */
	protected $skipMaintenance = false;

	/**
	 * Start the session and set the current User if a session is active
	 *
	 * Assumes that you have already performed all session-specific ini_set() and session_name() calls 
	 *
	 */
	public function __construct() {

		$this->config = $this->wire('config'); 
		$this->init();
		unregisterGLOBALS();
		$className = $this->className();
		$user = null;

		if(empty($_SESSION[$className])) $_SESSION[$className] = array();

		if($userID = $this->get('_user', 'id')) {
			if($this->isValidSession($userID)) {
				$user = $this->wire('users')->get($userID); 
			} else {
				$this->logout();
			}
		}

		if(!$user || !$user->id) $user = $this->wire('users')->getGuestUser();
		$this->wire('users')->setCurrentUser($user); 	

		foreach(array('message', 'error', 'warning') as $type) {
			$items = $this->get($type); 
			if(is_array($items)) foreach($items as $item) {
				list($text, $flags) = $item;
				parent::$type($text, $flags); 
			}
			// $this->remove($type);
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
	 * @param int $userID
	 * @return bool
	 *
	 */
	protected function ___isValidSession($userID) {

		$valid = true; 
		$reason = '';
		$sessionName = session_name();

		// check challenge cookie
		if($this->config->sessionChallenge) {
			if(empty($_COOKIE[$sessionName . "_challenge"]) || ($this->get('_user', 'challenge') != $_COOKIE[$sessionName . "_challenge"])) {
				$valid = false; 
				$reason = "Error: Invalid challenge value";
			}
		}	

		// check fingerprint
		if(!$this->isValidFingerprint()) {
			$reason = "Error: Session fingerprint changed (IP address or useragent)";
			$valid = false;
		}
	
		// check session expiration
		if($this->config->sessionExpireSeconds) {
			$ts = (int) $this->get('_user', 'ts');
			if($ts < (time() - $this->config->sessionExpireSeconds)) {
				// session time expired
				$valid = false;
				$this->error($this->_('Session timed out'));
				$reason = "Session timed out (session older than {$this->config->sessionExpireSeconds} seconds)";
			}
		}

		if($valid) {
			// if valid, update last request time
			$this->set('_user', 'ts', time());
			
		} else if($reason && $userID && $userID != $this->wire('config')->guestUserPageID) {
			// otherwise log the invalid session
			$user = $this->wire('users')->get((int) $userID);
			if($user && $user->id) $reason = "User '$user->name' - $reason";
			$reason .= " (IP: " . $this->getIP() . ")";
			$this->log($reason);
		}

		return $valid; 
	}

	/**
	 * Returns whether or not the current session fingerprint is valid
	 * 
	 * @return bool
	 * 
	 */
	protected function isValidFingerprint() {
		$fingerprint = $this->getFingerprint();
		if($fingerprint === false) return true; // fingerprints off
		if($fingerprint !== $this->get('_user', 'fingerprint')) return false;
		return true; 
	}

	/**
	 * Generate a session fingerprint
	 * 
	 * @return bool|string Returns false if fingerprints not enabled. Returns string if enabled.
	 * 
	 */
	protected function getFingerprint() {
		
		$useFingerprint = $this->config->sessionFingerprint;
		if(!$useFingerprint) return false;

		if(is_bool($useFingerprint) || $useFingerprint == 1) {
			// default (boolean true)
			$useFingerprint = self::fingerprintRemoteAddr | self::fingerprintUseragent;
		}

		$fingerprint = '';
		if($useFingerprint & self::fingerprintRemoteAddr) $fingerprint .= $this->getIP(true);
		if($useFingerprint & self::fingerprintClientAddr) $fingerprint .= $this->getIP(false, 2);
		if($useFingerprint & self::fingerprintUseragent) {
			$fingerprint .= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		}
		$fingerprint = md5($fingerprint);
		
		return $fingerprint;
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
	 * @param bool|int $useClient Give preference to client headers for IP? HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR (default=false)
	 * 	Specify integer 2 to include potentially multiple CSV separated IPs (when provided by client).
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
			if(strpos($ip, ',') !== false && $useClient !== 2) {
				list($ip) = explode(',', $ip);
			}
			// sanitize: if IP contains something other than digits, periods, commas, spaces, 
			// then don't use it and instead fallback to the REMOTE_ADDR. 
			$test = str_replace(array('.', ',', ' '), '', $ip); 
			if(!ctype_digit("$test")) $ip = $_SERVER['REMOTE_ADDR'];

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
	 * @param string|User $name May be user name or User object
	 * @param string $pass Raw, non-hashed password
	 * @param bool $force Specify boolean true to login user without requiring a password ($pass argument can be blank, or anything)
	 * 	You can also use the $session->forceLogin($user) method to force a login without a password. 
	 * @return User Return the $user if the login was successful or null if not. 
	 *
	 */
	public function ___login($name, $pass, $force = false) {
	
		$user = null;		
		if(is_object($name) && $name instanceof User) {
			$user = $name;
			$name = $user->name;
		} else {
			$name = $this->wire('sanitizer')->pageName($name);
		}

		if(!$this->allowLogin($name)) {
			$this->loginFailure($name, "User is not allowed to login");
			return null;
		}

		if(is_null($user)) {
			$user = strlen($name) ? $this->wire('users')->get("name=$name") : null;
		}

		if(	$user && $user->id 
			&& $user->id != $this->wire('config')->guestUserPageID 
			&& ($force === true || $this->authenticate($user, $pass))) { 

			$this->trackChange('login', $this->wire('user'), $user); 
			session_regenerate_id(true);
			$this->set('_user', 'id', $user->id); 
			$this->set('_user', 'ts', time());

			if($this->config->sessionChallenge) {
				// create new challenge
				$pass = new Password();
				$challenge = $pass->randomBase64String(32);
				$this->set('_user', 'challenge', $challenge); 
				$secure = $this->config->sessionCookieSecure ? (bool) $this->config->https : false;
				// set challenge cookie to last 30 days (should be longer than any session would feasibly last)
				setcookie(session_name() . '_challenge', $challenge, time()+60*60*24*30, '/', null, $secure, true); // PR #1264
			}

			if($this->config->sessionFingerprint) { 
				// remember a fingerprint that tracks the user's IP and user agent
				$this->set('_user', 'fingerprint', $this->getFingerprint()); 
			}

			$this->setFuel('user', $user); 
			$this->get('CSRF')->resetAll();
			$this->loginSuccess($user); 

			return $user; 
			
		} else {
			if(!$user || !$user->id) {
				$reason = "Unknown user: $name";
			} else if($user->id == $this->wire('config')->guestUserPageID) {
				$reason = "Guest user may not login";
			} else {
				$reason = "Invalid password";
			}
			$this->loginFailure($name, $reason); 
		}

		return null; 
	}

	/**
	 * Login a user without requiring a password
	 * 
	 * @param string|User $user Username or User object
	 * @return User|null Returns User object on success, or null on failure
	 * 
	 */
	public function forceLogin($user) {
		return $this->login($user, '', true);
	}

	/**
	 * Login success method for hooks
	 *
	 * @param User $user
	 *
	 */
	protected function ___loginSuccess(User $user) { 
		$this->log("Successful login for '$user->name'"); 
	}
	
	protected function ___loginFailure($name, $reason) { 
		$this->log("Error: Failed login for '$name' - $reason"); 
	}

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
		$time = time() - 42000;
		$secure = $this->config->sessionCookieSecure ? (bool) $this->config->https : false;
		if(isset($_COOKIE[$sessionName])) setcookie($sessionName, '', $time, '/', null, $secure, true);
		if(isset($_COOKIE[$sessionName . "_challenge"])) setcookie($sessionName . "_challenge", '', $time, '/', null, $secure, true);
		session_destroy();
		session_name($sessionName); 
		$this->init();
		session_regenerate_id(true);
		$_SESSION[$this->className()] = array();
		$user = $this->wire('user');
		if($user) $this->logoutSuccess($user); 
		$guest = $this->wire('users')->getGuestUser();
		if($this->wire('languages') && "$user->language" != "$guest->language") $guest->language = $user->language;
		$this->wire('users')->setCurrentUser($guest);
		$this->trackChange('logout', $user, $guest); 
		return $this; 
	}

	/**
	 * Logout success method for hooks
	 *
	 * @param User $user
	 *
	 */
	protected function ___logoutSuccess(User $user) { 
		$this->log("Logout for '$user->name'"); 
	}

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
		$notices = $this->wire('notices'); 
		if(count($notices)) foreach($notices as $notice) {
			if($notice instanceof NoticeWarning) $noticeType = 'warning';
				else if($notice instanceof NoticeError) $noticeType = 'error';
				else $noticeType = 'message';
			$this->queueNotice($notice->text, $noticeType, $notice->flags); 
		}

		// perform the redirect
		$page = $this->wire('page');
		if($page) {
			// ensure ProcessPageView is properly closed down
			$process = $this->wire('modules')->get('ProcessPageView'); 
			$process->setResponseType(ProcessPageView::responseTypeRedirect); 
			$process->finished();
			// retain modal=1 get variables through redirects (this can be moved to a hook later)
			if($page->template == 'admin' && $this->wire('input')->get('modal') && strpos($url, '//') === false) {
				if(!strpos($url, 'modal=')) $url .= (strpos($url, '?') !== false ? '&' : '?') . 'modal=1'; 
			}
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
	 * @param string $text
	 * @param string $type One of "message", "error" or "warning"
	 * @param int $flags
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

	/**
	 * Queue a warning to appear the next time session is instantiated
	 *
	 * @param string $text
	 * @param int $flags See Notice::flags
	 * @return $this
	 *
	 */
	public function warning($text, $flags = 0) {
		$this->queueNotice($text, 'warning', $flags);
		return $this;
	}

	/**
	 * Session maintenance
	 * 
	 * This is automatically called by ProcessWire at the end of the request,
	 * no need to call it on your own. 
	 *
	 * Keep track of session history, if $config->sessionHistory is used.
	 * It can be retrieved with the $session->getHistory() method.
	 * 
	 * @todo add extra gc checks
	 *
	 */
	public function maintenance() {

		if($this->skipMaintenance) return;
		
		// prevent multiple calls, just in case
		$this->skipMaintenance = true; 
		
		$historyCnt = (int) $this->config->sessionHistory;
		
		if($historyCnt) {
			
			$history = $this->get('_user', 'history');
			if(!is_array($history)) $history = array();

			$item = array(
				'time' => time(),
				'url'  => $this->wire('sanitizer')->entities($this->wire('input')->httpUrl()),
				'page' => $this->wire('page')->id,
			);

			$cnt = count($history); 
			if($cnt) {
				end($history);
				$lastKey = key($history);
				$nextKey = $lastKey+1;
				if($cnt >= $historyCnt) $history = array_slice($history, -1 * ($historyCnt-1), null, true); 
			} else {
				$nextKey = 0;
			}

			$history[$nextKey] = $item;
			$this->set('_user', 'history', $history);
		}
	}

	/**
	 * Get the session history (if enabled)
	 * 
	 * Applicable only if $config->sessionHistory > 0.
	 * 
	 * @return array of history entries: 
	 * 	pageViewNum => array(
	 * 		'url' => 'http://domain.com/path/to/page/', 
	 * 		'page' => 123, 
	 * 		'time' => 12345678
	 * 	); 
	 * 
	 */
	public function getHistory() {
		$value = $this->get('_user', 'history'); 
		if(!is_array($value)) $value = array();
		return $value; 
	}

	/**
	 * Remove queued notices
	 * 
	 */
	public function removeNotices() {
		foreach(array('message', 'error', 'warning') as $type) {
			$this->remove($type);
		}
	}

}
