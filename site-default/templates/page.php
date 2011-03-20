<?php 

/**
 * Page template
 *
 */

include("./head.inc"); 

// Not really necessary, but wanted the markup to line up with where it left off in header include,
// so put four tabs before the bodycopy before outputting it: 
echo "\t\t\t\t{$page->body}";

include("./foot.inc"); 

