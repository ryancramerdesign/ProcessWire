<?php

/**
 * ProcessWire InputfieldWrapper
 *
 * Classes built to provide a wrapper for Inputfield instances. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * A WireArray of Inputfield instances.
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

/**
 * A type of Inputfield that is designed specifically to wrap other Inputfields
 *
 * The most common example of an InputfieldWrapper is a <form> 
 *
 * InputfieldWrapper is not designed to render an Inputfield specifically, but you can set a value attribute
 * containing content that will be rendered before the wrapper. 
 *
 */ 
class InputfieldWrapper extends Inputfield {

	/**
	 * Instance of InputfieldsArray, if this Inputfield contains child Inputfields
	 *
	 */
	protected $children = null;

	/**
	 * Construct the Inputfield, setting defaults for all properties
	 *
	 */
	public function __construct() {
		parent::__construct();
 		$this->children = new InputfieldsArray(); 
		$this->set('skipLabel', true); 
	}

	/**
	 * By default, calls to get() are finding a child Inputfield based on the name attribute
	 *
	 */
	public function get($key) {
		if($inputfield = $this->getChildByName($key)) return $inputfield;
		if($this->fuel($key)) return $this->fuel($key); 
		if($key == 'children') return $this->children; 
		if(($value = parent::get($key)) !== null) return $value; 
		return null;
	}

	/**
	 * Add an Inputfield a child
	 *
	 * @param Inputfield $item
	 *
	 */
	public function add(Inputfield $item) {
		$item->setParent($this); 
		$this->children->add($item); 
		return $this; 
	}

	/**
	 * Prepend another Inputfield to this Inputfield's children
	 *
	 */
	public function prepend(Inputfield $item) {
		$item->setParent($this); 
		$this->children->prepend($item); 
		return $this; 
	}

	/**
	 * Append another Inputfield to this Inputfield's children
	 *
	 */
	public function append(Inputfield $item) {
		$item->setParent($this); 
		$this->children->append($item); 
		return $this; 
	}

	/**
	 * Insert one Inputfield before one that's already there
	 *
	 */
	public function insertBefore(Inputfield $item, Inputfield $existingItem) {
		$item->setParent($this); 
		$this->children->insertBefore($item, $existingItem); 
		return $this; 
	}

	/**
	 * Insert one Inputfield after one that's already there
	 *
	 */
	public function insertAfter(Inputfield $item, Inputfield $existingItem) {
		$item->setParent($this); 
		$this->children->insertAfter($item, $existingItem); 
		return $this; 
	}

	/**
	 * Remove an Inputfield from this Inputfield's children
	 *
	 */
	public function remove($item) {
		$this->children->remove($item); 
		return $this; 
	}

	/**
	 * Prepare children for rendering by creating any fieldset groups
	 *
	 */
	protected function preRenderChildren() {

		$children = new InputfieldWrapper(); 
		$wrappers = array($children);

		foreach($this->children as $inputfield) {

			$wrapper = end($wrappers); 

			if($inputfield instanceof InputfieldFieldsetClose) {
				if(count($wrappers) > 1) array_pop($wrappers); 
				continue; 

			} else if($inputfield instanceof InputfieldFieldsetOpen) {
				array_push($wrappers, $inputfield); 
			}


			$wrapper->add($inputfield); 
		}

		return $children;
	}

	/**
	 * Entity encode a string (de-encoding if necessary and then re-encoding)
	 *
	 */
	protected function entityEncode($str) {
		// if already encoded, then un-encode it
		if(strpos($str, '&') !== false && preg_match('/&(#\d+|[a-z]+);/', $str)) {
			$str = html_entity_decode($str, ENT_QUOTES, "UTF-8"); 
		}

		$str = htmlentities($str, ENT_QUOTES, "UTF-8"); 
		return $str; 
	}

