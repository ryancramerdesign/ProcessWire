<?php

/**
 * ProcessWire Configuration File
 *
 * Configuration options for ProcessWire
 * 
 * To override any of these options, copy the option you want to modify to
 * /site/config.php and adjust as you see fit. Options in /site/config.php
 * override those in this file. 
 * 
 * You may also make up your own configuration options by assigning them 
 * in /site/config.php 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2014 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 * 
 * TABLE OF CONTENTS
 * ===============================
 * 1. System modes
 * 2. Dates and times
 * 3. Session
 * 4. Template files
 * 5. Files and assets
 * 6. HTTP and input 
 * 7. Database
 * 8. Modules
 * 9. Misc
 * 10. Runtime 
 * 11. System
 * 
 */

if(!defined("PROCESSWIRE")) die();

/*** 1. SYSTEM MODES ****************************************************************************/

/**
 * Enable debug mode?
 * 
 * Debug mode causes additional info to appear for use during dev and debugging. 
 * This is almost always recommended for sites in development. However, you should
 * always have this disabled for live/production sites. 
 * 
 * @var bool
 *
 */
$config->debug = false;

/**
 * Enable PW development/advanced mode?
 * 
 * Turns on additional options in ProcessWire Admin that aren't applicable in all instances.
 * Recommended mode is false, except for ProcessWire developers.
 * 
 * @var bool
 *
 */
$config->advanced = false;

/**
 * Enable demo mode?
 * 
 * If true, disables save functions in Process modules (admin).
 * 
 */
$config->demo = false;




/*** 2. DATES AND TIMES *************************************************************************/

/**
 * Default time zone
 * 
 * Must be a [PHP timezone string](http://php.net/manual/en/timezones.php)
 *
 * @var string 
 * 
 */
$config->timezone = 'America/New_York'; 

/**
 * System date format
 *
 * Default system date format. Preferably in a format that is string sortable.
 *
 * This should be a [PHP date string](http://www.php.net/manual/en/function.date.php)
 *
 * @var string
 *
 */
$config->dateFormat = 'Y-m-d H:i:s';




/*** 3. SESSION *********************************************************************************/

/**
 * Session name
 * 
 * Default session name as used in session cookie
 * 
 * @var string
 *
 */
$config->sessionName = 'wire';

/**
 * Session expiration seconds
 * 
 * How many seconds of inactivity before session expires
 * 
 * @var int
 *
 */
$config->sessionExpireSeconds = 86400;

/**
 * Use session challenge?
 * 
 * Should login sessions have a challenge key? (for extra security, recommended)
 * 
 * @var bool
 *
 */
$config->sessionChallenge = true;

/**
 * Use session fingerprint?
 * 
 * Should login sessions be tied to IP and user agent?
 * More secure, but will conflict with dynamic IPs.
 * 
 * @var bool
 *
 */
$config->sessionFingerprint = true;

/**
 * Hash method to use for passwords.
 *
 * Can be any available with your PHP's hash() installation. For instance, you may prefer
 * to use something like sha256 if supported by your PHP installation.
 *
 * @deprecated Only here for backwards compatibility.
 *
 */
$config->userAuthHashType = 'sha1';




/*** 4. TEMPLATE FILES **************************************************************************/

/**
 * Prepend template file 
 * 
 * PHP file in /site/templates/ that will be loaded before each page's template file.
 * Example: _init.php
 * 
 * @var string
 *
 */
$config->prependTemplateFile = '';

/**
 * Append template file 
 * 
 * PHP file in /site/templates/ that will be loaded after each page's template file.
 * Example: _main.php
 * 
 * @var string
 *
 */
$config->appendTemplateFile = '';

/**
 * Regular expression to ignore template files
 *
 * When checking for new template files, files matching this PCRE regex will be ignored.
 *
 * In the default value, we are ignoring files that begin with an underscore.
 *
 * @var string
 *
 */
$config->ignoreTemplateFileRegex = '/^_/';

/**
 * Expected extension for template files
 *
 */
$config->templateExtension = 'php';




/*** 5. FILES AND ASSETS ************************************************************************/

/**
 * Directory mode
 *
 * Octal string permissions assigned to directories created by ProcessWire
 * This value should always be overwritten by site-specific settings as 0777 
 * is too open for many installations. 
 *
 * @var string
 *
 */
$config->chmodDir = "0777";

/**
 * File mode
 *
 * Octal string permissions assigned to files created by ProcessWire
 * This value should always be overwritten by site-specific settings as 0666
 * is too open for many installations. 
 *
 * @var string
 *
 */
$config->chmodFile = "0666";

/**
 * Bad file extensions for uploads
 * 
 * File extensions that are always disallowed from uploads (each separated by a space).
 * 
 * @var string
 *
 */
