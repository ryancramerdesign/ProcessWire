<?php

/**
 * ProcessWire Pages
 *
 * Manages Page instances, providing find, load, save and delete capabilities,
 * some of which are delegated to other classes but this provides the interface to them.
 *
 * This is the most used object in the ProcessWire API. 
 *
 * @TODO Move everything into delegate classes, leaving this as just the interface to them.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 *
 * @link http://processwire.com/api/variables/pages/ Offical $pages Documentation
 * @link http://processwire.com/api/selectors/ Official Selectors Documentation
 *
 * @method PageArray find() find($selectorString, array $options) Find and return all pages matching the given selector string. Returns a PageArray.
 * @method bool save() save(Page $page) Save any changes made to the given $page. Same as : $page->save() Returns true on success
 * @method bool saveField() saveField(Page $page, $field) Save just the named field from $page. Same as : $page->save('field')
 * @method bool trash() trash(Page $page, $save = true) Move a page to the trash. If you have already set the parent to somewhere in the trash, then this method won't attempt to set it again.
 * @method bool delete() delete(Page $page, $recursive = false) Permanently delete a page and it's fields. Unlike trash(), pages deleted here are not restorable. If you attempt to delete a page with children, and don't specifically set the $recursive param to True, then this method will throw an exception. If a recursive delete fails for any reason, an exception will be thrown.
 * @method Page|NullPage clone() clone(Page $page, Page $parent = null, $recursive = true, $options = array()) Clone an entire page, it's assets and children and return it.
 *
 */

class Pages extends Wire {

	/**
	 * Instance of PageFinder for finding pages
	 *
	 */
	protected $pageFinder; 

	/**
	 * Instance of Templates
	 *
	 */
	protected $templates; 

	/**
	 * Instance of PagesSortfields
	 *
	 */
	protected $sortfields;

	/**
	 * Pages that have been cached, indexed by ID
	 *
	 */
	protected $pageIdCache = array();

	/**
	 * Cached selector strings and the PageArray that was found.
	 *
	 */
	protected $pageSelectorCache = array();

	/**
	 * Controls the outputFormatting state for pages that are loaded
	 *
	 */
	protected $outputFormatting = false; 

	/**
	 * Runtime debug log of Pages class activities, see getDebugLog()
	 *
	 */
	protected $debugLog = array();

	/**
	 * Shortcut to $config API var
	 *
	 */
	protected $config = null;

	/**
	 * Create the Pages object
	 *
	 */
	public function __construct() {
		$this->config = $this->fuel('config');
		$this->templates = $this->fuel('templates'); 
		$this->pageFinder = new PageFinder(); 
		$this->sortfields = new PagesSortfields();
	}


	/**
	 * Given a Selector string, return the Page objects that match in a PageArray. 
	 *
	 * @param string $selectorString
	 * @param array $options 
		- findOne: apply optimizations for finding a single page and include pages with 'hidden' status
	 * @return PageArray
	 *
	 */
	public function ___find($selectorString, $options = array()) {

		// TODO selector strings with runtime fields, like url=/about/contact/, possibly as plugins to PageFinder

		if(!strlen($selectorString)) return new PageArray();
		if($selectorString === '/' || $selectorString === 'path=/') $selectorString = 1;

		if($selectorString[0] == '/') {
			// if selector begins with a slash, then we'll assume it's referring to a path
			$selectorString = "path=$selectorString";

		} else if(strpos($selectorString, ",") === false && strpos($selectorString, "|") === false) {
			// there is just one param. Lets see if we can find a shortcut. 
			if(ctype_digit("$selectorString") || strpos($selectorString, "id=") === 0) {
				// if selector is just a number, or a string like "id=123" then we're going to do a shortcut
				$s = str_replace("id=", '', $selectorString); 
				if(ctype_digit("$s")) {
					$page = $this->getById(array((int) $s)); 
					$pageArray = new PageArray();
					$value = $page ? $pageArray->add($page) : $pageArray; 
					if($this->config->debug) $this->debugLog('find', $selectorString . " [optimized]", $value); 
					return $value; 
				}
			}
		}

		// see if this has been cached and return it if so
		$pages = $this->getSelectorCache($selectorString, $options); 
		if(!is_null($pages)) {
			if($this->config->debug) $this->debugLog('find', $selectorString, $pages . ' [from-cache]'); 
			return $pages; 
		}

		// check if this find has already been executed, and return the cached results if so
		// if(null !== ($pages = $this->getSelectorCache($selectorString, $options))) return clone $pages; 

		// if a specific parent wasn't requested, then we assume they don't want results with status >= Page::statusUnsearchable
		// if(strpos($selectorString, 'parent_id') === false) $selectorString .= ", status<" . Page::statusUnsearchable; 

		$selectors = new Selectors($selectorString); 
		$pages = $this->pageFinder->find($selectors, $options); 

		// note that we save this pagination state here and set it at the end of this method
		// because it's possible that more find operations could be executed as the pages are loaded
		$total = $this->pageFinder->getTotal();
		$limit = $this->pageFinder->getLimit();
		$start = $this->pageFinder->getStart();

		// parent_id is null unless a single parent was specified in the selectors
		$parent_id = $this->pageFinder->getParentID();

		$idsSorted = array(); 
		$idsByTemplate = array();

		// organize the pages by template ID
		foreach($pages as $page) {
			$tpl_id = $page['templates_id']; 
			if(!isset($idsByTemplate[$tpl_id])) $idsByTemplate[$tpl_id] = array();
			$idsByTemplate[$tpl_id][] = $page['id'];
			$idsSorted[] = $page['id'];
		}

		if(count($idsByTemplate) > 1) {
			// perform a load for each template, which results in unsorted pages
			$unsortedPages = new PageArray();
			foreach($idsByTemplate as $tpl_id => $ids) {
				$unsortedPages->import($this->getById($ids, $this->templates->get($tpl_id), $parent_id)); 
			}

			// put pages back in the order that the selectorEngine returned them in, while double checking that the selector matches
			$pages = new PageArray();
			foreach($idsSorted as $id) {
				foreach($unsortedPages as $page) { 
					if($page->id == $id) {
						$pages->add($page); 
						break;
					}
				}
			}
		} else {
			// there is only one template used, so no resorting is necessary	
			$pages = new PageArray();
			reset($idsByTemplate); 
			$pages->import($this->getById($idsSorted, $this->templates->get(key($idsByTemplate)), $parent_id)); 
		}

		$pages->setTotal($total); 
		$pages->setLimit($limit); 
		$pages->setStart($start); 
		$pages->setSelectors($selectors); 
		$pages->setTrackChanges(true);
		$this->selectorCache($selectorString, $options, $pages); 
		if($this->config->debug) $this->debugLog('find', $selectorString, $pages); 

		return $pages; 
		//return $pages->filter($selectors); 
	}

