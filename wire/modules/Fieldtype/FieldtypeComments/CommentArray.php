<?php

/**
 * ProcessWire FieldtypeComments > CommentArray
 *
 * Maintains an array of multiple Comment instances.
 * Serves as the value referenced when a FieldtypeComment field is reference from a Page.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 * 
 *
 */

class CommentArray extends PaginatedArray implements WirePaginatable {

	/**
	 * Page that owns these comments, required to use the renderForm() or getCommentForm() methods. 
	 *
	 */
	protected $page = null; 

	/**
	 * Field object associated with this CommentArray
	 *
	 */
	protected $field = null;

	/**
	 * Total number of comments, including those here and others that aren't, but may be here in pagination.
	 * 
	 * @var int
	 * 
	 */
	protected $numTotal = 0;

	/**
	 * If this CommentArray is a partial representation of a larger set, this will contain the max number 
	 * of comments allowed to be present/loaded in the CommentArray at once.
	 *
	 * May vary from count() when on the last page of a result set.
	 * As a result, paging routines should refer to their own itemsPerPage rather than count().
	 * Applicable for paginated result sets. This number is not enforced for adding items to this CommentArray.
	 *
	 * @var int
	 * 
	 */
	protected $numLimit = 0;

	/**
	 * If this CommentArray is a partial representation of a larger set, this will contain the starting result 
	 * number if previous results preceded it.
	 *
	 * @var int
	 * 
	 */
	protected $numStart = 0;

	/**
	 * Per the WireArray interface, is the item a Comment
	 *
	 */
	public function isValidItem($item) {
		if($item instanceof Comment) {
			if($this->page) $item->setPage($this->page); 
			if($this->field) $item->setField($this->field); 
			return true; 
		} else {
			return false;
		}
	}

	/**
	 * Provides the default rendering of a comment list, which may or may not be what you want
 	 *
	 * @param array $options
	 * @return string
	 * @see CommentList class and override it to serve your needs
	 *
	 */
	public function render(array $options = array()) {
		$defaultOptions = array(
			'useGravatar' => ($this->field ? $this->field->useGravatar : ''),
			'useVotes' => ($this->field ? $this->field->useVotes : 0), 
			'useStars' => ($this->field ? $this->field->useStars : 0), 
			'depth' => ($this->field ? (int) $this->field->depth : 0), 	
			'dateFormat' => 'relative', 
			);
		$options = array_merge($defaultOptions, $options);
		$commentList = $this->getCommentList($options); 
		return $commentList->render();
	}
	
	public function makeNew() {
		$a = parent::makeNew();
		if($this->page) $a->setPage($this->page);
		if($this->field) $a->setField($this->field);
		return $a;
	}

	/**
	 * Provides the default rendering of a comment form, which may or may not be what you want
 	 *
	 * @param array $options
	 * @return string
	 * @see CommentForm class and override it to serve your needs
	 *
	 */
	public function renderForm(array $options = array()) {
		$defaultOptions = array(
			'depth' => ($this->field ? (int) $this->field->depth : 0)
			);
		$options = array_merge($defaultOptions, $options); 
		$form = $this->getCommentForm($options); 
		return $form->render();
	}

	/**
	 * Render all comments and a comments form below it
	 * 
	 * @param array $options
	 * @return string
	 * 
	 */
	public function renderAll(array $options = array()) {
		return $this->render($options) . $this->renderForm($options); 
	}

	/**
	 * Return instance of CommentList object
	 *
	 */
	public function getCommentList(array $options = array()) {
		return new CommentList($this, $options); 	
	}

	/**
	 * Return instance of CommentForm object
	 *
	 * @param array $options
	 * @return CommentForm
	 * @throws WireException
	 * 
	 */
	public function getCommentForm(array $options = array()) {
		if(!$this->page) throw new WireException("You must set a page to this CommentArray before using it i.e. \$ca->setPage(\$page)"); 
		return new CommentForm($this->page, $this, $options); 
	}

	/**
	 * Set the page that these comments are on 
	 *
	 */ 
	public function setPage(Page $page) {
		$this->page = $page; 
	}

	/**
	 * Set the Field that these comments are on 
	 *
	 */ 
	public function setField(Field $field) {
		$this->field = $field; 
	}
	
	/**
	 * Get the page that these comments are on
	 *
	 */
	public function getPage() { 
		return $this->page; 
	}

	/**
	 * Get the Field that these comments are on
	 *
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * Get the total number of comments
	 *
	 * Used for pagination.
	 *
	 * @return int
	 *
	 */
	public function getTotal() {
		if(!$this->numTotal) return $this->count();
		return $this->numTotal;
	}

	/**
	 * Get the imposed limit on number of comments.
	 *
	 * If no limit set, then return number of comments currently here.
	 *
	 * Used for pagination.
	 *
	 * @return int
	 *
	 */
	public function getLimit() {
		if($this->numLimit) return $this->numLimit;
			else return $this->count();
	}

	/**
	 * Is the given CommentArray identical to this one?
	 *
	 * @param WireArray $items
	 * @param bool|int $strict
	 * @return bool
	 *
	 */
	public function isIdentical(WireArray $items, $strict = true) {
		$isIdentical = parent::isIdentical($items, $strict);
		if($isIdentical && $strict) {
			if(((string) $this->getPage()) != ((string) $items->getPage())) return false;
			if(((string) $this->getField()) != ((string) $items->getField())) return false;
		}
		return $isIdentical;
	}
}


