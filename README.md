# ProcessWire 2.4

## Table of Contents

1. [About ProcessWire](#about-processwire)
2. [Installing ProcessWire](#installation)
   - [Requirements](#requirements)
   - [Installation from ZIP file](#installation-from-zip-file)
   - [Installation from GitHub](#installation-from-github)
   - [Troubleshooting Installation](#troubleshooting-installation)
       - [The homepage works but nothing else does](#the-homepage-works-but-nothing-else-does)
       - [Resolving an Apache 500 error](#resolving-an-apache-500-error)
       - [Resolving other error messages or a blank screen](#resolving-other-error-messages-or-a-blank-screen)
3. [Upgrading ProcessWire](#upgrades)
   - [Best Practices Before Upgrading](#best-practices-before-upgrading)
   - [General Upgrade Process](#general-upgrade-process)
       - [Replacing the /wire/ directory](#replacing-the-wire-directory)
       - [Replacing the /index.php file](#replacing-the-indexphp-file)
       - [Replacing the .htaccess file](#replacing-the-htaccess-file)
       - [Additional upgrade notes](#additional-upgrade-notes)
   - [Upgrading from ProcessWire 2.2 or 2.3](#upgrading-from-processwire-22-or-23)
   - [Upgrading from ProcessWire 2.1](#upgrading-from-processwire-21)
   - [Upgrading from ProcessWire 2.0](#upgrading-from-processwire-20)
   - [Troubleshooting an Upgrade](#troubleshooting-an-upgrade)
4. [Debug Mode](#debug-mode)
5. [Support](#support)

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
* PHP version 5.3.8 or newer.
* MySQL 5.0.15 or newer.
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

#### The homepage works but nothing else does

This indicates that Apache is not properly reading your .htaccess file. 
First we need to determine if Apache is reading your .htacess file at all.
To do this, open the .htaccess file in an editor and type in some random
characters at the top, like `lkjalefkjalkef` and save. Load your site in 
your browser. You should get a "500 Error". If you do not, that means 
Apache is not reading your .htaccess file at all. If this is your case,
contact your web host for further assistance. Or if maintaining your own
server, look into the Apache *AllowOverride* directive which you may need
to configure for the account in your httpd.conf file. 

If the above test did result in a 500 error, then that is good because we
know your .htaccess file is at least being used. Go ahead and remove the 
random characters you added at the top. Now look further down in the 
.htaccess file for suggested changes. Specially, you will want to look at 
the *RewriteBase* directive, which is commented out (disabled) by default. 
You may need to enable it.

#### Resolving an Apache 500 error

The presence of an Apache 500 error indicates that Apache does not
like one or more of the directives in the .htaccess file. Open the
.htaccess file in an editor and read the comments. Note those that 
indicate the term "500 NOTE" and they will provide further instructions
on optional directives you can try to comment out. Test one at a time,
save and reload in your browser till you determine which directive is
not working with your server.

#### Resolving other error messages or a blank screen

If you are getting an error message, a blank screen, or something
else unexpected, see the section at the end of this document on 
enabling debug mode. This will enable more detailed error reporting
which may help to resolve any issues. 

In addition, the ProcessWire error log is located in the file:
/site/assets/logs/errors.txt - look in here to see if more information
is available about the error message you have received. 

If the above suggestions do not help you to resolve the installation
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
   re-install them after upgrading. If uninstalling is 
   inconvenient, just be sure you have the ability to revert if for 
   some reason one of your modules does not like the upgrade.
   Modules that are compatible with ProcessWire 2.3 are generally
   going to also be compatible with 2.4. 


### General Upgrade Process

Upgrading from one version of ProcessWire to another is a matter of
replacing these files from your old version with those from the new:

```
/wire/
/index.php
/.htaccess 
```

Replacing the above directory/files is typically the primary thing you
need to do in order to upgrade. But please see the version-to-version
specific upgrade notes documented further in this section. Below are
more details about how you should replace the files mentioned above.


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
you don't need to replace this file unless it has changed. BUt when
in doubt, you should replace it. 


#### Replacing the .htaccess file

This is also a file that does not always change between versions.
But when it changes, it is usually important for security that you 
are up-to-date. When in doubt, replace your old .htaccess file with
the htaccess.txt from the new version. 

This file is initially named htaccess.txt in the ProcessWire source.
You will want to remove your existing .htaccess file and rename the
new htaccess.txt to .htaccess

Sometimes people have made changes to the .htaccess file. If this is
the case for your site, remember to migrate those changes to the new
.htaccess file. 

#### Additional upgrade notes

- If using Form Builder make sure you have the latest version,
  as past versions did not support ProcessWire 2.4. 

- If using ProCache you will need to go to the ProCache
  settings after the upgrade to have it update your .htaccess file
  again (since it was presumably replaced during the upgrade). 

- After completing the upgrade test out your site thoroughly
  to make sure everything continues to work as you expect. 

- ProcessWire 2.4 comes with a new admin theme. After completing
  your upgrade, you may choose a different color theme (if desired)
  by going to Modules > Core > Default Admin Theme. 

- If you want to return to the old admin theme or utilize an
  existing 3rd party admin theme designed for 2.3 or earlier,
  simply uninstall the Default Admin Theme module. 


### Upgrading from ProcessWire 2.2 or 2.3

ProcessWire 2.4 has two new software requirements: 

- PHP 5.3.8+ (older versions supported PHP 5.2)
- PDO database driver (older versions only used mysqli)

Please confirm your server meets these requirements before upgrading.
If you are not certain, paste the following into a test PHP file and 
load it from your browser:

```
<?php phpinfo();
```

This will show your PHP configuration. The PHP version should show 
PHP 5.3.8 or newer and there should be a distinct PDO section 
(header and information) present in the output. 

**To proceed with the upgrade** follow the [general upgrade process](#general-upgrade-process)
above. You *will* want to replace your index.php and .htaccess 
files as well.

**In addition** we recommend adding the following line to your 
/site/config.php: 
```
$config->httpHosts = array('domain.com', 'www.domain.com'); 
```
Replace domain.com with the hostname(s) your site runs from.


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

If you get an error message when loading your site after an upgrade,
hit "reload" in your browser once before doing anything else. If the error
is now gone, the error was normal and your upgrade was successful. 

If your site is not working after performing an upgrade, clear your
modules cache. You can do this by removing all of these files:
/site/assets/cache/Modules.*

If using Form Builder, make sure you have version 0.2.2 or newer, as older
versions did not support ProcessWire 2.4. 

If your site still doesn't work, remove the /wire/ directory completely. 
Then upload a fresh copy of the /wire/ directory. 

If your site still doesn't work, view the latest entries in your error
log file to see if it clarifies anything. The error log can be found in:
/site/assets/logs/errors.txt

If your site still doesn't work, please post in the
[ProcessWire support forums](http://processwire.com/talk/). 


## Debug Mode

Debug mode causes all errors to be reported to the screen, which can be
helpful during development or troubleshooting. When in the admin, it also
enables reporting of extra information in the footer. Debug mode is not
intended for live or production sites, as the information reported could
be a problem for security. So be sure not to leave debug mode on for
any live/production sites. 

1. Edit this file: `/site/config.php`
2. Find this line: `$config->debug = false;` 
3. Change the `false` to `true`, like below, and save. 

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

ProcessWire, Copyright 2014 by Ryan Cramer Design, LLC

