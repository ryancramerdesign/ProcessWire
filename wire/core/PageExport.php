<?php

/**
 * COMING SOON: Class PageExport
 * 
 * @todo make this module use a 'guid', adding it if not there already
 * 
 */

class PageExport extends Wire {
	
	/**
	 * Export the page's data to an array that can be later imported
	 * 
	 * @param Page $page
	 * @return array
	 * 
	 */
	public function export(Page $page) {

		$of = $page->of();
		$page->of(false);
		
		// todo: make user definable guid, except for ID part		
		$guid = $this->wire('config')->httpHost . $this->wire('config')->urls->root . $page->id;

		$data = array(
			'id' => $page->id,
			'guid' => $guid,
			'parent_id' => $page->parent_id,
			'parent' => $page->parent->path,
			'templates_id' => $page->templates_id,
			'template' => $page->template->name,
			'name' => $page->name,
			'status' => $page->status,
			'sort' => $page->sort,
			'sortfield' => $page->sortfield,
			'num_children' => $page->numChildren,
			'created' => $page->created,
			'created_users_id' => $page->created_users_id,
			'created_user' => $page->createdUser->name,
			'modified' => $page->modified,
			'modified_users_id' => $page->modified_users_id,
			'modified_user' => $page->modifiedUser->name, 
			'data' => array(),
			);

		foreach($page->template->fieldgroup as $field) {
			if($field->type instanceof FieldtypeFieldsetOpen) continue;
			$data['data'][$field->name] = $this->sleepValue($page, $field, $page->get($field->name));
		}
		
		$page->of($of);
		return $data; 	
	}

	public function ___import($page, $data = null) {
		
		if(is_null($data)) {
			$data = $page;
			$page = new Page();
		}

		$page->of(false);
		$page->resetTrackChanges(true);

		if(!is_array($data)) throw new WireException("Data passed to import() must be an array $data");

		if(!$page->parent_id) {
			$parent = $this->wire('pages')->get($data['parent']);
			if(!$parent->id) throw new WireException("Unknown parent: $data[parent]");
			$page->parent = $parent;
		}

		if(!$page->templates_id) {
			$template = $this->wire('templates')->get($data['template']);
			if(!$template) throw new WireException("Unknown template: $data[template]");
			$page->template = $template;
		}

		$page->name = $data['name'];
		$page->sort = $data['sort'];
		$page->sortfield = $data['sortfield'];
		$page->status = $data['status'];
		$page->guid = $data['id'];
		
		if(!$page->id) $page->save();

		foreach($data['data'] as $name => $value) {

			$field = wire('fields')->get($name);
			
			if(!$field) {
				$this->error("Unknown field: $name"); 
				continue; 
			}

			$newStr = var_export($value, true);
			$oldStr = var_export($this->sleepValue($page, $field, $page->get($field->name)), true);
			
			if($newStr === $oldStr) continue; // value has not changed, so abort

			$value = $this->wakeupValue($page, $field, $value);
			
			$page->set($field->name, $value);
		}

		return $page;

	}

	protected function sleepValue($page, $field, $value) {
		$sleepValue = $field->type->sleepValue($page, $field, $value);
		if($field->type instanceof FieldtypePage) {
			foreach($sleepValue as $key => $id) {
				$p = $this->wire('pages')->get($id);
				$info = array('id' => $p->id, 'path' => $p->path);
				$sleepValue[$key] = $info;
			}
		}
		return $sleepValue;
	}

	protected function wakeupValue($page, $field, $value) {
		if($field->type instanceof FieldtypePage) {
			foreach($value as $key => $info) {
				// convert $value[$key] from array to page ID
				$p = $this->wire('pages')->get((int) $info['id']);
				if(!$p->id || $p->path != $info['path']) {
					// if page ID wasn't found or path doesn't match, then we try to retrieve it by path instead
					// since path may be a more reliable indicator 
					$p2 = $this->wire('pages')->get($info['path']); 
					if($p2->id) $p = $p2;
				}
				$value[$key] = $p->id;
			}
		}
		$value = $field->type->wakeupValue($page, $field, $value);
		return $value;
	}


}