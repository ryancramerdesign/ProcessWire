<?php

/**
 * ProcessWire Configuration File
 *
 * User-configurable options within ProcessWire
 *
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
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
 * chmodDir: octal string permissions assigned to directories created by ProcessWire
 *
 */
$config->chmodDir = "0777";

/**
 * chmodFile: octal string permissions assigned to files created by ProcessWire
 *
 */
$config->chmodFile = "0666";    

/**
 * templateExtension: expected extension for template files
 *
 */
$config->templateExtension = 'php';

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
 * debug: debug mode causes additional info to appear for use during dev and debugging 
 *
 * Under no circumstance should you leave this ON with a live site. 
 *
 */
$config->debug = false; 

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

