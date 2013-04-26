<?php

/**
 * ProcessWire Inputfield
 *
 * Base class for Inputfield modules. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * Inputfields that implement this interface always have a $value attribute that is an array
 *
 */
interface InputfieldHasArrayValue { }


/**
 * An Inputfield an actual form input field widget, and this is provided as the base class
 * for different kinds of form input widgets provided as modules. 
 *
 * The class supports a parent/child hierarchy so that a given Inputfield can contain Inputfields
 * below it. An example would be the relationship between fieldsets and fields in a form. 
 *
 * An Inputfield is typically associated with a Fieldtype module. 
 *
 * All Inputfields have the following properties at minimum: 
 *
 * Inputfield::id 
 * 	A unique string identifier for this Inputfield
 *
 * Inputfield::class
 *	Class name(s) to be rendered with the Inputfield XHTML output
 *
 * Inputfield::name
 * 	Corresponds to XHTML "name" attribute
 *
 * Inputfield::value
 *	The current value of the field. May correspond go the XHTML "value" attribute on some inputs. 
 * 
 * @var null|Fieldtype hasFieldtype Set to the Fieldtype using this Inputfield (by Field), when applicable, null when not.
 * @var null|bool entityEncodeLabel Set to boolean false to specifically disable entity encoding of field header/label.
 *
 */
abstract class Inputfield extends WireData implements Module {

	/** 
	 * Constants for the standard Inputfield 'collapsed' setting
	 *
	 */
	const collapsedNo = 0; 		// will display 'open'
	const collapsedYes = 1; 	// will display collapsed, requiring a click to open
	const collapsedBlank = 2; 	// will display collapsed only if blank
	const collapsedHidden = 4; 	// will not be rendered in the form
	const collapsedPopulated = 5; 	// will display collapsed only if populated

	/**
	 * Constants for skipLabel setting
	 *
	 */
	const skipLabelNo = false; 	// don't skip the label at all (default)
	const skipLabelFor = true; 	// don't use a 'for' attribute with the <label>
	const skipLabelHeader = 2; 	// don't use a ui-widget-header label at all
	const skipLabelBlank = 4; 	// skip the label only when blank

	/**
	 * The total number of Inputfield instances, kept as a way of generating unique 'id' attributes
	 *
	 */
	static protected $numInstances = 0; 	

	/**
	 * Attributes specified for the XHTML output, like class, rows, cols, etc. 
	 *
	 */
	protected $attributes = array(); 

	/**
	 * The parent Inputfield, if applicable
	 *
	 */
	protected $parent = null; 

	/**
	 * The default ID attribute assigned to this field
	 *
	 */
	protected $defaultID = '';

	/**
	 * Construct the Inputfield, setting defaults for all properties
	 *
	 */
	public function __construct() {

		self::$numInstances++; 

		$this->set('label', ''); 	// primary clikable label
		$this->set('description', ''); 	// descriptive copy, below label
		$this->set('notes', ''); 	// highlighted descriptive copy, below output of input field
		$this->set('head', ''); 	// below label, above description
		$this->set('required', 0); 	// set to 1 to make value required for this field
		$this->set('collapsed', ''); 	// see the collapsed* constants at top of class (use blank string for unset value)
		$this->set('columnWidth', ''); 	// percent width of the field. blank or 0 = 100.
		$this->set('skipLabel', self::skipLabelNo); // See the skipLabel constants

		// default ID attribute if no 'id' attribute set
		$this->defaultID = $this->className() . self::$numInstances; 

		$this->setAttribute('id', $this->defaultID); 
		$this->setAttribute('class', ''); 
		$this->setAttribute('name', ''); 

		$value = $this instanceof InputfieldHasArrayValue ? array() : null;
		$this->setAttribute('value', $value); 
	}

	/**
	 * Get information about this module
	 *
	 */
	public static function getModuleInfo() {
		return array(
			'title' => '',
			'version' => 001, 
			'summary' => '', 
			); 
	}

