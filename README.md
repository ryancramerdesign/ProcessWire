# ProcessWire 2.3

## About ProcessWire

ProcessWire is an open source content management system (CMS) and web 
application framework aimed at the needs of designers, developers and their 
clients. ProcessWire gives you more control over your fields, templates and 
markup than other platforms, and provides a powerful template system that 
works the way you do. Not to mention, ProcessWire's API makes working with 
your content easy and enjoyable. Managing and developing a site in 
ProcessWire is shockingly simple compared to what you may be used to.

* [Learn more about ProcessWire](http://processwire.com)
* [Download the latest ProcessWire](http://processwire.com/download/)
* [Get support for ProcessWire](http://processwire.com/talk/)
* [Browse and install ProcessWire modules/plugins](http://modules.processwire.com)
* [Follow @ProcessWire on Twitter](http://twitter.com/processwire/)
* [Contact ProcessWire](http://processwire.com/contact/)


## Installation

### Requirements

* A web server running Apache. 
* PHP version 5.2.4 or greater (but PHP 5.3+ strongly preferred)
* MySQL 5.0.15 or greater.
* Apache must have mod_rewrite enabled. 
* Apache must support .htaccess files. 


### Installation from ZIP file

1. Unzip the ProcessWire installation file to the location where you want it
   installed on your web server. 

2. Load the location that you unzipped (or uploaded) the files to in your web
   browser. This will initiate the ProcessWire installer. The installer will
   guide you through the rest of the installation.


### Installation from GitHub

Git clone ProcessWire to the place where you want to install it:

```
git clone https://github.com/ryancramerdesign/ProcessWire 
```

Load the location where you installed ProcessWire into your browser. 
This will initiate the ProcessWire installer. The installer will guide
you through the rest of the installation.  


### Troubleshooting Installation

If the homepage works, but nothing else does, then that means Apache
is not properly reading your .htaccess file. Open the .htaccess file
in and editor and read through the comments in the file for suggested
changes. 

If you are getting an error message, a blank screen, or something
else unexpected, see the section at the end of this document on 
enabling debug mode. This will enable more detailed error reporting
which may help to resolve any issues. 

If the above suggestions don't help you to resolve the installation
error, please post in the [ProcessWire forums](http://processwire.com/talk). 


## Upgrades

### Best Practices Before Upgrading

1. Backup your database and backup all the files in your site.
2. When possible, test the upgrade on a development/staging site 
   before performing the upgrade on a live/production site. 
3. If you have 3rd party modules installed, confirm that they are 
   compatible with the ProcessWire version you are upgrading to. 
   If you cannot confirm compatibility, uninstall the 3rd party 
   modules before upgrading, when possible. You can attempt to
   re-install them after upgrading. 

### General Upgrade Process

If you are upgrading from a version of ProcessWire earlier than 2.3,
see the sections below for version-specific details before completing
the general upgrade process. 

Upgrading from one version of ProcessWire to another is a matter of
replacing these files from your old version with those from the new:

```
/wire/
/index.php
/.htaccess 
```

Replacing the above directory/files is typically the only thing you
need to do in order to upgrade. But please see below for more specific
details about each of these: 

#### Replacing the /wire/ directory

When you replace the /wire/ directory, make sure that you remove or 
rename the old one first. If you just copy or FTP changed files into
the existing /wire/ directory, you will end up with both old and new
files, which will cause an error. 

Note that the /wire/ directory does not contain any files specific to 
your site, only to ProcessWire. All the files specific to your site 
are stored in /site/ and you would leave that directory alone during 
an upgrade. 

#### Replacing the /index.php file

This file doesn't change often between minor versions. As a result,
you don't need to replace this file unless it has changed. 

#### Replacing the .htaccess file

This file is initially named htaccess.txt in the ProcessWire source.
You will want to remove your existing .htaccess file and rename the
new htaccess.txt to .htaccess

Sometimes people have made changes to the .htaccess file. If this is
the case for your site, remember to migrate those changes to the new
.htaccess file. 


### Upgrading from ProcessWire 2.2

1. Follow the general upgrade process above. You *will* want to replace
   your /index.php and .htaccess file as well.
2. Clear your modules cache (see section below).
3. Login to ProcessWire admin. You may get an error on the first web 
   request you try, but that should only happen once, so just reload 
   the page. 

**To clear your modules cache:** Remove all of these files:
/site/assets/cache/Modules.*


### Upgrading from ProcessWire 2.1

1. First upgrade to [ProcessWire 2.2](https://github.com/ryancramerdesign/ProcessWire/tree/2.2.9).
2. Follow the instructions above to upgrade from ProcessWire 2.2.


### Upgrading from ProcessWire 2.0

1. [Download ProcessWire 2.2](https://github.com/ryancramerdesign/ProcessWire/tree/2.2.9) 
   and follow the upgrade instructions in that version's [README](https://github.com/ryancramerdesign/ProcessWire/blob/2.2.9/README.txt) 
   file to upgrade from 2.0 to 2.2. 
2. After successfully upgrading to 2.2, follow the general upgrade 
   process above.


### Troubleshooting an Upgrade

If your site is not working after performing an upgrade, clear your
modules cache. You can do this by removing all of these files:
/site/assets/cache/Modules.*

If your site still doesn't work, remove the /wire/ directory completely. 
Then upload a fresh copy of the /wire/ directory. 

If your site still doesn't work, view the latest entries in your error
log file to see if it clarifies anything. The error log can be found in:
/site/assets/logs/errors.txt

If your site still doesn't work, please post in the
[ProcessWire support forums](http://processwire.com/talk/). 


## Debug Mode

Debug mode causes all errors to be reported to the screen, which can be
hepful during development or troubleshooting. When in the admin, it also
enables reporting of extra information in the footer. Debug mode is not
intended for live or production sites, as the information reported could
be a problem for security. So be sure not to leave debug mode on for
any live/production sites. 

1. Edit this file: /site/config.php
2. Find this line: $config->debug = false; 
3. Change the 'false' to 'true', like below, and save. 

```
$config->debug = true; 
```

This can be found near the bottom of the file. It will make PHP and 
ProcessWire report all errors, warnings, notices, etc. Of course, you'll
want to set it back to false once you've resolved any issues. 


## Support

Get support in the ProcessWire forum at:
[http://processwire.com/talk/](http://processwire.com/talk/)

------

ProcessWire, Copyright 2013 by Ryan Cramer Design, LLC

