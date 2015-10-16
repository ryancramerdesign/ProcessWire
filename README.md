# ProcessWire 3.x alpha (devns branch: dev with namespace support)

This branch will ultimately become ProcessWire 3.0. This is a work in progress 
and is recommended only for testing at this point in time. Do not use it in 
production, as things are still likely to change. However, if you want to try 
it, here's some tips on how to get this namespace version of ProcessWire 
working with your site and/or modules. 

## New installations

If creating a new installation, then you don't need to do anything different
than before--simply install ProcessWire as you always have. 

Note that few if any 3rd party modules currently support 3.x, so if you want 
to install 3rd party modules, you'll need to add a namespace to their files.
See the "Adding a namespace" section in this document for instructions. 

## Upgrading existing installations

If you are upgrading an existing version, here are the steps you should take: 

1. Replace your /wire/ directory and /index.php file with the ones from here.
   Don't forget the /index.php as it is definitely required! You do not however
   need to replace your .htaccess file.
   
2. Edit your /site/config.php and set `$config->debug = true;` to ensure you can
   see error messages. You likely will! 
   
3. Load your admin panel in the browser (typically domain.com/processwire/). 
   If you are using any 3rd party modules, there is a good chance you'll see 
   error messages from them. If so, read and follow the section below titled 
   "Resolving error messages".
   
4. Once you've resolved error messages in your admin, you'll want to do the same
   for the front-end of your site. Depending on your template file strategy,
   updates may or may not be necessary. Most issues can be resolved simply by
   enabling 2.x compatibility mode or adding the ProcessWire namespace to the
   top of the template file. See the sections below for both options.

## Resolving error messages

Most (likely all) error messages you get will be related to the fact that this
version of the core is now running in a namespace called ProcessWire, rather than
in the root PHP namespace. Error messages will likely mention a file in your 
/site/modules/ directory or a file in your /site/templates/ directory. 

There are two ways you can correct the errors:

1. Add a namespace to the top of the file; or
2. Enable ProcessWire 2.x compatibility mode

To add a namespace to the top of the file, see the section below titled "Adding the
ProcessWire namespace to a file." Or, to enable ProcessWire 2.x compatibility mode, 
see section titled "Enabling 2.x compatibility mode."

## Adding the ProcessWire namespace to a file

To add the namespace to a file, simply edit the file and add this at the very top:

``````````
<?php namespace ProcessWire;
``````````

If the file already has an opening `<?php tag` then just add the `namespace ProcessWire;`
immediately after it. In most cases, this is all you need to do. If the file you edited
also loads other PHP files, you may need to add the namespace to them as well. 

If you continue to see error messages for the file you added a namespace to, it is 
likely because the file was referring to a class in PHP's root namespace (such as PHP's 
classes and interfaces like the PDO class or the Countable interface). You can fix such 
errors simply by prepending a backslash to the place where the class is mentioned. For 
instance, `DirectoryIterator` would change to `\DirectoryIterator`, and then the error 
message would be resolved. 

## Enabling 2.x compatibility mode

When you enable 2.x compatibility mode, it enables many 3rd party modules and/or
your own template files to continue working, even if they don't use a namespace. 
Though it won't work for all cases, but it should for many. Note however that enabling 
compatibility mode reduces some of the benefit of using namespaces as it aliases many
common PW classes and functions back to the root namespace, like in 2.x.

*Note: most 3rd party Fieldtype modules (like ProFields) will require the namespace
added to them before they will work. Whereas most Process modules will work well in
compatibility mode.*


