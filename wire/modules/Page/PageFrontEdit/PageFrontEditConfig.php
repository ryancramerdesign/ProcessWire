<?php namespace ProcessWire;

/**
 * Class PageFrontEditConfig
 * 
 * Handles configuration for PageFrontEdit module
 *
 */

class PageFrontEditConfig extends ModuleConfig {
	
	public function getDefaults() {
		return array(
			'inlineEditFields' => array(), // fields that are inline-editable
			'editRegionAttr' => 'edit', // attribute to use for user-defined modal editable regions
			'editRegionTag' => 'edit', // tag to use for marking user-defined modal editable regions with tags
			'demoPages' => array(),
			'buttonLocation' => 'auto',
			'buttonType' => 'auto',
		);
	}
	
	public function getInputfields() {
		$inputfields = parent::getInputfields();
		$fields = array();

		foreach($this->wire('fields') as $field) {
			if(!$field->type instanceof FieldtypeText) continue;
			$fields[$field->name] = $field;
		}

		uksort($fields, 'strnatcasecmp');

		$fieldset = $this->wire('modules')->get('InputfieldFieldset');
		$fieldset->label = $this->_('Inline editor settings');
		$fieldset->attr('id+name', 'inlineSettings');
		$inputfields->add($fieldset);

		$f = $this->wire('modules')->get('InputfieldCheckboxes');
		$f->name = 'inlineEditFields';
		$f->icon = 'cube';
		$f->label = $this->_('Which fields should be editable inline on the front-end?');
		$f->description = $this->_('These text-based fields will be become editable on the front-end, directly in the page, simply by checking the boxes below. Users must have page-edit permission to the page as well as page-edit-front permission (if they are not a superuser).');
		$f->description .= ' ' . $this->_('If you are outputting the value of a field in more than one place on a page, you should instead use the front-end editor API.');
		$f->notes = $this->_('For more fields and options, you may use the front-end API. For details, edit any field, click the "Input" tab and review the "Front-end editing" options.');
		$f->optionColumns = 3;
		foreach($fields as $field) {
			$f->addOption($field->id, $field->name);
		}
		// $f->attr('value', $this->inlineEditFields);
		$fieldset->add($f);

		if(PageFrontEdit::demo) {
			$f = $this->modules->get('InputfieldPageListSelectMultiple');
			$f->attr('name', 'demoPages');
			$f->label = $this->_('Pages to use for demo');
			$f->description = $this->_('Enables the editor functions to appear to users without edit access, but changes will not be saved.');
			//$f->attr('value', $this->demoPages);
			$f->collapsed = Inputfield::collapsedBlank;
			$fieldset->add($f);
		}

		$fieldset2 = $this->wire('modules')->get('InputfieldFieldset');
		$fieldset2->label = $this->_('Save/cancel buttons');
		$fieldset2->collapsed = Inputfield::collapsedYes;
		$fieldset->add($fieldset2);

		$f = $this->modules->get('InputfieldRadios');
		$f->name = 'buttonLocation';
		$f->label = $this->_('Buttons location');
		$f->addOption('auto', $this->_('Auto'));
		$f->addOption('nw', $this->_('Top left'));
		$f->addOption('ne', $this->_('Top right'));
		$f->addOption('sw', $this->_('Bottom left'));
		$f->addOption('se', $this->_('Bottom right'));
		//$f->attr('value', $this->buttonLocation);
		$f->columnWidth = 50;
		$fieldset2->add($f);

		$f = $this->modules->get('InputfieldRadios');
		$f->name = 'buttonType';
		$f->label = $this->_('Buttons type');
		$f->addOption('auto', $this->_('Auto (icons and text for desktop, icons for touch devices)'));
		$f->addOption('both', $this->_('Icons and text'));
		$f->addOption('text', $this->_('Text only'));
		$f->addOption('icon', $this->_('Icons only'));
		//$f->attr('value', $this->buttonType);
		$f->columnWidth = 50;
		$fieldset2->add($f);

		return $inputfields;
	}
	
