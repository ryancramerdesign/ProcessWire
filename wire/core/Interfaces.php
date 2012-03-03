<?php

/**
 * ProcessWire Interfaces
 *
 * Interfaces used throughout ProcessWire's core.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/** 
 * For classes that are saved to a database or disk.
 *
 * Item must have a gettable/settable 'id' property for this interface as well
 *
 */
interface Saveable {

	/**
	 * Save the object's current state to database.
	 *
	 */
	public function save(); 

	/**
	 * Get an array of this item's saveable data, should match exact with the table it saves in
	 *
	 */
	public function getTableData(); 
}


interface HasRoles {
	// To be deleted
}


/**
 * For classes that contain lookup items, as used by WireSaveableItemsLookup
 *
 */
interface HasLookupItems {

	/**
	 * Get all lookup items, usually in a WireArray derived type, but specified by class
	 *
	 */
	public function getLookupItems(); 

	/**
	 * Add a lookup item to this instance
	 *
	 * @param int $item The ID of the item to add 
	 * @param array $row The row from which it was retrieved (in case you want to retrieve or modify other details)
	 *
	 */
	public function addLookupItem($item, array &$row); 
	
}


/**
 * For classes that need to track changes made by other objects. 
 *
 */
interface TrackChanges {

	/**
	 * Turn change tracking on (or off). 
	 *
	 * By default change tracking is off until turned on. 
	 *
	 */
	public function setTrackChanges($trackChanges = true); 


	/**
	 * Track a change to the name of the variable provided.	
	 *
	 * @param string $what The name of the variable that changed. 
	 *
	 */
	public function trackChange($what); 	

	
	/**
	 * Has this object changed since tracking was turned on?
	 *
	 * @return bool
	 *
	 */
	public function isChanged($what = ''); 

	/**
	 * Get array of all changes
	 *
	 * @return array
	 *
	 */
	public function getChanges();

}


/**
 * Indicates that a given Fieldtype may be used for page titles
 *
 */
interface FieldtypePageTitleCompatible { }


