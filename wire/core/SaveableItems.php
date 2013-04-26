<?php

/**
 * ProcessWire WireSaveableItems
 *
 * Wire Data Access Object, provides reusable capability for loading, saving, creating, deleting, 
 * and finding items of descending class-defined types. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

abstract class WireSaveableItems extends Wire implements IteratorAggregate {

	/**
	 * Return the WireArray that this DAO stores it's items in
	 *
	 */
	abstract public function getAll();

	/**
	 * Return a new blank item 
	 *
	 */
	abstract public function makeBlankItem();

	/**
	 * Return the name of the table that this DAO stores item records in
	 *
	 */
	abstract public function getTable();


	/**
	 * Return the default name of the field that load() should sort by (default is none)
	 *
	 * This is overridden by selectors if applied during the load method
	 *
	 */
	public function getSort() { return ''; }

	/**
	 * Provides additions to the ___load query for when selectors or selector string are provided
	 *
	 */
	protected function getLoadQuerySelectors($selectors, DatabaseQuerySelect $query) {

		$db = $this->getFuel('db'); 

		if(is_object($selectors) && $selectors instanceof Selectors) {
			// iterable selectors
		} else if($selectors && is_string($selectors)) {
			// selector string, convert to iterable selectors
			$selectors = new Selectors($selectors); 

		} else {
			// nothing provided, load all assumed
			return $query; 
		}

		$functionFields = array(
			'sort' => '', 
			'limit' => '', 
			'start' => '',
			); 

		foreach($selectors as $selector) {

			if(!$db->isOperator($selector->operator)) 
				throw new WireException("Operator '{$selector->operator}' may not be used in {$this->className}::load()"); 

			if(in_array($selector->field, $functionFields)) {
				$functionFields[$selector->field] = $selector->value; 
				continue; 
			}

			if(!in_array($selector->field, $fields)) 
				throw new WireException("Field '{$selector->field}' is not valid for {$this->className}::load()"); 

			$selectorField = $db->escapeTableCol($selector->field); 
			$value = $db->escape_string($selector->value); 
			$query->where("{$selectorField}{$selector->operator}'$value'"); // QA
		}

		if($functionFields['sort'] && in_array($functionFields['sort'], $fields)) $query->orderby("$functionFields[sort]");
		if($functionFields['limit']) $query->limit(($functionFields['start'] ? ((int) $functionFields['start']) . "," : '') . $functionFields['limit']); 

		return $query; 

	}

	/**
	 * Get the DatabaseQuerySelect to perform the load operation of items
	 *
	 * @param WireArray $items
	 * @param Selectors|string|null $selectors Selectors or a selector string to find, or NULL to load all. 
	 * @return DatabaseQuerySelect
	 *
	 */
	protected function getLoadQuery($selectors = null) {

		$item = $this->makeBlankItem();
		$fields = array_keys($item->getTableData());
		$table = $this->fuel('db')->escapeTable($this->getTable());
		foreach($fields as $k => $v) {
			$v = $this->fuel('db')->escapeCol($v);
			$fields[$k] = "$table.$v"; 
		}

		$query = new DatabaseQuerySelect();
		$query->select($fields)->from($table);
		if($sort = $this->getSort()) $query->orderby($this->fuel('db')->escape_string($sort)); 
		$this->getLoadQuerySelectors($selectors, $query); 

		return $query; 

	}

	/**
	 * Load items from the database table and return them in the same type class that getAll() returns
	 
	 * A selector string or Selectors may be provided so that this can be used as a find() by descending classes that don't load all items at once.  
	 *
	 * @param Selectors|string|null $selectors Selectors or a selector string to find, or NULL to load all. 
	 * @return WireArray Returns the same type as specified in the getAll() method.
	 *
	 */
	protected function ___load(WireArray $items, $selectors = null) {

		$query = $this->getLoadQuery($selectors);
		$result = $this->getFuel('db')->query($query); 

		while($row = $result->fetch_assoc()) {
			$item = $this->makeBlankItem();
			foreach($row as $field => $value) {
				if($field == 'data') {
					if($value) $value = $this->decodeData($value); 
						else continue; 
				}
				$item->$field = $value; 
			}
			$item->setTrackChanges(true); 
			$items->add($item); 
		}
		$result->free();

		$items->setTrackChanges(true); 
		return $items; 
	}

	/**
	 * Should the given item key/field be saved in the database?
	 *
	 * Template method used by ___save()
	 *
	 */
	protected function saveItemKey($key) {
		if($key == 'id') return false;
		return true; 
	}

	/**
	 * Save the provided item to database
	 *
	 * @param Saveable $item The item to save
	 * @return bool Returns true on success, false on failure
	 *
	 */
	public function ___save(Saveable $item) {

		$blank = $this->makeBlankItem();
		if(!$item instanceof $blank) throw new WireException("WireSaveableItems::save(item) requires item to be of type '" . $blank->className() . "'"); 

		$db = $this->getFuel('db'); 
		$table = $db->escapeTable($this->getTable());
		$sql = "`$table` SET ";
		$id = (int) $item->id;
		$data = $item->getTableData();

		foreach($data as $key => $value) {
			if(!$this->saveItemKey($key)) continue; 
			if($key == 'data') {
				if(is_array($value)) {
					$value = $this->encodeData($value); 
				} else $value = '';
			}
			$key = $db->escapeTableCol($key);
			$value = $db->escape_string("$value"); 
			$sql .= "`$key`='$value', ";
		}

		$sql = rtrim($sql, ", "); 

		if($id) {
			$result = $db->query("UPDATE $sql WHERE id=$id"); // QA
		} else {
			$result = $db->query("INSERT INTO $sql"); // QA
			if($result) {
				$item->id = $db->insert_id; 
				$this->getAll()->add($item); 
			}
		}

		if($result) $this->resetTrackChanges();
		return $result;
	}


	/** 
	 * Delete the provided item from the database
	 *
	 * @param Saveable $item Item to save
	 * @return bool Returns true on success, false on failure
	 *
	 */
	public function ___delete(Saveable $item) {
		$blank = $this->makeBlankItem();
		if(!$item instanceof $blank) throw new WireException("WireSaveableItems::delete(item) requires item to be of type '" . $blank->className() . "'"); 
		$id = (int) $item->id; 
		$db = $this->getFuel('db'); 
		if(!$id) return false; 
		$this->getAll()->remove($item); 
		$table = $db->escapeTable($this->getTable());
		$result = $db->query("DELETE FROM `$table` WHERE id=$id LIMIT 1"); // QA
		if($result) $item->id = 0; 
		
		return $result;	
	}

	/**
	 * Create and return a cloned copy of this item
	 *
	 * If the new item uses a 'name' field, it will contain a number at the end to make it unique
	 *
	 * @param Saveable $item Item to clone
	 * @param bool|Saveable $item Returns the new clone on success, or false on failure
	 *
	 */
	public function ___clone(Saveable $item) {

		$item = clone $item;

		if(array_key_exists('name', $item->getTableData())) {
			// this item uses a 'name' field for identification, so we want to ensure it's unique
			$n = 0;
			$name = $item->name; 
			// ensure the new name is unique
			while($this->get($name)) $name = $item->name . '_' . (++$n); 
			$item->name = $name; 
		}

		// id=0 forces the save() to create a new field
		$item->id = 0;
		if($this->save($item)) return $item; 
		return false; 
	}

	/**
	 * Find items based on Selectors or selector string
	 *
	 * This is a delegation to the WireArray associated with this DAO.
	 * This method assumes that all items are loaded. Desecending classes that don't load all items should 
	 * override this to the ___load() method instead. 
	 *
	 * @param Selectors|string $selectors 
	 * @return WireArray 
	 *
	 */
	public function ___find($selectors) {
		return $this->getAll()->find($selectors); 
	}

	public function getIterator() {
		return $this->getAll();
	}

	public function get($key) {
		return $this->getAll()->get($key); 
	}

	public function __get($key) {
		$value = $this->get($key);
		if(is_null($value)) $value = parent::__get($key);
		return $value; 
	}

	public function has($item) {
		return $this->getAll()->has($item); 
	}

	public function __isset($key) {
		return $this->get($key) !== null;	
	}

	/**
	 * Encode the 'data' portion of the table.
	 * 	
	 * This is a front-end to wireEncodeJSON so that it can be overridden if needed.
	 *
	 */
	protected function encodeData(array $value) {
		return wireEncodeJSON($value); 
	}

	/**
	 * Decode the 'data' portion of the table.
	 * 	
	 * This is a front-end to wireDecodeJSON that it can be overridden if needed.
	 *
	 */
	protected function decodeData($value) {
		return wireDecodeJSON($value);
	}

	/**
	 * Enforce no locally-scoped fuel for this class
	 *
	 */
	public function useFuel($useFuel = null) {
		return false;
	}
	
}