$config->uploadBadExtensions = 'php php3 phtml exe cfm shtml asp pl cgi sh vbs jsp';

/**
 * Secure page files?
 *
 * When, true, prevents http access to file assets of access protected pages.
 *
 * Set to true in /site/config.php if you want files on non-public or unpublished pages to be
 * protected from direct URL access.
 *
 * When used, such files will be delivered at a URL that is protected from public access.
 * See also: $config->fileContentTypes and $config->pagefileSecurePathPrefix
 *
 * @var bool
 *
 */
$config->pagefileSecure = false;

/**
 * String that prefixes filenames in PW URLs, becoming a shortcut to a page's file's URL
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
 * $config->pagefileUrlPrefix = '-/';
 * 
 */

/**
 * Prefix for secure page files
 *
 * One or more characters prefixed to the pathname of secured file dirs.
 *
 * If use of this feature originated with a pre-2.3 install, this may need to be 
 * specified as "." rather than "-". 
 *
 */
$config->pagefileSecurePathPrefix = '-';

/**
 * Use extended file mapping? Enable this if you expect to have >30000 pages in your site.
 * 
 * Warning: The extended file mapping feature is not yet widely tested, so consider it beta.
 *
 * Set to true in /site/config.php if you want files to live in an extended path mapping system
 * that limits the number of directories per path to under 2000.
 *
 * Use this on large sites living on file systems with hard limits on quantity of directories
 * allowed per directory level. For example, ext2 and its 30000 directory limit.
 *
 * Please note that for existing sites, this applies only for new pages created from this
 * point forward.
 * 
 * @var bool
 *
 */
$config->pagefileExtendedPaths = false;

/**
 * fileContentTypes: array of extention to content-type header, used by file passthru functions.
 *
 * Any content types that should be force-download should be preceded with a plus sign.
 * The '?' index must be present to represent a default for all not present.
 * 
 * @var array
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
 * Image sizer options
 *
 * Default ImageSizer options, as used by $page->image->size(w, h), for example.
 * 
 * @var array
 *
 */
$config->imageSizerOptions = array(
	'upscaling' => true, // upscale if necessary to reach target size?
	'cropping' => true, // crop if necessary to reach target size?
	'autoRotation' => true, // automatically correct orientation?
	'sharpening' => 'soft', // sharpening: none | soft | medium | strong
	'quality' => 90, // quality: 1-100 where higher is better but bigger
	'defaultGamma' => 2.0, // defaultGamma: 0.5 to 4.0 or -1 to disable gamma correction (default=2.0)
	);

/**
 * Temporary directory for uploads
 * 
 * Optionally override PHP's upload_tmp_dir with your own.
 * 
 * @var string
 * 
 * $config->uploadTmpDir = dirname(__FILE__) . '/assets/uploads/'; // example
 *
 */




/*** 6. HTTP AND INPUT **************************************************************************/

/**
 * HTTP hosts
 *
 * For added security, specify the host names ProcessWire should recognize.
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
 * @var array
 *
 */
$config->httpHosts = array(
	//'domain.com',
	//'www.domain.com',
	//'localhost:8888',
	);

/**
 * Runtime HTTP host
 * 
 * This is set automatically by ProcessWire at runtime, consisting of one of the values 
 * specified in $config->httpHosts. However, if you set a value for this, it will override
 * ProcessWire's runtime value. 
 * 
 * @var string
 * 
 */
$config->httpHost = '';

/**
 * Protect CSRF?
 *
 * Enables CSRF (cross site request forgery) protection on all PW forms, recommended for improved security.
 *
 * @var bool
 *
 */
$config->protectCSRF = true;

/**
 * Maximum URL segments
 * 
 * Maximum number of extra stacked URL segments allowed in a page's URL (including page numbers).
 *
 * i.e. /path/to/page/s1/s2/s3 where s1, s2 and s3 are URL segments that don't resolve to a page, but can be
 * checked in the API via $input->urlSegment1, $input->urlSegment2, $input->urlSegment3, etc.
 * To use this, your template settings (under the URL tab) must take advantage of it. Only change this
 * number if you need more (or fewer) URL segments for some reason.
 * 
 * @var int
 *
 */
$config->maxUrlSegments = 4;

/**
 * Pagination URL prefix
 *
 * Default prefix used for pagination, i.e. "page2", "page3", etc.
 *
 * If using multi-language page names, please use the setting in LanguageSupportPageNames module settings instead.
 *
 * @var string
 *
 */
$config->pageNumUrlPrefix = 'page';