	/**
	 * Like find() but returns only the first match as a Page object (not PageArray)
	 *
	 * @param string $selectorString
	 * @return Page|null
	 *
	 */
	public function findOne($selectorString, $options = array()) {
		if(empty($selectorString)) return new NullPage();
		if($page = $this->getCache($selectorString)) return $page; 
		$options['findOne'] = true; 
		$page = $this->find($selectorString, $options)->first();
		if(!$page) $page = new NullPage();
		return $page; 
	}

	/**
	 * Returns only the first match as a Page object (not PageArray).
	 *
	 * This is an alias of the findOne() method for syntactic convenience and consistency.
	 * Using get() is preferred.
	 *
	 * @param string $selectorString
	 * @return Page|NullPage Always returns a Page object, but will return NullPage (with id=0) when no match found
	 */
	public function get($selectorString) {
		return $this->findOne($selectorString); 
	}

	/**
	 * Given an array or CSV string of Page IDs, return a PageArray 
	 *
	 * @param array|WireArray|string $ids Array of IDs or CSV string of IDs
	 * @param Template $template Specify a template to make the load faster, because it won't have to attempt to join all possible fields... just those used by the template. 
	 * @param int $parent_id Specify a parent to make the load faster, as it reduces the possibility for full table scans
	 * @return PageArray
	 *
	 */
	public function getById($ids, Template $template = null, $parent_id = null) {

		static $instanceID = 0;

		$pages = new PageArray();
		if(is_string($ids)) $ids = explode(",", $ids); 
		if(!WireArray::iterable($ids) || !count($ids)) return $pages; 
		if(is_object($ids)) $ids = $ids->getArray();
		$loaded = array();

		foreach($ids as $key => $id) {
			$id = (int) $id; 
			$ids[$key] = $id; 

			if($page = $this->getCache($id)) {
				$loaded[$id] = $page; 
				unset($ids[$key]); 
			
			} else if(isset(Page::$loadingStack[$id])) {
				// if the page is already in the process of being loaded, point to it rather than attempting to load again.
				// the point of this is to avoid a possible infinite loop with autojoin fields referencing each other.
				$loaded[$id] = Page::$loadingStack[$id];
				// cache the pre-loaded version so that other pages referencing it point to this instance rather than loading again
				$this->cache($loaded[$id]); 
				unset($ids[$key]); 

			} else {
				$loaded[$id] = ''; // reserve the spot, in this order
			}
		}

		$idCnt = count($ids); 
		if(!$idCnt) return $pages->import($loaded); 
		$idsByTemplate = array();

		if(is_null($template)) {
			$sql = "SELECT id, templates_id FROM pages WHERE ";
			if($idCnt == 1) $sql .= "id=" . (int) reset($ids); 
				else $sql .= "id IN(" . implode(",", $ids) . ")";
			$result = $this->db->query($sql); // QA
			if($result && $result->num_rows) while($row = $result->fetch_row()) {
				list($id, $templates_id) = $row; 
				if(!isset($idsByTemplate[$templates_id])) $idsByTemplate[$templates_id] = array();
				$idsByTemplate[$templates_id][] = $id; 
			}
			$result->free();
		} else {
			$idsByTemplate = array($template->id => $ids); 
		}

		foreach($idsByTemplate as $templates_id => $ids) { 

			if(!$template || $template->id != $templates_id) $template = $this->fuel('templates')->get($templates_id);
			$fields = $template->fieldgroup; 
			$query = new DatabaseQuerySelect();
			$joinSortfield = empty($template->sortfield);

			$query->select(	
				"false AS isLoaded, pages.templates_id AS templates_id, pages.*, " . 
				($joinSortfield ? 'pages_sortfields.sortfield, ' : '') . 
				"(SELECT COUNT(*) FROM pages AS children WHERE children.parent_id=pages.id) AS numChildren"
				); 

			if($joinSortfield) $query->leftjoin('pages_sortfields ON pages_sortfields.pages_id=pages.id'); 
			$query->groupby('pages.id'); 
	
			foreach($fields as $field) { 
				if(!($field->flags & Field::flagAutojoin)) continue; 
				$table = $this->db->escapeTable($field->table); 
				if(!$field->type->getLoadQueryAutojoin($field, $query)) continue; // autojoin not allowed
				$query->leftjoin("$table ON $table.pages_id=pages.id"); // QA
			}

			if(!is_null($parent_id)) $query->where("pages.parent_id=" . (int) $parent_id); 

			$query->where("pages.templates_id=" . ((int) $template->id)); // QA
			$query->where("pages.id IN(" . implode(',', $ids) . ") "); // QA
			$query->from("pages"); 

			if(!$result = $query->execute()) throw new WireException($this->db->error); // QA

			$class = ($template->pageClass && class_exists($template->pageClass)) ? $template->pageClass : 'Page';

			while($page = $result->fetch_object($class, array($template))) {
				$page->instanceID = ++$instanceID; 
				$page->setIsLoaded(true); 
				$page->setIsNew(false); 
				$page->setTrackChanges(true); 
				$page->setOutputFormatting($this->outputFormatting); 
				$loaded[$page->id] = $page; 
				$this->cache($page); 
			}

			$template = null;
			$result->free();
		}

		return $pages->import($loaded); 
	}

