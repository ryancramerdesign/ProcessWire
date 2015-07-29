<?php

/**
 * ProcessWire InputfieldWrapper
 *
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * https://processwire.com
 *
 * About InputfieldWrapper
 * =======================
 * A type of Inputfield that is designed specifically to wrap other Inputfields.
 * The most common example of an InputfieldWrapper is a <form>.
 *
 * InputfieldWrapper is not designed to render an Inputfield specifically, but you can set a value attribute
 * containing content that will be rendered before the wrapper.
 *
 * @property bool $renderValueMode True when only rendering values, i.e. no inputs (default=false)
 * @property int $columnWidthSpacing Percentage spacing between columns or 0 for none. Default pulled from $config->inputfieldColumnWidthSpacing.
 * @property bool $useDependencies Whether or not to consider dependencies during processing (default=true)
 *
 */

class InputfieldWrapper extends Inputfield implements Countable, IteratorAggregate {

	/**
	 * Markup used during the render() method - customize with InputfieldWrapper::setMarkup($array)
	 *
	 */
	static protected $defaultMarkup = array(
		'list' => "\n<ul {attrs}>\n{out}\n</ul>\n",
		'item' => "\n\t<li {attrs}>\n{out}\n\t</li>", 
		'item_label' => "\n\t\t<label class='InputfieldHeader ui-widget-header{class}' for='{for}'>{out}</label>",
		'item_label_hidden' => "\n\t\t<label class='InputfieldHeader InputfieldHeaderHidden ui-widget-header{class}'><span>{out}</span></label>",
		'item_content' => "\n\t\t<div class='InputfieldContent ui-widget-content{class}'>\n{out}\n\t\t</div>", 
		'item_error' => "\n<p class='InputfieldError ui-state-error'><i class='fa fa-fw fa-flash'></i><span>{out}</span></p>",
		'item_description' => "\n<p class='description'>{out}</p>", 
		'item_head' => "\n<h2>{out}</h2>", 
		'item_notes' => "\n<p class='notes'>{out}</p>",
		'item_icon' => "<i class='fa fa-{name}'></i> ",
		'item_toggle' => "<i class='toggle-icon fa fa-angle-down' data-to='fa-angle-down fa-angle-right'></i>", 
		// ALSO: 
		// InputfieldAnything => array( any of the properties above to override on a per-Inputifeld basis)
		);

	static protected $markup = array();

	/**
	 * Classes used during the render() method - customize with InputfieldWrapper::setClasses($array)
	 *
	 */
	static protected $defaultClasses = array(
		'form' => '', // additional clases for InputfieldForm (optional)
		'list' => 'Inputfields',
		'list_clearfix' => 'ui-helper-clearfix', 
		'item' => 'Inputfield {class} Inputfield_{name} ui-widget',
		'item_label' => '', // additional classes for InputfieldHeader (optional)
		'item_content' => '',  // additional classes for InputfieldContent (optional)
		'item_required' => 'InputfieldStateRequired', // class is for Inputfield
		'item_error' => 'ui-state-error InputfieldStateError', // note: not the same as markup[item_error], class is for Inputfield
		'item_collapsed' => 'InputfieldStateCollapsed',
		'item_column_width' => 'InputfieldColumnWidth',
		'item_column_width_first' => 'InputfieldColumnWidthFirst',
		'item_show_if' => 'InputfieldStateShowIf',
		'item_required_if' => 'InputfieldStateRequiredIf'
		// ALSO: 
		// InputfieldAnything => array( any of the properties above to override on a per-Inputifeld basis)
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
		$columnWidthSpacing = $this->wire('config')->inputfieldColumnWidthSpacing; 
		$columnWidthSpacing = is_null($columnWidthSpacing) ? 1 : (int) $columnWidthSpacing; 
		$this->set('columnWidthSpacing', $columnWidthSpacing); 
		$this->set('useDependencies', true); // whether or not to use consider field dependencies during processing
		// allow optional override of any above settings with a $config->InputfieldWrapper array. 
		$settings = $this->wire('config')->InputfieldWrapper; 
		if(is_array($settings)) foreach($settings as $key => $value) $this->set($key, $value);
		$this->set('renderValueMode', false); 
	}

