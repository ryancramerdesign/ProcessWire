ABOUT PROCESSWIRE
---------------------------------------------------------------------------
ProcessWire is an open source content management system (CMS) and web 
application framework aimed at the needs of designers, developers and their 
clients. ProcessWire gives you more control over your fields, templates and 
markup than other platforms, and provides a powerful template system that 
works the way you do. Not to mention, ProcessWire's API makes working with 
your content easy and enjoyable. Managing and developing a site in 
ProcessWire is shockingly simple compared to what you may be used to.


ABOUT THIS VERSION
---------------------------------------------------------------------------
This version is a developer preview, primarily for testing and introducing
ProcessWire to other web designers and developers. This version is not 
intended for production use and there are likely to be some changes before 
the stable release. Please contact Ryan with any bugs or issues you may
encounter at http://ryancramer.com/contact/


REQUIREMENTS
---------------------------------------------------------------------------
1. A web server running Apache. 
2. PHP version 5.2.4 or greater.
3. MySQL 5.0.15 or greater.
4. Apache must have mod_rewrite enabled. 
5. Apache must support .htaccess files. 


INSTALLATION FROM ZIP
---------------------------------------------------------------------------

1. Unzip the ProcessWire installation file to the location where you want it
   installed on your web server. 

2. Load the location that you unzipped (or uploaded) the files to in your web
   browser. This will initiate the ProcessWire installer. The installer will
   guide you through the rest of the installation.


INSTALLATION FROM GIT
---------------------------------------------------------------------------

1. Git clone ProcessWire to the place where you want to install it. 

2. Load the location where you installed ProcessWire into your browser. 
   This will initiate the ProcessWire installer. The installer will guide
   you through the rest of the installation.  


TROUBLESHOOTING
---------------------------------------------------------------------------
If you run into a blank screen or an error you don't expect, turn on debug
mode by doing the following:

1. Edit this file: 
   /site/config.php

2. Find this line: 
   $config->debug = false; 

3. Change the 'false' to 'true', like below, and save. 
   $config->debug = true; 

This can be found near the bottom of the file. It will make PHP and 
ProcessWire report all errors, warnings, notices, etc. Of course, you'll
want to set it back to false once you've resolved any issues. 


HAVE QUESTIONS, NEED HELP, OR FOUND A BUG?
------------------------------------------
Please contact Ryan at: 
http://www.ryancramer.com/contact/ 


ProcessWire, Copyright 2010 by Ryan Cramer