	/**
	 * Given an ID return a path to a page, without loading the actual page
	 *
 	 * This is not meant to be public API: You should just use $pages->get($id)->path (or url) instead.
	 * This is just a small optimization function for specific situations (like the PW bootstrap).
	 * This function is not meant to be part of the public $pages API, as I think it only serves 
	 * to confuse with $page->path(). However, if you ever have a situation where you need to get a page
 	 * path and want to avoid loading the page for some reason, this function is your ticket.
	 *
	 * @param int $id ID of the page you want the URL to
	 * @return string URL to page or blank on error
	 *
 	 */
	public function _path($id) {

		if(is_object($id) && $id instanceof Page) return $id->path();
		$id = (int) $id;
		if(!$id) return '';

		// if page is already loaded, then get the path from it
		if(isset($this->pageIdCache[$id])) return $this->pageIdCache[$id]->path();

		if($this->modules->isInstalled('PagePaths')) {
			$path = $this->modules->get('PagePaths')->getPath($id);
			if(is_null($path)) $path = '';
			return $path; 
		}

		$path = '';
		$parent_id = $id; 
		do {
			$result = Wire::getFuel('db')->query("SELECT parent_id, name FROM pages WHERE id=" . ((int) $parent_id)); // QA
			list($parent_id, $name) = $result->fetch_row();
			$result->free();
			$path = $name . '/' . $path;
		} while($parent_id > 1); 

		return '/' . ltrim($path, '/');
	}

	/**
	 * Count and return how many pages will match the given selector string
	 *
	 * @param string $selectorString
	 * @return int
	 * @todo optimize this so that it only counts, and doesn't have to load any pages in the process. 
	 *
	 */
	public function count($selectorString, $options = array()) {
		// PW doesn't count when limit=1, which is why we limit=2
		return $this->find("$selectorString, limit=2", $options)->getTotal();
	}

	/**
	 * Is the given page in a state where it can be saved?
	 *
	 * @param Page $page
	 * @param string $reason Text containing the reason why it can't be saved (assuming it's not saveable)
	 * @return bool True if saveable, False if not
	 *
	 */
	public function isSaveable(Page $page, &$reason) {

		$saveable = false; 
		$outputFormattingReason = "Call \$page->setOutputFormatting(false) before getting/setting values that will be modified and saved. "; 

		if($page instanceof NullPage) $reason = "Pages of type NullPage are not saveable";
			else if((!$page->parent || $page->parent instanceof NullPage) && $page->id !== 1) $reason = "It has no parent assigned"; 
			else if(!$page->template) $reason = "It has no template assigned"; 
			else if(!strlen(trim($page->name)) && $page->id != 1) $reason = "It has an empty 'name' field"; 
			else if($page->is(Page::statusCorrupted)) $reason = $outputFormattingReason . " [Page::statusCorrupted]";
			else if($page->id == 1 && !$page->template->useRoles) $reason = "Selected homepage template cannot be used because it does not define access.";
			else if($page->id == 1 && !$page->template->hasRole('guest')) $reason = "Selected homepage template cannot be used because it does not have the required 'guest' role in it's access settings.";
			else $saveable = true; 

		// check if they could corrupt a field by saving
		if($saveable && $page->outputFormatting) {
			// iternate through recorded changes to see if any custom fields involved
			foreach($page->getChanges() as $change) {
				if($page->template->fieldgroup->getField($change) !== null) {
					$reason = $outputFormattingReason . " [$change]";	
					$saveable = false;
					break;
				}
			}
			// iterate through already-loaded data to see if any are objects that have changed
			if($saveable) foreach($page->getArray() as $key => $value) {
				if(!$page->template->fieldgroup->getField($key)) continue; 
				if(is_object($value) && $value instanceof Wire && $value->isChanged()) {
					$reason = $outputFormattingReason . " [$key]";
					$saveable = false; 
					break;
				}
			}
		}

		// check for a parent change
		if($saveable && $page->parentPrevious && $page->parentPrevious->id != $page->parent->id) {
			// page was moved
			if($page->template->noMove && ($page->is(Page::statusSystem) || $page->is(Page::statusSystemID) || !$page->isTrash())) {
				// make sure the page's template allows moves. only move laways allowed is to the trash, unless page has system status
				$saveable = false;
				$reason = "Pages using template '{$page->template}' are not moveable (template::noMove)";

			} else if($page->parent->template->noChildren) {
				$saveable = false;
				$reason = "Chosen parent '{$page->parent->path}' uses template that does not allow children.";

			} else if($page->parent->id && $page->parent->id != $this->config->trashPageID && count($page->parent->template->childTemplates) && !in_array($page->template->id, $page->parent->template->childTemplates)) {
				// make sure the new parent's template allows pages with this template
				$saveable = false;
				$reason = "Can't move '{$page->name}' because Template '{$page->parent->template}' used by '{$page->parent->path}' doesn't allow children with this template.";

			} else if(count($page->template->parentTemplates) && $page->parent->id != $this->config->trashPageID && !in_array($page->parent->template->id, $page->template->parentTemplates)) {
				$saveable = false;
				$reason = "Can't move '{$page->name}' because Template '{$page->parent->template}' used by '{$page->parent->path}' is not allowed by template '{$page->template->name}'.";

			} else if(count($page->parent->children("name={$page->name},status<" . Page::statusMax))) { 
				$saveable = false;
				$reason = "Chosen parent '{$page->parent->path}' already has a page named '{$page->name}'"; 
			}
		}

		return $saveable; 
	}

