<ul id='notices' class='ui-widget'>
<?php

foreach($notices as $notice) {

	$class = 'ui-state-highlight NoticeMessage';
	$text = $notice->text; 
	$icon = '';

	if($notice instanceof NoticeError || $notice->flags & Notice::warning) {
		$class = 'ui-state-error'; 
		if($notice->flags & Notice::warning) {
			$class .= ' NoticeWarning';
			$icon = 'ui-icon-info';
		} else {
			$class .= ' ui-priority-primary NoticeError';
			$icon = 'ui-icon-alert'; 
		}
	}

	if($notice->flags & Notice::debug) {
		$class .= ' ui-priority-secondary NoticeDebug';
		if(!$icon) $icon = 'ui-icon-circle-arrow-e';
	}

	if(!$icon) $icon = 'ui-icon-info';

	if($notice->class && $config->debug) $text = "{$notice->class}: $text";

	echo "\n\t\t<li class='$class'><div class='container'><p><span class='ui-icon $icon'></span>{$text}</p></div></li>";

}

echo "\n\t</ul><!--/notices-->";