	/**
	 * Per the Module interface, init() is called when the system is ready for API usage
	 *
	 */
	public function init() { 
		$this->addHookBefore('render', $this, 'hookRender'); 
	}

	/**
	 * This hook is called when the ___render() method is called, ensuring that related styles and scripts are added. 
	 *
	 * This is hooked rather than called directly in init() to make sure that styles/scripts aren't loaded in instances where 
	 * The Inputfield is loaded, but not rendered. 
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookRender($event) {
		$class = $this->className();
		$info = $this->getModuleInfo();
		$version = (int) $info['version'];
		if(is_file($this->config->paths->$class . "$class.css")) $this->config->styles->add($this->config->urls->$class . "$class.css?v=$version"); 
		if(is_file($this->config->paths->$class . "$class.js")) $this->config->scripts->add($this->config->urls->$class . "$class.js?v=$version"); 
	}

	/**
	 * Per the Module interface, install() is called when this Inputfield is instally installed
	 *
	 */
	public function ___install() { }

	/**
	 * Per the Module interface, uninstall() is called when this Inputfield is uninstalled
	 *
	 */
	public function ___uninstall() { }

	/**
	 * Multiple instances of a given Inputfield may be needed
	 *
	 */
	public function isSingular() {
		return false; 
	}

	/**
	 * Inputfields are not loaded until requested
	 *
	 */
	public function isAutoload() {
		return false; 
	}

	/**
	 * Set the Inputfield's parent, set a new/existing attribute, or set a custom data value
	 *
	 */
	public function set($key, $value) {
		if($key == 'parent' && ($value instanceof Inputfield)) return $this->setParent($value); 
		if(array_key_exists($key, $this->attributes)) return $this->setAttribute($key, $value); 
		if($key == 'required' && $value) $this->attr('class', 'required'); 
		if($key == 'columnWidth') {
			$value = (int) $value; 
			if($value < 10 || $value > 99) $value = '';
		}
		return parent::set($key, $value); 
	}

	/**
	 * Get a field attribute, label, description, parent, fuel or setting
	 *
	 * In cases where there are potential name conflicts, you may prefer to use a more specific method
	 * like getSetting() or getAttribute().
	 *
	 * This method is also tied into __get() like all WireData classes.
	 *
	 */ 
	public function get($key) {	
		if($key == 'label' && !parent::get('label')) {
			if($this->skipLabel & self::skipLabelBlank) return '';
			return $this->attributes['name']; 
		}
		if($key == 'attributes') return $this->attributes; 
		if($key == 'parent') return $this->parent; 
		if(($value = $this->getFuel($key)) !== null) return $value; 
		if(array_key_exists($key, $this->attributes)) return $this->attributes[$key]; 
		return parent::get($key); 
	}

	/**
	 * Gets a setting or fuel from the Inputfield, while ignoring attributes and anything else
	 *
	 * To be used in cases where there is a potential name conflict, like the 'collapsed' field when in the Fields editor.
	 * Otherwise don't bother using this method. 
	 *
	 */
	public function getSetting($key) {
		return parent::get($key); 
	}

	/**
	 * Set the parent of this Inputfield
	 *
	 * @param Inputfield $parent
	 *
	 */
	public function setParent(InputfieldWrapper $parent) {
		$this->parent = $parent; 
		return $this; 
	}

	/**
	 * Get this Inputfield's parent Inputfield, or NULL if it doesn't have one
	 *
	 * @return Inputfield|NULL
	 *
	 */
	public function getParent() {
		return $this->parent; 
	}

