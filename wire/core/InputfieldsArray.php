<?php

/**
 * A WireArray of Inputfield instances, as used by InputfieldWrapper. 
 *
 * The default numeric indexing of a WireArray is not overridden.
 *
 */
class InputfieldsArray extends WireArray {

	/**
	 * Per WireArray interface, only Inputfield instances are accepted.
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Inputfield;
	}

	/**
	 * Extends the find capability of WireArray to descend into the Inputfield children
	 *
	 */
	public function find($selector) {
		$a = parent::find($selector);
		foreach($this as $item) {
			if(!$item instanceof InputfieldWrapper) continue;
			$children = $item->children();
			if(count($children)) $a->import($children->find($selector));
		}
		return $a;
	}

	public function makeBlankItem() {
		return null; // Inputfield is abstract, so there is nothing to return here
	}

	public function usesNumericKeys() {
		return true;
	}

}
