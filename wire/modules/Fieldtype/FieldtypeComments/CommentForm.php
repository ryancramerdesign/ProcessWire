<?php

/**
 * ProcessWire CommentFormInterface and CommentForm
 *
 * Defines the CommentFormInterface and provides a base/example of this interface with the CommentForm class. 
 *
 * Use of this is optional, and it's primarily here for example purposes. 
 * You can make your own markup/output for the form directly in your own templates. 
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
 * Interface for building CommentForms, followed by an example/default implementation in the CommentForm class
 *
 */
interface CommentFormInterface {
	public function __construct(Page $page, CommentArray $comments, $options = array());
	public function render(); 
	public function processInput();
}

/**
 * Default/example implementation of the CommentFormInterface
 * 
 * Generates a user input form for comments, processes comment input, and saves to the page
 *
 * @see CommentArray::renderForm()
 *
 */
class CommentForm extends Wire implements CommentFormInterface {

	/**
	 * Page object where the comment is being submitted
	 *
	 */
	protected $page; 

	/**
	 * Instance of CommentArray, containing all Comment instances for this Page
	 *
	 */
	protected $comments;

	/**
	 * Key/values of the fields that are input by the user
	 *
	 */
	protected $inputValues = array(
		'cite' => '',
		'email' => '',
		'text' => '',
		); 

	protected $postedComment = null;

	/**
	 * Default options to modify the behavior of this class and it's output
	 *
	 */
	protected $options = array(
		'headline' => "<h3>Post Comment</h3>", 
		'successMessage' => "<p class='success'>Thank you, your submission has been saved.</p>", 
		'errorMessage' => "<p class='error'>Your submission was not saved due to one or more errors. Please check that you have completed all fields before submitting again.</p>", 
		'processInput' => true, 
		'encoding' => 'UTF-8', 
		'attrs' => array(
			'id' => 'CommentForm', 
			'action' => './', 
			'method' => 'post',
			'class' => '',
			'rows' => 5,
			'cols' => 50,
		),
		'labels' => array(
			'cite' => 'Your Name', 
			'email' => 'Your E-Mail', 
			'text' => 'Comments', 
			'submit' => 'Submit',
			),
		// the name of a field that must be set (and have any non-blank value), typically set in Javascript to keep out spammers
		// to use it, YOU must set this with a <input hidden> field from your own javascript, somewhere in the form
		'requireSecurityField' => '', 
		);

	/**
	 * Construct a CommentForm
	 *
	 * @param Page $page The page with the comments
	 * @param CommentArray $comments The field value from $page
	 * @param array $options Optional modifications to default behavior (see CommentForm::$options)
	 *
	 */
	public function __construct(Page $page, CommentArray $comments, $options = array()) {
		$this->page = $page;
		$this->comments = $comments; 
		if(isset($options['labels'])) {
			$this->options['labels'] = array_merge($this->options['labels'], $options['labels']); 
			unset($options['labels']); 
		}
		if(isset($options['attrs'])) {
			$this->options['attrs'] = array_merge($this->options['attrs'], $options['attrs']); 
			unset($options['attrs']); 
		}
		$this->options = array_merge($this->options, $options); 
	}

	public function setAttr($attr, $value) {
		$this->options['attrs'][$attr] = $value; 
	}

	public function setLabel($label, $value) {
		$this->options['labels'][$label] = $value; 
	}

	/**
	 * Replaces the output of the render() method when a Comment is posted
	 *
	 * A success message is shown rather than the form.
	 *
	 */
	protected function renderSuccess() {
		$id = $this->options['attrs']['id']; 
		$out = 	"\n<div id='$id' class='{$id}_success'>" . 
			"\n\t" . $this->options['successMessage'] . 
			"\n</div>";
		return $out; 
	}

