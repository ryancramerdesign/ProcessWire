<?php

/**
 * ProcessWire WireInputData and WireInput
 *
 * WireInputData and the WireInput class together form a simple 
 * front end to PHP's $_GET, $_POST, and $_COOKIE superglobals.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

/**
 * WireInputData manages one of GET, POST, COOKIE, or whitelist
 * 
 * Vars retrieved from here will not have to consider magic_quotes.
 * No sanitization or filtering is done, other than disallowing multi-dimensional arrays in input. 
 *
 * WireInputData specifically manages one of: get, post, cookie or whitelist, whereas the Input class 
 * provides access to the 3 InputData instances.
 *
 * Each WireInputData is not instantiated unless specifically asked for. 
 *
 *
 * @link http://processwire.com/api/variables/input/ Offical $input API variable Documentation
 *
 */
class WireInputData implements ArrayAccess, IteratorAggregate, Countable {

	protected $stripSlashes = false;
	protected $data = array();

	public function __construct(array $input = array()) {
		$this->stripSlashes = get_magic_quotes_gpc();
		$this->setArray($input); 
	}

	public function setArray(array $input) {
		foreach($input as $key => $value) $this->__set($key, $value); 
		return $this; 
	}

	public function getArray() {
		return $this->data; 
	}

	public function __set($key, $value) {
		if(is_string($value) && $this->stripSlashes) $value = stripslashes($value); 
		if(is_array($value)) $value = $this->cleanArray($value); 
		$this->data[$key] = $value; 
	}

	protected function cleanArray(array $a) {
		$clean = array();
		foreach($a as $key => $value) {
			if(is_array($value)) continue; // we only allow one dimensional arrays
			if(is_string($value) && $this->stripSlashes) $value = stripslashes($value); 
			$clean[$key] = $value; 
		}
		return $clean;	
	}

	public function setStripSlashes($stripSlashes) {
		$this->stripSlashes = $stripSlashes ? true : false; 
	}

	public function __get($key) {
		if($key == 'whitelist') return $this->whitelist; 
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	public function offsetExists($key) {
		return isset($this->data[$key]); 
	}

	public function offsetGet($key) {
		return $this->__get($key); 
	}

	public function offsetSet($key, $value) {
		$this->__set($key, $value); 
	}

	public function offsetUnset($key) {
		unset($this->data[$key]); 
	}

	public function count() {
		return count($this->data); 
	}

	public function removeAll() {
		$this->data = array();
	}

	public function __isset($key) {
		return $this->offsetExists($key); 
	}

	public function __unset($key) {
		return $this->offsetUnset($key); 
	}

	public function queryString() {
		return http_build_query($this->getArray()); 
	}
}

/**
 * Manages the group of GET, POST, COOKIE and whitelist vars, each of which is a WireInputData object.
 *
 * @link http://processwire.com/api/variables/input/ Offical $input API variable Documentation
 * 
 * @property string[] $urlSegments Retrieve all URL segments (array). This requires url segments are enabled on the template of the requested page. You can turn it on or off under the url tab when editing a template.
 * @property WireInputVars $post POST variables
 * @property WireInputVars $get GET variables
 * @property WireInputVars $cookie COOKIE variables
 * @property WireInputVars $whitelist Whitelisted variables
 * @property int $pageNum Current page number (where 1 is first)
 * @property string $urlSegmentsStr String of current URL segments, separated by slashes, i.e. a/b/c
 * @property string $urlSegmentStr Alias of urlSegmentsStr
 * @property string $url Current requested URL including page numbers and URL segments, excluding query string. 
 * @property string $httpUrl Like $url but includes the scheme/protcol and hostname. 
 * @property string $queryString Current query string
 * @property string $scheme Current scheme/protcol, i.e. http or https
 * 
 * @property string $urlSegment1 First URL segment
 * @property string $urlSegment2 Second URL segment
 * @property string $urlSegment3 Third URL segment
 * @property string $urlSegment4 Fourth URL segment
 * @property string $urlSegment5 Fifth URL segment, and so on...
 *
 */
class WireInput {

	protected $getVars = null;
	protected $postVars = null;
	protected $cookieVars = null;
	protected $whitelist = null;
	protected $urlSegments = array();
	protected $pageNum = 1; 