	/**
	 * By default, calls to get() are finding a child Inputfield based on the name attribute
	 * 
	 * @param string $key
	 * @return mixed
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
	 * Add an Inputfield child or array definition of Inputfields
	 *
	 * @param Inputfield|array $item
	 * @return $this
	 *
	 */
	public function add($item) {
		if(is_array($item)) {
			$this->importArray($item); 
		} else {
			$item->setParent($this); 
			$this->children->add($item); 
		}
		return $this; 
	}

	/**
	 * Import the given Inputfield items
	 * 
	 * If given an InputfieldWrapper, it will import the children of it and
	 * exclude the wrapper itself. This is different from add() in that add()
	 * adds the wrapper as-is. 
	 * 
	 * See also InputfieldWrapper::importArray()
	 * 
	 * @param InputfieldWrapper|array|InputfieldsArray $items
	 * @return $this
	 * @throws WireException
	 * 
	 */
	public function import($items) {
		if($items instanceof InputfieldWrapper || $items instanceof InputfieldsArray) {
			foreach($items as $item) {
				$this->add($item);
			}
		} else if(is_array($items)) {
			$this->importArray($items);
		} else if($items instanceof Inputfield) {
			$this->add($items);
		} else {
			throw new WireException("InputfieldWrapper::import() requires InputfieldWrapper, InputfieldsArray, array, or Inputfield");
		}
		return $this;
	}

	/**
	 * Prepend another Inputfield to this Inputfield's children
	 * 
	 * @param Inputfield $item
	 * @return this
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
	 * @param Inputfield $item
	 * @return this
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
	 * @param Inputfield $item Item to insert
	 * @param Inputfield $existingItem Existing item you want to insert before
	 * @return this
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
	 * @param Inputfield $item Item you want to insert
	 * @param Inputfield $existingItem Existing item you want to insert after
	 * @return this
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
	 * @param Inputfield $item
	 * @return this
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
	 * @todo this method has become too long/complex, move to its own pluggable class and split it up a lot
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
		$_markup = array_merge(self::$defaultMarkup, self::$markup);
		$_classes = array_merge(self::$defaultClasses, self::$classes);
		$useColumnWidth = true;
		if(isset($_classes['form']) && strpos($_classes['form'], 'InputfieldFormNoWidths') !== false) {
			$useColumnWidth = false;
		}
	
		// show description for tabs
		$description = $this->getSetting('description'); 
		if($description && class_exists("InputfieldFieldsetTabOpen") && $this instanceof InputfieldFieldsetTabOpen) {
			$out .= str_replace('{out}', nl2br($this->entityEncode($description, true)), $_markup['item_head']);
		}
		
		foreach($children as $inputfield) {
			
			$inputfieldClass = $inputfield->className();
			$markup = isset($_markup[$inputfieldClass]) ? array_merge($_markup, $_markup[$inputfieldClass]) : $_markup; 
			$classes = isset($_classes[$inputfieldClass]) ? array_merge($_classes, $_classes[$inputfieldClass]) : $_classes; 
			$renderValueMode = $this->renderValueMode; 

			$collapsed = (int) $inputfield->getSetting('collapsed'); 
			$required = $inputfield->getSetting('required');
			$requiredIf = $required ? $inputfield->getSetting('requiredIf') : false;
			$showIf = $inputfield->getSetting('showIf'); 
			
			if($collapsed == Inputfield::collapsedHidden) continue; 
			if($collapsed == Inputfield::collapsedNoLocked || $collapsed == Inputfield::collapsedYesLocked) $renderValueMode = true;

			$ffOut = $this->renderInputfield($inputfield, $renderValueMode); 	
			if(!strlen($ffOut)) continue; 

			$errorsOut = '';
			if(!$inputfield instanceof InputfieldWrapper) {
				$errors = $inputfield->getErrors(true);
				if(count($errors)) {
					$collapsed = $renderValueMode ? Inputfield::collapsedNoLocked : Inputfield::collapsedNo;
					$errorsOut = implode(', ', $errors);
				}
			} else $errors = array();
		
			foreach(array('error', 'description', 'head', 'notes') as $property) {
				$text = $property == 'error' ? $errorsOut : $inputfield->getSetting($property); 
				if(!empty($text)) {
					$text = nl2br($this->entityEncode($text, true));
					$text = str_replace('{out}', $text, $markup["item_$property"]);
				} else $text = '';
				$_property = '{' . $property . '}';
				if(strpos($markup['item_content'], $_property) !== false) {
					$markup['item_content'] = str_replace($_property, $text, $markup['item_content']);
				} else if(strpos($markup['item_label'], $_property) !== false) {
					$markup['item_label'] = str_replace($_property, $text, $markup['item_label']);
				} else if($text && $property == 'notes') {
					$ffOut .= $text;
				} else if($text) {
					$ffOut = $text . $ffOut;
				}
			}
			/*
			if($inputfield->getSetting('head')) {
				$text = str_replace('{out}', $this->entityEncode($inputfield->getSetting('head'), true), $markup['item_head']);
				$ffOut = $text . $ffOut; 
			}
			if($inputfield->getSetting('notes')) {
				$text = str_replace('{out}', nl2br($this->entityEncode($inputfield->notes, true)), $markup['item_notes']);
				$ffOut .= $text; 
			}
			*/