	/**
	 * Set a Formfield attribute to accompany this Inputfield's output
	 *
	 * The key may contain multiple keys by being specified as an array, or by being a string with multiple keys separated by "+" signs
	 * i.e. setAttribute("id+name", "template"); 
	 *
	 * If the value param is an array, then it will instruct the attribute to hold multiple values. Future calls to setAttribute()
	 * will enforce the array type for that attribute. 
	 *
	 * @param string|array $key
	 * @param string|int $value
	 * @return this
	 *
	 */
	public function setAttribute($key, $value) {
		
		if(is_array($key)) $keys = $key; 
			else if(strpos($key, '+')) $keys = explode('+', $key); 
			else $keys = array($key); 

		foreach($keys as $key) {

			if($key == 'name' && $value && ($this->getAttribute('id') == $this->defaultID)) {
				// formulate an ID attribute that consists of the className and name attribute
				$this->setAttribute('id', "Inputfield_$value");
			}

			if(!array_key_exists($key, $this->attributes)) $this->attributes[$key] = '';

			if(is_array($this->attributes[$key]) && !is_array($value)) {

				// If the attribute is already established as an array, then we'll keep it as an array
				// and stack any newly added values into the array.
				// Examples would be stacking of class attributes, or stacking of value attributes for 
				// an Inputfield that carries multiple values
				
				$this->attributes[$key][] = $value; 

			} else {
				$this->attributes[$key] = $value; 
			}
		}

		return $this; 
	}

	/**
	 * Just like setAttribute except that it accepts an associatve array of values to set
	 *
	 */
	public function setAttributes(array $attributes) {
		foreach($attributes as $key => $value) $this->setAttribute($key, $value); 
		return $this; 
	}

	/**
	 * Set or get a Formfield attribute (multipurpose combination of setAttribute and getAttribute)
	 *
	 * If setting, this functions exactly the same as setAttribute(), and is just a shorter front end for it. 
	 * Alternatively, you may specify an an associative array of values to set for the $key param.
	 * If getting an attribute, then don't specify the second $value param.
	 *
	 * @param string|array $key If an array, then all keyed values in the array will be set. 
	 * @param string|int $value Optional - Omit if setting an array in the key, or if getting a value. 
	 * @param Formfield|string|int If setting an attribute, it returns this instance. If getting an attribute, the attribute is returned. 
	 *
	 */
	public function attr($key, $value = null) {
		if(is_null($value)) {
			if(is_array($key)) return $this->setAttributes($key); 
				else return $this->getAttribute($key); 
		}
		return $this->setAttribute($key, $value); 
	}

	/**
	 * Get all attributes specified for this Inputfield
	 *
	 */
	public function getAttributes() {
		$attributes = array();
		foreach($this->attributes as $key => $value) {
			$attributes[$key] = $value; 
		}
		return $attributes; 
	}

	/**
	 * Get a specified attribute for this Inputfield
	 *
	 */
	public function getAttribute($key) {
		return isset($this->attributes[$key]) ? $this->attributes[$key] : null; 
	}

	/**
	 * Get an XHTML ready string of all this Inputfield's attributes
	 *
	 * @param array $allowedAttributes Optional array of attributes to build the strong from. If not specified, this Inputfield's attributes will be used.
	 *
	 */
	public function getAttributesString(array $attributes = null) {

		$str = '';

		// if no attributes provided then use the ones for this Inputfield by default
		if(is_null($attributes)) $attributes = $this->getAttributes();

		if($this instanceof InputfieldHasArrayValue) {
			// fields that use arrays as values aren't going to be using a value attribute in this string, so skip it
			unset($attributes['value']); 

			// tell PHP to return an array by adding [] to the name attribute, i.e. "myfield[]"
			if(isset($attributes['name']) && substr($attributes['name'], -1) != ']') $attributes['name'] .= '[]';
		}

		foreach($attributes as $attr => $value) {

			// skip over empty attributes
			if(!strlen("$value") && (!$value = $this->get($attr))) continue; 

			// if an attribute has multiple values (like class), then bundle them into a string separated by spaces
			if(is_array($value)) $value = implode(' ', $value); 

			$str .= "$attr=\"" . htmlspecialchars($value, ENT_QUOTES) . '" ';
		}

		return trim($str); 
	}