	/**
	 * Retrieve a GET value or all GET values
	 *
	 * @param string $key
	 * 	If populated, returns the value corresponding to the key or NULL if it doesn't exist.
	 *	If blank, returns reference to the WireDataInput containing all GET vars. 
	 * @return null|mixed|WireInputData
	 *
	 */
	public function get($key = '') {
		if(is_null($this->getVars)) {
			$this->getVars = new WireInputData($_GET);
			$this->getVars->offsetUnset('it');
		}
		return $key ? $this->getVars->__get($key) : $this->getVars; 
	}

	/**
	 * Retrieve a POST value or all POST values
	 *
	 * @param string $key
	 *	If populated, returns the value corresponding to the key or NULL if it doesn't exist.
	 *	If blank, returns reference to the WireDataInput containing all POST vars. 
	 * @return null|mixed|WireInputData
	 *
	 */
	public function post($key = '') {
		if(is_null($this->postVars)) $this->postVars = new WireInputData($_POST); 
		return $key ? $this->postVars->__get($key) : $this->postVars; 
	}

	/**
	 * Retrieve a COOKIE value or all COOKIE values
	 *
	 * @param string $key
	 *	If populated, returns the value corresponding to the key or NULL if it doesn't exist.
	 *	If blank, returns reference to the WireDataInput containing all COOKIE vars. 
	 * @return null|mixed|WireInputData
	 *
	 */
	public function cookie($key = '') {
		if(is_null($this->cookieVars)) $this->cookieVars = new WireInputData($_COOKIE); 
		return $key ? $this->cookieVars->__get($key) : $this->cookieVars; 
	}

	/**
	 * Get or set a whitelist var
	 *	
	 * Whitelist vars are used by modules and templates and assumed to be clean.
	 * 
	 * The whitelist is a list of variables specifically set by the application as clean for use elsewhere in the application.
	 * Only the version returned from this method should be considered clean.
	 * This whitelist is not specifically used by ProcessWire unless you populate it from your templates or the API. 
	 *
	 * @param string $key 
	 * 	If $key is blank, it assumes you are asking to return the entire whitelist. 
	 *	If $key and $value are populated, it adds the value to the whitelist.
	 * 	If $key is an array, it adds all the values present in the array to the whitelist.
	 * 	If $value is ommited, it assumes you are asking for a value with $key, in which case it returns it. 
	 * @param mixed $value
	 * 	See explanation for the $key param
	 * @return null|mixed|WireInputData
	 * 	See explanation for the $key param 
	 *
	 */
	public function whitelist($key = '', $value = null) {
		if(is_null($this->whitelist)) $this->whitelist = new WireInputData(); 
		if(!$key) return $this->whitelist; 
		if(is_array($key)) return $this->whitelist->setArray($key); 
		if(is_null($value)) return $this->whitelist->__get($key); 
		$this->whitelist->__set($key, $value); 
		return $this->whitelist; 
	}

	/**
	 * Retrieve the URL segment with index $num
	 *
	 * Note that the index is 1 based (not 0 based).
	 * The maximum segments allowed can be adjusted in your /site/config.php.
	 *
	 * @param int $num Retrieve the $n'th URL segment (integer).
	 * @return string Returns a blank string if the specified index is not found
	 *
	 */
	public function urlSegment($num = 1) {
		if($num < 1) $num = 1; 
		return isset($this->urlSegments[$num]) ? $this->urlSegments[$num] : '';
	}

	/**
	 * Set a URL segment value 
	 *
	 * To unset, specify NULL as the value. 
	 *
	 * @param int $num Number of this URL segment (1 based)
	 * @param string|null $value 
	 *
	 */
	public function setUrlSegment($num, $value) {
		$num = (int) $num; 
		if(is_null($value)) {
			// unset
			$n = 0;
			$urlSegments = array();
			foreach($this->urlSegments as $k => $v) {
				if($k == $num) continue; 
				$urlSegments[++$n] = $v; 	
			}
			$this->urlSegments = $urlSegments; 
		} else {
			// set
			$this->urlSegments[$num] = wire('sanitizer')->name($value); 	
		}
	}

	/**
	 * Return the current page number. 
	 *
	 * First page number is 1 (not 0). 
	 *
	 * @return int
	 *
	 */
	public function pageNum() {
		return $this->pageNum; 	
	}

	/**
	 * Set the current page number. 
	 *
	 * Note that the first page should be 1 (not 0).
	 *
	 * @param int $num
	 *
	 */
	public function setPageNum($num) {
		$this->pageNum = (int) $num;	
	}

