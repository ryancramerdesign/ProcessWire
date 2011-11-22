<?php

/**
 * Installer and uninstaller for LanguageSupport module
 *
 * Split off into a seprate class/file because it's only needed once and 
 * didn't want to keep all this code in the main module that's loaded every request.
 *
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class LanguageSupportInstall extends Wire { 

	/**
	 * Install the module and related modules
	 *
	 */
	public function ___install() {

		$configData = array();

		if($this->templates->get(LanguageSupport::languageTemplateName)) 
			throw new WireException("There is already a template installed called 'language'"); 

		if($this->fields->get(LanguageSupport::languageFieldName)) 
			throw new WireException("There is already a field installed called 'language'"); 

		$adminPage = $this->pages->get($this->config->adminRootPageID); 
		$setupPage = $adminPage->child("name=setup"); 
		if(!$setupPage->id) throw new WireException("Unable to locate {$adminPage->path}setup/"); 

		// create the languages parent page
		$languagesPage = new Page(); 
		$languagesPage->parent = $setupPage; 
		$languagesPage->template = $this->templates->get('admin'); 
		$languagesPage->process = $this->modules->get('ProcessLanguage'); // installs ProcessLanguage module
		$languagesPage->name = 'languages';
		$languagesPage->title = 'Languages';
		$languagesPage->status = Page::statusSystem; 
		$languagesPage->sort = $setupPage->numChildren; 
		$languagesPage->save();
		$configData['languagesPageID'] = $languagesPage->id; 

		// create the 'language_files' field used by the 'language' fieldgroup
		$field = new Field();	
		$field->type = $this->modules->get("FieldtypeFile"); 
		$field->name = 'language_files';
		$field->label = 'Language Translation Files';	
		$field->extensions = 'json';
		$field->maxFiles = 0; 
		$field->inputfieldClass = 'InputfieldFile';	
		$field->unzip = 1; 	
		$field->descriptionRows = 1; 
		$field->flags = Field::flagSystem | Field::flagPermanent;
		$field->save();

		// create the fieldgroup to be used by the language template
		$fieldgroup = new Fieldgroup(); 
		$fieldgroup->name = LanguageSupport::languageTemplateName;
		$fieldgroup->add($this->fields->get('title')); 
		$fieldgroup->add($field); // language_files
		$fieldgroup->save();

		// create the template used by Language pages
		$template = new Template();	
		$template->name = LanguageSupport::languageTemplateName;
		$template->fieldgroup = $fieldgroup; 
		$template->parentTemplates = array($adminPage->template->id); 
		$template->slashUrls = 1; 
		$template->pageClass = 'Language';
		$template->pageLabelField = 'name';
		$template->noGlobal = 1; 
		$template->noMove = 1; 
		$template->noChangeTemplate = 1; 
		$template->nameContentTab = 1; 
		$template->flags = Template::flagSystem; 
		$template->save();

		// create the default language page
		$en = new Language();
		$en->template = $template; 
		$en->parent = $languagesPage; 
		$en->name = 'en-us';
		$en->title = 'English US'; 
		$en->status = Page::statusSystem; 
		$en->save();
		$configData['systemLanguagePageID'] = $en->id; 
		$configData['defaultLanguagePageID'] = $en->id; 

		// create the translator page and process
		$translatorPage = new Page(); 
		$translatorPage->parent = $setupPage; 
		$translatorPage->template = $this->templates->get('admin'); 
		$translatorPage->status = Page::statusHidden | Page::statusSystem; 
		$translatorPage->process = $this->modules->get('ProcessLanguageTranslator'); 
		$translatorPage->name = 'language-translator';
		$translatorPage->title = 'Language Translator';
		$translatorPage->save();
		$configData['languageTranslatorPageID'] = $translatorPage->id; 

		// save the module config data
		$this->modules->saveModuleConfigData('LanguageSupport', $configData); 
		
		// install 'language' field that will be added to the user fieldgroup
		$field = new Field(); 
		$field->type = $this->modules->get("FieldtypePage"); 
		$field->name = LanguageSupport::languageFieldName; 
		$field->label = 'Language';
		$field->derefAsPage = 1; 	
		$field->parent_id = $languagesPage->id; 
		$field->labelFieldName = 'title';
		$field->inputfield = 'InputfieldRadios';
		$field->required = 1; 
		$field->flags = Field::flagSystem | Field::flagPermanent; 
		$field->save();

		// make the 'language' field part of the profile fields the user may edit
		$profileConfig = $this->modules->getModuleConfigData('ProcessProfile'); 	
		$profileConfig['profileFields'][] = 'language';
		$this->modules->saveModuleConfigData('ProcessProfile', $profileConfig); 

		// add to 'user' fieldgroup
		$userFieldgroup = $this->templates->get('user')->fieldgroup; 
		$userFieldgroup->add($field); 
		$userFieldgroup->save();

		// update all users to have the default value set for this field
		foreach($this->users as $user) {
			$user->set('language', $en);
			$user->save();
		}
	}

	public function ___uninstall() {

		$configData = $this->modules->getConfigData('LanguageSupport'); 

		$field = $this->fields->get(LanguageSupport::languageFieldName); 
		$field->status = Field::flagSystemOverride; 
		$field->status = 0; 
		$userFieldgroup = $this->templates->get('user')->fieldgroup; 
		$userFieldgroup->remove($field); 
		$userFieldgroup->save();
		$this->fields->delete($field); 	

		$deletePageIDs = array(
			$configData['defaultLanguagePageID'], 
			$configData['systemLanguagePageID'],
			$configData['languageTranslatorPageID'],
			$configData['languagesPageID']
			);

		foreach($deletePageIDs as $id) {
			$page = $this->pages->get($id); 
			$page->status = Page::statusSystemOverride; 
			$page->status = 0;
			$this->pages->delete($page, true); 
		}

		$template = $this->templates->get(LanguageSupport::languageTemplateName); 	
		$template->flags = Template::flagSystemOverride; 
		$template->flags = 0;
		$this->templates->delete($template); 

		$fieldgroup = $this->fieldgroups->get(LanguageSupport::languageTemplateName); 
		$this->fieldgroups->delete($fieldgroup); 

		$field = $this->fields->get("language_files"); 
		$field->flags = Field::flagSystemOverride; 
		$field->flags = 0;
		$this->fields->delete($field); 
		

	}

