<?php

/**
 * ProcessWire Configuration File
 *
 * Hard-coded configuration options for ProcessWire
 * These may be overridden in the /site/config.php, but it is not recommended.
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

