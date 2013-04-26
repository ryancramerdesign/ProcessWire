<?php

/**
 * ProcessWire FieldtypeComments > CommentArray
 *
 * Maintains an array of multiple Comment instances.
 * Serves as the value referenced when a FieldtypeComment field is reference from a Page.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class CommentArray extends WireArray {

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
	 * Per the WireArray interface, is the item a Comment
	 *
	 */
	public function isValidItem($item) {
		return $item instanceof Comment; 	
	}

	/**
	 * Provides the default rendering of a comment list, which may or may not be what you want
 	 *
	 * @see CommentList class and override it to serve your needs
	 *
	 */
	public function render(array $options = array()) {
		$defaultOptions = array(
			'useGravatar' => ($this->field ? $this->field->useGravatar : '')
			);
		$options = array_merge($defaultOptions, $options);
		$commentList = $this->getCommentList($options); 
		return $commentList->render();
	}

	/**
	 * Provides the default rendering of a comment form, which may or may not be what you want
 	 *
	 * @see CommentForm class and override it to serve your needs
	 *
	 */
	public function renderForm(array $options = array()) {
		$form = $this->getCommentForm($options); 
		return $form->render();
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

}


