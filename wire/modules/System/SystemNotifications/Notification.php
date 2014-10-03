<?php

/**
 * An individual notification item to be part of a NotificationArray for a Page
 *
 */
class Notification extends WireData {

	const flagMessage = 2; 		// informational
	const flagWarning = 4;		// warning 
	const flagError = 8;		// error 
	const flagDebug = 16; 		// Show/save only when the system is in debug mode
	const flagNotice = 32; 		// Show only as a single-request notice (not stored in DB)
	const flagSession = 64; 	// Notification lasts for only this session (not stored in DB)
	const flagEmail = 1024; 	// title and body will also be emailed to user (if page is user)

	static protected $_flagNames = array(
		self::flagMessage => 'message',
		self::flagWarning => 'warning',
		self::flagError => 'error',
		self::flagNotice => 'notice',
		self::flagDebug => 'debug',
		self::flagSession => 'session',
		self::flagEmail => 'email',
		);

	protected $page; 

	/**
	 * Construct a new Notification
	 *
	 */
	public function __construct() {

		// db native vars
		$this->set('pages_id', 0); 	// page ID notification is for (likely a User page)
		$this->set('sort', 0); 		// sort value, as required by Fieldtype
		$this->set('src_id', 0); 	// page ID when notification was generated
		$this->set('title', ''); 	// title/headline
		$this->set('flags', 0);		// flags: see flag constants 
		$this->set('created', 0); 	// datetime created (unix timestamp)
		$this->set('modified', 0); 	// datetime created (unix timestamp)
		$this->set('qty', 1); 		// quantity of times this notification has been repeated

		// data encoded vars, all optional
		$this->set('id', ''); 		// unique ID (among others the user may have)
		$this->set('text', ''); 	// extended text
		$this->set('html', ''); 	// extended text as HTML markup 
		$this->set('from', '');		// "from" text where applicable, like a class name
		$this->set('icon', ''); 	// fa-icon when applicable
		$this->set('href', ''); 	// clicking notification goes to this URL
		$this->set('progress', 0); 	// progress percent 0-100
		$this->set('expires', 0); 	// datetime after which will automatically be deleted
		
	}

	public function is($name) {
		$flags = $this->flagNamesToFlags($name); 
		$is = 0;
		foreach($flags as $flag) { 
			if($this->flags & $flag) $is++;
		}
		return $is == count($flags); 
	}

	protected function flagNameToFlag($name) {
		if(is_string($name)) {
			$flag = array_search($name, self::$_flagNames); 
			if(!$flag) throw new WireException("Unknown flag: $name"); 
		} else {
			$flag = $name;
			if(!isset(self::$_flagNames[$flag])) throw new WireException("Unknown flag: $flag"); 
		}
		return $flag;
	}

	protected function flagNamesToFlags($names) {
		if(strpos($names, ',') !== false) $names = str_replace(',', ' ', $names); 
		$names = explode(' ', $names); 
		$flags = array();
		foreach($names as $name) {
			if(empty($name)) continue; 
			$flag = $this->flagNameToFlag($name); 
			if($flag) $flags[$name] = $flag;
		}
		return $flags; 
	}

	/**
	 * Set a named flag
	 *
	 * @param string|int $name Flag to set
	 * @param bool $add True to add flag, false to remove
	 * @return this
	 *
	 */
	public function setFlag($name, $add = true) {

		$flag = ctype_digit("$name") ? (int) $name : $this->flagNameToFlag($name); 	
		$flags = parent::get('flags'); 

		if($add) {
			// add flag
			if($flags & $flag) {
				// flag is already set
			} else {
				$flags = $flags | $flag;
				parent::set('flags', $flags); 
			}
		} else {
			// remove flag
			if($flags & $flag) {
				// flag is set, remove it
				$flags = $flags & ~$flag;
			} else {
				// flag is not set
			}
		}

		return $this; 
	}

	public function setFlags($names, $add = true) {
		$flags = $this->flagNamesToFlags($names); 
		foreach($flags as $name => $flag) {
			$this->setFlag($flag, $add); 	
		}
		return $this; 
	}

	/**
	 * Set a value to the Notification
	 *
	 */
	public function set($key, $value) {
 
		if($key == 'page') {
			$this->page = $value; 
			return $this; 

		} else if($key == 'created' || $key == 'modified' || $key == 'expires') {
			// convert date string to unix timestamp
			if($value && !ctype_digit("$value")) $value = strtotime($value); 	

			// sanitized date value is always an integer
			$value = (int) $value; 

		} else if($key == 'title' || $key == 'from') {
			// regular text sanitizer
			$value = $this->sanitizer->text($value); 

		} else if($key == 'text') {
			// regular text sanitizer
			$value = $this->sanitizer->textarea($value); 

		} else if(in_array($key, array('pages_id', 'sort', 'src_id', 'flags', 'progress'))) {
			$value = (int) $value; 
		}

		return parent::set($key, $value); 
	}

	public function getID() {

		$id = parent::get('id'); 
		if($id) return $id; 

		$id = 	parent::get('title') . ',' . 
			parent::get('created') . ',' . 
			parent::get('from') . ',' . 
			parent::get('src_id') . ',' . 
			($this->page ? $this->page->id : '?') . ',';
			parent::get('flags');

		return md5($id); 
	}

	/**
	 * Retrieve a value from the Notification
	 *
	 */
	public function get($key) {

		if($key == 'id') return $this->getID();

		if($key == 'flagNames') {
			$flags = parent::get('flags');
			$flagNames = array();
			foreach(self::$_flagNames as $val => $name) {
				if($flags & $val) $flagNames[$val] = $name;
			}
			return $flagNames;
		}

		$value = parent::get($key); 

		// if the page's output formatting is on, then we'll return formatted values
		if($this->page && $this->page->of()) {

			if($key == 'created' || $key == 'expires' || $key == 'modified') {
				// format a unix timestamp to a date string
				$value = date('Y-m-d H:i:s', $value); 				

			} else if($key == 'title' || $key == 'text') {
				// return entity encoded versions of strings
				$value = $this->sanitizer->entities($value); 
			}
		}

		return $value; 
	}

	public function __toString() {
		$str = $this->title; 
		if($this->text) $str .= " - $this->text";
			else if($this->html) $str .= " - " . strip_tags($this->html);
		$str .= " (" . implode(', ', $this->get('flagNames')) . ")";
		return $str; 
	}


}

