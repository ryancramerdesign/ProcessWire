<?php 

// sitemap.php template file
// Generate navigation that descends up to 4 levels into the tree.
// See the _func.php for the renderNav() function definition. 

$content = renderNav($homepage, 4); 

// if you want the line below to happen automatically, uncomment
// the $config->appendTemplateFile line in /site/config.php 
include("./_main.php"); 

