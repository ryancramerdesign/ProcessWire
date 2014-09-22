HOW TO INSTALL MODULES
======================

This directory /site/modules/ is where you may install additional plugin modules.

If the module you want to install contains only one file, like Helloworld.module,
you may place it directly in /site/modules/. If the modue contains multiple files,
you should create a new directory for it. The directory should be the same as the
module name, minus the ".module" part. For example, if we were to create a dir for
the Helloworld.module file, we would create /site/modules/Helloworld/ and place
the module's files in there. 

Once you have placed a new module in this directory, you need to let ProcessWire
know about it. Login to the admin and click "Modules". Then click the "Check for
new modules" button. It will find your new module(s). Click the "Install" button
next to any new modules that you want to install.

To uninstall a module, you must uninstall it from the ProcessWire admin first. 
You will see an "Uninstall" option on each module's configuration screen. Once
you have uninstalled a module in ProcessWire admin, you may remove the files 
from the /site/modules/ directory. 

INFORMATION ABOUT PLUGIN MODULES
================================

To find and download new modules, see the modules directory at:
http://modules.processwire.com/ 

For more information about modules, see the documentation at:
http://processwire.com/api/modules/

For a tutorial on how to create modules, see:
http://wiki.processwire.com/index.php/Module_Creation

For discussion and support of modules, see:
http://processwire.com/talk/forum/4-modulesplugins/

