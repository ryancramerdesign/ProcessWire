<?php

/**
 * ProcessWire Inputfield
 *
 * Base class for Inputfield modules. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
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
 * @property string $name HTML 'name' attribute for Inputfield (required). 
 * @property string $id HTML 'id' attribute for the Inputfield (value is set automatically). 
 * @property mixed $value HTML 'value' attribute for the Inputfield. 
 * @property string $class HTML 'class' attribute for the Inputfield, but it is better to use the addClass() method. 
 *
 * @property string $label Inputfield label 
 * @property string $description Optional description (appears under label to provide more detailed label/description).
 * @property string $notes Optional notes (appears under input area to provide additional notes).
 * @property string $icon Optional font-awesome icon name to accompany label. 
 * @property string $head Optional text that appears below label but above description (only used by some Inputfields). 
 * @property int|bool $required Set to 1 to make input required, or 0 to make not required (default). 
 * @property string $requiredIf Optional conditions under which input is required (selector string). 
 * @property string $showIf Optional conditions under which the Inputfield appears in the form (selector string). 
 * @property int $collapsed Whether the field is collapsed or visible, i.e. Inputfield::collapsedYes, Inputfield::collapsedBlank, etc., see the 'collapsed' constants in Inputfield class. 
 * @property int $columnWidth Width of column for this Inputfield 10-100 percent. 0 is assumed to be 100 (default). 
 * @property int $skipLabel Skip display of the label? See the skipLabel constants for options. 
 * @property string $wrapClass Optional class name (CSS) to apply to the HTML element wrapping the Inputfield.
 * @property string $headerClass Optional class name (CSS) to apply to the InputfieldHeader element
 * @property string $contentClass Optional class name (CSS) to apply to the InputfieldContent element
 * @property InputfieldWrapper|null $parent The parent InputfieldWrapper for this Inputfield or null if not set. 
 * @property null|Fieldtype $hasFieldtype Set to the Fieldtype using this Inputfield (by Field), when applicable, null when not.
 * @property null|bool $entityEncodeLabel Set to boolean false to specifically disable entity encoding of field header/label.
 * @property null|bool $entityEncodeText Set to boolean false to specifically disable entity encoding for other text (description, notes, etc.)
 * @property bool|null $useLanguages When multi-language support active, can be set to true to make it provide inputs for each language (where supported).
 * @property string|null $prependMarkup Optional markup to prepend to the inputfield content container. 
 * @property string|null $appendMarkup Optional markup to append to the inputfield content container. 
 * @property int $textFormat Formatting applied to description and notes, see textFormat constants. 
 * 
 * @method string render()
 * @method string renderValue()
 * @method Inputfield processInput(WireInputData $input)
 * @method InputfieldWrapper getConfigInputfields()
 * @method array getConfigArray()
 * @method array getConfigAllowContext(Field $field)
 * @method array exportConfigData(array $data)
 * @method array importConfigData(array $data)
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
	const collapsedNoLocked = 7;	// value is visible but not editable
	const collapsedYesLocked = 8;  	// value is collapsed but not editable, otherwise same as collapsedYes
	const collapsedLocked = 8; 	// for backwards compatibility
	const collapsedNever = 9; // input may not be collapsed
	const collapsedYesAjax = 10; // collapsed and dynamically loaded by ajax when opened
	const collapsedBlankAjax = 11; // collapsed when blankn and dynamically loaded by ajax when opened

	/**
	 * Constants for skipLabel setting
	 *
	 */
	const skipLabelNo = false; 	// don't skip the label at all (default)
	const skipLabelFor = true; 	// don't use a 'for' attribute with the <label>
	const skipLabelHeader = 2; 	// don't use a ui-widget-header label at all
	const skipLabelBlank = 4; 	// skip the label only when blank

	/**
	 * Formats allowed in description, notes, label
	 * 
	 */
	const textFormatNone = 2;		// no type of markdown or HTML allowed
	const textFormatBasic = 4;		// only basic/inline markdown and no HTML (default setting for Inputfields)
	const textFormatMarkdown = 8;	// full markdown support with HTML also allowed
	
	/**
	 * The total number of Inputfield instances, kept as a way of generating unique 'id' attributes
	 *
	 */
	static protected $numInstances = 0;

	/**
	 * Custom html for Inputfield output, if supported, and default overridden
	 * 
	 * In the string specify {attr} to substitute a string of all attributes, or to
	 * specify attributes individually, specify name="{name}" replacing "name" in both
	 * cases with the actual name of the attribute. 
	 * 
	 * @var string
	 * 
	private $html = '';
	 */

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
	 * Whether or not this Inputfield is editable
	 * 
	 * When false, its processInput method won't be called by InputfieldWrapper's processInput
	 * 
	 * @var bool
	 * 
	 */
	protected $editable = true;

	/**
	 * Construct the Inputfield, setting defaults for all properties
	 *
	 */
	public function __construct() {

		self::$numInstances++; 

		$this->set('label', ''); 	// primary clikable label
		$this->set('description', ''); 	// descriptive copy, below label
		$this->set('icon', ''); // optional icon name to accompany label
		$this->set('notes', ''); 	// highlighted descriptive copy, below output of input field
		$this->set('head', ''); 	// below label, above description
		$this->set('required', 0); 	// set to 1 to make value required for this field
		$this->set('requiredIf', ''); // optional conditions to make it required
		$this->set('collapsed', ''); 	// see the collapsed* constants at top of class (use blank string for unset value)
		$this->set('showIf', ''); 		// optional conditions selector
		$this->set('columnWidth', ''); 	// percent width of the field. blank or 0 = 100.
		$this->set('skipLabel', self::skipLabelNo); // See the skipLabel constants
		$this->set('wrapClass', ''); // optional class to apply to the Inputfield wrapper (contains InputfieldHeader + InputfieldContent)
		$this->set('headerClass', ''); // optional class to apply to InputfieldHeader wrapper
		$this->set('contentClass', ''); // optional class to apply to InputfieldContent wrapper
		$this->set('textFormat', self::textFormatBasic); // format applied to description and notes

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
	public static function getModuleInfo() {
		return array(
			'title' => '',
			'version' => 1, 
			'summary' => '', 
			); 
	}
	 */

	/**
	 * Per the Module interface, init() is called when the system is ready for API usage
	 *
	 */
	public function init() { 
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
		if($key == 'parent' && ($value instanceof InputfieldWrapper)) return $this->setParent($value); 
		if($key == 'collapsed') {
			if($value === true) $value = self::collapsedYes; 
			$value = (int) $value;
		}
		if(array_key_exists($key, $this->attributes)) return $this->setAttribute($key, $value); 
		if($key == 'required' && $value && !is_object($value)) $this->addClass('required'); 
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
	 * @param string $key
	 * @return mixed
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
	 * @param InputfieldWrapper $parent
	 * @return $this
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
	 * Get array of all parents
	 * 
	 * @return array
	 * 
	 */
	public function getParents() {
		$parent = $this->getParent();
		if(!$parent) return array();
		$parents = array($parent);
		foreach($parent->getParents() as $p) $parents[] = $p;
		return $parents; 
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
	 * @return $this
	 *
	 */
	public function setAttribute($key, $value) {
		
		if(is_array($key)) $keys = $key; 
			else if(strpos($key, '+')) $keys = explode('+', $key); 
			else $keys = array($key); 

		foreach($keys as $key) {

			if($key == 'name' && strlen($value)) {
				$idAttr = $this->getAttribute('id'); 
				$nameAttr = $this->getAttribute('name'); 
				if($idAttr == $this->defaultID || $idAttr == $nameAttr || $idAttr == "Inputfield_$nameAttr") {
					// formulate an ID attribute that consists of the className and name attribute
					$this->setAttribute('id', "Inputfield_$value");
				}
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
	 * Remove an attribute
	 *
	 * @param string $key
	 * @return this
	 *
	 */ 
	public function removeAttr($key) {
		unset($this->attributes[$key]); 
		return $this;
	}

	/**
	 * Remove an attribute (alias of removeAttr for syntax consistency with setAttribute)
	 *
	 * @param string $key
	 * @return this
	 *
	 */ 
	public function removeAttribute($key) {
		return $this->removeAttr($key);
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
	 * @param string|int|null $value Optional - Omit if setting an array in the key, or if getting a value. 
	 * @return $this|string|int If setting an attribute, it returns this instance. If getting an attribute, the attribute is returned. 
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
	 * Add the given classname to this inputfield
	 * 
	 * @param string $class
	 * @param string $property Optionally specify property you want to add class to (default=class)
	 * @return $this
	 * 
	 */
	public function addClass($class, $property = 'class') {
		if($property == 'contentClass') {
			$value = $this->contentClass;
		} else if($property == 'wrapClass') {
			$value = $this->wrapClass;
		} else if($property == 'headerClass') {
			$value = $this->headerClass;
		} else {
			$property = 'class';
			$value = $this->getAttribute('class');
		}
		$c = explode(' ', $value); 
		$c[] = $class;
		$value = implode(' ', $c); 
		if($property == 'class') {
			$this->attributes['class'] = $value;
		} else {
			$this->set($property, $value); 
		}
		return $this;
	}

	/**
	 * Does this inputfield have the given class?
	 * 
	 * @param $class
	 * @param string $property Optionally specify property you want to pull class from (default=class)
	 * @return bool
	 * 
	 */
	public function hasClass($class, $property = 'class') {
		if($property == 'class') {
			$value = explode(' ', $this->getAttribute('class'));
		} else {
			$value = explode(' ', $this->$property);
		}
		return in_array($class, $value); 
	}

	/**
	 * Remove the given classname from this inputfield
	 *
	 * @param string $class
	 * @param string $property
	 * @return $this
	 *
	 */
	public function removeClass($class, $property = 'class') {
		if($property == 'class') {
			$c = explode(' ', $this->getAttribute('class'));
		} else {
			$c = explode(' ', $this->$property); 
		}
		$key = array_search($class, $c);
		if($key !== false) unset($c[$key]);
		if($property == 'class') {
			$this->attributes['class'] = implode(' ', $c);
		} else {
			$this->set($property, implode(' ', $c)); 
		}
		return $this;
	}

	/**
	 * Get an XHTML ready string of all this Inputfield's attributes
	 *
	 * @param array $attributes Optional array of attributes to build the strong from. If not specified, this Inputfield's attributes will be used.
	 * @return string
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

			$str .= "$attr=\"" . htmlspecialchars($value, ENT_QUOTES, "UTF-8") . '" ';
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
	 * This is within the context of an InputfieldForm, where the rendered markup can have
	 * external CSS or JS dependencies (in Inputfield[Name].css or Inputfield[Name].js)
	 *
 	 * @return string of text or markup where applicable
	 *
	 */
	public function ___renderValue() {
		$value = $this->attr('value');
		if(is_array($value)) {
			if(!count($value)) return '';
			$out = "<ul>";
			foreach($value as $v) $out .= "<li>" . $this->wire('sanitizer')->entities($v) . "</li>";
			$out .= "</ul>";
		} else {
			$out = $this->wire('sanitizer')->entities($value); 
		}
		return $out; 
	}
	
	/**
	 * Called before render() or renderValue() method by InputfieldWrapper, before Inputfield-specific CSS/JS files added
	 * 
	 * In this case used to populate any required CSS/JS files. The return value is true if assets were just added, 
	 * and false if assets have already been added in a previous call. This distinction probably doesn't matter in 
	 * most usages, but here just in case a descending class needs to know when/if to add additional assets (i.e. 
	 * when this function returns true). 
	 * 
	 * @param Inputfield|InputfieldWrapper|null The parent Inputfield/wrapper that is rendering it or null if no parent.
	 * @param bool $renderValueMode Whether renderValueMode will be used. 
	 * @return bool 
	 *
	 */
	public function renderReady(Inputfield $parent = null, $renderValueMode = false) {
		return $this->wire('modules')->loadModuleFileAssets($this) > 0;
	}

	/**
	 * This hook was replaced by renderReady
	 * 
	 * @param $event
	 * @deprecated
	 *
	 */
	public function hookRender($event) {  }
	
	/**
	 * Process the input from the given WireInputData (usually $input->get or $input->post), load and clean the value for use in this Inputfield. 
	 *
	 * @param WireInputData $input
	 * @return $this
	 * 
	 */
	public function ___processInput(WireInputData $input) {

		if(isset($input[$this->name])) {
			$value = $input[$this->name]; 

		} else if($this instanceof InputfieldHasArrayValue) {
			$value = array();
		} else {
			$value = $input[$this->name];
		}

		$changed = false; 

		if($this instanceof InputfieldHasArrayValue && !is_array($value)) {
			$this->error("Expected an array value and did not receive it"); 
			return $this;
		}
		
		$previousValue = $this->attr('value');

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

			if($previousValue !== $values) { 
				// If it has changed, then update for the changed value
				$changed = true; 
				$this->setAttribute('value', $values); 
			}

		} else { 
			// string value provided in the input
			$this->setAttribute('value', $value); 
			$value = $this->attr('value'); 
			if("$value" !== (string) $previousValue) {
				$changed = true; 
			}
		}

		if($changed) { 
			$this->trackChange('value', $previousValue, $value); 

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
	 * Intended to be extended by each Inputfield as needed to add more config options. 
	 * 
	 * NOTE: Inputfields with a name that starts with an underscore, i.e. "_myname" are assumed to be for runtime
	 * use and are NOT stored in the database.
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function ___getConfigInputfields() {

		$conditionsText = $this->_('Conditions are expressed with a "field=value" selector containing fields and values to match. Multiple conditions should be separated by a comma.');
		$conditionsNote = $this->_('Read more about [how to use this](https://processwire.com/api/selectors/inputfield-dependencies/).'); 

		$fields = new InputfieldWrapper();

		$fieldset = $this->modules->get('InputfieldFieldset');
		$fieldset->label = $this->_('Visibility'); 
		$fieldset->attr('name', 'visibility'); 
		$fieldset->icon = 'eye';
		$field = $this->modules->get("InputfieldSelect"); 
		$field->attr('name', 'collapsed'); 
		$field->label = $this->_('Presentation'); 
		$field->description = $this->_("How should this field be displayed in the editor?");
		$field->addOption(self::collapsedNo, $this->_('Open'));
		$field->addOption(self::collapsedNever, $this->_('Open + Cannot be closed'));
		$field->addOption(self::collapsedBlank, $this->_('Open when populated + Closed when blank'));
		if($this->hasFieldtype !== false) {
			$field->addOption(self::collapsedBlankAjax, $this->_('Open when populated + Closed when blank + Load only when opened (AJAX)') . " †");
		}
		$field->addOption(self::collapsedNoLocked, $this->_('Open when populated + Closed when blank + Locked (not editable)'));
		$field->addOption(self::collapsedPopulated, $this->_('Open when blank + Closed when populated')); 
		$field->addOption(self::collapsedYes, $this->_('Closed')); 
		$field->addOption(self::collapsedYesLocked, $this->_('Closed + Locked (not editable)'));
		if($this->hasFieldtype !== false) {
			$field->addOption(self::collapsedYesAjax, $this->_('Closed + Load only when opened (AJAX)') . " †");
			$field->notes = sprintf($this->_('Options indicated with %s may not work with all input types or placements, test to ensure compatibility.'), '†');
		}
		$field->addOption(self::collapsedHidden, $this->_('Hidden (not shown in the editor)'));
		$field->attr('value', (int) $this->collapsed); 
		$fieldset->append($field); 

		$field = $this->modules->get("InputfieldText"); 
		$field->label = $this->_('Show this field only if');
		$field->description = $this->_('Enter the conditions under which the field will be shown.') . ' ' . $conditionsText; 
		$field->notes = $conditionsNote; 
		$field->icon = 'question-circle';
		$field->attr('name', 'showIf'); 
		$field->attr('value', $this->getSetting('showIf')); 
		$field->collapsed = Inputfield::collapsedBlank;
		$field->showIf = "collapsed!=" . self::collapsedHidden;
		$fieldset->append($field);
		
		$fieldset->collapsed = $this->collapsed == Inputfield::collapsedNo && !$this->getSetting('showIf') ? Inputfield::collapsedYes : Inputfield::collapsedNo;
		$fields->append($fieldset); 

		$field = $this->modules->get('InputfieldInteger'); 
		$value = (int) $this->getSetting('columnWidth'); 
		if($value < 10 || $value >= 100) $value = 100;
		$field->label = sprintf($this->_("Column Width (%d%%)"), $value);
		$field->icon = 'arrows-h';
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
			$field->icon = 'asterisk';
			$field->attr('name', 'required'); 
			$field->attr('value', 1); 
			$field->attr('checked', $this->getSetting('required') ? 'checked' : ''); 
			$field->description = $this->_("If checked, a value will be required for this field.");
			$field->collapsed = $this->getSetting('required') ? Inputfield::collapsedNo : Inputfield::collapsedYes; 
			$fields->add($field);
			
			$field = $this->modules->get('InputfieldText'); 
			$field->label = $this->_('Required only if');
			$field->icon = 'asterisk';
			$field->description = $this->_('Enter the conditions under which a value will be required for this field.') . ' ' . $conditionsText; 
			$field->notes = $conditionsNote; 
			$field->attr('name', 'requiredIf'); 
			$field->attr('value', $this->getSetting('requiredIf')); 
			$field->collapsed = $field->attr('value') ? Inputfield::collapsedNo : Inputfield::collapsedYes; 
			$field->showIf = "required>0"; 
			$fields->add($field); 
			
		}
	
		return $fields; 
	}

	/**
	 * Same as getConfigInputfields but allows for array definition instead
	 * 
	 * If both getConfigInputfields and getConfigArray are implemented, both will be used. 
	 * 
	 * See comments for InputfieldWrapper::importArray for example of array definition. 
	 * 
	 * @return array
	 * 
	 */
	public function ___getConfigArray() {
		return array(
			/* Example:
			'test' => array(
				'type' => 'text',
				'label' => 'This is a test',
				'value' => 'Test', 
			)
			*/
		);
	}

	/**
	 * Return a list of Inputfield names from getConfigInputfields() that are allowed in fieldgroup/template context
	 * 
	 * @param Field $field
	 * @return array of Inputfield names
	 * 
	 */
	public function ___getConfigAllowContext($field) {
		return array(
			'visibility', 
			'collapsed', 
			'columnWidth', 
			'required', 
			'requiredIf', 
			'showIf'
		);
	}
	
	/**
	 * Export configuration values for external consumption
	 *
	 * Use this method to externalize any config values when necessary.
	 * For example, internal IDs should be converted to GUIDs where possible.
	 * 
	 * Most Inputfields do not need to implement this.
	 * 
	 * @param array $data
	 * @return array
	 *
	 */
	public function ___exportConfigData(array $data) {
		$inputfields = $this->getConfigInputfields(); 
		if(!$inputfields || !count($inputfields)) return $data;
		foreach($inputfields->getAll() as $inputfield) {
			$value = $inputfield->isEmpty() ? '' : $inputfield->value;
			if(is_object($value)) $value = (string) $value;
			$data[$inputfield->name] = $value;
		}
		return $data;
	}

	/**
	 * Convert an array of exported data to a format that will be understood internally (opposite of exportConfigData)
	 * 
	 * Most Inputfields do not need to implement this.
	 *
	 * @param array $data
	 * @return array Data as given and modified as needed. Also included is $data[errors], an associative array
	 * 	indexed by property name containing errors that occurred during import of config data. 
	 *
	 */
	public function ___importConfigData(array $data) {
		return $data;
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
	 * @param string $text
	 * @param int $flags
	 * @return mixed
	 *
	 */
	public function error($text, $flags = 0) {
		$key = $this->getErrorSessionKey();
		$errors = $this->session->$key;			
		if(!is_array($errors)) $errors = array();
		if(!in_array($text, $errors)) {
			$errors[] = $text; 
			$this->session->set($key, $errors); 
		}
		$text .= $this->name ? " ($this->name)" : "";
		return parent::error($text, $flags); 
	}

	/**
	 * Return array of strings containing errors that occurred during processInput
	 * 
	 * Note that this is different from Wire::errors() in that it retrieves errors from the session
	 * rather than just the current run. 
	 *
	 * @param bool $clear Optionally clear the errors after getting them. Default=false.
	 * @return array
	 *
	 */
	public function getErrors($clear = false) {
		$key = $this->getErrorSessionKey();
		$errors = $this->session->get($key);
		if(!is_array($errors)) $errors = array();
		if($clear) {
			$this->session->remove($key); 
			parent::errors("clear"); 
		}
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
	 * @param string $what Name of property that changed
	 * @param mixed $old Previous value before change
	 * @param mixed $new New value
	 * @return $this
	 *
	 */
	public function trackChange($what, $old = null, $new = null) {
		if($what != 'value') return $this;
		return parent::trackChange($what, $old, $new); 
	}

	/**
	 * Entity encode a string (de-encoding if necessary and then re-encoding)
	 *
	 * Also option for markdown support when 2nd argument is true. 
	 *
	 * @param string $str
	 * @param bool|int $markdown Whether to allow any kind of formatting (according to $textFormat), or specify a textFormat constant.
	 * @return string
	 *
	 */
	public function entityEncode($str, $markdown = false) {
		
		// if already encoded, then un-encode it
		if(strpos($str, '&') !== false && preg_match('/&(#\d+|[a-z]+);/', $str)) {
			$str = html_entity_decode($str, ENT_QUOTES, "UTF-8"); 
		}

		if($markdown && $markdown !== self::textFormatNone) {
			if(is_int($markdown)) {
				$textFormat = $markdown;
			} else {
				$textFormat = $this->getSetting('textFormat');
			}
			if(!$textFormat) $textFormat = self::textFormatBasic;
			if($textFormat & self::textFormatBasic) {
				// only basic markdown allowed (default behavior)
				$str = $this->wire('sanitizer')->entitiesMarkdown($str);
			} else if($textFormat & self::textFormatMarkdown) {
				// full markdown, plus HTML is also allowed
				$str = $this->wire('sanitizer')->entitiesMarkdown($str, true);
			} else {
				// nothing allowed, text fully entity encoded regardless of $markdown request
				$str = $this->wire('sanitizer')->entities($str); 
			}
		} else {
			$str = $this->wire('sanitizer')->entities($str); 
		}

		return $str; 
	}

	/**
	 * Get or set editable state
	 * 
	 * When set to false, this Inputfield's processInput() method won't be called by InputfieldWrapper.
	 * 
	 * @param bool|null $setEditable Specify bool to set the editable state
	 * @return bool Returns the current editable state
	 * 
	 */
	public function editable($setEditable = null) {
		if(!is_null($setEditable)) $this->editable = $setEditable ? true : false;
		return $this->editable;
	}
	
	/**
	 * Set custom html render, see $this->html at top for reference.
	 *
	 * @param string $html
	 *
	public function setHTML($html) { 
		$this->html = $html;
	}
	 */

	/**
	 * Get default or custom HTML for render
	 * 
	 * If $this->html is populated, it gets returned. 
	 * If not, then this should return the default HTML for the Inputfield,
	 * where supported. 
	 * 
	 * If this returns blank, then it means either custom HTML is not supported.
	 *
	 * @param array $attr When populated with key=value, tags will be replaced. 
	 * @return array
	 *
	public function getHTML($attr = array()) { 
		if(!strlen($this->html) || empty($attr) || strpos($this->html, '{') === false) return $this->html;
		$html = $this->html;
		
		if(strpos($html, '{attr}')) {
			
			$html = str_replace('{attr}', $this->getAttributesString($attr), $html);	
			
		} else {
			
			// a version of html where the {tags} get replaced with blanks
			// used for testing if more attributes present without possibility
			// of those attributes being injected
			// $_html = $html;
			
			// extract value so that a substitution can't result in input-injected tags
			if(isset($attr['value'])) {
				$value = $attr['value'];
				unset($attr['value']); 
			} else {
				$value = null;
			}
			// populate attributes
			foreach($attr as $name => $v) {
				$tag = '{' . $name . '}';
				if(strpos($html, $tag) === false) continue; 
				$v = htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); 
				$html = str_replace($tag, $v, $html);
				//$_html = str_replace($tag, '', $html); 
			}
			// see if any non-value attributes are left
			$pos = strpos($html, '{'); 
			if($pos !== false && $pos != strpos($html, '{value}')) {
				// there are unpopulated tags that need to be removed
				preg_match_all('/\{[-_a-zA-Z0-9]+\}/', $html, $matches); 
			}
			// once all other attributes populated, we can populate {value}
			if($value !== null) {
				$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
				$html = str_replace('{value}', $value, $html);
				$_html = str_replace('{value}', '', $html);
			}
			// if ther
		}
		return $html;	
	}
	 */

}
