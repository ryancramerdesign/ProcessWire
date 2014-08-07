<?php

/**
 * ProcessWire Configuration File
 *
 * Hard-coded configuration options for ProcessWire
 * These may be overridden in the /site/config.php, but it is not recommended.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2014 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

if(!defined("PROCESSWIRE")) die();

$config->rootPageID = 1; 
$config->adminRootPageID = 2; 
$config->trashPageID = 7; 
$config->loginPageID = 23; 
$config->http404PageID = 27;
$config->usersPageID = 29; 
$config->rolesPageID = 30; 
$config->externalPageID = 27; 
$config->permissionsPageID = 31; 
$config->guestUserPageID = 40; 
$config->superUserPageID = 41; 
$config->guestUserRolePageID = 37; 
$config->superUserRolePageID = 38; 
$config->userTemplateID = 3;
$config->roleTemplateID = 4;
$config->permissionTemplateID = 5; 

/**
 * Page IDs that will be preloaded with every request
 * 
 * This reduces number of total number of queries by reducing some on-demand queries
 *
 */
$config->preloadPageIDs = array(
	1, // root/homepage
	2, // admin
	28, // access
	29, // users
	30, // roles
	37, // guest user role
	38, // super user role
	40, // guest user
	);

/**
 * templateExtension: expected extension for template files
 *
 */
$config->templateExtension = 'php';

/**
 * Database character set. utf8 recommended.
 *
 * Note that you should probably not add/change this on an existing site. i.e. don't add this to 
 * an existing ProcessWire installation without asking how in the ProcessWire forums. 
 *
 */
$config->dbCharset = 'utf8';

/**
 * Database engine to use. Use 'MyISAM' or 'InnoDB'. 
 *
 * Note that use of 'InnoDB' is currently experimental. Avoid changing this after install.
 *
 */
$config->dbEngine = 'MyISAM'; 

/**
 * userAuthHashType: hash method to use for passwords. typically 'md5' or 'sha1', 
 *
 * Can be any available with your PHP's hash() installation. For instance, you may prefer 
 * to use something like sha256 if supported by your PHP installation.
 *
 */
$config->userAuthHashType = 'sha1';

/**
 * dateFormat: default system date format. Preferably in a format that is string sortable. 
 *
 * This should be a PHP date() string: http://www.php.net/manual/en/function.date.php
 *
 */
$config->dateFormat = 'Y-m-d H:i:s';  

/**
 * protectCSRF: enables CSRF (cross site request forgery) protection on all PW forms,
 * recommended for improved security.
 *
 */
$config->protectCSRF = true;

/**
 * Default ImageSizer options, as used by $page->image->size(w, h), for example. 
 *
 */
$config->imageSizerOptions = array(
	'upscaling' => true,
	'cropping' => true, 
	'quality' => 90,
	);

/**
 * Default prefix used for pagination, i.e. "page2", "page3", etc.
 *
 */
$config->pageNumUrlPrefix = 'page';

/**
 * Multiple prefixes that may be used for detecting pagination
 *
 * Typically used for multi-language support and populated automatically at runtime by
 * multi-language support modules. When populated, they override $config->pageNumUrlPrefix.
 *
 * $config->pageNumUrlPrefixes = array();
 * 
 */

/**
 * Set to true in /site/config.php if you want files on non-public or unpublished pages to be 
 * protected from direct URL access. 
 *
 * When used, such files will be delivered at a URL that is protected from public access. 
 * See also: $config->fileContentTypes and $config->pagefileSecurePathPrefix
 *
 */
$config->pagefileSecure = false; 

/**
 * pagefileUrlPrefix: String that prefixes filenames in PW URLs, becoming a shortcut to a page's file's URL
 *
 * This must be at the end of the URL. For the prefix "-/", a files URL would look like this:
 * /path/to/page/-/filename.jpg => same as: /site/assets/files/123/filename.jpg
 *
 * This should be a prefix that is not the same as any page name, as it takes precedence.
 *
 * This prefix is deprecated. Insert this into your /site/config.php as a temporary fix only if you
 * have broken images from <img> tags placed in textarea fields.
 *
 * @deprecated
 *
$config->pagefileUrlPrefix = '-/'; 
 */

/**
 * pagefileSecurePathPrefix: One or more characters prefixed to the pathname of secured file dirs.
 *
 * This should be some prefix that the .htaccess file knows to block requests for. This is typically
 * overridden as '-' in /site/config.php, but kept as '.' in this file for fallback/backwards 
 * compatibility with pre 2.3 htaccess files. It is preferred for this to be '-' in your site/config.php.
 *
 */
