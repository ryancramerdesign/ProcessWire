
Do not replace the /wire/templates-admin/ with this directory.
This directory should be kept in /site/templates-admin/. If you 
want to use the original addmin theme, then either delete this
templates-admin directory or rename it. 

COLOR SCHEMES
=============
This theme comes with 3 color schemes: 

- warm: a new color scheme with seasonal (Fall) colors. 
- modern: similar to processwire.com site colors.
- classic: similar colors to PW original admin theme. 

To switch to one or the other, specify a GET variable of 'colors' in any 
URL you are accessing in the admin:

/processwire/?colors=warm
/processwire/?colors=modern
/processwire/?colors=classic

To specify a default, edit your /site/config.php and set to one of the 
following:

$config->adminThemeColors = 'warm';
$config->adminThemeColors = 'modern';
$config->adminThemeColors = 'classic';

