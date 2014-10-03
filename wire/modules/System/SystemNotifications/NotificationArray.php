<?php

/**
 * Contains multiple Event objects for a single page
 *
 */

class NotificationArray extends WireArray {

	protected $page;

	public function __construct(Page $page) {
		$this->page = $page; 
	}

	public function isValidItem($item) {
		return $item instanceof Notification;
	}

	public function add($item) {
		$item->page = $this->page; 
		$duplicate = false;
		$itemID = $item->getID();
		foreach($this as $notification) {
			if($notification === $item) continue; 

			if($notification->getID() == $itemID) {
				$duplicate = $notification;
				break;
			}

			/*
			if(	$notification->title == $item->title && 
				$notification->flags == $item->flags && 
				$notification->text == $item->text && 
				$notification->html == $item->html &&
				$notification->from == $item->from) { 
				
				$duplicate = $notification;
				break;
			}
			*/
		}

		// don't add if it's a dupliate, just update it
		if($duplicate) {
			$item = $duplicate;
			$item->modified = time();
			$item->qty++;
		}

		return parent::add($item); 
	}

	public function __toString() {
		$out = '';
		foreach($this as $item) $out .= "\n$item"; 
		return trim($out); 
	}

	public function getNew($flag = 'message', $addNow = true) {
		$notification = new Notification();
		$notification->setFlags($flag, true); 
		$notification->created = time();
		$notification->title = 'Untitled';
		if($addNow) $this->add($notification); 
		return $notification; 
	}
}