	/**
	 * Render the CommentForm output and process the input if it's been submitted
	 *
	 * @return string
	 *
	 */
	public function render() {

		$options = $this->options; 	
		$labels = $options['labels'];
		$attrs = $options['attrs'];
		$id = $attrs['id'];
		$submitKey = $id . "_submit";
		$inputValues = array(
			'cite' => '', 
			'email' => '', 
			'text' => ''); 
		$input = $this->fuel('input'); 
		$divClass = 'new';
		$class = $attrs['class'] ? " class='$attrs[class]'" : '';
		$note = '';

		if(is_array($this->session->CommentForm)) {
			// submission data available in the session
			foreach($inputValues as $key => $value) {
				if($key == 'text') continue; 
				$inputValues[$key] = htmlentities($this->session->CommentForm->$key, ENT_QUOTES, $this->options['encoding']); 
			}
		}

		if($options['processInput'] && $input->post->$submitKey == 1) {
			if($this->processInput()) return $this->renderSuccess(); // success, return
			$inputValues = array_merge($inputValues, $this->inputValues); 
			foreach($inputValues as $key => $value) {
				$inputValues[$key] = htmlentities($value, ENT_QUOTES, $this->options['encoding']); 
			}
			$note = "\n\t$options[errorMessage]";
			$divClass = 'error';
		}

		$out = 	"\n<div id='{$id}' class='{$id}_$divClass'>" . 	
			"\n" . $this->options['headline'] . $note . 
			"\n<form id='{$id}_form'$class action='$attrs[action]' method='$attrs[method]'>" . 
			"\n\t<p class='{$id}_cite'>" . 
			"\n\t\t<label for='{$id}_cite'>$labels[cite]</label>" . 
			"\n\t\t<input type='text' name='cite' class='required' id='{$id}_cite' value='$inputValues[cite]' maxlength='128' />" . 
			"\n\t</p>" . 
			"\n\t<p class='{$id}_email'>" . 
			"\n\t\t<label for='{$id}_email'>$labels[email]</label>" . 
			"\n\t\t<input type='text' name='email' class='required email' id='{$id}_email' value='$inputValues[email]' maxlength='255' />" . 
			"\n\t</p>" . 
			"\n\t<p class='{$id}_text'>" . 
			"\n\t\t<label for='{$id}_text'>$labels[text]</label>" . 
			"\n\t\t<textarea name='text' class='required' id='{$id}_text' rows='$attrs[rows]' cols='$attrs[cols]'>$inputValues[text]</textarea>" . 
			"\n\t</p>" . 
			"\n\t<p class='{$id}_submit'>" . 
			"\n\t\t<button type='submit' name='{$id}_submit' id='{$id}_submit' value='1'>$labels[submit]</button>" . 
			"\n\t\t<input type='hidden' name='page_id' value='{$this->page->id}' />" . 
			"\n\t</p>" . 
			"\n</form>" . 
			"\n</div><!--/$id-->";

		return $out; 
	
	}

	/**
	 * Process a submitted CommentForm, insert the Comment, and save the Page
	 *
	 */
	public function processInput() {

		$data = $this->fuel('input')->post; 
		if(!count($data)) return false; 	

		if($key = $this->options['requireSecurityField']) {
			if(empty($data[$key])) return false; 
		}

		$comment = new Comment(); 
		$comment->user_agent = $_SERVER['HTTP_USER_AGENT']; 
		$comment->ip = $_SERVER['REMOTE_ADDR']; 
		$comment->created_users_id = $this->user->id; 
		$comment->sort = count($this->comments)+1; 

		$errors = array();
		$pageFieldName = '';
		$sessionData = array(); 

		foreach($this->page as $key => $value) if($value === $this->comments) $pageFieldName = $key;

		foreach(array('cite', 'email', 'text') as $key) {
			$comment->$key = $data->$key; 
			if(!$comment->$key) $errors[] = $key;
			$this->inputValues[$key] = $comment->$key;
			if($key != 'text') $sessionData[$key] = $comment->$key; 
		}
		
		if(!count($errors)) {
			if($this->comments->add($comment) && $pageFieldName) {
				$this->page->save($pageFieldName); 
				$this->postedComment = $comment; 
				return $comment; 
			}
		}

		return false;
	}

	/**
	 * Return the Comment that was posted or NULL if not yet posted
	 *
	 */
	public function getPostedComment() {
		return $this->postedComment; 
	}
}