	/**
	 * Validate that a new page is in a saveable condition and correct it if not.
	 *
	 * Currently just sets up up a unique page->name based on the title if one isn't provided already. 
	 *
	 */
	protected function ___setupNew(Page $page) {

		if(!$page->name && $page->title) {
			$n = 0;
			$pageName = $this->fuel('sanitizer')->pageName($page->title, Sanitizer::translate); 
			do {
				$name = $pageName . ($n ? "-$n" : '');
				$child = $page->parent->child("name=$name"); // see if another page already has the same name
				$n++;
			} while($child->id); 
			$page->name = $name; 
		}

		if($page->sort < 0) {
			$page->sort = $page->parent->numChildren;
		}
	}

	/**
	 * Save a page object and it's fields to database. 
	 *
	 * If the page is new, it will be inserted. If existing, it will be updated. 
	 *
	 * This is the same as calling $page->save()
	 *
	 * If you want to just save a particular field in a Page, use $page->save($fieldName) instead. 
	 *
	 * @param Page $page
	 * @return bool True on success, false on failure
	 *
	 */
	public function ___save(Page $page, $options = array()) {

		$defaultOptions = array(
			'uncacheAll' => true,
			'resetTrackChanges' => true,
			);
		$options = array_merge($defaultOptions, $options); 

		$reason = '';
		$isNew = $page->isNew();
		if($isNew) $this->setupNew($page);
		if(!$this->isSaveable($page, $reason)) throw new WireException("Can't save page {$page->id}: {$page->path}: $reason"); 
		if($page->is(Page::statusUnpublished) && $page->template->noUnpublish) $page->removeStatus(Page::statusUnpublished); 

		if($page->parentPrevious) {
			if($page->isTrash() && !$page->parentPrevious->isTrash()) $this->trash($page, false); 
				else if($page->parentPrevious->isTrash() && !$page->parent->isTrash()) $this->restore($page, false); 
		}

		$user = $this->fuel('user'); 
		$userID = $user ? $user->id : $this->config->superUserPageID; 
		if(!$page->created_users_id) $page->created_users_id = $userID; 
		$extraData = $this->saveReady($page); 

		$sql = 	"pages SET " . 
			"parent_id=" . ((int) $page->parent_id) . ", " . 
			"templates_id=" . ((int) $page->template->id) . ", " . 
			"name='" . $this->db->escape_string($page->name) . "', " . 
			"modified_users_id=" . ((int) $userID) . ", " . 
			"status=" . ((int) $page->status) . ", " . 
			"sort=" . ($page->sort > -1 ? (int) $page->sort : 0) . "," . 
			"modified=NOW()"; 

		if(is_array($extraData) && count($extraData)) foreach($extraData as $column => $value) {
			$column = $this->db->escapeCol($column); 
			$value = strtoupper($value) === 'NULL' ? 'NULL' : "'" . $this->db->escape_string($value) . "'";
			$sql .= ", $column=$value";
		}

		if($isNew) {
			if($page->id) $sql .= ", id=" . (int) $page->id; 
			$result = $this->db->query("INSERT INTO $sql, created=NOW(), created_users_id=" . ((int) $userID)); // QA
			if($result) {
				$page->id = $this->db->insert_id; 
				$page->parent->numChildren++;
			}

		} else {
			if($page->template->allowChangeUser) $sql .= ", created_users_id=" . ((int) $page->created_users_id);
			$result = $this->db->query("UPDATE $sql WHERE id=" . (int) $page->id); // QA
			if($page->parentPrevious && $page->parentPrevious->id != $page->parent->id) {
				$page->parentPrevious->numChildren--;
				$page->parent->numChildren++;
			}
		}

		// if save failed, abort
		if(!$result) return false;

		// if page hasn't changed, don't continue further
		if(!$page->isChanged()) {
			$this->debugLog('save', '[not-changed]', true); 
			return true; 
		}

		if(PagefilesManager::hasPath($page)) $page->filesManager->save();

		// disable outputFormatting and save state
		$outputFormatting = $page->outputFormatting; 
		$page->setOutputFormatting(false); 

		// save each individual Fieldtype data in the fields_* tables
		foreach($page->fieldgroup as $field) {
			if($field->type) $field->type->savePageField($page, $field);
		}

		// return outputFormatting state
		$page->setOutputFormatting($outputFormatting); 

		if(empty($page->template->sortfield)) $this->sortfields->save($page); 
		if($options['resetTrackChanges']) $page->resetTrackChanges();
		if($isNew) {
			$page->setIsNew(false); 
			$triggerAddedPage = $page; 
		} else $triggerAddedPage = null;

		if($page->templatePrevious && $page->templatePrevious->id != $page->template->id) {
			// the template was changed, so we may have data in the DB that is no longer applicable
			// find unused data and delete it
			foreach($page->templatePrevious->fieldgroup as $field) {
				if($page->template->fieldgroup->has($field)) continue; 
				$field->type->deletePageField($page, $field); 
				if($this->config->debug) $this->message("Deleted field '$field' on page {$page->url}"); 
			}
		}

		if($options['uncacheAll']) $this->uncacheAll();

		// determine whether the pages_access table needs to be updated so that pages->find()
		// operations can be access controlled. 

		if($isNew || $page->parentPrevious || $page->templatePrevious) new PagesAccess($page);


		// lastly determine whether the pages_parents table needs to be updated for the find() cache
		// and call upon $this->saveParents where appropriate. 

		if($page->parentPrevious && $page->numChildren > 0) { 		
			// page is moved and it has children
			$this->saveParents($page->id, $page->numChildren); 

		} else if(($page->parentPrevious && $page->parent->numChildren == 1) || 
			($isNew && $page->parent->numChildren == 1) || 
			($page->forceSaveParents)) { 					
			// page is moved and is the first child of it's new parent
			// OR page is NEW and is the first child of it's parent
			// OR $page->forceSaveParents is set (debug/debug, can be removed later)
			$this->saveParents($page->parent_id, $page->parent->numChildren); 
		} 

		if($page->parentPrevious && $page->parentPrevious->numChildren == 0) {
			// $page was moved and it's previous parent is now left with no children, this ensures the old entries get deleted
			$this->saveParents($page->parentPrevious->id, 0); 
		}

		// trigger hooks
		$this->saved($page);
		if($triggerAddedPage) $this->added($triggerAddedPage);
		if($page->namePrevious && $page->namePrevious != $page->name) $this->renamed($page); 
		if($page->parentPrevious) $this->moved($page);
		if($page->templatePrevious) $this->templateChanged($page);

		$this->debugLog('save', $page, true); 

		return true; 
	}

