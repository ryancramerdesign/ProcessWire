<ul id='notices' class='ui-widget'>
<?php

foreach($notices as $notice) {

	$class = 'ui-state-highlight';
	$icon = 'ui-icon-info';

	if($notice instanceof NoticeError) {
		$class = 'ui-state-error'; 
		$icon = 'ui-icon-alert'; 
	}

	$text = $notice->text; 
	if($notice->class && $config->debug) $text = "{$notice->class}: $text";

	echo "\n\t\t<li class='$class'><div class='container'><p><span class='ui-icon $icon'> </span>{$text}</p></div></li>";

}

?>
</ul><!--/notices-->

