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
	 * Reference to the Field object used by this CommentForm
	 *
	 */
	protected $commentsField; 

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
		'headline' => '',	// Post Comment
		'successMessage' => '',	// Thank you, your submission has been saved
		'pendingMessage' => '', // Your comment has been submitted and will appear once approved by the moderator.
		'errorMessage' => '',	// Your submission was not saved due to one or more errors. Try again.
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
			'cite' => '',	// Your Name
			'email' => '',	// Your E-Mail
			'website' => '',// Website
			'text' => '',	// Comments
			'submit' => '', // Submit
			),
		// the name of a field that must be set (and have any non-blank value), typically set in Javascript to keep out spammers
		// to use it, YOU must set this with a <input hidden> field from your own javascript, somewhere in the form
		'requireSecurityField' => '', 
		// should a redirect be performed immediately after a comment is successfully posted?
		'redirectAfterPost' => null, // null=unset (must be set to true to enable)
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

		// default messages
		$h3 = $this->_('h3'); // Headline tag
		$this->options['headline'] = "<$h3>" . $this->_('Post Comment') . "</$h3>"; // Form headline
		$this->options['successMessage'] = "<p class='success'>" . $this->_('Thank you, your submission has been saved.') . "</p>"; 
		$this->options['pendingMessage'] = "<p class='success pending'>" . $this->_('Your comment has been submitted and will appear once approved by the moderator.') . "</p>"; 
		$this->options['errorMessage'] = "<p class='error'>" . $this->_('Your submission was not saved due to one or more errors. Please check that you have completed all fields before submitting again.') . "</p>"; 

		// default labels
		$this->options['labels']['cite'] = $this->_('Your Name'); 
		$this->options['labels']['email'] = $this->_('Your E-Mail'); 
		$this->options['labels']['website'] = $this->_('Your Website URL'); 
		$this->options['labels']['text'] = $this->_('Comments'); 
		$this->options['labels']['submit'] = $this->_('Submit'); 

		if(isset($options['labels'])) {
			$this->options['labels'] = array_merge($this->options['labels'], $options['labels']); 
			unset($options['labels']); 
		}
		if(isset($options['attrs'])) {
			$this->options['attrs'] = array_merge($this->options['attrs'], $options['attrs']); 
			unset($options['attrs']); 
		}
		$this->options = array_merge($this->options, $options); 

		// determine which field on the page is the commentsField and save the Field instance
		foreach(wire('fields') as $field) {
			if(!$field->type instanceof FieldtypeComments) continue; 
			$value = $this->page->get($field->name); 
			if($value === $this->comments) {
				$this->commentsField = $field;
				break;
			}
		}
		// populate the vlaue of redirectAfterPost
		if($this->commentsField && is_null($this->options['redirectAfterPost'])) {
			$this->options['redirectAfterPost'] = (bool) $this->commentsField->redirectAfterPost;
		}
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

		$pageID = (int) wire('input')->post->page_id; 
		if($pageID && $this->options['redirectAfterPost']) {
			// redirectAfterPost option
			$page = wire('pages')->get($pageID); 
			if(!$page->viewable() || !$page->id) $page = wire('page');
			$url = $page->id ? $page->url : './';
			wire('session')->set('PageRenderNoCachePage', $page->id); // tell PageRender not to use cache if it exists for this page
			wire('session')->redirect($url . '?comment_success=1' . '#' . $this->options['attrs']['id']);
			return;
		}

		$message = $this->commentsField && $this->commentsField->moderate == FieldtypeComments::moderateNone ? $this->options['successMessage'] : $this->options['pendingMessage']; 

		$id = $this->options['attrs']['id']; 
		$out = 	"\n<div id='$id' class='{$id}_success'>" . 
			"\n\t" . $message . 
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

		if(!$this->commentsField) return "Unable to determine comments field";
		$options = $this->options; 	
		$labels = $options['labels'];
		$attrs = $options['attrs'];
		$id = $attrs['id'];
		$submitKey = $id . "_submit";
		$inputValues = array('cite' => '', 'email' => '', 'website' => '', 'text' => ''); 
		$user = wire('user'); 
		if($user->isLoggedin()) {
			$inputValues['cite'] = $user->name; 
			$inputValues['email'] = $user->email;
		}
		$input = $this->fuel('input'); 
		$divClass = 'new';
		$class = $attrs['class'] ? " class='$attrs[class]'" : '';
		$note = '';

		if(is_array($this->session->CommentForm)) {
			// submission data available in the session
			$sessionValues = $this->session->CommentForm;
			foreach($inputValues as $key => $value) {
				if($key == 'text') continue; 
				if(!isset($sessionValues[$key])) $sessionValues[$key] = '';
				$inputValues[$key] = htmlentities($sessionValues[$key], ENT_QUOTES, $this->options['encoding']); 
			}
			unset($sessionValues);
		}

		$showForm = true; 
		if($options['processInput'] && $input->post->$submitKey == 1) {
			if($this->processInput()) return $this->renderSuccess(); // success, return
			$inputValues = array_merge($inputValues, $this->inputValues); 
			foreach($inputValues as $key => $value) {
				$inputValues[$key] = htmlentities($value, ENT_QUOTES, $this->options['encoding']); 
			}
			$note = "\n\t$options[errorMessage]";
			$divClass = 'error';

		} else if($this->options['redirectAfterPost'] && $input->get('comment_success') == 1) {
			$note = $this->renderSuccess();
			$showForm = false;
		}

		$form = '';
		if($showForm) {
			$form = "\n<form id='{$id}_form'$class action='$attrs[action]#$id' method='$attrs[method]'>" . 
				"\n\t<p class='{$id}_cite'>" . 
				"\n\t\t<label for='{$id}_cite'>$labels[cite]</label>" . 
				"\n\t\t<input type='text' name='cite' class='required' required='required' id='{$id}_cite' value='$inputValues[cite]' maxlength='128' />" . 
				"\n\t</p>" . 
				"\n\t<p class='{$id}_email'>" . 
				"\n\t\t<label for='{$id}_email'>$labels[email]</label>" . 
				"\n\t\t<input type='text' name='email' class='required email' required='required' id='{$id}_email' value='$inputValues[email]' maxlength='255' />" . 
				"\n\t</p>";

			if($this->commentsField && $this->commentsField->useWebsite && $this->commentsField->schemaVersion > 0) {
				$form .= 
				"\n\t<p class='{$id}_website'>" . 
				"\n\t\t<label for='{$id}_website'>$labels[website]</label>" . 
				"\n\t\t<input type='text' name='website' class='website' id='{$id}_website' value='$inputValues[website]' maxlength='255' />" . 
				"\n\t</p>";
			}

			$form .="\n\t<p class='{$id}_text'>" . 
				"\n\t\t<label for='{$id}_text'>$labels[text]</label>" . 
				"\n\t\t<textarea name='text' class='required' required='required' id='{$id}_text' rows='$attrs[rows]' cols='$attrs[cols]'>$inputValues[text]</textarea>" . 
				"\n\t</p>" . 
				"\n\t<p class='{$id}_submit'>" . 
				"\n\t\t<button type='submit' name='{$id}_submit' id='{$id}_submit' value='1'>$labels[submit]</button>" . 
				"\n\t\t<input type='hidden' name='page_id' value='{$this->page->id}' />" . 
				"\n\t</p>" . 
				"\n</form>";
		}

		$out = 	"\n<div id='{$id}' class='{$id}_$divClass'>" . 	
			"\n" . $this->options['headline'] . $note . $form . 
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
		$sessionData = array(); 

		foreach(array('cite', 'email', 'website', 'text') as $key) {
			if($key == 'website' && (!$this->commentsField || !$this->commentsField->useWebsite)) continue; 
			$comment->$key = $data->$key; // Comment performs sanitization/validation
			if($key != 'website' && !$comment->$key) $errors[] = $key;
			$this->inputValues[$key] = $comment->$key;
			if($key != 'text') $sessionData[$key] = $comment->$key; 
		}

		if(!count($errors)) {
			if($this->comments->add($comment) && $this->commentsField) {
				$outputFormatting = $this->page->outputFormatting; 
				$this->page->setOutputFormatting(false);
				$this->page->save($this->commentsField->name); 
				$this->page->setOutputFormatting($outputFormatting); 
				$this->postedComment = $comment; 
				wire('session')->set('CommentForm', $sessionData);
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
