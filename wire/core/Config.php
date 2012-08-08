<?php

/**
 * ProcessWire Config
 *
 * Handles ProcessWire configuration data
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 *
 * @see http://processwire.com/api/variables/config/ Offical $config API variable Documentation
 *
 * @property bool $ajax If the current request is an ajax (asynchronous javascript) request, this is set to true.
 * @property string $httpHost Current HTTP host name.
 * @property bool $https If the current request is an HTTPS request, this is set to true.
 * @property string $version Current ProcessWire version string (i.e. "2.2.3")
 * @property FilenameArray $styles Array used by ProcessWire admin to keep track of what stylesheet files its template should load. It will be blank otherwise. Feel free to use it for the same purpose in your own sites.
 * @property FilenameArray $scripts Array used by ProcessWire admin to keep track of what javascript files its template should load. It will be blank otherwise. Feel free to use it for the same purpose in your own sites.
 * @property Paths $urls Items from $config->urls reflect the http path one would use to load a given location in the web browser. URLs retrieved from $config->urls always end with a trailing slash.
 * @property Paths $paths All of what can be accessed from $config->urls can also be accessed from $config->paths, with one important difference: the returned value is the full disk path on the server. There are also a few items in $config->paths that aren't in $config->urls. All entries in $config->paths always end with a trailing slash.
 *
 */
class Config extends WireData { 

	/**
	 * List of config keys that are also exported in javascript
	 *
	 */
	protected $jsFields = array();

	/**
	 * Set a config field that is shared in Javascript, OR retrieve one or all params already set
	 *
	 * Specify only a $key and omit the $value in order to retrieve an existing set value.
	 * Specify no params to retrieve in array of all existing set values.
	 *
	 * @param string $key 
	 * @param mixed $value
 	 *
	 */
	public function js($key = null, $value = null) {

		if(is_null($key)) {
			$data = array();
			foreach($this->jsFields as $field) {
				$data[$field] = $this->get($field); 
			}
			return $data; 

		} else if(is_null($value)) {
			return in_array($key, $this->jsFields) ? $this->get($key) : null;
		}

		$this->jsFields[] = $key; 
		return parent::set($key, $value); 
	}
}

