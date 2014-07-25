<?php

/**
 * Initialize variables output in _main.php
 *
 * Values populated to these may be changed as desired by each template file.
 * You can setup as many such variables as you'd like. 
 *
 */

// Variables for regions we will populate in _main.php
// Here we also assign default values for each of them.
$title = $page->get('headline|title'); 
$content = $page->body;
$sidebar = $page->sidebar;

// Specify useMain=false to bypass output of _main.php
$useMain = true; 

// We refer to our homepage a few times in our site, so 
// we preload a copy here in $homepage for convenience. 
$homepage = $pages->get('/'); 

// Include shared functions
include_once("./_func.php"); 

