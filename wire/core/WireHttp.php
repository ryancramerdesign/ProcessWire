<?php

/**
 * ProcessWire HTTP tools
 *
 * Provides capability for sending POST/GET requests to URLs
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2014 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class WireHttp extends Wire {
	
	const debug = false; 

	/**
	 * Default value for $headers, when reset
	 *
	 */
	protected $defaultHeaders = array(
		'charset' => 'utf-8',
		);

	/**
	 * Schemes wwe are allowed to use
	 *
	 */
	protected $allowSchemes = array('http', 'https'); 

	/**
	 * Headers to include in the request
	 *
	 */
	protected $headers = array();

	/**
	 * HTTP error codes
	 * 
	 * @var array
	 * 
	 */
	protected $errorCodes = array(
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		419 => 'Authentication Timeout (not in RFC 2616)',
		420 => 'Enhance Your Calm ',
		422 => 'Unprocessable Entity (WebDAV; RFC 4918)',
		423 => 'Locked (WebDAV; RFC 4918)',
		424 => 'Failed Dependency (WebDAV; RFC 4918)',
		426 => 'Upgrade Required',
		428 => 'Precondition Required (RFC 6585)',
		429 => 'Too Many Requests (RFC 6585)',
		431 => 'Request Header Fields Too Large (RFC 6585)',
		440 => 'Login Timeout (Microsoft)',
		444 => 'No Response (Nginx)',
		449 => 'Retry With (Microsoft)',
		450 => 'Blocked by Windows Parental Controls (Microsoft)',
		451 => 'Redirect (Microsoft)',
		451 => 'Unavailable For Legal Reasons (Internet draft)',
		494 => 'Request Header Too Large (Nginx)',
		495 => 'Cert Error (Nginx)',
		496 => 'No Cert (Nginx)',
		497 => 'HTTP to HTTPS (Nginx)',
		498 => 'Token expired/invalid (Esri)',
		499 => 'Client Closed Request (Nginx)',
		499 => 'Token required (Esri)',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates (RFC 2295)',
		507 => 'Insufficient Storage (WebDAV; RFC 4918)',
		508 => 'Loop Detected (WebDAV; RFC 5842)',
		509 => 'Bandwidth Limit Exceeded (Apache bw/limited extension)[25]',
		510 => 'Not Extended (RFC 2774)',
		511 => 'Network Authentication Required (RFC 6585)',
		520 => 'Origin Error (Cloudflare)',
		521 => 'Web server is down (Cloudflare)',
		522 => 'Connection timed out (Cloudflare)',
		523 => 'Proxy Declined Request (Cloudflare)',
		524 => 'A timeout occurred (Cloudflare)',
		598 => 'Network read timeout error (Unknown)',
		599 => 'Network connect timeout error (Unknown)',
		);

	/**
	 * Last HTTP code
	 * 
	 * @var int
	 * 
	 */
	protected $httpCode = 0;

	/**
	 * Data to send in the request
	 *
	 */
	protected $data = array();

	/**
	 * Raw data, when data is not an array
	 *
	 */
	protected $rawData = null;

	/**
	 * Last response header
	 *
	 */
	protected $responseHeader = array();

	/**
	 * Last response headers parsed into key => value properties
	 * 
	 * Note that keys are always lowercase
	 *
	 */
	protected $responseHeaders = array();
	
	/**
	 * Last error message
	 *
	 */
	protected $error = '';

	/**
	 * Whether the system supports CURL
	 * 
	 * @var bool
	 * 
	 */
	protected $hasCURL = false;
	
	/**
	 * Whether the system supports fopen of URLs 
	 *
	 * @var bool
	 *
	 */
	protected $hasFopen = false;

	/**
	 * Construct/initialize
	 * 
	 */
	public function __construct() {
		$this->hasCURL = function_exists('curl_init') && !ini_get('safe_mode') && !ini_get('open_basedir');
		$this->hasFopen = ini_get('allow_url_fopen');
		$this->resetRequest();
		$this->resetResponse();
	}

	/**
	 * Send to a URL using POST
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	public function post($url, $data = array()) {
		if(!isset($this->headers['content-type'])) $this->setHeader('content-type', 'application/x-www-form-urlencoded; charset=utf-8');
		return $this->send($url, $data, 'POST');
	}

	/**
	 * Send to a URL using GET
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data to send
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	public function get($url, $data = array()) {
		return $this->send($url, $data, 'GET');
	}

	/**
	 * Send to a URL that responds with JSON using GET and return the resulting array or object
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param bool $assoc Default is to return an array (specified by TRUE). If you want an object instead, specify FALSE. 
	 * @param mixed $data Array of data to send (if not already set before) or raw data to send
	 * @return bool|array|object False on failure or an array or object on success. 
	 *
	 */
	public function getJSON($url, $assoc = true, $data = array()) {
		return json_decode($this->get($url, $data), $assoc); 
	}

	/**
	 * Send to a URL using HEAD (@horst)
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data to send
	 * @return bool|array False on failure or Arrray with ResponseHeaders on success.
	 *
	 */
	public function head($url, $data = array()) {
		$responseHeader = $this->send($url, $data, 'HEAD');
		return is_array($responseHeader) ? $responseHeader : false;
	}

	/**
	 * Send to a URL using HEAD and return the status code (@horst)
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data
	 * @param bool $textMode When true function will return a string rather than integer, see the statusText() method.
	 * @return bool|integer|string False on failure or integer of status code (200|404|etc) on success.
	 *
	 */
	 public function status($url, $data = array(), $textMode = false) {
		$responseHeader = $this->send($url, $data, 'HEAD');
		if(!is_array($responseHeader)) return false;
		$statusCode = (preg_match("=^(HTTP/\d+\.\d+) (\d{3}) (.*)=", $responseHeader[0], $matches) === 1) ? intval($matches[2]) : false;
		if($textMode) $statusCode = isset($matches[3]) ? "$statusCode $matches[3]" : "$statusCode";
		return $statusCode;
	}

	/**
	 * Send to a URL using HEAD and return the status code and text like "200 OK"
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data
	 * @return bool|string False on failure or string of status code + text on success.
	 *	Example: "200 OK', "302 Found", "404 Not Found"
	 *
	 */
	public function statusText($url, $data = array()) {
		return $this->status($url, $data, true); 
	}

	/**
	 * Set an array of headers, removes any existing headers
	 *
	 */
	public function setHeaders(array $headers) {
		foreach($headers as $key => $value) {
			$this->setHeader($key, $value);
		}
		return $this;
	}

	/**
	 * Send an individual header to send
	 *
	 */
	public function setHeader($key, $value) {
		$key = strtolower($key);
		$this->headers[$key] = $value; 
		return $this;
	}

	/**
	 * Set an array of data, removes any existing data
	 *
	 *  
	 *
	 */
	public function setData($data) {
		if(is_array($data)) $this->data = $data; 
			else $this->rawData = $data; 
		return $this;
	}

	/**
	 * Set a variable to be included in the POST/GET request
	 *
	 * @param string $key
	 * @param string|int $value
	 * @return $this
	 *
	 */
	public function set($key, $value) {
		$this->$data[$key] = $value; 
		return $this;
	}

	/**
	 * Allows setting to $data via $http->key = $value
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Enables getting from $data via $http->key 
	 *
	 */
	public function __get($key) {
		return array_key_exists($key, $this->data) ? $this->data[$key] : null;
	}	

	/**
	 * Send the given $data array to a URL using either POST or GET
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param array $data Array of data to send (if not already set before)
	 * @param string $method Method to use (either POST or GET)
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	protected function send($url, $data = array(), $method = 'POST') { 

		$url = $this->validateURL($url, false); 
		if(empty($url)) return false;
		$this->resetResponse();
		$unmodifiedURL = $url;

		if(!empty($data)) $this->setData($data);
		if($method !== 'GET') $method = 'POST';

		if(!$this->hasFopen || strpos($url, 'https://') === 0 && !extension_loaded('openssl')) {
			return $this->sendSocket($url, $method); 
		}

		if(!empty($this->data)) {
			$content = http_build_query($this->data); 
			if($method === 'GET' && strlen($content)) { 
				$url .= (strpos($url, '?') === false ? '?' : '&') . $content; 
				$content = '';
			}
		} else if(!empty($this->rawData)) {
			$content = $this->rawData; 
		} else {
			$content = '';
		}

		$this->setHeader('content-length', strlen($content)); 

		$header = '';
		foreach($this->headers as $key => $value) $header .= "$key: $value\r\n";

		$options = array(
			'http' => array( 
				'method' => $method,
				'content' => $content,
				'header' => $header
				)
			);      

		$context = @stream_context_create($options); 
		$fp = @fopen($url, 'rb', false, $context); 
		if(!$fp) {
			//$this->error = "fopen() failed, see result of getResponseHeader()";
			//if(isset($http_response_header)) $this->responseHeader = $http_response_header; 
			return $this->sendSocket($unmodifiedURL, $method); 
		}

		$result = @stream_get_contents($fp); 
		if(isset($http_response_header)) $this->setResponseHeader($http_response_header); 
		return $result;
	}

	/**
	 * Alternate method of sending when allow_url_fopen isn't allowed
	 *
	 */
	protected function sendSocket($url, $method = 'POST') {
		
		static $level = 0; // recursion level

		$this->resetResponse();
		$timeoutSeconds = 3; 
		if($method != 'GET') $method = 'POST';

		$info = parse_url($url);
		$host = $info['host'];
		$path = empty($info['path']) ? '/' : $info['path'];
		$query = empty($info['query']) ? '' : '?' . $info['query'];

		if($info['scheme'] == 'https') {
			$port = 443; 
			$scheme = 'ssl://';
		} else {
			$port = empty($info['port']) ? 80 : $info['port'];
			$scheme = '';
		}

		if(!empty($this->data)) {
			$content = http_build_query($this->data); 
			if($method === 'GET' && strlen($content)) { 
				$query .= (strpos($query, '?') === false ? '?' : '&') . $content; 
				$content = '';
			}
		} else if(!empty($this->rawData)) {
			$content = $this->rawData; 
		} else {
			$content = '';
		}

		$this->setHeader('content-length', strlen($content));

		$request = "$method $path$query HTTP/1.0\r\nHost: $host\r\n";

		foreach($this->headers as $key => $value) {
			$request .= "$key: $value\r\n";
		}

		$response = '';
		$errno = '';
		$errstr = '';

		if(false !== ($fs = fsockopen($scheme . $host, $port, $errno, $errstr, $timeoutSeconds))) {
			fwrite($fs, "$request\r\n$content");
			while(!feof($fs)) {
				// get 1 tcp-ip packet per iteration
				$response .= fgets($fs, 1160); 
			}
			fclose($fs);
		}
		if(strlen($errstr)) $this->error = $errno . ': ' . $errstr; 
	
		// skip past the headers in the response, so that it is consistent with 
		// the results returned by the regular send() method
		$pos = strpos($response, "\r\n\r\n"); 
		$this->setResponseHeader(explode("\r\n", substr($response, 0, $pos))); 
		$response = substr($response, $pos+4); 

		// if response resulted in a redirect, follow it 
		if($this->httpCode == 301 || $this->httpCode == 302) {
			// follow redirects
			$location = $this->getResponseHeader('location'); 
			if(!empty($location) && ++$level <= 5) {
				if(strpos($location, '://') === false && preg_match('{(https?://[^/]+)}i', $url, $matches)) {
					// if location is relative, convert to absolute
					$location = $matches[1] . '/' . ltrim($location, '/'); 
				}
				return $this->sendSocket($location, $method); 	
			}
		}

		return $response;

	}

	/**
	 * Download a file from a URL and save it locally
	 * 
	 * First it will attempt to use CURL. If that fails, it will try fopen, 
	 * unless you specify a useMethod in $options.
	 * 
	 * @param string $fromURL URL of file you want to download.
	 * @param string $toFile Filename you want to save it to (including full path).
	 * @param array $options Optional aptions array for PHP's stream_context_create(), plus these optional options: 
	 * 	- useMethod (string): Specify "curl", "fopen" or "socket" to force a specific method (default=autodetect)
	 * @return string Filename that was downloaded (including full path).
	 * @throws WireException All error conditions throw exceptions. 
	 * 
	 */
	public function download($fromURL, $toFile, array $options = array()) {

		$fromURL = $this->validateURL($fromURL, true); 
		$http = stripos($fromURL, 'http://') === 0; 
		$https = stripos($fromURL, 'https://') === 0;
		$allowMethods = array('curl', 'fopen', 'socket');
		$triedMethods = array();
		
		if(!$http && !$https) {
			throw new WireException($this->_('Download URLs must begin with http:// or https://'));
		}
		
		if(isset($options['useMethod'])) {
			$useMethod = $options['useMethod'];
			unset($options['useMethod']);
			if(!in_array($useMethod, $allowMethods)) throw new WireException("Unrecognized useMethod: $useMethod"); 
			if($useMethod == 'curl' && !$this->hasCURL) throw new WireException("System does not support CURL");
			if($useMethod == 'fopen' && !$this->hasFopen) throw new WireException("System does not support fopen"); 
		} else {
			if($this->hasCURL) $useMethod = 'curl';
				else if($this->hasFopen) $useMethod = 'fopen';
				else $useMethod = 'socket';
		}
		
		if(($fp = fopen($toFile, 'wb')) === false) {
			throw new WireException($this->_('fopen error for filename:') . ' ' . $toFile);
		}

		// CURL
		if($useMethod == 'curl') {
			$triedMethods[] = 'curl';
			$result = $this->downloadCURL($fromURL, $fp, $options);
			if($result === false && !$this->httpCode) {
				$useMethod = $this->hasFopen ? 'fopen' : 'socket'; 
			}	
		}
		
		// FOPEN 
		if($useMethod == 'fopen') {
			$triedMethods[] = 'fopen';
			if($https && !extension_loaded('openssl')) {
				// WireHttp::download-OpenSSL extension required but not available, fallback to socket
				$useMethod = 'socket';
			} else {
				$result = $this->downloadFopen($fromURL, $fp, $options);
				if($result === false && !$this->httpCode) $useMethod = 'socket'; 
			}
		}
	
		// SOCKET
		if($useMethod == 'socket') {
			$triedMethods[] = 'socket';
			$this->downloadSocket($fromURL, $fp, $options); 
		}
		
		fclose($fp); 
			
		$methods = implode(", ", $triedMethods);
		if($this->error || isset($this->errorCodes[$this->httpCode])) {
			unlink($toFile);
			$error = $this->_('File could not be downloaded') . ' ' . htmlentities("($fromURL) ") . $this->getError() . " (tried: $methods)";
			throw new WireException($error); 
		} else {
			$bytes = filesize($toFile); 
			$this->message("Downloaded " . htmlentities($fromURL) . " => $toFile (using: $methods) [$bytes bytes]", Notice::debug); 
		}
		
		$chmodFile = $this->wire('config')->chmodFile; 
		if($chmodFile) chmod($toFile, octdec($chmodFile));
		
		return $toFile;
	}

	/**
	 * Download file using CURL 
	 * 
	 * @param string $fromURL
	 * @param resource $fp Open file pointer
	 * @param array $options
	 * @return bool True if successful false if not
	 * 
	 */
	protected function downloadCURL($fromURL, $fp, array $options) {
		
		$this->resetResponse();
		$fromURL = str_replace(' ', '%20', $fromURL);
		
		$curl = curl_init($fromURL);
		
		curl_setopt($curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($curl, CURLOPT_FILE, $fp); // write curl response to file
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
		$result = curl_exec($curl);
		if($result) $this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if($result === false) $this->error = curl_error($curl);
		curl_close($curl);
		
		return $result; 
	}

	/**
	 * Download file using fopen
	 *
	 * @param string $fromURL
	 * @param resource $fp Open file pointer
	 * @param array $options
	 * @return bool True if successful false if not
	 *
	 */
	protected function downloadFopen($fromURL, $fp, array $options) {
		
		$this->resetResponse();

		// Define the options
		$defaultOptions = array('max_redirects' => 3);
		$options = array_merge($defaultOptions, $options);
		$context = stream_context_create(array('http' => $options));

		// download the file
		$content = file_get_contents($fromURL, false, $context);

		if(isset($http_response_header)) $this->setResponseHeader($http_response_header);

		if($content === false) {
			$result = false;
		} else {
			$result = true; 
			fwrite($fp, $content);
		}
		
		return $result; 
	}
	
	/**
	 * Download file using sockets
	 *
	 * @param string $fromURL
	 * @param resource $fp Open file pointer
	 * @param array $options
	 * @return bool True if successful false if not
	 *
	 */
	protected function downloadSocket($fromURL, $fp, array $options) {
		$this->resetResponse();
		$this->resetRequest();

		// download the file
		$content = $this->sendSocket($fromURL, 'GET');
		fwrite($fp, $content);
		if(empty($content) && !$this->error) $this->error = 'no data received'; 
		return $this->error ? false : true; 
	}

	/**
	 * Get the last HTTP response headers (normal array)
	 * 
	 * Useful to examine for errors if your request returned false
	 * However, the getResponseHeaders() plural method may be better
	 * and this one is kept primarily for backwards compatibility.
	 *
	 * @param string $key Optional header name you want to get
	 * @return array|string|null
	 *
	 */
	public function getResponseHeader($key = '') {
		if(!empty($key)) return $this->getResponseHeaders($key);
		return $this->responseHeader;
	}
	
	/**
	 * Get the last HTTP response headers (associative array)
	 *
	 * All headers are translated to key => value properties in the array. 
	 * The keys are always lowercase. 
	 *
	 * @param string $key Optional header name you want to get
	 * @return array|string|null
	 *
	 */
	public function getResponseHeaders($key = '') {
		if(!empty($key)) {
			$key = strtolower($key);
			return isset($this->responseHeaders[$key]) ? $this->responseHeaders[$key] : null;
		}
		return $this->responseHeaders;
	}
	
	/**
	 * Set the response header
	 *
	 * @param array
	 *
	 */
	protected function setResponseHeader(array $responseHeader) {
		
		$this->responseHeader = $responseHeader;
		
		if(isset($responseHeader[0])) {
			$properties = explode(' ', $responseHeader[0]);
			$httpCode = isset($properties[1]) ? (int) $properties[1] : 0;
			$message = isset($properties[2]) ? $properties[2] : '';
		} else {
			$httpCode = 0;
			$message = '';
		}
		
		$this->httpCode = (int) $httpCode;
		if(isset($this->errorCodes[$this->httpCode])) $this->error = $this->errorCodes[$this->httpCode]; 

		// parsed version
		$this->responseHeaders = array();
		foreach($responseHeader as $header) {
			$pos = strpos($header, ':');
			if($pos !== false) {
				$key = trim(strtolower(substr($header, 0, $pos)));
				$value = trim(substr($header, $pos+1));
			} else {
				$key = $header;
				$value = '';
			}
			if(!isset($this->responseHeaders[$key])) $this->responseHeaders[$key] = $value;
		}
	
		/*
		if(self::debug && count($responseHeader)) {
			$this->message("httpCode: $this->httpCode, message: $message"); 
			$this->message("<pre>" . print_r($this->getResponseHeader(true), true) . "</pre>", Notice::allowMarkup);
		}
		*/
	}

	/**
	 * Validate a URL for WireHttp use
	 *
	 * @param string $url
	 * @param bool $throw Whether to throw exception on validation fail (default=false)
	 * @throws Exception|WireException
	 * @return string $url Valid URL or blank string on failure
	 * 
	 */
	public function validateURL($url, $throw = false) {
		$options = array(
			'allowRelative' => false, 
			'allowSchemes' => $this->allowSchemes, 
			'requireScheme' => true, 
			'throw' => true,
			);
		try {
			$url = $this->wire('sanitizer')->url($url, $options); 
		} catch(WireException $e) {
			if($throw) throw $e; 
			$url = '';
		}
		return $url;
	}

	/**
	 * Reset all response properties
	 *
	 */
	protected function resetResponse() {
		$this->responseHeader = array();
		$this->responseHeaders = array();
		$this->httpCode = 0;
		$this->error = '';
	}

	/**
	 * Reset all request data
	 *
	 */
	protected function resetRequest() {
		$this->data = array();
		$this->rawData = null;
		$this->headers = $this->defaultHeaders;
	}

	/**
	 * Get a string of the last error message
	 *
	 * @return string
	 *
	 */
	public function getError() {
		$error = $this->error; 
		if(isset($this->errorCodes[$this->httpCode])) {
			$error = "$this->httpCode " . $this->errorCodes[$this->httpCode] . ": $error";
		}
		return $error; 
	}

	/**
	 * Get last HTTP error code
	 *
	 * @return int
	 *
	 */
	public function getHttpCode() {
		return $this->httpCode; 
	}

	/**
	 * Return array of all possible HTTP error codes as (code => description)
	 * 
	 * @return array
	 * 
	 */
	public function getErrorCodes() {
		return $this->errorCodes;
	}

	/**
	 * Set schemes WireHttp is allowed to access (default=[http, https])
	 *
	 * @param array|string $schemes Array of schemes or space-separated string of schemes
	 * @param bool $replace Specify true to replace any existing schemes already allowed (default=false)
	 * @return this
	 *
	 */
	public function setAllowSchemes($schemes, $replace = false) {
		if(is_string($schemes)) {
			$str = strtolower($schemes); 
			$schemes = array();
			$str = str_replace(',', ' ', $str); 
			foreach(explode(' ', $str) as $scheme) {
				if($scheme) $schemes[] = $scheme;
			}
		}
		if(is_array($schemes)) {
			if($replace) {
				$this->allowSchemes = $schemes;
			} else {
				$this->allowSchemes = array_merge($this->allowSchemes, $schemes); 
			}
		}
		return $this;
	}

	/**
	 * Return array of allowed schemes
	 * 
	 * @return array
	 * 
	 */
	public function getAllowSchemes() {
		return $this->allowSchemes; 
	}


}
