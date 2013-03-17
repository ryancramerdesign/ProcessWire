<?php

/**
 * ProcessWire CommentListInterface and CommentList
 *
 * CommentListInterface defines an interface for CommentLists.
 * CommentList provides the default implementation of this interface. 
 *
 * Use of these is not required. These are just here to provide output for a FieldtypeComments field. 
 * Typically you would iterate through the field and generate your own output. But if you just need
 * something simple, or are testing, then this may fit your needs. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/*
 * CommentListInterface defines an interface for CommentLists.
 *
 */
interface CommentListInterface {
	public function __construct(CommentArray $comments, $options = array()); 
	public function render();
	public function renderItem(Comment $comment);
}

/**
 * CommentList provides the default implementation of the CommentListInterface interface. 
 *
 */
class CommentList extends Wire implements CommentListInterface {

	/**
	 * Reference to CommentsArray provided in constructor
	 *
	 */
	protected $comments = null;

	/**
	 * Default options that may be overridden from constructor
	 *
	 */
	protected $options = array(
		'headline' => '', 	// '<h3>Comments</h3>', 
		'commentHeader' => '', 	// 'Posted by {cite} on {created}',
		'dateFormat' => '', 	// 'm/d/y g:ia', 
		'encoding' => 'UTF-8', 
		'admin' => false, 	// shows unapproved comments if true
		'useGravatar' => '', 	// enable gravatar? if so, specify maximum rating: [ g | pg | r | x ] or blank = disable gravatar
		'useGravatarImageset' => 'mm',	// default gravatar imageset, specify: [ 404 | mm | identicon | monsterid | wavatar ]
		); 

	/**
	 * Construct the CommentList
	 *
	 * @param CommentArray $comments 
	 * @param array $options Options that may override those provided with the class (see CommentList::$options)
	 *
	 */
	public function __construct(CommentArray $comments, $options = array()) {

		$h3 = $this->_('h3'); // Headline tag
		$this->options['headline'] = "<$h3>" . $this->_('Comments') . "</$h3>"; // Header text
		$this->options['commentHeader'] = $this->_('Posted by {cite} on {created}'); // Comment header // Include the tags {cite} and {created}, but leave them untranslated
		$this->options['dateFormat'] = $this->_('%b %e, %Y %l:%M %p'); // Date format in either PHP strftime() or PHP date() format // Example 1 (strftime): %b %e, %Y %l:%M %p = Feb 27, 2012 1:21 PM. Example 2 (date): m/d/y g:ia = 02/27/12 1:21pm.
		
		$this->comments = $comments; 
		$this->options = array_merge($this->options, $options); 
	}

	/**
	 * Rendering of comments for API demonstration and testing purposes (or feel free to use for production if suitable)
	 *
	 * @see Comment::render()
	 * @return string or blank if no comments
	 *
	 */
	public function render() {

		$out = '';

		foreach($this->comments as $comment) {
			if(!$this->options['admin']) if($comment->status != Comment::statusApproved) continue; 
			$out .= $this->renderItem($comment); 
		}

		if($out) $out = 
			"\n" . $this->options['headline'] . 
			"\n<ul class='CommentList'>$out\n</ul><!--/CommentList-->";

		return $out;
	}

	/**
	 * Render the comment
	 *
	 * This is the default rendering for development/testing/demonstration purposes
	 *
	 * It may be used for production, but only if it meets your needs already. Typically you'll want to render the comments
	 * using your own code in your templates. 
	 *
	 * @see CommentArray::render()
	 * @return string 
	 *
	 */
	public function renderItem(Comment $comment) {

		$text = htmlentities(trim($comment->text), ENT_QUOTES, $this->options['encoding']);
		$text = str_replace("\n\n", "</p><p>", $text); 
		$text = str_replace("\n", "<br />", $text); 

		$cite = htmlentities(trim($comment->cite), ENT_QUOTES, $this->options['encoding']); 

		$gravatar = '';
		if($this->options['useGravatar']) {
			$imgUrl = $comment->gravatar($this->options['useGravatar'], $this->options['useGravatarImageset']); 
			if($imgUrl) $gravatar = "\n\t\t<img class='CommentGravatar' src='$imgUrl' alt='$cite' />";
		}

		$website = '';
		if($comment->website) $website = htmlentities(trim($comment->website), ENT_QUOTES, $this->options['encoding']); 
		if($website) $cite = "<a href='$website' rel='nofollow' target='_blank'>$cite</a>";

		if(strpos($this->options['dateFormat'], '%') !== false) $created = strftime($this->options['dateFormat'], $comment->created); 
			else $created = date($this->options['dateFormat'], $comment->created); 

		$header = str_replace(array('{cite}', '{created}'), array($cite, $created), $this->options['commentHeader']); 

		if($comment->status == Comment::statusPending) $liClass = ' CommentStatusPending'; 
			else if($comment->status == Comment::statusSpam) $liClass = ' CommentStatusSpam';
			else $liClass = '';

		$out = 	"\n\t<li id='Comment{$comment->id}' class='CommentListItem$liClass'>" . $gravatar . 
			"\n\t\t<p class='CommentHeader'>$header</p>" . 
			"\n\t\t<div class='CommentText'>" . 
			"\n\t\t\t<p>$text</p>" . 
			"\n\t\t</div>" . 
			"\n\t</li>";
	
		return $out; 	
	}


}