	/**
	 * Return the completed output of this Inputfield, ready for insertion in an XHTML form
	 *
	 * This includes the output of any child Inputfields (if applicable). Children are presented as list items in an unordered list. 
	 *
	 * @return string
	 *
	 */
	public function ___render() {

		$out = '';
		$children = $this->preRenderChildren();

		foreach($children as $inputfield) {

			$collapsed = (int) $inputfield->getSetting('collapsed'); 
			if($collapsed == Inputfield::collapsedHidden) continue; 

			$ffOut = $inputfield->render();
			if(!$ffOut) continue; 


			if(!$inputfield instanceof InputfieldWrapper) {
				$errors = $inputfield->getErrors(true);
				if(count($errors)) $collapsed = Inputfield::collapsedNo; 
				foreach($errors as $error) $ffOut = "\n<p class='ui-state-error-text'>" . $this->entityEncode($error) . "</p>" . $ffOut; 
			} else $errors = array();
			
			if($inputfield->getSetting('description')) $ffOut = "\n<p class='description'>" . $this->entityEncode($inputfield->getSetting('description')) . "</p>" . $ffOut;
			if($inputfield->getSetting('head')) $ffOut = "\n<h2>" . $this->entityEncode($inputfield->getSetting('head')) . "</h2>" . $ffOut; 

			$ffOut = preg_replace('/(\n\s*)</', "$1\t\t\t<", $ffOut); // indent lines beginning with markup
			if($inputfield->notes) $ffOut .= "\n<p class='notes'>" . $this->entityEncode($inputfield->notes) . "</p>"; 

			// The inputfield's classname is always used in it's LI wrapper
			$ffAttrs = array(
				'class' => $inputfield->className() . ($name = $inputfield->attr('name') ? ' Inputfield_' . $inputfield->attr('name') : '') . ' ui-widget', 
			);

			if(count($errors)) $ffAttrs['class'] .= " ui-state-error InputfieldStateError"; 

			if($collapsed) {
				if($inputfield instanceof InputfieldWrapper || $collapsed === Inputfield::collapsedYes || $collapsed === true || $inputfield->isEmpty()) 
					$ffAttrs['class'] .= " InputfieldStateCollapsed";
			}
			
			if($inputfield instanceof InputfieldWrapper) {
				// if the child is a wrapper, then id, title and class attributes become part of the LI wrapper
				foreach($inputfield->getAttributes() as $k => $v) {
					if(in_array($k, array('id', 'title', 'class'))) {
						$ffAttrs[$k] = isset($ffAttrs[$k]) ? $ffAttrs[$k] . " $v" : $v; 
					}
				}
			} 

			// if the inputfield resulted in output, wrap it in an LI
			if($ffOut) {
				$attrs = '';
				$label = '';
				if($inputfield->label) {
					$for = $inputfield->skipLabel ? '' : " for='" . $inputfield->attr('id') . "'";
					$label = "\n\t\t<label class='ui-widget-header'$for>" . $this->entityEncode($inputfield->label) . "</label>";
				}
				if(!isset($ffAttrs['id'])) $ffAttrs['id'] = 'wrap_' . $inputfield->attr('id'); 
				foreach($ffAttrs as $k => $v) {
					$attrs .= " $k='" . $this->entityEncode(trim($v)) . "'";
				}
				if($inputfield->className() != 'InputfieldWrapper') $ffOut = "\n\t\t<div class='ui-widget-content'>$ffOut\n\t\t</div>";
				$out .= "\n\t<li$attrs>$label$ffOut\n\t</li>\n";
			}
		}

		if($out) {
			$ulClass = "Inputfields";
			$out = $this->attr('value') . "\n<ul class='$ulClass'>$out\n</ul><!--/$ulClass-->\n";
		}

		return $out; 
	}

	/**
	 * Pass the given array to all children to process input
	 *
	 * @param array $input
	 * 
	 */
	public function ___processInput(WireInputData $input) {
	
		if(!$this->children) return $this; 

		foreach($this->children as $key => $child) {

			// skip over collapsedHidden inputfields, beacuse they were never drawn
			if($child->collapsed == Inputfield::collapsedHidden) continue; 

			// call the inputfield's processInput method
			$child->processInput($input); 
		}

		return $this; 
	}

	/**
	 * Return an array of errors that occurred on any of the children during processInput()
	 *
	 * Should only be called after processInput()
	 *
	 * @return array
	 *
	 */
	public function getErrors($clear = false) {
		$errors = parent::getErrors($clear); 
		foreach($this->children as $key => $child) {
			foreach($child->getErrors($clear) as $e) 
				$errors[] = $child->attr('name') . ": $e";
		}
		return $errors;
	}

	/**
	 * Return all child Inputfields, or a blank InputfieldArray if none
	 * 	
	 * @param string $selector Optional selector string to filter the children by
 	 * @return InputfieldArray
	 *
	 */
	public function children($selector = '') {
		if($selector) return $this->children->find($selector); 
			else return $this->children; 
	}

	/**
	 * Return all child Inputfields, or a blank InputfieldArray if none
	 *
	 * Alias of children()
	 *
	 * @param string $selector Optional selector string to filter the children by
 	 * @return InputfieldArray
	 *
	 */
	public function getChildren($selector = '') {
		return $this->children($selector); 
	}


	/**
	 * Like children() but $selector is not optional, and the method name is more readable in instances where you are filtering.
	 *
	 * @param string $selector Required selector string to filter the children by
 	 * @return InputfieldArray
	 *
	 */
	public function find($selector) {
		return $this->children->find($selector); 
	}

	/**
	 * Given a field name, return the child Inputfield or NULL if not found
	 *
	 * @param string $name
	 * @return Inputfield|null
	 *
	 */
	public function getChildByName($name) {
		$inputfield = $this->children->find("name=$name"); 	
		if(count($inputfield)) return $inputfield->first();
		return null;
	}

	/**
	 * Per the InteratorAggregate interface, make the Inputfield children iterable
	 *
	 */
	public function getIterator() {
		return $this->children; 
	}

	/**
	 * Start or stop tracking changes, applying the same to any children
	 *
	 */
	public function setTrackChanges($trackChanges = true) {
		if(count($this->children)) foreach($this->children as $child) $child->setTrackChanges($trackChanges); 
		return parent::setTrackChanges($trackChanges); 
	}

	/**
	 * Start or stop tracking changes after clearing out any existing tracked changes, applying the same to any children
	 *
	 */
	public function resetTrackChanges($trackChanges = true) {
		if(count($this->children)) foreach($this->children as $child) $child->resetTrackChanges($trackChanges); 
		return parent::resetTrackChanges();
	}

	/**
	 * Get any configuration Inputfields common to all InputfieldWrappers
	 *
	 */
	public function ___getConfigInputfields() {
		$fields = new InputfieldWrapper();

		$field = $this->modules->get("InputfieldRadios"); 
		$field->attr('name', 'collapsed'); 
		$field->label = "How should this field be displayed?";
		$field->addOption(Inputfield::collapsedNo, "Always open"); 
		$field->addOption(Inputfield::collapsedYes, "Always collapsed, requiring a click to open"); 
		$field->attr('value', (int) $this->collapsed); 
		$fields->append($field); 

		return $fields;
	}


}