	/**
	 * Return the completed output of this Inputfield, ready for insertion in an XHTML form
	 *
	 * The method as it appears here does not actually output an Inputfield. However, subclasses should render the output 
	 * for an Inputfield and then call upon this parent render method to output any children (if applicable). 
	 *
	 * This includes the output of any child Inputfields (if applicable). Children are presented as list items in an unordered list. 
	 *
	 * @return string
	 *
	 */
	abstract public function ___render();

	/**
	 * Render just the value (not input) in text/markup for presentation purposes
	 *
 	 * @return string of text or markup where applicable
	 *
	 */
	public function ___renderValue() {
		$out = htmlentities($this->attr('value'), ENT_QUOTES, "UTF-8"); 
		return $out; 
	}

	/**
	 * Process the input from the given WireInputData (usually $input->get or $input->post), load and clean the value for use in this Inputfield. 
	 *
	 * @param $this
	 * 
	 */
	public function ___processInput(WireInputData $input) {

		// if value was unset in the array, then just return
		// TODO should this set an empty value rather than leaving an existing value?
		if(!isset($input[$this->name])) return $this; 

		$value = $input[$this->name]; 
		$changed = false; 

		if($this instanceof InputfieldHasArrayValue && !is_array($value)) {
			$this->error("Expected an array value and did not receive it"); 
			return $this;
		}

		if(is_array($value)) {
			// an array value was provided in the input
			// note: only arrays one level deep are allowed
			
			if(!$this instanceof InputfieldHasArrayValue) {
				$this->error("Received an unexpected array value"); 
				return $this; 
			}

			$values = array();
			foreach($value as $v) {
				if(is_array($v)) continue; // skip over multldimensional arrays, not allowed
				if(ctype_digit("$v") && (((int) $v) <= PHP_INT_MAX)) $v = (int) "$v"; // force digit strings as integers
				$values[] = $v; 
			}

			if($this->attr('value') !== $values) { 
				// If it has changed, then update for the changed value
				$changed = true; 
				$this->setAttribute('value', $values); 
			}

		} else { 
			// string value provided in the input
			if("$value" !== (string) $this->attr('value')) {
				$changed = true; 
				$this->setAttribute('value', $value); 
			}
		}

		if($changed) { 
			$this->trackChange('value'); 

			// notify the parent of the change
			if($parent = $this->getParent()) $parent->trackChange($this->name); 
		}

		return $this; 
	}

	/**
	 * Return true if this field is empty (contains no/blank value), or false if it's not
	 *
	 * Used by the 'required' check to see if the field is populated, and descending Inputfields may 
	 * override this according to their own definition of 'empty'.
	 *
	 * @return bool
	 *
	 */
	public function isEmpty() {
		$value = $this->attr('value'); 
		if(is_array($value)) return count($value) == 0;
		if(!strlen("$value")) return true; 
		// if($value === 0) return true; 
		return false; 
	}