			// The inputfield's classname is always used in it's LI wrapper
			$ffAttrs = array(
				'class' => str_replace(array('{class}', '{name}'), array($inputfield->className(), $inputfield->attr('name')), $classes['item'])
				);
			if($inputfield instanceof InputfieldItemList) $ffAttrs['class'] .= " InputfieldItemList";
			if($collapsed) $ffAttrs['class'] .= " collapsed$collapsed";

			if(count($errors)) $ffAttrs['class'] .= ' ' . $classes['item_error'];
			if($required) $ffAttrs['class'] .= ' ' . $classes['item_required']; 
			if(strlen($showIf) && !$this->renderValueMode) { // note: $this->renderValueMode (rather than $renderValueMode) is intentional
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
					$collapsed === Inputfield::collapsedYesLocked ||
					$collapsed === true || 
					($isEmpty && $collapsed === Inputfield::collapsedBlank) ||
					($isEmpty && $collapsed === Inputfield::collapsedNoLocked) || // collapsedNoLocked assumed to be like a collapsedBlankLocked
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
				$label = $inputfield->getSetting('label');
				if(!strlen($label) && $inputfield->skipLabel != Inputfield::skipLabelBlank) $label = $inputfield->attr('name');
				if($label) {
					$for = $inputfield->skipLabel ? '' : $inputfield->attr('id');
					// if $inputfield has a property of entityEncodeLabel with a value of boolean FALSE, we don't entity encode
					if($inputfield->entityEncodeLabel !== false) $label = $this->entityEncode($label);
					$icon = $inputfield->icon ? str_replace('{name}', $this->sanitizer->name(str_replace(array('icon-', 'fa-'), '', $inputfield->icon)), $markup['item_icon']) : ''; 
					$toggle = $collapsed == Inputfield::collapsedNever ? '' : $markup['item_toggle']; 
					if($inputfield->skipLabel === Inputfield::skipLabelHeader) {
						// label only shows when field is collapsed
						$label = str_replace('{out}', $icon . $label . $toggle, $markup['item_label_hidden']); 
					} else {
						// label always visible
						$label = str_replace(array('{for}', '{out}'), array($for, $icon . $label . $toggle), $markup['item_label']); 
					}
					$headerClass = trim("$inputfield->headerClass $classes[item_label]");
					if($headerClass) {
						if(strpos($label, '{class}') !== false) {
							$label = str_replace('{class}', ' ' . $headerClass, $label); 
						} else {
							$label = preg_replace('/( class=[\'"][^\'"]+)/', '$1 ' . $headerClass, $label, 1);
						}
					} else if(strpos($label, '{class}') !== false) {
						$label = str_replace('{class}', '', $label); 
					}
				}
				if($useColumnWidth) {
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
				}
				if(!isset($ffAttrs['id'])) $ffAttrs['id'] = 'wrap_' . $inputfield->attr('id'); 
				$ffAttrs['class'] = str_replace('Inputfield_ ', '', $ffAttrs['class']); 
				if($inputfield->wrapClass) $ffAttrs['class'] .= " " . $inputfield->wrapClass; 
				foreach($ffAttrs as $k => $v) {
					$attrs .= " $k='" . $this->entityEncode(trim($v)) . "'";
				}
				$markupItemContent = $markup['item_content'];
				$contentClass = trim("$inputfield->contentClass $classes[item_content]");
				if($contentClass) {
					if(strpos($markupItemContent, '{class}') !== false) {
						$markupItemContent = str_replace('{class}', ' ' . $contentClass, $markupItemContent); 
					} else {
						$markupItemContent = preg_replace('/( class=[\'"][^\'"]+)/', '$1 ' . $contentClass, $markupItemContent, 1);
					}
				} else if(strpos($markupItemContent, '{class}') !== false) {
					$markupItemContent = str_replace('{class}', '', $markupItemContent); 
				}
				if($inputfield->className() != 'InputfieldWrapper') $ffOut = str_replace('{out}', $ffOut, $markupItemContent); 
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
	 * Render output for an Inputfield
	 * 
	 * @param Inputfield $inputfield The Inputfield to render
	 * @param bool $renderValueMode 
	 * @return string Rendered output
	 * 
	 */
	public function renderInputfield(Inputfield $inputfield, $renderValueMode = false) {
		$inputfield->renderReady($this, $renderValueMode);
		if(!$renderValueMode) return $inputfield->render();
	
		// renderValueMode
		$out = $inputfield->renderValue();
		if(is_null($out)) return '';
		if(!strlen($out)) $out = '&nbsp;'; // prevent output from being skipped over
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

			// pass along the dependencies value to child wrappers
			if($child instanceof InputfieldWrapper && $this->useDependencies === false) {
				$child->set('useDependencies', false); 
			}

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
		// visibility settings that aren't saveable
		static $skipTypes = array(
			Inputfield::collapsedHidden,
			Inputfield::collapsedLocked,
			Inputfield::collapsedNoLocked,
			Inputfield::collapsedYesLocked
			);
		if(in_array((int) $inputfield->getSetting('collapsed'), $skipTypes)) return false;

		// if dependencies aren't in use, we can skip the rest
		if($this->useDependencies === false) return true; 
		
		if(strlen($inputfield->getSetting('showIf')) || 
			($inputfield->getSetting('required') && strlen($inputfield->getSetting('requiredIf')))) {
			
			$name = $inputfield->attr('name'); 
			if(!$name) {
				$name = $inputfield->attr('id'); 
				if(!$name) $name = $this->wire('sanitizer')->fieldName($inputfield->label); 
				$inputfield->attr('name', $name); 
			}
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
	 * Per the Countable interface
	 *
	 */
	public function count() {
		return count($this->children);
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
		return parent::resetTrackChanges($trackChanges);
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
	public static function setMarkup(array $markup) { 
		self::$markup = array_merge(self::$markup, $markup); 
	}

	/**
	 * Get custom markup for render, see self::$markup at top for reference.
	 *
	 * @return array 
	 *
	 */
	public static function getMarkup() { 
		return array_merge(self::$defaultMarkup, self::$markup); 
	}

	/**
	 * Set custom classes for render, see self::$classes at top for reference.
	 * 
	 * @param array $classes
	 *
	 */
	public static function setClasses(array $classes) { 
		self::$classes = array_merge(self::$classes, $classes); 
	}

	/**
	 * Get custom classes for render, see self::$classes at top for reference.
	 *
	 * @return array
	 * 
	 */
	public static function getClasses() { 
		return array_merge(self::$defaultClasses, self::$classes); 
	}

	/**
	 * Import an array of Inputfield definitions to to this InputfieldWrapper instance
	 *
	 * Your array should be an array of associative arrays, with each element describing an Inputfield.
	 * It is required to have a "type" property which tells which Inputfield module to use. You are also
	 * required to have a "name" property. You should probably always have a "label" property too. You may
	 * optionally specify the shortened Inputfield "type" if preferred, i.e. "text" rather than
	 * "InputfieldText". Here is an example of how you might define the array:
	 *
	 * array(
	 *   array(
	 *     'name' => 'fullname',
	 *     'type' => 'text',
	 *     'label' => 'Field label'
	 *     'columnWidth' => 50,
	 *     'required' => true,
	 *   ),
	 *   array(
	 *     'name' => 'color',
	 *     'type' => 'select',
	 *     'label' => 'Your favorite color',
	 *     'description' => 'Select your favorite color or leave blank if you do not have one.',
	 *     'columnWidth' => 50,
	 *     'options' => array(
	 *        'red' => 'Brilliant Red',
	 *        'orange' => 'Citrus Orange',
	 *        'blue' => 'Sky Blue'
	 *     )
	 *   ),
	 *   // alternative usage: associative array where name attribute is specified as key
	 *   'my_fieldset' => array(
	 *     'type' => 'fieldset',
	 *     'label' => 'My Fieldset',
	 *     'children' => array(
	 *       'some_field' => array(
	 *         'type' => 'text',
	 *         'label' => 'Some Field',
	 *       )
	 *     )
	 * );
	 *
	 * Note: you may alternatively use associative arrays where the keys are assumed to be the 'name' attribute.
	 * See the last item 'my_fieldset' above for an example. 
	 *
	 * @param array $a Array of Inputfield definitions
	 * @param InputfieldWrapper $inputfields Specify the wrapper you want them added to, or omit to use current.
	 * @return $this
	 *
	 */
	public function importArray(array $a, InputfieldWrapper $inputfields = null) {
		
		if(is_null($inputfields)) $inputfields = $this; 
		if(!count($a)) return $inputfields;
	
		// if just a single field definition rather than an array of them, normalize to array of array
		$first = reset($a); 
		if(!is_array($first)) $a = array($a); 
		
		foreach($a as $name => $info) {

			if(isset($info['name'])) {
				$name = $info['name'];
				unset($info['name']);
			}

			if(!isset($info['type'])) {
				$this->error("Skipped field '$name' because no 'type' is set");
				continue;
			}

			$type = $info['type'];
			unset($info['type']);
			if(strpos($type, 'Inputfield') !== 0) $type = "Inputfield" . ucfirst($type);
			$f = $this->wire('modules')->get($type);

			if(!$f) {
				$this->error("Skipped field '$name' because module '$type' does not exist");
				continue;
			}
			
			$f->attr('name', $name);
			
			if($type == 'InputfieldCheckbox') {
				// checkbox behaves a little differently, just like in HTML
				if(!empty($info['attr']['value'])) {
					$f->attr('value', $info['attr']['value']);
				} else if(!empty($info['value'])) {
					$f->attr('value', $info['value']);
				}
				unset($info['attr']['value'], $info['value']);
				$f->autocheck = 1; // future value attr set triggers checked state
			}

			if(isset($info['attr']) && is_array($info['attr'])) {
				foreach($info['attr'] as $key => $value) {
					$f->attr($key, $value);
				}
				unset($a['attr']);
			}

			foreach($info as $key => $value) {
				if($key == 'children') continue;
				$f->$key = $value;
			}

			if($f instanceof InputfieldWrapper && !empty($info['children'])) {
				$this->importArray($info['children'], $f);
			}

			$inputfields->add($f);
		}

		return $inputfields;
	}

	/**
	 * Populate values for all Inputfields in this wrapper from the given $data object or array
	 * 
	 * This iterates through every field in this InputfieldWrapper and looks for field names 
	 * that are also present in the given object or array. If present, it uses them to populate
	 * the associated Inputfield. 
	 * 
	 * If given an array, it should be associative with the field 'name' as the keys and
	 * the field 'value' as the array value, i.e. array('field_name' => 'field_value', etc.)
	 * 
	 * @param WireData|Wire|ConfigurableModule|array $data
	 * @return array Returns array of field names that were populated
	 * 
	 */
	public function populateValues($data) {
		$populated = array();
		foreach($this->getAll() as $inputfield) {
			if($inputfield instanceof InputfieldWrapper) continue; 
			$name = $inputfield->attr('name');
			if(!$name) continue;
			if(is_array($data)) {
				// array
				$value = isset($data[$name]) ? $data[$name] : null;
			} else if($data instanceof WireData) {
				// WireData object
				$value = $data->data($name);
			} else if(is_object($data)) {
				// Wire or other object with __get() implemented
				$value = $data->$name;
			} 
			if($value === null) continue;
			if($inputfield instanceof InputfieldCheckbox) $inputfield->autocheck = 1; 
			$inputfield->attr('value', $value);
			$populated[$name] = $name;
		}
		return $populated;
	}
	
}

