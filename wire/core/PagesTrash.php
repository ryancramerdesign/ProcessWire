<?php namespace ProcessWire;

/**
 * ProcessWire Pages Trash
 *
 * Implements page trash/restore/empty methods of the $pages API variable
 *
 * ProcessWire 3.x (development), Copyright 2016 by Ryan Cramer
 * https://processwire.com
 *
 */

class PagesTrash extends Wire {

	/**
	 * @var Pages
	 * 
	 */
	protected $pages;
	
	public function __construct(Pages $pages) {
		$this->pages = $pages;
	}
	
	/**
	 * Move a page to the trash
	 *
	 * If you have already set the parent to somewhere in the trash, then this method won't attempt to set it again.
	 *
	 * @param Page $page
	 * @param bool $save Set to false if you will perform the save() call, as is the case when called from the Pages::save() method.
	 * @return bool
	 * @throws WireException
	 *
	 */
	public function trash(Page $page, $save = true) {
		if(!$this->pages->isDeleteable($page) || $page->template->noTrash) {
			throw new WireException("This page may not be placed in the trash");
		}
		if(!$trash = $this->pages->get($this->config->trashPageID)) {
			throw new WireException("Unable to load trash page defined by config::trashPageID");
		}
		$page->addStatus(Page::statusTrash);
		if(!$page->parent->isTrash()) {
			$parentPrevious = $page->parent;
			$page->parent = $trash;
		} else if($page->parentPrevious && $page->parentPrevious->id != $page->parent->id) {
			$parentPrevious = $page->parentPrevious;
		} else {
			$parentPrevious = null;
		}
		if(!preg_match('/^' . $page->id . '(\.\d+\.\d+)?_.+/', $page->name)) {
			// make the name unique when in trash, to avoid namespace collision and maintain parent restore info
			$name = $page->id;
			if($parentPrevious && $parentPrevious->id) {
				$name .= "." . $parentPrevious->id;
				$name .= "." . $page->sort;
			}
			$name .= "_" . $page->name;
			$page->name = $name;
		}
		if($save) $this->pages->save($page);
		$this->pages->editor()->savePageStatus($page->id, Page::statusTrash, true, false);
		$this->pages->trashed($page);
		$this->pages->debugLog('trash', $page, true);
		return true;
	}

	/**
	 * Restore a page from the trash back to a non-trash state
	 *
	 * Note that this method assumes already have set a new parent, but have not yet saved.
	 * If you do not set a new parent, then it will restore to the original parent, when possible.
	 *
	 * @param Page $page
	 * @param bool $save Set to false if you only want to prep the page for restore (i.e. being saved elsewhere)
	 * @return bool
	 *
	 */
	public function restore(Page $page, $save = true) {

		if(preg_match('/^(' . $page->id . ')((?:\.\d+\.\d+)?)_(.+)$/', $page->name, $matches)) {

			if($matches[2]) {
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($unused, $parentID, $sort) = explode('.', $matches[2]);
				$parentID = (int) $parentID;
				$sort = (int) $sort;
			} else {
				$parentID = 0;
				$sort = 0;
			}
			$name = $matches[3];

			if($parentID && $page->parent->isTrash() && !$page->parentPrevious) {
				// no new parent was defined, so use the one in the page name
				$newParent = $this->pages->get($parentID);
				if($newParent->id && $newParent->id != $page->id) {
					$page->parent = $newParent;
					$page->sort = $sort;
				}
			}
			if(!count($page->parent->children("name=$name, include=all"))) {
				$page->name = $name;  // remove namespace collision info if no collision
			}
		}

		if(!$page->parent->isTrash()) {
			$page->removeStatus(Page::statusTrash);
			if($save) $page->save();
			$this->pages->editor()->savePageStatus($page->id, Page::statusTrash, true, true);
			$this->pages->restored($page);
			$this->pages->debugLog('restore', $page, true);
		} else {
			if($save) $page->save();
		}

		return true;
	}

	/**
	 * Delete all pages in the trash
	 *
	 * Populates error notices when there are errors deleting specific pages.
	 *
	 * @return int Returns total number of pages deleted from trash.
	 * 	This number is negative or 0 if not all pages could be deleted and error notices may be present.
	 *
	 */
	public function emptyTrash() {

		$trashPage = $this->pages->get($this->wire('config')->trashPageID);
		$selector = "include=all, has_parent=$trashPage, children.count=0, status=" . Page::statusTrash;
		$totalDeleted = 0;
		$lastTotalInTrash = 0;
		$numBatches = 0;

		do {
			set_time_limit(60 * 10);
			$totalInTrash = $this->pages->count($selector);
			if(!$totalInTrash || $totalInTrash == $lastTotalInTrash) break;
			$lastTotalInTrash = $totalInTrash;
			$items = $this->pages->find("$selector, limit=100");
			$cnt = $items->count();
			foreach($items as $item) {
				try {
					$totalDeleted += $this->pages->delete($item, true);
				} catch(\Exception $e) {
					$this->error($e->getMessage());
				}
			}
			$this->pages->uncacheAll();
			$numBatches++;
		} while($cnt);

		// just in case anything left in the trash, use a backup method
		$trashPage = $this->pages->get($trashPage->id); // fresh copy
		$trashPages = $trashPage->children("include=all");
		foreach($trashPages as $t) {
			try {
				$totalDeleted += $this->pages->delete($t, true);
			} catch(\Exception $e) {
				$this->error($e->getMessage());
			}
		}

		$this->pages->uncacheAll();
		if($totalDeleted) {
			$totalInTrash = $this->pages->count("has_parent=$trashPage, include=all, status=" . Page::statusTrash);
			if($totalInTrash) $totalDeleted = $totalDeleted * -1;
		}

		return $totalDeleted;
	}

}