$config->pagefileSecurePathPrefix = '.';

/**
 * Set to true in /site/config.php if you want files to live in an extended path mapping system
 * that limits the number of directories per path to under 2000. 
 *
 * Use this on large sites living on file systems with hard limits on quantity of directories
 * allowed per directory level. For example, ext2 and its 30000 directory limit. 
 *
 * Please note that for existing sites, this applies only for new pages created from this
 * point forward. 
 *
 */
$config->pagefileExtendedPaths = false;

/**
 * fileContentTypes: array of extention to content-type header, used by file passthru functions.
 *
 * Any content types that should be force-download should be preceded with a plus sign.
 * The '?' index must be present to represent a default for all not present.
 *
 */
$config->fileContentTypes = array(
	'?' => '+application/octet-stream',
	'pdf' => '+application/pdf',
	'doc' => '+application/msword',
	'docx' => '+application/msword',
	'xls' => '+application/excel',
	'xlsx' => '+application/excel',
	'rtf' => '+application/rtf',
	'gif' => 'image/gif',
	'jpg' => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'png' => 'image/x-png',
	);

/** 
 * chmodDir: octal string permissions assigned to directories created by ProcessWire
 *
 * This is overwritten at runtime with a more specific value in /site/config.php
 *
 */
$config->chmodDir = "0777";

/**
 * chmodFile: octal string permissions assigned to files created by ProcessWire
 * 
 * This is overwritten at runtime with a more specific value in /site/config.php
 *
 */
$config->chmodFile = "0666";

/**
 * Max number of supported paginations when using page numbers
 * 
 */
$config->maxPageNum = 999;


/**
 * httpHosts: For added security, specify the host names ProcessWire should recognize. 
 *
 * If your site may be accessed from multiple hostnames, you'll also want to use this setting.
 * If left empty, the httpHost will be determined automatically, but use of this whitelist
 * is recommended for production environments. 
 * 
 * If your hostname uses a port other than 80, make sure to include that as well. 
 * For instance "localhost:8888". 
 *
 * This setting is now added to /site/config.php by the installer, so this commentary
 * is primarily for those upgrading from older versions of ProcessWire. If that is you,
 * then specify the httpHosts in /site/config.php rather than here. 
 *
 */
$config->httpHosts = array(
        //'domain.com',
        //'www.domain.com',
        //'localhost:8888',
	);

/**
 * HTML used for fatal error messages in HTTP mode
 *
 * Should use inline styles since no guarantee stylesheets are present when these are displayed. 
 * 
 */
$config->fatalErrorHTML = "<p style='background:crimson;color:white;padding:0.5em;font-family:sans-serif;'><b>{message}</b><br /><small>{why}</small></p>";

/**
 * When checking for new template files, files matching this PCRE regex will be ignored. 
 *  
 * In the default below, we are ignoring files that begin with an underscore. 
 *
 */
$config->ignoreTemplateFileRegex = '/^_/';

/**
 * Substitute module names for when requested module doesn't exist
 * 
 * Array of module name => substitute module name
 * 
 */
$config->substituteModules = array(
	// TinyMCE replaced with CKEditor as default RTE in 2.4.9+
	'InputfieldTinyMCE' => 'InputfieldCKEditor' 
	);

/**
 * Settings specific to InputfieldWrapper class
 *
 * Setting useDependencies to false may enable to use depencencies in some places where
 * they aren't currently supported, like files/images and repeaters. Note that setting it
 * to false only disables it server-side. The javascript dependencies work either way. 
 *
 * Uncomment and paste into /site/config.php if you want to use this
 * 
 * $config->InputfieldWrapper = array(
 *	'useDependencies' => true,
 * 	'requiredLabel' => 'Missing required value', 
 *	);
 * 
 */

/***********************************************************************************
 * The following are runtime-only settings that are set automatically and cannot be 
 * overwritten from your /site/config.php file. 
 * 
 */

/**
 * https: This is automatically set to TRUE when the request is an HTTPS request
 *
 */
$config->https = false;

/**
 * ajax: This is automatically set to TRUE when the request is an AJAX request.
 *
 */
$config->ajax = false;

/**
 * external: This is automatically set to TRUE when PW is externally bootstrapped.
 *
 */
$config->external = false;

/**
 * cli: This is automatically set to TRUE when PW is booted as a command line (non HTTP) script.
 *
 */
$config->cli = false;

/**
 * version: This is automatically populated with the current PW version string (i.e. 2.5.0)
 *
 */
$config->version = '';


