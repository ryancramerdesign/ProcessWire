ABOUT PROCESSWIRE
---------------------------------------------------------------------------
ProcessWire is an open source content management system (CMS) and web 
application framework aimed at the needs of designers, developers and their 
clients. ProcessWire gives you more control over your fields, templates and 
markup than other platforms, and provides a powerful template system that 
works the way you do. Not to mention, ProcessWire's API makes working with 
your content easy and enjoyable. Managing and developing a site in 
ProcessWire is shockingly simple compared to what you may be used to.

Learn more about ProcessWire at:
http://processwire.com

Download ProcessWire at:
http://github.com/ryancramerdesign/ProcessWire

Follow ProcessWire on Twitter at:
http://twitter.com/processwire/


ABOUT THIS VERSION
---------------------------------------------------------------------------
This is ProcessWire 2.2 stable. This is our current production version and 
is the 3rd production version release of ProcessWire. Please join us in 
the ProcessWire forums: http://processwire.com/talk/


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


UPGRADES
---------------------------------------------------------------------------

Upgrading from one version of ProcessWire 2.1/2.2 to another is a matter of
replacing these files from your old version with those from the new:

  /wire/ 	< entire directory
  /index.php	< if changed
  /.htaccess 	< if changed (rename htaccess.txt in the source)

Because index.php and .htaccess aren't updated very often, you may only
have to replace your /wire/ directory. Note that the /wire/ directory 
does not contain any files specific to your site, only to ProcessWire. 
All the files specific to your site are stored in /site/ and you would
leave that directory alone during an upgrade. 

If you are interested, this process is outlined in more detail in our
upgrade FAQ: 

http://processwire.com/talk/index.php/topic,58.0.html


UPGRADING FROM 2.1
---------------------------------------------------------------------------
The instructions above (in the 'UPGRADES') section apply to an upgrade 
from ProcesWire 2.1 to 2.2. Note that replacing the /index.php and 
.htaccess is required in this upgrade, as both of those files have 
changed. 

If you are using non-superuser roles for page-edit access, you'll
also need to edit any access-defining templates after upgrading and give
any relevant roles the new page-create permission. More information here:

http://processwire.com/talk/topic/853-processwire-22-soft-launch/


UPGRADING FROM 2.0
---------------------------------------------------------------------------
Upgrading from ProcessWire 2.0 to 2.1 requires more than just replacing the 
/wire/ directory. Because there are many differences between 2.0 and 2.1/2.2,
particularly with the user system, you must export your 2.0 site and import
it to a new 2.2 installation. This is relatively easy to do and has the 
added benefit of being completely safe (you never touch your old site until
your new 2.2 site is up and running). Please visit the following link for 
instructions on how to complete this upgrade:

http://processwire.com/talk/index.php/topic,583.0.html


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
Get support in the ProcessWire forum:
http://processwire.com/talk/

You can also contact us at: 
http://processwire.com/contact/


ProcessWire, Copyright 2012 by Ryan Cramer

