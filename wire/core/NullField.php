<?php

/**
 * Class NullField
 * 
 */

class NullField extends Field {
	public function get($key) {
		if($key == 'id') return 0;
		if($key == 'name') return '';
		return parent::get($key); 
	}
}
