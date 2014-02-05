<?php

/**
 * ProcessWire Config
 *
 * Handles ProcessWire configuration data
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 *
 * @see http://processwire.com/api/variables/config/ Offical $config API variable Documentation
 *
 * @property bool $ajax If the current request is an ajax (asynchronous javascript) request, this is set to true.
 * @property string $httpHost Current HTTP host name.
 * @property bool $https If the current request is an HTTPS request, this is set to true.
 * @property string $version Current ProcessWire version string (i.e. "2.2.3")
 * 
 * @property FilenameArray $styles Array used by ProcessWire admin to keep track of what stylesheet files its template should load. It will be blank otherwise. Feel free to use it for the same purpose in your own sites.
 * @property FilenameArray $scripts Array used by ProcessWire admin to keep track of what javascript files its template should load. It will be blank otherwise. Feel free to use it for the same purpose in your own sites.
 * 
 * @property Paths $urls Items from $config->urls reflect the http path one would use to load a given location in the web browser. URLs retrieved from $config->urls always end with a trailing slash.
 * @property Paths $paths All of what can be accessed from $config->urls can also be accessed from $config->paths, with one important difference: the returned value is the full disk path on the server. There are also a few items in $config->paths that aren't in $config->urls. All entries in $config->paths always end with a trailing slash.
 * 
 * @property string $templateExtension Default is 'php'
 * 
 * @property string $dateFormat Default system date format, preferably in sortable string format. Default is 'Y-m-d H:i:s'
 * 
 * @property bool $protectCSRF Enables CSRF (cross site request forgery) protection on all PW forms, recommended for security. 
 * 
 * @property array $imageSizerOptions Default value is array('upscaling' => true, 'cropping' => true, 'quality' => 90)
 * 
 * @property bool $pagefileSecure When used, files in /site/assets/files/ will be protected with the same access as the page. Routines files through a passthrough script. 
 * @property string $pagefileSecurePathPrefix One or more characters prefixed to the pathname of protected file dirs. This should be some prefix that the .htaccess file knows to block requests for.
 * 
 * @property array $fileContentTypes Array of extensions and the associated MIME type for each. See /wire/config.php for details and defaults.
 * 
 * @property string $chmodDir Octal string permissions assigned to directories created by ProcessWire
 * @property string $chmodFile Octal string permissions assigned to files created by ProcessWire
 * 
 * @property string $timezone Current timezone using PHP timeline options: http://php.net/manual/en/timezones.php
 * 
 * @property string $sessionName Default session name to use (default='wire')
 * @property int $sessionExpireSeconds How many seconds of inactivity before session expires?
 * @property bool $sessionChallenge Should login sessions have a challenge key? (for extra security, recommended)
 * @property bool $sessionFingerprint Should login sessions be tied to IP and user agent? May conflict with dynamic IPs. 
 * 
 * @property string $prependTemplateFile PHP file in /site/templates/ that will be loaded before each page's template file (default=none)
 * @property string $appendTemplateFile PHP file in /site/templates/ that will be loaded after each page's template file (default=none)
 * 
 * @property string $uploadUnzipCommand Shell command to unzip archives, used by WireUpload class.
 * $property string $uploadTmpDir Optionally override PHP's upload_tmp_dir with your own. Should include a trailing slash.
 * @property string $uploadBadExtensions Space separated list of file extensions that are always disallowed from uploads.
 * 
 * @property string $adminEmail Email address to send fatal error notifications to.
 * 
 * @property string $pageNumUrlPrefix Prefix used for pagination URLs. Default is "page", resulting in "/page1", "/page2", etc.
 * @property int $maxUrlSegments Maximum number of extra stacked URL segments allowed in a page's URL (including page numbers).
 * @property string $wireInputOrder Order that variables with the $input API var are handled when you access $input->var.
 * 
 * @property bool $advanced Special mode for ProcessWire system development. Not recommended for regular site development or production use. 
 * @property bool $demo Special mode for demonstration use that causes POST requests to be disabled. Applies to core, but may not be safe with 3rd party modules.
 * @property bool $debug Special mode for use when debugging or developing a site. Recommended TRUE when site is in development and FALSE when not.
 * 
 * @property string $dbHost Database host
 * @property string $dbName Database name
 * @property string $dbUser Database user
 * @property string $dbPass Database password
 * @property string $dbPort Database port (default=3306)
 * @property string $dbCharset Default is 'utf8'
 * @property string $dbSocket Optional DB socket config for sites that need it. 
 * @property bool $dbCache Whether to allow MySQL query caching.
 * @property bool $dbLowercaseTables Force any created field_* tables to be lowercase.
 * 
 * @property string $userAuthSalt Salt generated at install time to be used as a secondary/non-database salt for the password system.
 * @property string $userAuthHashType Default is 'sha1' - used only if Blowfish is not supported by the system.
 * 
 * @property int $adminRootPageID
 * @property int $trashPageID
 * @property int $loginPageID
 * @property int $http404PageID
 * @property int $usersPageID
 * @property int $rolesPageID
 * @property int $permissionsPageID
 * @property int $guestUserPageID
 * @property int $superUserPageID
 * @property int $guestUserRolePageID
 * @property int $superUserRolePageID
 * @property int $userTemplateID
 * @property int $roleTemplateID
 * @property int $permissionTemplateID
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
	 * @return array|mixed|null|this
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

