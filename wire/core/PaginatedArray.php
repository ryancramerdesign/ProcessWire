<?php

/**
 * ProcessWire Paginated WireArray
 *
 * Like WireArray, but with the additional methods and properties needed for WirePaginatable interface.
 *
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 * https://processwire.com
 *
 */

class PaginatedArray extends WireArray implements WirePaginatable {

	/**
	 * Total number of items, including those here and others that aren't, but may be here in pagination.
	 *
	 * @var int
	 *
	 */
	protected $numTotal = 0;

	/**
	 * If this WireArray is a partial representation of a larger set, this will contain the max number of items allowed to be
	 * present/loaded in the WireArray at once.
	 *
	 * May vary from count() when on the last page of a result set.
	 * As a result, paging routines should refer to their own itemsPerPage rather than count().
	 * Applicable for paginated result sets. This number is not enforced for adding items to this WireArray.
	 *
	 * @var int
	 *
	 */
	protected $numLimit = 0;

	/**
	 * If this WireArray is a partial representation of a larger set, this will contain the starting result number if previous results preceded it.
	 *
	 * @var int
	 *
	 */
	protected $numStart = 0;

	/**
	 * Set the total number of items, if more than are in the WireArray.
	 *
	 * @param int $total
	 * @return this
	 *
	 */
	public function setTotal($total) {
		$this->numTotal = (int) $total;
		return $this;
	}

	/**
	 * Get the total number of items in all paginations of the WireArray.
	 *
	 * If no limit used, this returns total number of items currently in the WireArray.
	 *
	 * @return int
	 *
	 */
	public function getTotal() {
		return $this->numTotal;
	}

	/**
	 * Set the limit that was used in pagination.
	 *
	 * @param int $numLimit
	 * @return this
	 *
	 */
	public function setLimit($numLimit) {
		$this->numLimit = (int) $numLimit; 
		return $this; 
	}

	/**
	 * Get the limit that was used in pagination.
	 *
	 * If no limit set, then return number of items currently in this WireArray.
	 *
	 * @return int
	 *
	 */
	public function getLimit() {
		return $this->numLimit; 
	}

	/**
	 * Set the starting offset that was used for pagination.
	 *
	 * @param int $numStart;
	 * @return this
	 *
	 */
	public function setStart($numStart) {
		$this->numStart = (int) $numStart; 
		return $this; 
	}

	/**
	 * Get the starting offset that was used for pagination.
	 *
	 * @return int
	 *
	 */
	public function getStart() {
		return $this->numStart; 
	}

	/**
	 * Get a property of the PageArray
	 *
	 * These map to functions form the array and are here for convenience.
	 * Properties include count, total, start, limit, last, first, keys, values,
	 * These can also be accessed by direct reference.
	 *
	 * @param string $property
	 * @return mixed
	 *
	 */
	public function getProperty($property) {
		static $properties = array(
			// property => method to map to
			'total' => 'getTotal',
			'start' => 'getStart',
			'limit' => 'getLimit',
		);
		if(!in_array($property, $properties)) return parent::getProperty($property);
		$func = $properties[$property];
		return $this->$func();
	}

	/**
	 * Return a string of "1 to 10 of 30" (items) or "1 of 10" (pages) for example
	 * 
	 * @param string $label Label to identify item type (i.e. "Items" or "Page", etc.)
	 * @param bool usePageNum Specify true to show page numbers rather than item numbers.
	 * 	Omit to use the default item numbers. 
	 * @return string
	 * 
	 */
	public function getPaginationString($label = '', $usePageNum = false) {
		
		$count = $this->count();
		$start = $this->getStart();
		$limit = $this->getLimit();
		$total = $this->getTotal();
		
		if($usePageNum) {
			
			$pageNum = $start ? ($start / $limit) + 1 : 1;
			$totalPages = ceil($total / $limit); 
			if(!$totalPages) $pageNum = 0;
			$str = sprintf($this->_('%1$s %1$d of %2$d'), $label, $pageNum, $totalPages); // Page quantity, i.e. Page 1 of 3
			
		} else {

			if($count > $limit) $count = $limit;
			$end = $start + $count;
			if($end > $total) $total = $end;
			$start++; // make 1 based rather than 0 based...
			if($end == 0) $start = 0; // ...unless there are no items
			$str = sprintf($this->_('%1$s %2$d to %3$d of %4$d'), $label, $start, $end, $total); // Pagination item quantity, i.e. Items 1 to 10 of 50
		}
		
		return trim($str); 
	}


	/**
	 * debugInfo PHP 5.6+ magic method
	 *
	 * @return array
	 *
	 */
	public function __debugInfo() {
		$info = parent::__debugInfo();
		if($this->getLimit()) $info['pager'] = $this->getPaginationString();
		$info['total'] = $this->getTotal();
		$info['start'] = $this->getStart();
		$info['limit'] = $this->getLimit();
		return $info;
	}
}