	/**
	 * Save just a field from the given page as used by Page::save($field)
	 *
	 * This function is public, but the preferred manner to call it is with $page->save($field)
	 *
	 * @param Page $page
	 * @param string|Field $fieldName
	 * @return bool True on success
	 *
	 */
	public function ___saveField(Page $page, $field) {

		$reason = '';
		if($page->isNew()) throw new WireException("Can't save field from a new page - please save the entire page first"); 
		if(!$this->isSaveable($page, $reason)) throw new WireException("Can't save field from page {$page->id}: {$page->path}: $reason"); 
		if($field && (is_string($field) || is_int($field))) $field = $this->fuel('fields')->get($field);
		if(!$field instanceof Field) throw new WireException("Unknown field supplied to saveField for page {$page->id}");
		if(!$page->fields->has($field)) throw new WireException("Page {$page->id} does not have field {$field->name}"); 

		$value = $page->get($field->name); 
		if($value instanceof Pagefiles || $value instanceof Pagefile) $page->filesManager()->save();
		$page->trackChange($field->name); 	

		if($field->type->savePageField($page, $field)) { 
			$page->untrackChange($field->name); 
			$user = $this->fuel('user'); 
			$userID = (int) ($user ? $user->id : $this->config->superUserPageID); 
			$this->db->query("UPDATE pages SET modified_users_id=$userID, modified=NOW() WHERE id=" . (int) $page->id); // QA
			$return = true; 
		} else {
			$return = false; 
		}

		$this->debugLog('saveField', "$page:$field", $return);
		return $return;
	}


	/**
	 * Save references to the Page's parents in pages_parents table, as well as any other pages affected by a parent change
	 *
	 * Any pages_id passed into here are assumed to have children
	 *
	 * @param int $pages_id ID of page to save parents from
	 * @param int $numChildren Number of children this Page has
	 * @param int $level Recursion level, for debugging.
	 *
	 */
	protected function saveParents($pages_id, $numChildren, $level = 0) {

		$pages_id = (int) $pages_id; 
		if(!$pages_id) return false; 

		$sql = "DELETE FROM pages_parents WHERE pages_id=" . (int) $pages_id; 
		$this->db->query($sql); // QA

		if(!$numChildren) return true; 

		$insertSql = ''; 
		$id = $pages_id; 
		$cnt = 0;

		do {
			if($id < 2) break; // home has no parent, so no need to do that query
			$sql = "SELECT parent_id FROM pages WHERE id=" . (int) $id; 
			$result = $this->db->query($sql); // QA
			list($id) = $result->fetch_array();
			$id = (int) $id; 
			$result->free();
			if(!$id) break;
			$insertSql .= "($pages_id, $id),";
			$cnt++; 

		} while(1); 

		if($insertSql) {
			$sql = "INSERT INTO pages_parents (pages_id, parents_id) VALUES" . rtrim($insertSql, ","); 
			$this->db->query($sql); // QA
		}

		// find all children of $pages_id that themselves have children
		$sql = 	"SELECT pages.id, COUNT(children.id) AS numChildren " . 
			"FROM pages " . 
			"JOIN pages AS children ON children.parent_id=pages.id " . 
			"WHERE pages.parent_id=$pages_id " . 
			"GROUP BY pages.id ";
		$result = $this->db->query($sql); // QA

		while($row = $result->fetch_array()) {
			$this->saveParents($row['id'], $row['numChildren'], $level+1); 	
		}
		$result->free();

		return true; 	
	}