/**
 * Multiple prefixes that may be used for detecting pagination
 *
 * Typically used for multi-language support and populated automatically at runtime by
 * multi-language support modules. When populated, they override $config->pageNumUrlPrefix.
 *
 * @internal
 *
 * $config->pageNumUrlPrefixes = array();
 *
 */

/**
 * Maximum paginations
 *
 * Maxmum number of supported paginations when using page numbers.
 *
 * @var int
 *
 */
$config->maxPageNum = 999;

/**
 * Input variable order
 *
 * Order that variables with the $input API var are handled when you access $input->some_var.
 *
 * This does not affect the dedicated $input->[get|post|cookie|whitelist] variables/functions.
 * Possible values are a combination of: "get post cookie whitelist" in any order, separated by 1 space.
 * To disable $input->some_var from considering get/post/cookie, make wireInputOrder blank.
 *
 * @var string
 *
 */
$config->wireInputOrder = 'get post';




/*** 7. DATABASE ********************************************************************************/

/**
 * Database character set
 * 
 * utf8 is the only recommended value for this. 
 *
 * Note that you should probably not add/change this on an existing site. i.e. don't add this to 
 * an existing ProcessWire installation without asking how in the ProcessWire forums. 
 *
 */
$config->dbCharset = 'utf8';

/**
 * Database engine
 * 
 * MyISAM is the recommended value, but you may also use InnoDB (experimental). 
 *
 * Note that use of 'InnoDB' is currently experimental. Avoid changing this after install.
 * 
 */
$config->dbEngine = 'MyISAM';

/**
 * Allow MySQL query caching?
 * 
 * Set to false to to disable query caching. This will make everything run slower so should
 * only used for DB debugging purposes.
 * 
 * @var bool
 *
 */
$config->dbCache = true;

/**
 * MySQL database exec path
 * 
 * Path to mysql/mysqldump commands on the file system
 *
 * This enables faster backups and imports when available.
 *
 * Example: /usr/bin/
 * Example: /Applications/MAMP/Library/bin/
 * 
 * @param string
 *
 */
$config->dbPath = '';

/**
 * Force lowercase tables?
 * 
 * Force any created field_* tables to be lowercase.
 * Recommend value is true except for existing installations that already have mixed case tables.
 * 
 * @var bool
 *
 */
$config->dbLowercaseTables = true;

/**
 * Database username
 * 
 */
$config->dbUser = '';

/**
 * Database password
 * 
 */
$config->dbPass = '';

/**
 * Database host
 * 
 */
$config->dbHost = '';

/**
 * Database port
 * 
 */
$config->dbPort = 3306;

/**
 * Optional DB socket config for sites that need it (for most you should exclude this)
 *
 */
$config->dbSocket = '';





/*** 8. MODULES *********************************************************************************/

/**
 * URL to modules directory service
 *
 * @var string
 *
 */
$config->moduleServiceURL = 'http://modules.processwire.com/export-json/';

/**
 * API key for modules directory service
 *
 * @var string
 *
 */
$config->moduleServiceKey = 'pw250';

/**
 * Substitute modules
 *
 * Names of substitutute mdoules for when requested module doesn't exist
 *
 * array associative, of module name => replacement name
 *
 */
$config->substituteModules = array(
	// TinyMCE replaced with CKEditor as default RTE in 2.4.9+
	'InputfieldTinyMCE' => 'InputfieldCKEditor'
);



/*** 9. MISC ************************************************************************************/

/**
 * Default admin theme module for guest and users that haven't already selected one
 *
 * Core options include: AdminThemeDefault or AdminThemeReno.
 * Additional options will depend on what other 3rd party AdminTheme modules you have installed.
 *
 * @var string
 *
 */
$config->defaultAdminTheme = 'AdminThemeDefault';

/**
 * Admin email address
 *
 * Optional email address to send fatal error notifications to.
 *
 * @var string
 *
 */
$config->adminEmail = '';

/**
 * Fatal error HTML
 * 
 * HTML used for fatal error messages in HTTP mode.
 *
 * This should use inline styles since no guarantee stylesheets are present when these are displayed. 
 * String should contain two placeholders: {message} and {why}
 * 
 * @var string
 * 
 */
$config->fatalErrorHTML = "<p style='background:crimson;color:white;padding:0.5em;font-family:sans-serif;'><b>{message}</b><br /><small>{why}</small></p>";

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


/*** 10. RUNTIME ********************************************************************************
 * 
 * The following are runtime-only settings and cannot be changed from /site/config.php
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


/**
 * versionName: This is automatically populated with the current PW version name (i.e. 2.5.0 dev)
 *
 */
$config->versionName = '';



/*** 11. SYSTEM *********************************************************************************
 * 
 * Values in this section are not meant to be changed
 *
 */

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