	/**
	 * Get any custom configuration fields for this Inputfield
	 *
	 * Intended to be extended or overriden
	 *
	 * @return FormfieldWrapper
	 *
	 */
	public function ___getConfigInputfields() {

		$fields = new InputfieldWrapper();

		$field = $this->modules->get("InputfieldSelect"); 
		$field->attr('name', 'collapsed'); 
		$field->label = $this->_("Visibility"); 
		$field->description = $this->_("How should this field be displayed in the editor?");
		$field->addOption(self::collapsedNo, $this->_('Always open (default)')); 
		$field->addOption(self::collapsedBlank, $this->_("Collapsed only when blank")); 
		$field->addOption(self::collapsedPopulated, $this->_("Collapsed only when populated")); 
		$field->addOption(self::collapsedYes, $this->_("Always collapsed, requiring a click to open")); 
		$field->addOption(self::collapsedHidden, $this->_("Hidden, not shown in the editor")); 
		$field->attr('value', (int) $this->collapsed); 
		if($this->collapsed == Inputfield::collapsedNo) $field->collapsed = Inputfield::collapsedYes;
		$fields->append($field); 

		$field = $this->modules->get('InputfieldInteger'); 
		$value = (int) $this->getSetting('columnWidth'); 
		if($value < 10 || $value >= 100) $value = 100;
		$field->label = sprintf($this->_("Column Width (%d%%)"), $value);
		$field->attr('id+name', 'columnWidth'); 
		$field->attr('type', 'text');
		$field->attr('maxlength', 4); 
		$field->attr('size', 4); 
		$field->attr('max', 100); 
		$field->attr('value', $value . '%'); 
		$field->description = $this->_("The percentage width of this field's container (10%-100%). If placed next to other fields with reduced widths, it will create floated columns."); // Description of colWidth option
		$field->notes = $this->_("Note that not all fields will work at reduced widths, so you should test the result after changing this."); // Notes for colWidth option
		if(!wire('input')->get('process_template')) if($value == 100) $field->collapsed = Inputfield::collapsedYes; 
		$fields->append($field); 

		if(!$this instanceof InputfieldWrapper) {
			$field = $this->modules->get('InputfieldCheckbox'); 
			$field->label = $this->_('Required?');
			$field->attr('name', 'required'); 
			$field->attr('value', 1); 
			$field->collapsed = $this->required ? Inputfield::collapsedNo : Inputfield::collapsedYes; 
			$field->attr('checked', $this->required ? 'checked' : ''); 
			$field->description = $this->_("If checked, a value will be required for this field.");
			$fields->append($field); 
		}

		return $fields; 
	}

	/**
	 * Returns a unique key variable used to store errors in the session
	 *
	 */
	public function getErrorSessionKey() {
		$name = $this->attr('name'); 
		if(!$name) $name = $this->attr('id'); 
		$key = "_errors_" . $this->className() . "_$name";
		return $key;
	}

	/**
	 * Override Wire's error method and place errors in the context of their inputfield
	 *
	 */
	public function error($text, $flags = 0) {
		$key = $this->getErrorSessionKey();
		$errors = $this->session->$key;			
		if(!is_array($errors)) $errors = array();
		$errors[] = $text; 
		$this->session->set($key, $errors); 
		return parent::error($text . " ({$this->name})", $flags); 
	}

	/**
	 * Return array of strings containing errors that occurred during processInput
	 *
	 * @param bool $clear Optionally clear the errors after getting them
	 *
	 */
	public function getErrors($clear = false) {
		$key = $this->getErrorSessionKey();
		$errors = $this->session->get($key);
		if(!is_array($errors)) $errors = array();
		if($clear) $this->session->remove($key); 
		return $errors; 
	}

	/**
	 * Does this Inputfield have the requested property or attribute?
	 *
	 * @param string $key
	 * @return bool
	 *
	 */
	public function has($key) {
		$has = parent::has($key); 
		if(!$has) $has = isset($this->attributes[$key]); 
		return $has; 
	}

	/**
	 * Track the change, but only if it was to the 'value' attribute.
	 *
	 * We don't track changes to any other properties of Inputfields. 
	 *
	 * @param string $key
	 * @return this
	 *
	 */
	public function trackChange($key) {
		if($key != 'value') return $this;
		return parent::trackChange($key); 
	}

	/**
	 * Entity encode a string (de-encoding if necessary and then re-encoding)
	 *
	 * Also option for basic markdown support when 2nd argument is true. 
	 *
	 * @param string $str
	 * @param bool $markdown
	 * @return string
	 *
	 */
	public function entityEncode($str, $markdown = false) {
		// if already encoded, then un-encode it
		if(strpos($str, '&') !== false && preg_match('/&(#\d+|[a-z]+);/', $str)) {
			$str = html_entity_decode($str, ENT_QUOTES, "UTF-8"); 
		}

		$str = htmlentities($str, ENT_QUOTES, "UTF-8"); 

		// convert markdown-style links to HTML
		if($markdown && strpos($str, '](')) {
			$str = preg_replace('/\[(.+?)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $str); 
		}

		return $str; 
	}


}
