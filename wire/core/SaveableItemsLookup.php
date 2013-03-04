<?php

/**
 * ProcessWire WireSaveableItemsLookup
 *
 * Provides same functionality as WireSaveableItems except that this class includes joining/modification of a related lookup table
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

abstract class WireSaveableItemsLookup extends WireSaveableItems {

	/**
	 * If a lookup table should be left joined, this method should return the table name
	 *
	 */
	abstract public function getLookupTable();

	/**
	 * If a lookup table should be left joined, this method returns the name of the array field in $data that contains multiple values
	 * 
	 * i.e. roles_permissions becomes permissions_id if getTable() returns roles
	 * Does not need to be overridden unless the table naming structure doesn't follow existing logic.
	 *
	 */
	public function getLookupField() { 
		$lookupTable = $this->getLookupTable();
		if(!$lookupTable) return ''; 
		return preg_replace('/_?' . $this->getTable() . '_?/', '', $lookupTable) . '_id';
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
		$query = parent::getLoadQuery($selectors); 
		$table = $this->fuel('db')->escapeTable($this->getTable());
		$lookupTable = $this->fuel('db')->escapeTable($this->getLookupTable());	
		$query->select("$lookupTable." . $this->fuel('db')->escapeCol($this->getLookupField())); // QA 
		$query->leftjoin("$lookupTable ON $lookupTable.{$table}_id=$table.id ")->orderby("sort"); // QA
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
		$result = $this->fuel('db')->query($query); 
		$lookupField = $this->getLookupField();

		while($row = $result->fetch_assoc()) {

			$item = $this->makeBlankItem();
			$lookupValue = $row[$lookupField]; 
			unset($row[$lookupField]); 
			$item->addLookupItem($lookupValue, $row); 

			foreach($row as $field => $value) {
				$item->$field = $value; 
			}

			if($items->has($item)) {
				// LEFT JOIN is adding more elements of the same item, i.e. from lookup table
				// if the item is already present in $items, then use the existing one rather 
				// and throw out the one we just created
				$item = $items->get($item); 
				$item->addLookupItem($lookupValue, $row); 
			} else {
				// add a new item
				$items->add($item); 
			}
		}

		if($result) $result->free();
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
		if($key == $this->getLookupField()) return false; 
		return parent::saveItemKey($key); 
	}

	/**
	 * Save the provided item to database
	 *
	 */
	public function ___save(Saveable $item) {

		if(!$item instanceof HasLookupItems) throw new WireException($this->className() . "::save() requires an item that implements HasLookupItems interface"); 
	
		$db = $this->fuel('db'); 	
		$lookupTable = $db->escapeTable($this->getLookupTable());
		$lookupField = $db->escapeCol($this->getLookupField());
		$table = $db->escapeTable($this->getTable());
		$item_id = (int) $item->id; 

		if($item_id) $db->query("DELETE FROM $lookupTable WHERE {$table}_id=$item_id"); // QA
			
		$result = parent::___save($item); 
		$item_id = (int) $item->id; // reload, in case it was 0 before

		$sort = 0; 
		if($item_id) foreach($item->getLookupItems() as $key => $value) {
			$value_id = (int) $value->id; 
			$db->query("INSERT INTO $lookupTable SET {$table}_id='$item_id', $lookupField='$value_id', sort='$sort'"); // QA
			$this->resetTrackChanges();
			$sort++; 
		}


		return $result;	
	}

	/** 
	 * Delete the provided item from the database
	 *
	 */
	public function ___delete(Saveable $item) {
		$db = $this->fuel('db');
		$lookupTable = $db->escapeTable($this->getLookupTable()); 
		$table = $db->escapeTable($this->getTable()); 
		$item_id = (int) $item->id; 
		$db->query("DELETE FROM $lookupTable WHERE {$table}_id=$item_id"); // QA
		return parent::___delete($item); 
	}
}