	/**
	 * Sets a new Page status and saves the page, optionally recursive with the children, grandchildren, and so on.
	 *
	 * While this can be performed with other methods, this is here just to make it fast for internal/non-api use. 
	 * See the trash and restore methods for an example. 
	 *
	 * @param int $pageID 
	 * @param int $status Status per flags in Page::status* constants
	 * @param bool $recursive Should the status descend into the page's children, and grandchildren, etc?
	 * @param bool $remove Should the status be removed rather than added?
	 *
	 */
	protected function savePageStatus($pageID, $status, $recursive = false, $remove = false) {
		$pageID = (int) $pageID; 
		$status = (int) $status; 
		$sql = $remove ? "status & ~$status" : $sql = "status|$status";
		$this->db->query("UPDATE pages SET status=$sql WHERE id=$pageID"); // QA
		if($recursive) { 
			$result = $this->db->query("SELECT id FROM pages WHERE parent_id=$pageID"); // QA
			while($row = $result->fetch_array()) {
				$this->savePageStatus($row['id'], $status, true, $remove); 
			}
			$result->free();
		}
	}

	/**
	 * Is the given page deleteable?
	 *
	 * Note: this does not account for user permission checking. It only checks if the page is in a state to be saveable via the API. 
	 *
	 * @param Page $page
	 * @return bool True if deleteable, False if not
	 *
	 */
	public function isDeleteable(Page $page) {

		$deleteable = true; 
		if(!$page->id || $page->status & Page::statusSystemID || $page->status & Page::statusSystem) $deleteable = false; 
			else if($page instanceof NullPage) $deleteable = false;

		return $deleteable;
	}

	/**
	 * Move a page to the trash
	 *
	 * If you have already set the parent to somewhere in the trash, then this method won't attempt to set it again. 
	 *
	 * @param Page $page
	 * @param bool $save Set to false if you will perform the save() call, as is the case when called from the Pages::save() method.
	 * @return bool
	 *
	 */
	public function ___trash(Page $page, $save = true) {
		if(!$this->isDeleteable($page) || $page->template->noTrash) throw new WireException("This page may not be placed in the trash"); 
		if(!$trash = $this->get($this->config->trashPageID)) {
			throw new WireException("Unable to load trash page defined by config::trashPageID"); 
		}
		$page->addStatus(Page::statusTrash); 
		if(!$page->parent->isTrash()) $page->parent = $trash;
		if(!preg_match('/^' . $page->id . '_.+/', $page->name)) {
			// make the name unique when in trash, to avoid namespace collision
			$page->name = $page->id . "_" . $page->name; 
		}
		if($save) $this->save($page); 
		$this->savePageStatus($page->id, Page::statusTrash, true, false); 
		$this->trashed($page);
		$this->debugLog('trash', $page, true); 
		return true; 
	}

	/**
	 * Restore a page from the trash back to a non-trash state
	 *
	 * Note that this method assumes already have set a new parent, but have not yet saved
	 *
	 * @param Page $page
	 * @param bool $save Set to false if you only want to prep the page for restore (i.e. being saved elsewhere)
	 * @return bool
	 *
	 */
	protected function ___restore(Page $page, $save = true) {
		if(preg_match('/^(' . $page->id . ')_(.+)$/', $page->name, $matches)) {
			$name = $matches[2]; 
			if(!count($page->parent->children("name=$name"))) 
				$page->name = $name;  // remove namespace collision info if no collision
		}
		$page->removeStatus(Page::statusTrash); 
		if($save) $page->save();
		$this->savePageStatus($page->id, Page::statusTrash, true, true); 
		$this->restored($page);
		$this->debugLog('restore', $page, true); 
		return true; 
	}

	/**
	 * Permanently delete a page and it's fields. 
	 *
	 * Unlike trash(), pages deleted here are not restorable. 
	 *
	 * If you attempt to delete a page with children, and don't specifically set the $recursive param to True, then 
	 * this method will throw an exception. If a recursive delete fails for any reason, an exception will be thrown.
	 *
	 * @param Page $page
	 * @param bool $recursive If set to true, then this will attempt to delete all children too. 
	 * @return bool
	 *
	 */
	public function ___delete(Page $page, $recursive = false) {

		if(!$this->isDeleteable($page)) throw new WireException("This page may not be deleted"); 

		if($page->numChildren) {
			if(!$recursive) throw new WireException("Can't delete Page $page because it has one or more children."); 
			foreach($page->children("status<" . Page::statusMax) as $child) {
				if(!$this->delete($child, true)) throw new WireException("Error doing recursive page delete, stopped by page $child"); 
			}
		}

		// trigger a hook to indicate delete is ready and WILL occur
		$this->deleteReady($page); 
	
		foreach($page->fieldgroup as $field) {
			if(!$field->type->deletePageField($page, $field)) {
				$this->error("Unable to delete field '$field' from page '$page'"); 
			}
		}

		try { 
			if(PagefilesManager::hasPath($page)) $page->filesManager->emptyAllPaths(); 
		} catch(Exception $e) { 
		}
		// $page->getCacheFile()->remove();

		$access = new PagesAccess();	
		$access->deletePage($page); 

		$this->db->query("DELETE FROM pages_parents WHERE pages_id=" . (int) $page->id); // QA
		$this->db->query("DELETE FROM pages WHERE id=" . ((int) $page->id) . " LIMIT 1"); // QA

		$this->sortfields->delete($page); 
		$page->setTrackChanges(false); 
		$page->status = Page::statusDeleted; // no need for bitwise addition here, as this page is no longer relevant
		$this->deleted($page);
		$this->uncacheAll();
		$this->debugLog('delete', $page, true); 

		return true; 
	}


