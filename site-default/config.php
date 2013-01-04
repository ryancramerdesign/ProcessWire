<?php

/**
 * ProcessWire Configuration File
 *
 * User-configurable options within ProcessWire
 *
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

if(!defined("PROCESSWIRE")) die();

/**
 * Timezone: current timezone using PHP timeline options
 *
 * To change, see timezone list at: http://php.net/manual/en/timezones.php
 *
 */
$config->timezone = 'America/New_York';

/**
 * sessionName: default session name as used in session cookie
 *
 */
$config->sessionName = 'wire';

/**
 * sessionExpireSeconds: how many seconds of inactivity before session expires
 *
 */
$config->sessionExpireSeconds = 86400; 

/**
 * sessionChallenge: should login sessions have a challenge key? (for extra security, recommended) 
 *
 */
$config->sessionChallenge = true; 

/**
 * sessionFingerprint: should login sessions be tied to IP and user agent? 
 *
 * More secure, but will conflict with dynamic IPs. 
 *
 */
$config->sessionFingerprint = true; 

/**
 * prependTemplateFile: PHP file in /site/templates/ that will be loaded before each page's template file
 *
 * Uncomment and edit to enable.
 *
 */
// $config->prependTemplateFile = '_init.php';

/**
 * appendTemplateFile: PHP file in /site/templates/ that will be loaded after each page's template file
 *
 * Uncomment and edit to enable.
 *
 */
// $config->appendTemplateFile = '_done.php';

/**
 * uploadUnzipCommand: shell command to unzip archives, used by WireUpload class. 
 *
 * If unzip doesn't work, you may need to precede 'unzip' with a path.
 *
 */
$config->uploadUnzipCommand = 'unzip -j -qq -n /src/ -x __MACOSX .* -d /dst/';

/**
 * uploadBadExtensions: file extensions that are always disallowed from uploads
 *
 */
$config->uploadBadExtensions = 'php php3 phtml exe cfm shtml asp pl cgi sh vbs jsp';

/**
 * uploadTmpDir: optionally override PHP's upload_tmp_dir with your own 
 * 
 * $config->uploadTmpDir = dirname(__FILE__) . '/assets/uploads/'; // example
 *
 */

/**
 * adminEmail: address to send optional fatal error notifications to.
 *
 */
$config->adminEmail = '';

/**
 * Prefix to use in page URLs for page numbers, i.e. a prefix of 'page' would use 'page1', 'page2', etc. 
 *
 */
$config->pageNumUrlPrefix = 'page';

/**
 * Maximum number of extra stacked URL segments allowed in a page's URL (including page numbers). 
 *
 * i.e. /path/to/page/s1/s2/s3 where s1, s2 and s3 are URL segments that don't resolve to a page, but can be
 * checked in the API via $input->urlSegment1, $input->urlSegment2, $input->urlSegment3, etc. 
 * To use this, your template settings (under the URL tab) must take advantage of it. Only change this 
 * number if you need more (or fewer) URL segments for some reason.
 *
 */
$config->maxUrlSegments = 4; 

/**
 * Order that variables with the $input API var are handled when you access $input->var
 *
 * This does not affect the dedicated $input->get/post/cookie/whitelist variables/functions. 
 * Possible values are a combination of: "get post cookie whitelist" in any order, separated by 1 space.
 * To disable $input->var from considering get/post/cookie, make wireInputOrder blank.
 *
 */
$config->wireInputOrder = 'get post'; 

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
 * Set to true if you want files on non-public or unpublished pages to be protected from direct URL access. 
 *
 * When used, such files will be delivered at a URL that is protected from public access. 
 * See also: pagefileUrlPrefix and fileContentTypes in /wire/config.php, which you may 
 * copy/paste into this file and modify if you want to.
 *
 */
$config->pagefileSecure = true; 

/**
 * pagefileSecurePathPrefix: One or more characters prefixed to the pathname of protected file dirs.
 *
 * This should be some prefix that the .htaccess file knows to block requests for. As of version 2.3
 * ProcessWire's htaccess file blocks files directories starting with a "-", so the default value 
 * below is recommended here. 
 *
 */
$config->pagefileSecurePathPrefix = '-';

/**
 * advanced: turns on additional options in ProcessWire Admin that aren't applicable 
 * in all instances. Recommended mode is 'false', except for ProcessWire developers.
 *
 */
$config->advanced = false;

/**
 * demo: if true, disables save functions in Process modules (admin)
 *
 */
$config->demo = false;

/**
 * debug: debug mode causes additional info to appear for use during dev and debugging 
 *
 * Under no circumstance should you leave this ON with a live site. 
 *
 */
$config->debug = false; 

/**
 * dbCache: whether to allow MySQL query caching
 *
 * Set to false to to disable query caching. This will make everything run slower so should 
 * only used for db debugging.
 *
 */
$config->dbCache = true;

/**
 * Force any created field_* tables to be lowercase
 *
 * Recommend value is true except for existing installations that already have mixed case tables. 
 *
 */
$config->dbLowercaseTables = true; 

/**
 * Optional 'set names utf8' for sites that need it (this option is deprecated)
 *
 * This may be used instead of the $config->dbCharset = 'utf8' option, and exists here only for
 * backwards compatibility with existing installations. Otherwise, this option is deprecated.
 * 
 * $config->dbSetNamesUTF8 = true; 
 *
 */ 

/**
 * Optional DB socket config for sites that need it (for most you should exclude this)
 *
 * $config->dbSocket = '';
 * 
 */

/**
 * Installer config data appears below
 *
 */

