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
		'depth' => 0, 
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
		
		if(empty($options['commentHeader'])) {
			if(empty($options['dateFormat'])) {
				$this->options['dateFormat'] = 'relative';
			}
		} else {
			//$this->options['commentHeader'] = $this->('Posted by {cite} on {created}'); // Comment header // Include the tags {cite} and {created}, but leave them untranslated
			if(empty($options['dateFormat'])) {
				$this->options['dateFormat'] = $this->_('%b %e, %Y %l:%M %p'); // Date format in either PHP strftime() or PHP date() format // Example 1 (strftime): %b %e, %Y %l:%M %p = Feb 27, 2012 1:21 PM. Example 2 (date): m/d/y g:ia = 02/27/12 1:21pm.
			}
		}
		
		$this->comments = $comments; 
		$this->options = array_merge($this->options, $options); 
	}

	/**
	 * Get replies to the given comment ID, or 0 for root level comments
	 * 
	 * @param int|Comment $commentID
	 * @return array
	 * 
	 */
	public function getReplies($commentID) {
		if(is_object($commentID)) $commentID = $commentID->id; 
		$commentID = (int) $commentID; 
		$admin = $this->options['admin'];
		$replies = array();
		foreach($this->comments as $c) {
			if($c->parent_id != $commentID) continue;
			if(!$admin && $c->status != Comment::statusApproved) continue;
			$replies[] = $c;
		}
		return $replies; 
	}

	/**
	 * Rendering of comments for API demonstration and testing purposes (or feel free to use for production if suitable)
	 *
	 * @see Comment::render()
	 * @return string or blank if no comments
	 *
	 */
	public function render() {
		$out = $this->renderList(0); 
		if($out) $out = "\n" . $this->options['headline'] . $out; 
		return $out;
	}
	
	protected function renderList($parent_id = 0, $depth = 0) {
		$out = '';
		$comments = $this->options['depth'] > 0 ? $this->getReplies($parent_id) : $this->comments;
		if(!count($comments)) return '';
		foreach($comments as $comment) $out .= $this->renderItem($comment, $depth);
		if(!$out) return '';
		$class = "CommentList";
		if($this->options['depth'] > 0) $class .= " CommentListThread";
			else $class .= " CommentListNormal";
		if($this->options['useGravatar']) $class .= " CommentListHasGravatar";
		if($parent_id) $class .= " CommentListReplies";
		$out = "<ul class='$class'>$out\n</ul><!--/CommentList-->";
		
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
	public function renderItem(Comment $comment, $depth = 0) {

		$text = $comment->getFormatted('text'); 
		$cite = $comment->getFormatted('cite'); 

		$gravatar = '';
		if($this->options['useGravatar']) {
			$imgUrl = $comment->gravatar($this->options['useGravatar'], $this->options['useGravatarImageset']); 
			if($imgUrl) $gravatar = "\n\t\t<img class='CommentGravatar' src='$imgUrl' alt='$cite' />";
		}

		$website = '';
		if($comment->website) $website = $comment->getFormatted('website'); 
		if($website) $cite = "<a href='$website' rel='nofollow' target='_blank'>$cite</a>";
		$created = wireDate($this->options['dateFormat'], $comment->created); 
		
		if(empty($this->options['commentHeader'])) {
			$header = "<span class='CommentCite'>$cite</span> <small class='CommentCreated'>$created</small>";		
		} else {
			$header = str_replace(array('{cite}', '{created}'), array($cite, $created), $this->options['commentHeader']);
		}

		$liClass = '';
		$replies = $this->options['depth'] > 0 ? $this->renderList($comment->id, $depth+1) : ''; 
		if($replies) $liClass .= ' CommentHasReplies';
		if($comment->status == Comment::statusPending) $liClass .= ' CommentStatusPending'; 
			else if($comment->status == Comment::statusSpam) $liClass .= ' CommentStatusSpam';
		
		$out = 
			"\n\t<li id='Comment{$comment->id}' class='CommentListItem$liClass'>" . $gravatar . 
			"\n\t\t<p class='CommentHeader'>$header</p>" . 
			"\n\t\t<div class='CommentText'>" . 
			"\n\t\t\t<p>$text</p>" . 
			"\n\t\t</div>";
		
		if($this->options['depth'] > 0 && $depth < $this->options['depth']) {
			$out .=
				"\n\t\t<div class='CommentFooter'>" . 
				"\n\t\t\t<p class='CommentAction'>" .
				"\n\t\t\t\t<a class='CommentActionReply' data-comment-id='$comment->id' href='#Comment{$comment->id}'>" . $this->_('Reply') . "</a>" .
				"\n\t\t\t</p>" . 
				"\n\t\t</div>";
			
			if($replies) $out .= $replies;
			
		} else {
			$out .= "\n\t\t<div class='CommentFooter'></div>";
		}
	
		$out .= "\n\t</li>";
	
		return $out; 	
	}


}