	/**
	 * Clone an entire page, it's assets and children and return it. 
	 *
	 * @param Page $page Page that you want to clone
	 * @param Page $parent New parent, if different (default=same parent)
	 * @param bool $recursive Clone the children too? (default=true)
	 * @param array $options Optional options that can be passed to clone or save
	 * @return Page the newly cloned page or a NullPage() with id=0 if unsuccessful.
	 *
	 */
	public function ___clone(Page $page, Page $parent = null, $recursive = true, $options = array()) {

		// if parent is not changing, we have to modify name now
		if(is_null($parent)) {
			$parent = $page->parent; 
			$n = 1; 
			$name = $page->name . '-' . $n; 
		} else {
			$name = $page->name; 
			$n = 0; 
		}

		// make sure that we have a unique name
		while(count($parent->children("name=$name"))) {
			$name = $page->name . '-' . (++$n); 
		}


		// Ensure all data is loaded for the page
		foreach($page->template->fieldgroup as $field) {
			$page->get($field->name); 
		}

		// clone in memory
		$copy = clone $page; 
		$copy->id = 0; 
		$copy->setIsNew(true); 
		$copy->name = $name; 
		$copy->parent = $parent; 

		// tell PW that all the data needs to be saved
		foreach($copy->template->fieldgroup as $field) {
			$copy->trackChange($field->name); 
		}

		$o = $copy->outputFormatting; 
		$copy->setOutputFormatting(false); 
		$this->cloneReady($page, $copy); 
		$this->save($copy, $options); 
		$copy->setOutputFormatting($o); 

		// check to make sure the clone has worked so far
		if(!$copy->id || $copy->id == $page->id) return new NullPage(); 

		// copy $page's files over to new page
		if(PagefilesManager::hasFiles($page)) {
			$copy->filesManager->init($copy); 
			$page->filesManager->copyFiles($copy->filesManager->path()); 
		}

		// if there are children, then recurisvely clone them too
		if($page->numChildren && $recursive) {
			foreach($page->children("include=all") as $child) {
				$this->clone($child, $copy); 	
			}	
		}

		$copy->parentPrevious = null;
		$copy->resetTrackChanges();

		$this->cloned($page, $copy); 
		$this->debugLog('clone', "page=$page, parent=$parent", $copy);
	
		return $copy; 	
	}


	/**
	 * Given a Page ID, return it if it's cached, or NULL of it's not. 
	 *
	 * If no ID is provided, then this will return an array copy of the full cache.
	 *
	 * You may also pass in the string "id=123", where 123 is the page_id
	 *
	 * @param int|string|null $id 
	 * @return Page|array|null
	 *
	 */
	public function getCache($id = null) {
		if(!$id) return $this->pageIdCache; 
		if(!ctype_digit("$id")) $id = str_replace('id=', '', $id); 
		$id = (int) $id; 
		if(!isset($this->pageIdCache[$id])) return null; 
		$page = $this->pageIdCache[$id];
		$page->setOutputFormatting($this->outputFormatting); 
		return $page; 
	}

	/**
	 * Cache the given page. 
	 *
	 * @param Page $page
	 *
	 */
	public function cache(Page $page) {
		if($page->id) $this->pageIdCache[$page->id] = $page; 
	}

	/**
	 * Remove the given page from the cache. 
	 *
	 * Note: does not remove pages from selectorCache. Call uncacheAll to do that. 
	 *
	 * @param Page $page
	 *
	 */
	public function uncache(Page $page) {
		$page->uncache();
		unset($this->pageIdCache[$page->id]); 
	}

	/**
	 * Remove all pages from the cache. 
	 *
	 */
	public function uncacheAll() {

		unset($this->pageFinder); 
		$this->pageFinder = new PageFinder(); 

		unset($this->sortfields); 
		$this->sortfields = new PagesSortfields();

		if($this->config->debug) $this->debugLog('uncacheAll', 'pageIdCache=' . count($this->pageIdCache) . ', pageSelectorCache=' . count($this->pageSelectorCache)); 

		foreach($this->pageIdCache as $id => $page) {
			if(!$page->numChildren) $this->uncache($page); 
		}


		$this->pageIdCache = array();
		$this->pageSelectorCache = array();
	}

	/**
	 * Cache the given selector string and options with the given PageArray
	 *
	 * @param string $selector
	 * @param array $options
	 * @param PageArray $pages
	 * return bool True of pages were cached, false if not
	 *
	 */
	protected function selectorCache($selector, array $options, PageArray $pages) {

		// get the string that will be used for caching
		$selector = $this->getSelectorCache($selector, $options, true); 		

		// optimization: don't cache single pages that have an unpublished status or higher
		if(count($pages) && !empty($options['findOne']) && $pages->first()->status >= Page::statusUnpublished) return false; 

		$this->pageSelectorCache[$selector] = clone $pages; 

		return true; 
	}

