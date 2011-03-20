PRE ELEMENT FIX

Author: Thomas Andersen <tan@enonic.com>
CSS used with permission from Matt Gallagher <http://projectswithlove.com/>

------------------------------------------------------------------------------------------------------------------------
LICENSE

LGPL

See license.txt

------------------------------------------------------------------------------------------------------------------------
COMPABILITY

TinyMCE 3.2.7 (Should work with the 3.2.* branch, but not tested).
IE 7.x
Firefox 3.x
Safari 4.x

------------------------------------------------------------------------------------------------------------------------
DESCRIPTION

The goal of this plug-in is to fix some issues I have experienced with the PRE element in TinyMCE.
I have found that WYSIWYG editors and browsers behaves different and nonintuive regarding this element so I decided to try fixing it.

In my opinion editing content inside the PRE element should:

1. Perserve white space.
2. The PRE element should contain no child elements.
4. Tab key should create a tab character. This is just nice to have and since the MISE content editable handles this :)

This plug-in tries to fix the above issues.

Here are the issues and solutions so far:

ENTER key:
MSIE and WebKit creates a new sibling PRE element each time the ENTER key is pressed.
Opera and Firefox creates a BR element.

The goal is to create a newline (\n) for all browsers when the ENTER key is pressed.

TAB key:
When pressing the tab key in Firefox nothing happens. IE creates a TAB
WebKit creates a TAB char inside a span element.

The goal is to create a tab (\t) for all browsers when the TAB key is pressed.

------------------------------------------------------------------------------------------------------------------------
INSTALL

- Unzip the downloaded zip to the plug-in directory of your TinyMCE installation.
- Add "preelementfix" (without the quotes) to your plug-in TinyMCE configuration
  NOTE! if you are planning to use the preelementfix_css_classes config option it is recomended to place the prelementfix plug-in after the table plug-in.
  See known issues.

------------------------------------------------------------------------------------------------------------------------
CONFIGURATION OPTIONS AND STYLING:

You need to make sure the pre element is in  your valid_elements or extend_valid_element configuration.

Options:

preelementfix_css_aliases

- A list of CSS classes.
The TinyMCE contextmenu must be enabled to use this option (http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/contextmenu). 
If this option is set and the user context click(right click) on the PRE element, the TinyMCE context menu will be populated with names from this list.
The list is a JS object (name and value pairs).
The name part is the text for the menu item and the value is the CSS class which to be added to the PRE element.
Please note that we quote the property name.

    // Example
    tinyMCE.init({
        ...
        preelementfix_css_aliases: {
            'C++': 'cpp',
            'C#': 'csharp',
            'Delphi': 'delphi',
            'HTML': 'html',
            'Java': 'java',
            'Java Script': 'javascript',
            'PHP': 'php',
            'Python': 'python',
            'Ruby': 'ruby',
            'Sql': 'sql',
            'VB': 'vb',
            'XHTML': 'xhtml',
            'XML': 'xml'
         }
    });


There is also an CSS file where you can style the PRE element/s and the UI tooltip.
See: plugins/prelementfix/css/prelementfix.css

------------------------------------------------------------------------------------------------------------------------
KNOWN ISSUES

- Does not work well in Opera. 
- Pasting is not handled. When pasting &nbsp; is used instead of whitespace.

TODO
- 0.4 More testing. 

------------------------------------------------------------------------------------------------------------------------
LOG
 
0.2
- The last newline before the closing PRE element is now removed making it more cross browser.

0.3
- Added license information

0.4b
- Refactoring.
- Improved Opera support.
- Fixed some issues with the cursor in Firefox.
- Added option for adding CSS aliases to the PRE element through the context menu.

0.4b2
- Fixed some enter key issues in Firefox and Opera.
- Fixed some conflicts with the context menu in the table plug-in.