<?php

/**
 * Class MarkupFieldtype
 * 
 * Provides pre-packaged markup rendering for Fieldtypes
 * and potentially serves as a module type. This base class
 * just provides generic rendering for various differnet types,
 * accommodating just about any Fieldtype. But it is built to be
 * extended for more specific needs in various Fieldtypes. 
 * 
 * USAGE:
 * 
 * $m = new MarkupFieldtype($page, $field, $value);
 * echo $m->render();
 * 
 * // Alternate usage:
 * $m = new MarkupFieldtype();
 * $m->setPage($page);
 * $m->setField($field);
 * $m->setValue($value); 
 * echo $m->render();
 *
 * // Render just a specific property: 
 * echo $m->render('property'); 
 * 
 */

class MarkupFieldtype extends WireData implements Module {

	/**
	 * @var Page|null
	 * 
	 */
	protected $_page = null;

	/**
	 * @var Field|null
	 * 
	 */
	protected $_field = null;

	/**
	 * Unformatted value that will be used for rendering
	 * 
	 * If not set, it will be pulled from $page->get($field->name) automatically. 
	 * 
	 * @var mixed
	 * 
	 */
	protected $_value = null;

	/**
	 * True when we are unable to render and should delegate to Inputfield::renderValue instead
	 * 
	 * @var bool
	 * 
	 */
	protected $renderIsUseless = false; 

	/**
	 * Construct the MarkupFieldtype
	 * 
	 * If you construct without providing page and field, please populate them
	 * separately with the setPage and setField methods before calling render().
	 * 
	 * @param Page $page
	 * @param Field $field
	 * @param mixed $value
	 * 
	 */
	public function __construct(Page $page = null, Field $field = null, $value = null) {
		if($page) $this->setPage($page);
		if($field) $this->setField($field); 
		if(!is_null($value)) $this->setValue($value); 
	}
	
	/**
	 * Render markup for the field or for the property from field
	 * 
	 * @param string $property Optional property (for object or array field values)
	 * @return string
	 * 
	 */
	public function render($property = '') {
		
		$value = $this->getValue(); 
		
		if($property) {
			// render specific property requested
			
			if($property == 'count' && WireArray::iterable($value)) {
				return count($value); 
			}
			
			if(is_array($value) && isset($value[$property])) {
				// array 
				$value = $value[$property];

			} else if(is_object($value)) { 
				// object
				if($value instanceof WireArray) {
					// WireArray object: get array of property value from each item
					$value = $value->explode($property);
					
				} else if($value instanceof Wire) {
					// Wire object
					$value = $value->$property;
				}
			
				// make sure the property returned is a safe one
				if(!is_null($value) && parent::get($property) !== null) {
					// this is an API variable or something that we don't want to allow
					$this->warning("Disallowed property: $property", Notice::debug); 
					$value = null;
				}
				
			} else {
				// something we don't know how to retrieve a property from
				$value = null;
			}
			
			$out = $this->renderProperty($property, $value); 
			
		} else {
			// render entire value requested
			$out = $this->renderValue($value); 
		}
		
		if($this->renderIsUseless && $field = $this->getField()) {
			// if we detected that we're rendering something useless (like a list of class names)
			// then attempt to delegate to Inputfield::renderValue() instead. 
			$in = $field->getInputfield($this->getPage()); 
			if($in) $out = $in->renderValue();
		}
		
		return $out; 
	}

	/**
	 * Render the entire $page->get($field->name) value. 
	 * 
	 * Classes descending from RenderFieldtype this would implement their own method. 
	 * 
	 * @param $value The unformatted value to render. 
	 * @return string
	 * 
	 */
	protected function renderValue($value) {
		return $this->valueToString($value); 
	}
	
	/**
	 * Render the just a property from the $page->get($field->name) value.
	 *
	 * Applicable only if the value of the field is an array or object.
	 * 
	 * Classes descending from RenderFieldtype this would implement their own method.
	 *
	 * @param string $property The property name being rendered.
	 * @param mixed $value The value of the property.
	 * @return string
	 *
	 */
	protected function renderProperty($property, $value) {
		return $this->valueToString($value); 
	}

	/**
	 * Convert any value to a string
	 * 
	 * @param mixed $value
	 * @return string
	 * 
	 */	
	protected function valueToString($value) {
		if(WireArray::iterable($value)) {
			return $this->arrayToString($value);
		} else if(is_object($value)) {
			return $this->objectToString($value);
		} else {
			return $this->wire('sanitizer')->entities1($value);
		}
	}

	/**
	 * Render an unknown array or WireArray to a string
	 * 
	 * @param array|WireArray $value
	 * @return string
	 * 
	 */
	protected function arrayToString($value) {
		if(!count($value)) return '';
		$out = "<ul class='MarkupFieldtype'>";
		foreach($value as $v) {
			$out .= "<li>" . $this->valueToString($v) . "</li>";
		}
		$out .= "</ul>";
		return $out; 
	}

	/**
	 * Render an object to a string
	 * 
	 * @param Wire|object $value
	 * @return string
	 * 
	 */
	protected function objectToString($value) {
		$className = get_class($value); 
		$out = (string) $value; 
		if($out === $className) {
			// just the class name probably isn't useful here, see if we can do do something else with it
			$this->renderIsUseless = true; 
		}
		return $out; 
	}

	/**
	 * The string value of a RenderFieldtype is always the fully rendered field
	 * 
	 * @return string
	 * 
	 */
	public function __toString() {
		return $this->render();
	}
	
	public function setPage(Page $page) { $this->_page = $page;  }
	public function setField(Field $field) { $this->_field = $field;  }
	public function getPage() { return $this->_page ? $this->_page : new NullPage(); }
	public function getField() { return $this->_field; }
	public function setValue($value) { $this->_value = $value; }
	
	public function getValue() {
		$value = $this->_value;
		if(is_null($value)) {
			$value = $this->getPage()->getFormatted($this->getField()->name);
			$this->_value = $value; 
		}
		return $value;
	}

	public function get($key) {
		if($key == 'page') return $this->getPage();
		if($key == 'field') return $this->getField();
		if($key == 'value') return $this->getValue();
		return parent::get($key); 
	}

}