	/**
	 * Retrieve any cached page IDs for the given selector and options OR false if none found.
	 *
	 * You may specify a third param as TRUE, which will cause this to just return the selector string (with hashed options)
	 *
	 * @param string $selector
	 * @param array $options
	 * @param bool $returnSelector default false
	 * @return array|null|string
	 *
	 */
	protected function getSelectorCache($selector, $options, $returnSelector = false) {

		if(count($options)) {
			$optionsHash = '';
			ksort($options);		
			foreach($options as $key => $value) $optionsHash .= "[$key:$value]";
			$selector .= "," . $optionsHash;
		} else $selector .= ",";

		// optimization to use consistent conventions for commonly interchanged names
		$selector = str_replace(array('path=/,', 'parent=/,'), array('id=1,', 'parent_id=1,'), $selector); 

		// optimization to filter out common status checks for pages that won't be cached anyway
		if(!empty($options['findOne'])) {
			$selector = str_replace(array("status<" . Page::statusUnpublished, "status<" . Page::statusMax, 'start=0', 'limit=1', ',', ' '), '', $selector); 
			$selector = trim($selector, ", "); 
		}

		if($returnSelector) return $selector; 
		if(isset($this->pageSelectorCache[$selector])) return $this->pageSelectorCache[$selector]; 

		return null; 
	}

	/**
	 * For internal Page instance access, return the Pages sortfields property
	 *
	 * @return PagesSortFields
	 *
	 */
	public function sortfields() {
		return $this->sortfields; 
	}

	/**	
 	 * Return a fuel or other property set to the Pages instance
	 *
	 */
	public function __get($key) {
		return parent::__get($key); 
	}

	/**
	 * Set whether loaded pages have their outputFormatting turn on or off
	 *
	 * By default, it is turned on. 
	 *
	 */
	public function setOutputFormatting($outputFormatting = true) {
		$this->outputFormatting = $outputFormatting ? true : false; 
	}

	/**
	 * Log a Pages class event
	 *
	 * Only active in debug mode. 
	 *
	 * @param string $action Name of action/function that occurred.
	 * @param string $details Additional details, like a selector string. 
	 * @param string|object The value that was returned.
	 *
	 */
	protected function debugLog($action = '', $details = '', $result = '') {
		if(!$this->config->debug) return;
		$this->debugLog[] = array(
			'time' => microtime(),
			'action' => (string) $action, 
			'details' => (string) $details, 
			'result' => (string) $result
			);
	}

	/**
	 * Get the Pages class debug log
	 *
	 * Only active in debug mode
	 *
	 * @param string $action Optional action within the debug log to find
	 * @return array
	 *
	 */
	public function getDebugLog($action = '') {
		if(!$this->config->debug) return array();
		if(!$action) return $this->debugLog; 
		$debugLog = array();
		foreach($this->debugLog as $item) if($item['action'] == $action) $debugLog[] = $item; 
		return $debugLog; 
	}

	/**
	 * Hook called after a page is successfully saved
	 *
	 * This is the same as Pages::save, except that it occurs before other save-related hooks (below),
	 * Whereas Pages::save occurs after. In most cases, the distinction does not matter. 
	 *
	 */
	protected function ___saved(Page $page) { }

	/**
	 * Hook called when a new page has been added
	 *
	 */
	protected function ___added(Page $page) { }

	/**
	 * Hook called when a page has been moved from one parent to another
	 *
	 * Note the previous parent is in $page->parentPrevious
	 *
	 */
	protected function ___moved(Page $page) { }

	/**
	 * Hook called when a page's template has been changed
	 *
	 * Note the previous template is in $page->templatePrevious
	 *
	 */
	protected function ___templateChanged(Page $page) { }

	/**
	 * Hook called when a page has been moved to the trash
	 *
	 */
	protected function ___trashed(Page $page) { }

	/**
	 * Hook called when a page has been moved OUT of the trash
	 *
	 */
	protected function ___restored(Page $page) { }

	/**
	 * Hook called just before a page is saved
	 *
	 * May be preferable to a before(save) hook because you know for sure a save will 
	 * be executed immediately after this is called. Whereas you don't necessarily know
 	 * that when before(save) is called, as an error may prevent it. 
	 *
	 * Note that there is no ___saved() hook because it's already provided by after(save).
	 *
	 * @param Page $page The page about to be saved
	 * @return array Optional extra data to add to pages save query.
	 *
	 */
	protected function ___saveReady(Page $page) { return array(); }

	/**
	 * Hook called when a page is about to be deleted, but before data has been touched
	 *
	 * This is different from a before(delete) hook because this hook is called once it has 
	 * been confirmed that the page is deleteable and WILL be deleted. 
	 *
	 */
	protected function ___deleteReady(Page $page) { }

	/**
	 * Hook called when a page and it's data have been deleted
	 *
	 */
	protected function ___deleted(Page $page) { }

	/**
	 * Hook called when a page is about to be cloned, but before data has been touched
	 *
	 * @param Page $page The original page to be cloned
	 * @param Page $copy The actual clone about to be saved
	 *
	 */
	protected function ___cloneReady(Page $page, Page $copy) { }

	/**
	 * Hook called when a page has been cloned
	 *
	 * @param Page $page The original page to be cloned
	 * @param Page $copy The completed cloned version of the page
	 *
	 */
	protected function ___cloned(Page $page, Page $copy) { }

	/**
	 * Hook called when a page has been renamed (i.e. had it's name field change)
	 *
	 * The previous name can be accessed at $page->namePrevious;
	 * The new name can be accessed at $page->name
	 *
	 * This hook is only called when a page's name changes. It is not called when
	 * a page is moved unless the name was changed at the same time. 
	 *
	 * @param Page $page The $page that was renamed
	 *
	 */
	protected function ___renamed(Page $page) { }

}