	public function fieldHelpInputfields(InputfieldWrapper $fieldset, Field $field) {
		
		$module = $this->wire('modules')->get('PageFrontEdit');
		$fieldset->description = $this->_('There are a few different ways you can enable front-end editing for this field. Regardless of which option you choose, front-end editing will only appear when the user has appropriate permissions to the page and field. Required permissions are *page-edit* and *page-edit-front*.');
		$fieldset->description .= ' ' . $this->_('When a field is editable, hovering it shows a context mouse pointer rather than a regular pointer. To edit the field on the front-end, you must **double click** it.');
		$name = $field->name;
		$sanitizer = $this->wire('sanitizer');
		$labelNotSupported = $this->_('This option is not supported for this field.');
		$preStyle = " style='padding:10px;border:1px dashed #ccc'";

		$f = $this->wire('modules')->get('InputfieldMarkup');
		$f->label = $this->_('Option A: Automatic editing');
		$fieldset->add($f);
		if($module->inlineSupported($field)) {
			$f->icon = 'check-circle';
			$this->wire('modules')->get('JqueryUI')->use('modal');
			$f->description = $this->_('When the formatted value of the field is retrieved from a $page, it will be editable without you having to write any markup/code for it. This is assuming the user has permission to edit it.');
			$href = "{$this->config->urls->admin}module/edit?name=PageFrontEdit&modal=1&";
			$f->value =
				"<p><a class='pw-modal ui-button ui-state-default' data-buttons='.ui-button' data-autoclose href='$href'>" .
				"<i class='fa fa-gear fa-fw'></i>" . $this->_('Configure') . "</a></p>";
			if(in_array($field->id, $module->inlineEditFields)) {
				$f->notes = $this->_('This option is currently ENABLED for this field.'); 
			}
		} else {
			$f->icon = 'times-circle';
			$f->description = $labelNotSupported;
			$f->collapsed = Inputfield::collapsedYes;
		}

		$f = $this->wire('modules')->get('InputfieldMarkup');
		$f->label = $this->_('Option B: API method call');
		$fieldset->add($f);
		$supports = true;
		try {
			$test = $field->type->getBlankValue(new NullPage(), $field);
			if(is_object($test)) {
				$classParents = wireClassParents($test);
				if(!in_array('FieldtypeText', $classParents)) $supports = false;
			}
		} catch(\Exception $e) {
			$supports = false;
		}
		if($supports) {
			$f->icon = 'check-circle';
			$f->description = sprintf($this->_('Use $page->edit("%s"); rather than $page->%s; when you want to output the value:'), $name, $name);
			$f->value = "<pre$preStyle>&lt;?php echo \$page->edit('$name'); ?&gt;</pre>";
		} else {
			$f->icon = 'times-circle';
			$f->description = $labelNotSupported; 
			$f->collapsed = Inputfield::collapsedYes;
		}

		$note = $this->_('Any of the following syntax options are supported (choose one). The "1001" may be any page ID or path, and the "..." may be any markup or code, typically where you output your field value.');
		$f = $this->wire('modules')->get('InputfieldMarkup');
		$fieldset->add($f);
		$f->label = $this->_('Option C: Add HTML edit tags to create an editable region');
		$f->icon = 'check-circle';
		$f->description = $note;
		$f->value = "<pre$preStyle>" . $sanitizer->entities(
				"<edit $name>...</edit>\n" .
				"<edit field=\"$name\">...</edit>\n" .
				"<edit field=\"$name\" page=\"1001\">...</edit>\n" .
				"<edit field=\"1001.$name\">...</edit>"
			) . "</pre>";

		$f = $this->wire('modules')->get('InputfieldMarkup');
		$fieldset->add($f);
		$f->label = $this->_('Option D: Add HTML edit attributes to existing markup tag to create editable region');
		$f->icon = 'check-circle';
		$f->description = $this->_('The div tag shown below may be any existing HTML tag that wraps your editable region.') . ' ' . $note;
		$f->value = "<pre$preStyle>" . $sanitizer->entities(
				"<div edit=\"$name\">...</div>\n" .
				"<div edit=\"1001.$name\">...</div>"
			) . "</pre>";
		$f->notes = $this->_('Note that when using edit attributes, the modal editor is always used. All the other options will use the inline editor when supported.'); 
	}
		
}