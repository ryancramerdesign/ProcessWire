<?php

class InputfieldPageTableAjax extends Wire {

	protected $notes = '';

	public function __construct() {
		$this->checkAjax();
	}

	protected function checkAjax() { 

		$input = $this->wire('input'); 
		$fieldName = $input->get('InputfieldPageTableField'); 
		if(!$fieldName) return;

		$processPage = $this->wire('page'); 
		if($processPage->process != 'ProcessPageEdit') return; // die('process is not ProcessPageEdit'); 			

		$field = $this->wire('fields')->get($this->wire('sanitizer')->fieldName($fieldName)); 
		if(!$field || !$field->type instanceof FieldtypePageTable) return; // die('field does not exist or is not FieldtypePageTable'); 

		$pageID = (int) $input->get('id'); 
		if(!$pageID) return; // die('page ID not specified'); 

		$page = $this->wire('pages')->get($pageID); 
		if(!$page->id) return;
		if(!$page->editable()) return;

		// check for new item that should be added
		$itemID = (int) $input->get('InputfieldPageTableAdd'); 
		if($itemID) $this->addItem($page, $field, $this->wire('pages')->get($itemID)); 

		$this->renderAjax($page, $field); 
	}

	protected function renderAjax(Page $page, Field $field) {
		$inputfield = $field->getInputfield($page); 
		if(!$inputfield) return;
		echo $inputfield->render();
		if($this->notes) {
			echo "<p class='notes'>" . $this->wire('sanitizer')->entities($this->notes) . "</p>";
			$this->notes = '';
		}
		exit; 
	}

	protected function addItem(Page $page, Field $field, Page $item) {
		// add an item and save the field
		if(!$item->id || $item->createdUser->id != $this->wire('user')->id) return false;

		$value = $page->getUnformatted($field->name); 

		if($value instanceof PageArray && !$value->has($item)) { 
			$of = $page->of();
			$page->of(false); 
			$value->add($item); 	
			$page->set($field->name, $value); 
			$page->save($field->name); 
			$this->notes = $this->_('Added item') . ' - ' . $item->name; 
			$page->of($of); 
			return true; 
		}

		return false;
	}
}
