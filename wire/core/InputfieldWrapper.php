<?php

/**
 * ProcessWire InputfieldWrapper
 *
 * Classes built to provide a wrapper for Inputfield instances. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
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
	 * Markup used during the render() method - customize with InputfieldWrapper::setMarkup($array)
	 *
	 */
	static protected $defaultMarkup = array(
		'list' => "\n<ul {attrs}>\n{out}\n</ul>\n",
		'item' => "\n\t<li {attrs}>\n{out}\n\t</li>", 
		'item_label' => "\n\t\t<label class='InputfieldHeader ui-widget-header' for='{for}'>{out}</label>",
		'item_label_hidden' => "\n\t\t<label class='InputfieldHeader InputfieldHeaderHidden ui-widget-header'><span>{out}</span></label>",
		'item_content' => "\n\t\t<div class='InputfieldContent ui-widget-content'>\n{out}\n\t\t</div>", 
		'item_error' => "\n<p><span class='ui-state-error'>{out}</span></p>",
		'item_description' => "\n<p class='description'>{out}</p>", 
		'item_head' => "\n<h2>{out}</h2>", 
		'item_notes' => "\n<p class='notes'>{out}</p>",
		'item_icon' => "<i class='fa fa-{name}'></i> ",
		);

	static protected $markup = array();

	/**
	 * Classes used during the render() method - customize with InputfieldWrapper::setClasses($array)
	 *
	 */
	static protected $defaultClasses = array(
		'list' => 'Inputfields',
		'list_clearfix' => 'ui-helper-clearfix', 
		'item' => 'Inputfield {class} Inputfield_{name} ui-widget',
		'item_required' => 'InputfieldStateRequired',
		'item_error' => 'ui-state-error InputfieldStateError', 
		'item_collapsed' => 'InputfieldStateCollapsed',
		'item_column_width' => 'InputfieldColumnWidth',
		'item_column_width_first' => 'InputfieldColumnWidthFirst',
		'item_show_if' => 'InputfieldStateShowIf',
		'item_required_if' => 'InputfieldStateRequiredIf'
		);

	static protected $classes = array();

	/**
	 * Instance of InputfieldsArray, if this Inputfield contains child Inputfields
	 *
	 */
	protected $children = null;

	/**
	 * Array of Inputfields that had their processing delayed by dependencies. 
	 *
	 */
	protected $delayedChildren = array();

	/**
	 * Label displayed when a value is required but missing
	 *
	 */
	protected $requiredLabel = '';

	/**
	 * Construct the Inputfield, setting defaults for all properties
	 *
	 */
	public function __construct() {
		parent::__construct();
 		$this->children = new InputfieldsArray(); 
		$this->set('skipLabel', Inputfield::skipLabelFor); 
		$this->requiredLabel = $this->_('Missing required value');
		$this->set('renderValueMode', false); 
		$columnWidthSpacing = $this->wire('config')->inputfieldColumnWidthSpacing; 
		$columnWidthSpacing = is_null($columnWidthSpacing) ? 1 : (int) $columnWidthSpacing; 
		$this->set('columnWidthSpacing', $columnWidthSpacing); 
		//$this->set('useDependencies', true); // whether or not to use consider field dependencies during processing
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
	 * @return $this
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

		if($this->InputfieldWrapper_isPreRendered) return $this->children; 

		$children = new InputfieldWrapper(); 
		$wrappers = array($children);

		foreach($this->children as $inputfield) {

			$wrapper = end($wrappers); 

			if($inputfield instanceof InputfieldFieldsetClose) {
				if(count($wrappers) > 1) array_pop($wrappers); 
				continue; 

			} else if($inputfield instanceof InputfieldFieldsetOpen) {
				$inputfield->set('InputfieldWrapper_isPreRendered', true); 
				array_push($wrappers, $inputfield); 
			} 

			$wrapper->add($inputfield); 
		}

		return $children;
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
		$columnWidthTotal = 0;
		$columnWidthSpacing = $this->columnWidthSpacing; 
		$lastInputfield = null;
		$markup = array_merge(self::$defaultMarkup, self::$markup);
		$classes = array_merge(self::$defaultClasses, self::$classes);
	
		// show description for tabs
		if($this instanceof InputfieldFieldsetTabOpen && $this->description) {
			$out .= str_replace('{out}', nl2br($this->entityEncode($this->description, true)), $markup['item_head']);
		}
		
		foreach($children as $inputfield) {
			$renderValueMode = $this->renderValueMode; 

			$collapsed = (int) $inputfield->getSetting('collapsed'); 
			$required = $inputfield->getSetting('required');
			$requiredIf = $required ? $inputfield->getSetting('requiredIf') : false;
			$showIf = $inputfield->getSetting('showIf'); 
			
			if($collapsed == Inputfield::collapsedHidden) continue; 
			if($collapsed == Inputfield::collapsedLocked) $renderValueMode = true; 

			if($renderValueMode) {
				$ffOut = $inputfield->renderValue();
				if(is_null($ffOut)) continue; 
				if(!strlen($ffOut)) $ffOut = '&nbsp;';
			} else {
				$ffOut = $inputfield->render();
			}
			if(!strlen($ffOut)) continue; 

			if(!$inputfield instanceof InputfieldWrapper) {
				$errors = $inputfield->getErrors(true);
				if(count($errors)) $collapsed = Inputfield::collapsedNo; 
				foreach($errors as $error) $ffOut = str_replace('{out}', $this->entityEncode($error, true), $markup['item_error']) . $ffOut; 
			} else $errors = array();
			
			if($inputfield->getSetting('description')) $ffOut = str_replace('{out}',  nl2br($this->entityEncode($inputfield->getSetting('description'), true)), $markup['item_description']) . $ffOut;
			if($inputfield->getSetting('head')) $ffOut = str_replace('{out}', $this->entityEncode($inputfield->getSetting('head'), true), $markup['item_head']) . $ffOut; 

			$ffOut = preg_replace('/(\n\s*)</', "$1\t\t\t<", $ffOut); // indent lines beginning with markup

			if($inputfield->getSetting('notes')) $ffOut .= str_replace('{out}', nl2br($this->entityEncode($inputfield->notes, true)), $markup['item_notes']); 

			// The inputfield's classname is always used in it's LI wrapper
			$ffAttrs = array(
				'class' => str_replace(array('{class}', '{name}'), array($inputfield->className(), $inputfield->attr('name')), $classes['item'])
				);
			
			if($inputfield instanceof InputfieldItemList) $ffAttrs['class'] .= " InputfieldItemList";

			//if(count($errors)) $ffAttrs['class'] .= " ui-state-error InputfieldStateError"; 
			if(count($errors)) $ffAttrs['class'] .= ' ' . $classes['item_error'];
			if($required) $ffAttrs['class'] .= ' ' . $classes['item_required']; 
			if(strlen($showIf)) {
				// support for repeaters, added by soma:
				if(strpos($inputfield->name, "_repeater") !== false) { 
					$rep = explode("repeater", $inputfield->name);
					$showIfPart = explode("=", $showIf);
					if(!empty($rep[1]) && ctype_digit($rep[1])) {
						$showIf = $showIfPart[0] . "_repeater{$rep[1]}={$showIfPart[1]}";
					}
				} // -soma
				$ffAttrs['data-show-if'] = $showIf;
				$ffAttrs['class'] .= ' ' . $classes['item_show_if'];
			}
			if(strlen($requiredIf)) {
				$ffAttrs['data-required-if'] = $requiredIf; 
				$ffAttrs['class'] .= ' ' . $classes['item_required_if']; 
			}

			if($collapsed) {
				$isEmpty = $inputfield->isEmpty();
				if(($isEmpty && $inputfield instanceof InputfieldWrapper) || 
					$collapsed === Inputfield::collapsedYes ||
					$collapsed === Inputfield::collapsedLocked ||
					$collapsed === true || 
					($isEmpty && $collapsed === Inputfield::collapsedBlank) ||
					(!$isEmpty && $collapsed === Inputfield::collapsedPopulated)) {
						$ffAttrs['class'] .= ' ' . $classes['item_collapsed'];
					}
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
				//if($inputfield->label && $inputfield->skipLabel !== Inputfield::skipLabelHeader) {
				if($inputfield->label) {
					$for = $inputfield->skipLabel ? '' : $inputfield->attr('id');
					$label = $inputfield->label;
					// if $inputfield has a property of entityEncodeLabel with a value of boolean FALSE, we don't entity encode
					if($inputfield->entityEncodeLabel !== false) $label = $this->entityEncode($label);
					$icon = $inputfield->icon ? str_replace('{name}', $this->sanitizer->name(str_replace(array('icon-', 'fa-'), '', $inputfield->icon)), $markup['item_icon']) : ''; 
					if($inputfield->skipLabel === Inputfield::skipLabelHeader) {
						// label only shows when field is collapsed
						$label = str_replace('{out}', $icon . $label, $markup['item_label_hidden']); 
					} else {
						// label always visible
						$label = str_replace(array('{for}', '{out}'), array($for, $icon . $label), $markup['item_label']); 
					}
				}
				$columnWidth = (int) $inputfield->getSetting('columnWidth');
				$columnWidthAdjusted = $columnWidth + ($columnWidthTotal ? -1 * $columnWidthSpacing : 0);
				if($columnWidth >= 9 && $columnWidth <= 100) {
					$ffAttrs['class'] .= ' ' . $classes['item_column_width']; 
					if(!$columnWidthTotal) $ffAttrs['class'] .= ' ' . $classes['item_column_width_first']; 
					$ffAttrs['style'] = "width: $columnWidthAdjusted%;"; 
					$columnWidthTotal += $columnWidth;
					//if($columnWidthTotal >= 100 && !$requiredIf) $columnWidthTotal = 0; // requiredIf meant to be a showIf?
					if($columnWidthTotal >= 100) $columnWidthTotal = 0;
				} else {
					$columnWidthTotal = 0;
				}
				if(!isset($ffAttrs['id'])) $ffAttrs['id'] = 'wrap_' . $inputfield->attr('id'); 
				$ffAttrs['class'] = str_replace('Inputfield_ ', '', $ffAttrs['class']); 
				foreach($ffAttrs as $k => $v) {
					$attrs .= " $k='" . $this->entityEncode(trim($v)) . "'";
				}
				if($inputfield->className() != 'InputfieldWrapper') $ffOut = str_replace('{out}', $ffOut, $markup['item_content']); 
				$out .= str_replace(array('{attrs}', '{out}'), array(trim($attrs), $label . $ffOut), $markup['item']); 
				$lastInputfield = $inputfield;
			}
		}

		if($out) {
			$ulClass = $classes['list'];
			if($columnWidthTotal || ($lastInputfield && $lastInputfield->columnWidth >= 10 && $lastInputfield->columnWidth < 100)) $ulClass .= ' ' . $classes['list_clearfix']; 
			$attrs = "class='$ulClass'"; // . ($this->attr('class') ? ' ' . $this->attr('class') : '') . "'";
			if(!($this instanceof InputfieldForm)) foreach($this->getAttributes() as $attr => $value) if(strpos($attr, 'data-') === 0) $attrs .= " $attr='" . $this->entityEncode($value) . "'";
			$out = $this->attr('value') . str_replace(array('{attrs}', '{out}'), array($attrs, $out), $markup['list']); 
		}

		return $out; 
	}

	public function ___renderValue() {
		$this->attr('class', trim($this->attr('class') .' InputfieldRenderValueMode'));
		$this->set('renderValueMode', true); 
		$out = $this->render(); 
		$this->set('renderValueMode', false); 
		return $out; 
	}

	/**
	 * Pass the given array to all children to process input
	 *
	 * @param WireInputData $input
	 * @return $this
	 * 
	 */
	public function ___processInput(WireInputData $input) {
	
		if(!$this->children) return $this; 

		foreach($this->children as $key => $child) {

			// skip over the field if it is not processable
			if(!$this->isProcessable($child)) continue; 	

			// pass along the dependencies valeu to child wrappers
			//if($child instanceof InputfieldWrapper) $child->set('useDependencies', $this->useDependencies); 

			// call the inputfield's processInput method
			$child->processInput($input); 

			// check if a value is required and field is empty, trigger an error if so
			if($child->name && $child->getSetting('required') && $child->isEmpty()) {
				$child->error($this->requiredLabel); 
			}
		}

		return $this; 
	}

	/**
	 * Returns whether or not the given Inputfield should be processed by processInput()
	 * 
	 * When an $inputfield has a 'showIf' property, then this returns false, but it queues
	 * the field in the delayedChildren array for later processing. The root container should
	 * temporarily remove the 'showIf' property of inputfields they want processed. 
	 * 
	 * @param Inputfield $inputfield
	 * @return bool
	 * 
	 */
	protected function isProcessable(Inputfield $inputfield) {
		// skip over collapsedHidden or collapsedLocked inputfields, beacuse they are not saveable
		if($inputfield->collapsed === Inputfield::collapsedHidden) return false;
		if($inputfield->collapsed === Inputfield::collapsedLocked) return false;

		// if dependencies aren't in use, we can skip the rest
		//if(!$this->useDependencies) return true; 
		
		if(strlen($inputfield->getSetting('showIf')) || 
			($inputfield->getSetting('required') && strlen($inputfield->getSetting('requiredIf')))) {
			
			$name = $inputfield->attr('name'); 
			$this->delayedChildren[$name] = $inputfield; 
			return false;
		}
		
		return true;
	}

	public function isEmpty() {
		$empty = true; 
		foreach($this->children as $child) {
			if(!$child->isEmpty()) {
				$empty = false;
				break;
			}
		}
		return $empty;
	}

	/**
	 * Return an array of errors that occurred on any of the children during processInput()
	 *
	 * Should only be called after processInput()
	 *
	 * @param bool $clear
	 * @return array
	 *
	 */
	public function getErrors($clear = false) {
		$errors = parent::getErrors($clear); 
		foreach($this->children as $key => $child) {
			foreach($child->getErrors($clear) as $e) {
				$msg = $child->label ? $child->label : $child->attr('name'); 
				$errors[] = $msg . " - $e";
			}
		}
		return $errors;
	}

	/**
	 * Return all child Inputfields, or a blank InputfieldArray if none
	 * 	
	 * @param string $selector Optional selector string to filter the children by
 	 * @return InputfieldsArray
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
 	 * @return InputfieldsArray
	 *
	 */
	public function getChildren($selector = '') {
		return $this->children($selector); 
	}

	/**
	 * Return array of inputfields (indexed by name) of fields that had dependencies and were not processed
	 * 
	 * The results are to be handled by the root containing element (i.e. InputfieldForm).
	 *
	 * @param bool $clear Set to true in order to clear the delayed children list.
	 * @return array
	 *
	 */
	public function _getDelayedChildren($clear = false) {
		$a = $this->delayedChildren; 
		foreach($this->children as $child) {
			if(!$child instanceof InputfieldWrapper) continue; 
			$a = array_merge($a, $child->_getDelayedChildren($clear)); 
		}
		if($clear) $this->delayedChildren = array();
		return $a; 
	}

	/**
	 * Like children() but $selector is not optional, and the method name is more readable in instances where you are filtering.
	 *
	 * @param string $selector Required selector string to filter the children by
 	 * @return InputfieldsArray
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
		if(!strlen($name)) return null;
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
	 * Get all fields recursively in a flat InputfieldWrapper, not just direct children
	 *
	 * Note that all InputfieldWrappers are removed as a result (except for the containing InputfieldWrapper)
 	 *  
	 * @return InputfieldWrapper
	 *
	 */
	public function getAll() {
		$all = new InputfieldsArray();
		foreach($this->children as $child) {
			if($child instanceof InputfieldWrapper) {
				foreach($child->getAll() as $c) {
					$all->add($c); 
				}
			} else {
				$all->add($child); 
			}
		}
		return $all;
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

		$inputfields = parent::___getConfigInputfields();

		// remove all options for 'collapsed' except collapsedYes and collapsedNo
		foreach($inputfields as $f) {
			if($f->attr('name') != 'collapsed') continue; 
			foreach($f->getOptions() as $value => $label) {
				if(!in_array($value, array(Inputfield::collapsedNo, Inputfield::collapsedYes))) $f->removeOption($value); 
			}
		}

		return $inputfields;
	}

	/**
	 * Set custom markup for render, see self::$markup at top for reference.
	 *
	 * @param array $markup
	 *
	 */
	public static function setMarkup(array $markup) { self::$markup = array_merge(self::$markup, $markup); }

	/**
	 * Get custom markup for render, see self::$markup at top for reference.
	 *
	 * @return array 
	 *
	 */
	public static function getMarkup() { return array_merge(self::$defaultMarkup, self::$markup); }

	/**
	 * Set custom classes for render, see self::$classes at top for reference.
	 *
	 */
	public static function setClasses(array $classes) { self::$classes = array_merge(self::$classes, $classes); }

	/**
	 * Get custom classes for render, see self::$classes at top for reference.
	 *
	 * @return array
	 * 
	 */
	public static function getClasses() { return array_merge(self::$defaultClasses, self::$classes); }
}

