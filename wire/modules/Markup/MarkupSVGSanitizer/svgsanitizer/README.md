SVG Sanitizer
======

Whitelist-based PHP SVG sanitizer.

Usage
-----

```php
<?php

// load
require_once('SvgSanitizer.php');
$svg = new SvgSanitizer();

// load SVG
$svg->load("evil.svg");

// sanitize!
$svg->sanitize();

// Print out sanitized SVG
echo $svg->saveSVG();

?>
```

License
-------

[MIT](http://opensource.org/licenses/MIT)