	/**	
	 * Retrieve the get, post, cookie or whitelist vars using a direct reference, i.e. $input->cookie
	 *
	 * Can also be used with URL segments, i.e. $input->urlSegment1, $input->urlSegment2, $input->urlSegment3, etc. 
	 * And can also be used for $input->pageNum.
	 *
	 * @param string $key
	 * @return string|int|null
	 *
	 */
	public function __get($key) {

		if($key == 'pageNum') return $this->pageNum; 
		if($key == 'urlSegments') return $this->urlSegments; 
		if($key == 'urlSegmentsStr' || $key == 'urlSegmentStr') return $this->urlSegmentStr();
		if($key == 'url') return $this->url();
		if($key == 'httpUrl' || $key == 'httpURL') return $this->httpUrl();
		if($key == 'fragment') return $this->fragment();
		if($key == 'queryString') return $this->queryString();
		if($key == 'scheme') return $this->scheme();

		if(strpos($key, 'urlSegment') === 0) {
			if(strlen($key) > 10) $num = (int) substr($key, 10); 
				else $num = 1; 
			return $this->urlSegment($num);
		}

		$value = null;
		$gpc = array('get', 'post', 'cookie', 'whitelist'); 

		if(in_array($key, $gpc)) {
			$value = $this->$key(); 

		} else {
			// Like PHP's $_REQUEST where accessing $input->var considers get/post/cookie/whitelist
			// what it actually considers depends on what's set in the $config->wireInputOrder variable
			$order = (string) wire('config')->wireInputOrder; 
			if(!$order) return null;
			$types = explode(' ', $order); 
			foreach($types as $t) {
				if(!in_array($t, $gpc)) continue; 	
				$value = $this->$t($key); 
				if(!is_null($value)) break;
			}
		}
		return $value; 
	}
	
	public function urlSegmentStr() {
		return implode('/', $this->urlSegments);
	}

	public function __isset($key) {
		return $this->__get($key) !== null;
	}

	/**
	 * URL that initiated the current request, including URL segments
	 * 
	 * Note that this does not include query string or fragment
	 * 
	 * @return string
	 * 
	 */
	public function url() {

		$url = '';
		$page = wire('page'); 
		
		if($page && $page->id) {
			// pull URL from page
			$url = $page && $page->id ? wire('page')->url : '';
			$segmentStr = $this->urlSegmentStr();
			$pageNum = $this->pageNum();
			if(strlen($segmentStr) || $pageNum > 1) {
				if($segmentStr) $url = rtrim($url, '/') . '/' . $segmentStr;
				if($pageNum > 1) $url = rtrim($url, '/') . '/' . wire('config')->pageNumUrlPrefix . $pageNum;
				if(isset($_SERVER['REQUEST_URI'])) {
					$info = parse_url($_SERVER['REQUEST_URI']);
					if(!empty($info['path']) && substr($info['path'], -1) == '/') $url .= '/'; // trailing slash
				}
			}
			
		} else if(isset($_SERVER['REQUEST_URI'])) {
			// page not yet available, attempt to pull URL from request uri
			$parts = explode('/', $_SERVER['REQUEST_URI']); 
			foreach($parts as $part) {
				$url .= "/" . wire('sanitizer')->pageName($part);
			}
			$info = parse_url($_SERVER['REQUEST_URI']);
			if(!empty($info['path']) && substr($info['path'], -1) == '/') {
				$url = rtrim($url, '/') . '/'; // trailing slash
			}
		}
		
		return $url;
	}

	/**
	 * URL including scheme
	 * 
	 * @return string
	 * 
	 */
	public function httpUrl() {
		return $this->scheme() . '://' . wire('config')->httpHost . $this->url();
	}

	/**
	 * Anchor/fragment for current request (i.e. #fragment)
	 * 
	 * Note that this is not sanitized. Fragments generally can't be seen
	 * by the server, so this function may be useless.
	 *
	 * @return string
	 *
	 */
	public function fragment() {
		if(strpos($_SERVER['REQUEST_URI'], '#') === false) return '';
		$info = parse_url($_SERVER['REQUEST_URI']);
		return empty($info['fragment']) ? '' : $info['fragment']; 
	}

	/**
	 * Return the query string that was part of this request or blank if none
	 * 
	 * Note that this is not sanitized.
	 * 
	 * @return string
	 * 
	 */
	public function queryString() {
		return $this->getVars->queryString();
	}

	/**
	 * Return the current access scheme/protocol 
	 *
	 * Note that this is only useful for http/https, as we don't detect other schemes.
	 *
	 * @return string either "https" or "http"
	 *
	 */
	public function scheme() {
		return wire('config')->https ? 'https' : 'http'; 
	}
}

