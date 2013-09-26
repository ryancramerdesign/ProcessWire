<?php

$config->inputfieldColumnWidthSpacing = 0; // percent spacing between columns

$markup = InputfieldWrapper::getMarkup(); 
$markup['item_label'] = "\n\t\t<label class='InputfieldHeader' for='{for}'>{out}</label>";
$markup['item_content'] = "\n\t\t<div class='InputfieldContent'>\n{out}\n\t\t</div>";
InputfieldWrapper::setMarkup($markup); 

$class = InputfieldWrapper::getClasses();
$classes['item'] = "Inputfield {class} Inputfield_{name}";
$classes['item_error'] = "InputfieldStateError";
InputfieldWrapper::setClasses($classes); 

