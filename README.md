# ProcessWire 3.x alpha (devns branch: dev with namespace support)

This branch will ultimately become ProcessWire 3.0. This is a work in progress 
and is recommended for testing at this point in time. We don't recommend using 
it in production yet unless it's for a site that you monitor closely. 

## New installations

If creating a new installation, then you don't need to do anything different
than before--simply install ProcessWire as you always have. 

## Upgrading to 3.0

The following instructions assume you are already running ProcessWire 2.7. 
If you are not already running PW 2.7, please upgrade to it first. 

1. Login to the admin of your site. 

2. Edit your /site/config.php and set `$config->debug = true;` to ensure you can
   see error messages. 

3. Replace your /wire/ directory and /index.php file with the ones from here.
   Don't forget the /index.php as it is definitely required (it will tell you
   if you forget). You do not however need to replace your .htaccess file.
   
4. Click a tab page in your admin, such as "Pages". You may notice a delay. 
   This is ProcessWire compiling 3rd party modules into a format that is
   compatible with version 3.x. Keep an eye out for any error messages. 
   
5. Once you've resolved error messages in your admin, you'll want to test out 
   the front end of your site. Again, expect a delay while ProcessWire compiles
   any files to make them compatible with 3.x. Depending on your template file 
   strategy, updates may or may not be necessary. If you run into any pages 
   that aren't working, see the section furthern down on troubleshooting.
   
## Downgrading to 2.7   

So long as you haven't modified your template or module files to add PW 3.x 
specific code to them (like a namespace), you can easily switch between 
ProcessWire 2.7 and 3.0 simply by swapping the /wire/ directory and /index.php
file from the appropriate version. ProcessWire 3.x doesn't make changes to 
the database, so the database should be identical between 2.7 and 3.0.
  
## Troubleshooting

Most (likely all) error messages you get will be related to the fact that this
version of the core is now running in a namespace called ProcessWire, rather than
in the root PHP namespace. Error messages will likely mention a file in your 
/site/modules/ directory or a file in your /site/templates/ directory. 

ProcessWire attempts to compile any module or template PHP files that it thinks
will have issues due to namespace. This should work well in most instances.
However, if you come across any instances where it does not work, see the next
section on adding a namespace to your file(s). We would also appreciate 
[issue reports](https://github.com/ryancramerdesign/ProcessWire/issues)
of any instances where you find the file compiler doesn't work.

### Adding the ProcessWire namespace to a file

To add the namespace to a file, simply edit the file and add this at the very top:

``````````
<?php namespace ProcessWire;
``````````

If the file already has an opening `<?php tag` then just add the `namespace ProcessWire;`
immediately after it. If the file you edited also loads other PHP files, you may need 
to add the namespace to them as well. 

If you continue to see error messages for the file you added a namespace to, it is 
likely because the file was referring to a class in PHP's root namespace (such as PHP's 
classes and interfaces like the PDO class or the Countable interface). You can fix such 
errors simply by prepending a backslash to the place where the class is mentioned. For 
instance, `DirectoryIterator` would change to `\DirectoryIterator`, and then the error 
message would be resolved. 

## About the file compiler

ProcessWire 3.x compiles 3rd party module and template files that it thinks may need
adjustments in order to run with version 3.x. It copies them into the directory
/site/assets/cache/FileCompiler/ and adjusts any references to ProcessWire classes so
that they will work in ProcessWire's namespace. The compiled module/template files
continue to run in the root namespace as it would have in ProcessWire 2.7. 

### Should you add a namespace to your template and module files now?

We suggest that you don't add a namespace if you want to remain compatible with 
the 2.x branch of ProcessWire. ProcessWire's file compiler is quite reliable and
will take care of the namespace details for you. However, if you do want to focus
purely on ProcessWire 3.x and/or want your file to run in its own namespace, then
you should go ahead and add it.

### Do compiled PHP files add any overhead?

The only overhead associated with compiled PHP files occurs when they are actually
compiled (which you may notice as a delay). However, this only occurs once, or 
whenever the source file is changed. 

Once a file has already been compiled, it adds no overhead to the request. In fact
you may notice ProcessWire 3.x performance to be slighly better than 2.x in many 
instances. 

### How do you disable the file compiler for a given PHP File?

If you want to disable the file compiler for a given PHP file, include the string 
`FileCompiler=0` anywhere in the file. For instance, in a PHP comment:
`````````
// FileCompiler=0
`````````	
When it comes to 3rd party module files, ProcessWire will not attempt to compile 
module files that already declare a namespace at the top. 

The file compiler can also be disabled for a given template file by editing the
template settings in your admin. Setup > Templates > [template] > Files. 

## Please report any errors 

If you run into any errors with ProcessWire 3.x, whether due to file compilation
or anything else, please report them in the 
[GitHub issue reports](https://github.com/ryancramerdesign/ProcessWire/issues)
and be sure to indicate that you are talking about the 3.x version of ProcessWire. 


