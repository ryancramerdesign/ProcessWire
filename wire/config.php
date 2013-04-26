<?php

/**
 * ProcessWire Configuration File
 *
 * Hard-coded configuration options for ProcessWire
 * These may be overridden in the /site/config.php, but it is not recommended.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

if(!defined("PROCESSWIRE")) die();

$config->adminRootPageID = 2; 
$config->trashPageID = 7; 
$config->loginPageID = 23; 
$config->http404PageID = 27;
$config->usersPageID = 29; 
$config->rolesPageID = 30; 
$config->permissionsPageID = 31; 
$config->guestUserPageID = 40; 
$config->superUserPageID = 41; 
$config->guestUserRolePageID = 37; 
$config->superUserRolePageID = 38; 
$config->userTemplateID = 3;
$config->roleTemplateID = 4;
$config->permissionTemplateID = 5; 

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

