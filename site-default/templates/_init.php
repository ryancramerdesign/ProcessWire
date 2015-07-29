<?php

/**
 * Initialize variables output in _main.php
 *
 * Values populated to these may be changed as desired by each template file.
 * You can setup as many such variables as you'd like. 
 *
 * This file is automatically prepended to all template files as a result of:
 * $config->prependTemplateFile = '_init.php'; in /site/config.php. 
 *
 * If you want to disable this automatic inclusion for any given template, 
 * go in your admin to Setup > Templates > [some-template] and click on the 
 * "Files" tab. Check the box to "Disable automatic prepend file". 
 *
 */

// Variables for regions we will populate in _main.php. Here we also assign 
// default values for each of them.
$title = $page->get('headline|title'); // headline if available, otherwise title
$content = $page->body;
$sidebar = $page->sidebar;


// We refer to our homepage a few times in our site, so we preload a copy 
// here in a $homepage variable for convenience. 
$homepage = $pages->get('/'); 


// Include shared functions (if any)
include_once("./_func.php"); 


// What happens after this?
// ------------------------
// 1. ProcessWire loads your page's template file (i.e. basic-page.php).
// 2. ProcessWire loads the _main.php file
// 
// See the README.txt file for more information.

