<?php
die("This upgrade script isn't yet ready to use, though it does work on my computer. Uncomment this line from the file to test it out at your own risk."); 

define("PROCESSWIRE_INSTALL", 1); 
define("PROCESSWIRE_UPGRADE", 1); 
error_reporting(E_ALL); 

class upgradeProcessWire {

	protected $numErrors = 0; 

	protected $adminTemplateID = 2; 
	protected $adminPageID = 2; 
	protected $accessPageID = 28; 
	protected $accessUsersPageID = 29; 
	protected $accessRolesPageID = 30; 
	protected $accessPermissionsPageID = 31; 

	protected $newPermissions = array(
		'ProcessPageView' => 'page-view',
		'ProcessPageEdit' => 'page-edit',
		'ProcessPageAdd' => 'page-add',
		'ProcessPageEditDelete' => 'page-delete',
		'ProcessPageSortMove' => 'page-move',
		'ProcessPageSort' => 'page-sort',
		'page-template' => 'page-template',
		'ProcessModule' => 'module-admin',
		'ProcessField' => 'field-admin',
		'ProcessTemplate' => 'template-admin',
		'ProcessUser' => 'user-admin',
		'ProcessRole' => 'role-admin',
		'ProcessPermission' => 'permission-admin',
		'profile-edit' => 'profile-edit',
		); 

	protected $permissionTitles = array(
		'user-admin' =>	'Administer Users',
		'role-admin' => 'Administer Roles',
		'page-edit' => 'Edit Pages',
		'page-add' => 'Add New Pages',
		'page-delete' => 'Delete Pages',
		'page-move' => 'Move Pages (Change Parent)',
		'page-view' => 'View Pages',
		'page-sort' => 'Sort Pages',
		'page-template' => 'Change Templates on Pages',
		'module-admin' => 'Administer Modules',
		'field-admin' => 'Administer Fields',
		'template-admin' => 'Administer Templates',
		'permission-admin' => 'Administer Permissions',
		'profile-edit' => 'User can update their own profile/password',
		);

	public function __construct() {
	}

	public function __get($key) {
		return wire($key); 
	}

	public function err($str) { 
		echo "\n<li class='ui-state-error'><span class='ui-icon ui-icon-alert'> </span>$str</li>"; $this->numErrors++; 
	}

	public function li($str, $icon = 'check') { 
		echo "\n<li class='ui-state-highlight'><span class='ui-icon ui-icon-$icon'> </span>$str</li>"; 
	}

	public function ok($str) { 
		echo "\n<li class='ui-state-highlight ui-state-disabled'><span class='ui-icon ui-icon-check'> </span>$str</li>"; 
	}

	protected function btn($label, $value) {
		echo "\n<p style='float: left; padding-right: 0.5em;'><button name='step' type='submit' class='ui-button ui-widget ui-state-default ui-corner-all' value='$value'><span class='ui-button-text'><span class='ui-icon ui-icon-carat-1-e'> </span>$label</span></a></button></p>";
	}

	/**
	 * Verify and maintain access to the installer 
	 *	
 	 */
	public function authenticate() {

		$auth = md5($this->config->dbPass); 

		if(isset($_POST['dbpass'])) {
			if($_POST['dbpass'] === $this->config->dbPass) {
				echo "<input type='hidden' name='auth' value='$auth' />";
				return true; 
			} 
		} else if(isset($_POST['auth'])) {
			if($_POST['auth'] === $auth) {
				echo "<input type='hidden' name='auth' value='$auth' />";
				return true; 
			} 
		}
		return false; 
	}

	/**
	 * Run the installer
	 *
	 */
	public function execute($step = 0) {

		$step = 0;
		$totalSteps = 8;
		if(isset($_POST['step'])) $step = (int) $_POST['step'];
		$nextStep = $step+1;

		if($step > 0) {
			require("./index.php"); 
			$this->config->debug = true; 
		}

		$title = "ProcessWire 2.1 Upgrade ";
		$subhead = ($step > 0 ? "<h2 style='margin-top: 0;'>Step $step of $totalSteps</h2>" : ''); 
		$bgTitle = 'Upgrade';
		$formAction = './upgrade.php';

		require("./wire/templates-admin/install-head.inc"); 

		if($step === 0 || $this->authenticate()) {

			echo $subhead; 
			echo "<ul>";

			switch($step) {

				case 0: 
					echo "<li><h2>This will upgrade your installation from v2.0 to v2.1. Before starting this upgrade, please make sure that you have completed the following:</h2>";
					$this->li("Backed up ALL of your web site files, including all files in your web root, /site/ and /wire/.", 'carat-1-e'); 
					$this->li("Backed up your MySQL database used by ProcessWire.", 'carat-1-e'); 
					$this->li("Uninstalled any 3rd-party modules (you can re-install them after the upgrade).", 'carat-1-e'); 
					$this->li("Removed custom admin theme (if you installed one in /site/templates-admin/).", 'carat-1-e'); 
					$this->li("Replaced the /wire/ directory with the new one provided in ProcessWire 2.1."); 
					$this->checkFileVersions();
					$this->checkTemplatesAdmin();
					echo "<li><h2 style='margin: 1em 0 0 0;'>Please verify that you are allowed to perform this upgrade:</h2></li>";
					echo "<li><strong>Enter your MySQL database password as used by ProcessWire.</strong><br />For reference, this should be located at the bottom of /site/config.php</li>"; 
					echo "<li><input type='password' name='dbpass' size='30' /></li>";
					break;

				case 1:
					$this->li("Resetting modules cache"); 
					$this->modules->resetCache();
					$this->upgradeExistingTableDefinitions();
					$this->modules->install("ProcessPageType"); 
					$this->modules->install("ProcessPermission"); 
					$this->modules->install("ProcessProfile"); 
					break;

				case 2:
					$this->upgradeExistingSystemPages();
					$this->upgradeExistingTemplates();
					$this->upgradeExistingFields();
					$this->removeExistingPages();
					$this->addNewUserFields();
					break;

				case 3: 
					$this->addNewSystemTemplates();
					$this->addNewSystemPages();
					break;

				case 4: 
					$this->addNewPermissionPages();
					break;

				case 5: 
					$this->addNewRolesPages();
					break;

				case 6: 
					$this->addNewUsersPages();
					break;

				case 7: 
					$this->setupPagesAccess();
					$this->renameOldTables();
					break;

				case 8: 
					$this->createProfilePage();
					$nextStep = 0;
					break;

				default: 
					$this->err("There is no step $step");
					$nextStep = 0; 

			} 

			echo "</ul>";

			if($step > 0) $this->btn("Return to previous step", $step-1); 
			if($this->numErrors) $this->btn("Try this step again", $step); 
			if($nextStep) $this->btn("Continue to step $nextStep", $nextStep); 

			echo "<br style='clear: both;' />";

		} else {
			$this->err("Unable to authenticate for access to this upgrade utility");
		}

		require("./wire/templates-admin/install-foot.inc"); 
	}


	/************************************************************************************************************
	 * Add new columns to necessary tables
	 *
	 */
	function upgradeExistingTableDefinitions() {

		// update templates table to add 'flags' column
		$result = $this->db->query("SELECT * FROM templates LIMIT 1"); 
		$row = $result->fetch_assoc();
		if(!isset($row['flags'])) {
			$this->li("Adding 'flags' column to 'templates' table.");
			$this->db->query("ALTER TABLE templates ADD flags INT UNSIGNED NOT NULL DEFAULT 0"); 
		} else {
			$this->ok("Field 'flags' already present on 'templates' table.");
		}

		// create pages_access table
		$sql = 	"CREATE TABLE `pages_access` (" . 
			"pages_id int(11) NOT NULL," . 
			"templates_id int(11) NOT NULL," . 
			"PRIMARY KEY  (pages_id)," . 
			"KEY templates_id (templates_id)" . 
			") ENGINE=MyISAM;";

		$result = $this->db->query("SHOW TABLES LIKE 'pages_access'"); 
		if(!$result || !$result->num_rows) {
			$this->li("Creating pages_access table"); 
			$this->db->query($sql); 
		} else {
			$this->ok("pages_access table already present"); 
		}

	}

	/************************************************************************************************************
	 * Update pages for new 'system' status
	 *
	 */
	function upgradeExistingSystemPages() {

		$systemPageIDs = array(1, 
			$this->config->trashPageID, 
			$this->config->adminRootPageID,
			$this->config->http404PageID,
			$this->config->loginPageID,
			);

		$system2PageIDs = array(
			3, 	// page/
			6, 	// page/add/
			8, 	// page/list/
			9, 	// page/sort/
			10,	// page/edit/
			5722,	// page/search/
			5729,	// page/trash/
			5731,	// page/link/
			5733,	// page/image/
			22,	// setup/
			11,	// setup/template/
			16, 	// setup/field/
			21,	// module/
			); 

		foreach(array('systemPageIDs', 'system2PageIDs') as $pageIDs) {
			foreach($$pageIDs as $id) {
				$page = $this->pages->get($id); 
				$status = $pageIDs == 'systemPageIDs' ? Page::statusSystemID : Page::statusSystem;
				if(!$page->id) {
					$this->err("Unable to load page ID $id"); 
					continue; 
				}
				if($page->status & $status) {
					$this->ok("Page '{$page->path}' already has system status.");
					continue; 
				}
				$this->li("Adding 'system' status to page '{$page->path}'.");
				$page->status = $page->status | $status;
				$page->save();
			}
		}

	}


	/************************************************************************************************************
	 * Make existing admin template a system template
	 *
	 */
	function upgradeExistingTemplates() {

		$template = $this->templates->get('admin'); 
		if($template->flags & Template::flagSystem) {
			$this->ok("Template 'admin' already has system status."); 	
		} else {
			$template->flags = $template->flags | Template::flagSystem; 
			$template->save(); 
			$this->li("Added 'system' status to template 'admin'."); 
		}

	}

	/************************************************************************************************************
	 * Make existing 'title' and 'process' fields system fields
	 *
	 */
	function upgradeExistingFields() {

		// title
		$field = $this->fields->get('title'); 
		if($field && $field->id) {
			if($field->flags & Field::flagSystem) {
				$this->ok("Field 'title' already has the system flag."); 
			} else {
				$field->flags = $field->flags | Field::flagSystem;
				$field->save();
				$this->li("Added 'system' flag to field 'title'"); 
			}
		} else {
			$this->err("Your system is missing a required 'title' field."); 
			return false;
		}
		$fieldTitle = $field;

		// process
		$field = $this->fields->get('process'); 
		if($field && $field->id) {
			if($field->flags & Field::flagSystem) {
				$this->ok("Field 'process' already has the system flag."); 
			} else {
				$field->flags = $field->flags | Field::flagSystem;
				$field->save();
				$this->li("Added 'system' flag to field 'process'"); 
			}
		} else {
			$this->err("Your system is missing a required 'process' field."); 
			return false;
		}
		$fieldProcess = $field;

	}

	/************************************************************************************************************
	 * Remove existing 'access' section
	 *
	 */
	function removeExistingPages() {

		$removePageIDs = array(24, 25, 26, 5); // logout, users, roles, access
		foreach($removePageIDs as $id) {
			$page = $this->pages->get($id); 
			if($page->id) { 
				$this->li("Removed page ID $id: {$page->path}"); 
				$page->delete();
			} else {
				$this->ok("Page ID $id already removed."); 
			}
		}
	}

	/************************************************************************************************************
	 * Add new user system fields
	 *
	 */
	function addNewUserFields() {

		$field = $this->fields->get('roles'); 

		if($field && $field->id) { 
			$this->ok("Your system already has the 'roles' field"); 
			if($field->type != 'FieldtypePage' || $field->parent_id != 30) {
				$this->err("You already have a field named 'roles' and the new PW version needs that name."); 
				return false;
			}

			if(($field->flags & Field::flagSystem) && ($field->flags & Field::flagPermanent)) {
				$this->ok("Field 'roles' already has the permanent flag."); 
			} else {
				$field->flags = Field::flagSystem | Field::flagPermanent;
				$field->save();
				$this->li("Added 'permanent' flag to field 'roles'"); 
			}
		} else { 
			$field = new Field();
			$field->name = 'roles';	
			$field->type = $this->modules->get('FieldtypePage'); 
			$field->label = "Roles";
			$field->parent_id = 30; 
			$field->inputfield = 'InputfieldCheckboxes';
			$field->flags = Field::flagSystem | Field::flagPermanent; 
			$field->save();
			$this->li("Added new system field 'roles'"); 
		}
		$fieldRoles = $field;

		$field = $this->fields->get('permissions'); 
		if($field && $field->id) { 
			$this->ok("Your system already has the 'permissions' field"); 
			if($field->type != 'FieldtypePage' || $field->parent_id != 31) {
				$this->err("You already have a field named 'permissions' and the new PW version needs that name."); 
				return false;
			}

			if(($field->flags & Field::flagSystem) && ($field->flags & Field::flagPermanent)) {
				$this->ok("Field 'permissions' already has the permanent flag."); 
			} else {
				$field->flags = Field::flagSystem | Field::flagPermanent;
				$field->save();
				$this->li("Added 'permanent' flag to field 'permissions'"); 
			}
		} else { 
			$field = new Field();
			$field->name = 'permissions';	
			$field->type = $this->modules->get('FieldtypePage'); 
			$field->label = "Permissions";
			$field->parent_id = 31; 
			$field->inputfield = 'InputfieldCheckboxes';
			$field->flags = Field::flagSystem | Field::flagPermanent; 
			$field->save();
			$this->li("Added new system field 'permissions'"); 
		}
		$fieldPermissions = $field;

		$field = $this->fields->get('pass'); 
		if($field && $field->id) { 
			$this->ok("Your system already has the 'pass' field"); 
			if($field->type != 'FieldtypePassword') {
				$this->err("You already have a field named 'pass' and the new PW version needs that name."); 
				return false;
			}

			if(($field->flags & Field::flagSystem) && ($field->flags & Field::flagPermanent)) {
				$this->ok("Field 'pass' already has the permanent flag."); 
			} else {
				$field->flags = Field::flagSystem | Field::flagPermanent;
				$field->save();
				$this->li("Added 'permanent' flag to field 'pass'"); 
			}
		} else { 
			$field = new Field();
			$field->name = 'pass';	
			$field->type = $this->modules->get('FieldtypePassword'); 
			$field->label = "Password";
			$field->flags = Field::flagSystem | Field::flagPermanent; 
			$field->save();
			$this->li("Added new system field 'pass'"); 
		}
		$fieldPass = $field;

		$field = $this->fields->get('email'); 
		if($field && $field->id) { 
			$this->ok("Your system already has the 'email' field"); 
			if($field->type != 'FieldtypeEmail') {
				$this->err("You already have a field named 'email' and the new PW version needs that name."); 
				return false;
			}

			if(($field->flags & Field::flagSystem) && ($field->flags & Field::flagPermanent)) {
				$this->ok("Field 'email' already has the permanent flag."); 
			} else {
				$field->flags = Field::flagSystem | Field::flagPermanent;
				$field->save();
				$this->li("Added 'permanent' flag to field 'email'"); 
			}
		} else { 
			$field = new Field();
			$field->name = 'email';	
			$field->type = $this->modules->get('FieldtypeEmail'); 
			$field->label = "E-Mail";
			$field->flags = Field::flagSystem | Field::flagPermanent; 
			$field->save();
			$this->li("Added new system field 'email'"); 
		}
		$fieldPass = $field;
	}

	/************************************************************************************************************
	 * Add new system fieldgroups and templates: user, role, permission
	 *
	 */
	function addNewSystemTemplates() {

		// fieldgroup: user

		$fieldPass = $this->fields->get('pass'); 
		$fieldRoles = $this->fields->get('roles'); 
		$fieldEmail = $this->fields->get('email'); 
		$fieldgroup = $this->fieldgroups->get('user'); 

		if($fieldgroup && $fieldgroup->id) {
			$this->ok("Fieldgroup 'user' already exists"); 
			if($fieldgroup->has("pass")) {
				$this->ok("Fieldgroup 'user' already has field 'pass'"); 
			}  else {
				$fieldgroup->add($fieldPass); 
				$this->li("Added field 'pass' to fieldgroup 'user'"); 
			}
			if($fieldgroup->has("email")) {
				$this->ok("Fieldgroup 'user' already has field 'email'"); 
			}  else {
				$fieldgroup->add($fieldEmail); 
				$this->li("Added field 'email' to fieldgroup 'user'"); 
			}
			if($fieldgroup->has("roles")) {
				$this->ok("Fieldgroup 'user' already has field 'roles'"); 
			}  else {
				$fieldgroup->add($fieldRoles); 
				$this->li("Added field 'roles' to fieldgroup 'user'"); 
			}
			$fieldgroup->save();
		} else {
			$fieldgroup = new Fieldgroup();
			$fieldgroup->name = 'user';
			$fieldgroup->add($fieldPass); 
			$fieldgroup->add($fieldEmail);
			$fieldgroup->add($fieldRoles); 
			$fieldgroup->save();
			$this->li("Added fieldgroup 'user'"); 
		}
		$fieldgroupUser = $fieldgroup;

		// template: user

		$template = $this->templates->get('user'); 
		if($template && $template->id) {
			$this->ok("Template 'user' already exists."); 
			if($template->flags & Template::flagSystem) {
				$this->ok("Template 'user' already has the system flag."); 
			} else {
				$this->li("Adding system flag to template 'user'."); 
				$template->flags = $template->flags | Template::flagSystem; 
				$template->save();
			}
		} else {
			$template = new Template();
			$template->name = 'user';
			$template->fieldgroup = $fieldgroupUser; 
			$template->flags = Template::flagSystem; 
			$template->slashUrls = 1; 
			$template->noGlobal = 1; 
			$template->noMove = 1; 
			$template->noChangeTemplate = 1; 
			$template->nameContentTab = 1; 
			$template->pageClass = 'User';
			$template->altFilename = 'admin';
			$template->save();
			$this->li("Added new template 'user'"); 
		}
		$templateUser = $template;

		// fieldgroup: role

		$fieldPermissions = $this->fields->get("permissions"); 
		$fieldTitle = $this->fields->get("title"); 
		$fieldgroup = $this->fieldgroups->get('role'); 
		if($fieldgroup && $fieldgroup->id) {
			$this->ok("Fieldgroup 'role' already exists"); 

			if($fieldgroup->has("title")) {
				$this->ok("Fieldgroup 'role' already has field 'title'"); 
			}  else {
				$fieldgroup->add($fieldTitle); 
				$fieldgroup->save();
				$this->li("Added field 'title' to fieldgroup 'role'"); 
			}
			if($fieldgroup->has("permissions")) {
				$this->ok("Fieldgroup 'role' already has field 'permissions'"); 
			}  else {
				$fieldgroup->add($fieldPermissions); 
				$fieldgroup->save();
				$this->li("Added field 'permissions' to fieldgroup 'role'"); 
			}
		} else {
			$fieldgroup = new Fieldgroup();
			$fieldgroup->name = 'role';
			$fieldgroup->add($fieldTitle); 
			$fieldgroup->add($fieldPermissions); 
			$fieldgroup->save();
			$this->li("Added fieldgroup 'role'"); 
		}
		$fieldgroupRole = $fieldgroup;

		// template: role

		$template = $this->templates->get('role'); 
		if($template && $template->id) {
			$this->ok("Template 'role' already exists."); 
			if($template->flags & Template::flagSystem) {
				$this->ok("Template 'role' already has the system flag."); 
			} else {
				$this->li("Adding system flag to template 'role'."); 
				$template->flags = $template->flags | Template::flagSystem; 
				$template->save();
			}
		} else {
			$template = new Template();
			$template->name = 'role';
			$template->fieldgroup = $fieldgroupRole; 
			$template->flags = Template::flagSystem; 
			$template->slashUrls = 1; 
			$template->noGlobal = 1; 
			$template->noMove = 1; 
			$template->noChangeTemplate = 1; 
			$template->nameContentTab = 1; 
			$template->pageClass = 'Role';
			$template->altFilename = 'admin';
			$template->save();
			$this->li("Added new template 'role'"); 
		}
		$templateRole = $template;

		// fieldgroup: permission

		$fieldgroup = $this->fieldgroups->get('permission'); 
		if($fieldgroup && $fieldgroup->id) {
			$this->ok("Fieldgroup 'permission' already exists"); 
			if($fieldgroup->has("title")) {
				$this->ok("Fieldgroup 'permission' already has field 'title'"); 
			}  else {
				$fieldgroup->add($fieldTitle); 
				$fieldgroup->save();
				$this->li("Added field 'title' to fieldgroup 'permission'"); 
			}
		} else {
			$fieldgroup = new Fieldgroup();
			$fieldgroup->name = 'permission';
			$fieldgroup->add($fieldTitle); 
			$fieldgroup->save();
			$this->li("Added fieldgroup 'permission'"); 
		}
		$fieldgroupPermission = $fieldgroup;

		// template: permission

		$template = $this->templates->get('permission'); 
		if($template && $template->id) {
			$this->ok("Template 'permission' already exists."); 
			if($template->flags & Template::flagSystem) {
				$this->ok("Template 'permission' already has the system flag."); 
			} else {
				$this->li("Adding system flag to template 'permission'."); 
				$template->flags = $template->flags | Template::flagSystem; 
				$template->save();
			}
		} else {
			$template = new Template();
			$template->name = 'permission';
			$template->fieldgroup = $fieldgroupPermission; 
			$template->flags = Template::flagSystem; 
			$template->slashUrls = 1; 
			$template->noGlobal = 1; 
			$template->noMove = 1; 
			$template->noChangeTemplate = 1; 
			$template->nameContentTab = 1; 
			$template->pageClass = 'Permission';
			$template->altFilename = 'admin';
			$template->save();
			$this->li("Added new template 'permission'"); 
		}
		$templatePermission = $template;

	}

	/************************************************************************************************************
	 * Add new admin pages
	 *
	 */
	function addNewSystemPages() {

		// page: access

		$status = Page::statusOn | Page::statusLocked | Page::statusSystemID | Page::statusSystem; 

		$id = $this->accessPageID; 
		$page = $this->pages->get($id); 
		if($page->id) {
			$this->ok("Page {$page->path} already exists."); 
		} else {
			$sql = "INSERT INTO pages SET id=$id, name='access', status=$status, parent_id={$this->adminPageID}, templates_id={$this->adminTemplateID}, created=NOW(), sort=3";
			$result = $this->db->query($sql); 
			$page = $this->pages->get($id); 
			$this->li("Created page {$page->path}"); 
		}
		$page->name = "access";
		$page->title = "Access";
		$page->process = "ProcessList";
		$page->save();
		$pageAccess = $page; 

		// page: access/users

		$id = $this->accessUsersPageID; 
		$page = $this->pages->get($id); 
		if($page->id) {
			$this->ok("Page {$page->path} already exists."); 
		} else {
			$sql = "INSERT INTO pages SET id=$id, name='users', status=$status, parent_id={$this->accessPageID}, templates_id={$this->adminTemplateID}, created=NOW(), sort=0";
			$result = $this->db->query($sql); 
			$page = $this->pages->get($id); 
			$this->li("Added page '{$page->url}'"); 
		}
		$page->title = "Users";
		$page->process = "ProcessUser";
		$page->save();
		$pageUsers = $page; 

		// page: access/roles

		$id = $this->accessRolesPageID; 
		$page = $this->pages->get($id); 
		if($page->id) {
			$this->ok("Page {$page->path} already exists."); 
		} else {
			$sql = "INSERT INTO pages SET id=$id, name='roles', status=$status, parent_id={$this->accessPageID}, templates_id={$this->adminTemplateID}, created=NOW(), sort=1";
			$result = $this->db->query($sql); 
			$page = $this->pages->get($id); 
			$this->li("Added page '{$page->url}'"); 
		}
		$page->title = "Roles";
		$page->process = "ProcessRole";
		$page->save();
		$pageRoles = $page; 

		// page: access/permissions

		$id = $this->accessPermissionsPageID; 
		$page = $this->pages->get($id); 
		if($page->id) {
			$this->ok("Page {$page->path} already exists."); 
		} else {
			$sql = "INSERT INTO pages SET id=$id, name='permissions', status=$status, parent_id={$this->accessPageID}, templates_id={$this->adminTemplateID}, created=NOW(), sort=2";
			$result = $this->db->query($sql); 
			$page = $this->pages->get($id); 
			$this->li("Added page '{$page->url}'"); 
		}
		$page->title = "Permissions";
		$page->process = "ProcessPermission";
		$page->save();
		$pagePermissions = $page; 

	}

	/************************************************************************************************************
	 * Add new permissions pages
	 *
	 */
	function addNewPermissionPages() {

		$pagePermissions = $this->pages->get($this->accessPermissionsPageID); 
		$permissionTemplate = $this->templates->get("permission"); 

		$sort = 0; 
		foreach($this->newPermissions as $name) {
			$page = $this->pages->get("name=$name, template=permission"); 
			if($page->id) {
				$this->ok("Permission '$name' already exists."); 
				if($page->status & Page::statusSystemID) {
					$this->ok("Permission '$name' has system status"); 
				} else {
					$this->li("Adding system status to permission '$name'."); 
					$page->status = $page->status | Page::statusSystem | Page::statusSystemID; 
					$page->save();
				}

				if(!$page->title && isset($this->permissionTitles[$name])) {
					$page->title = $this->permissionTitles[$name]; 	
					$this->li("Added permission title: $name => {$page->title}"); 
					$page->save();
				}
			} else {
				$page = new Page();
				$page->name = $name; 
				$page->parent = $pagePermissions; 
				$page->template = $permissionTemplate;
				$page->sort = $sort;
				$page->status = $page->status | Page::statusSystem | Page::statusSystemID; 
				if(isset($this->permissionTitles[$name])) $page->title = $this->permissionTitles[$name];
				$page->save();
				$this->li("Added permission '$name'."); 
			}

			$sort++;
		}

	}

	/************************************************************************************************************
	 * Add new roles pages and give them the appropriate permissions
	 *
	 */
	function addNewRolesPages() {

		if(!$this->tableExists('roles')) {
			$this->ok("Table 'roles' no longer present"); 
		} else { 

			$result = $this->db->query("SELECT * FROM roles ORDER BY id"); 
			$permissions = $this->newPermissions; 

			while($role = $result->fetch_assoc()) {

				if($role['name'] == 'owner') continue; 
				if($role['name'] == 'guest') $role['id'] = $this->config->guestUserRolePageID;
				if($role['name'] == 'superuser') $role['id'] = $this->config->superUserRolePageID; 

				$r = $this->roles->get("$role[name]");

				if($r->id) {
					$this->ok("Role '$role[name]' already exists."); 
				} else {
					$this->li("Adding role '$role[name]'."); 
					if($role['name'] == 'guest' || $role['name'] == 'superuser') {
						$r = new Role();
						$r->id = $role['id'];
						$r->name = $role['name'];
						$r->save();
					} else {
						$r = $this->roles->add($role['name']); 
					}
					if(!$r || !$r->id) {
						$this->err("Unable to add role '$role[name]'"); 
						continue; 
					}
				}

				$result2 = $this->db->query("SELECT permissions.name AS permission_name FROM roles_permissions JOIN permissions ON permissions.id=roles_permissions.permissions_id WHERE roles_permissions.roles_id=$role[id]");

				while($permission = $result2->fetch_assoc()) {
					$name = $permission['permission_name'];
					if($name == 'ProcessPageView') continue; 
					if(!isset($permissions[$name])) {
						$this->ok("Skipping permission '$name' - not applicable in new system."); 
						continue; 
					}
					$name = $permissions[$name]; 
					$p = $r->permissions->get("name=$name");
					if($p && $p->id) {
						$this->ok("Permission '$name' already exists with role '$role[name]'."); 
					} else { 
						$this->li("Adding permission '$name' to role '$role[name]'.");
						$p = $this->permissions->get("name=$name"); 
						if($p && $p->id) $r->permissions->add($p); 		
					}
				}


				$r->save();
			}
		}

		$pageView = $this->permissions->get("name=page-view"); 
		foreach($this->roles as $r) { 
			$r->permissions->add($pageView); 
			$this->li("Added permission 'page-view' to role '{$r->name}'"); 
			$r->trackChange('permissions');
			$r->save();
		}

		/*
		// double check that these minimum required permissions are attached to the built in roles
		foreach(array('guest', 'superuser') as $name) {
			$role = $this->roles->get($name); 

			if(!$role->permissions->has('name=page-view')) {
				$permission = $this->permissions->get('page-view'); 
				$role->permissions->add($permission); 
				$role->save('permissions');
				$this->li("Added 'page-view' permission to role '$name'"); 
			} else {
				$this->ok("Role '$name' has permission 'page-view'"); 
			}

		}
		*/

		
	}


	/************************************************************************************************************
	 * Add new users pages and give them the appropriate roles
	 *
	 */
	function addNewUsersPages() {

		if(!$this->tableExists('users')) {
			$this->ok("Table 'users' no longer present."); 
		} else { 

			$result = $this->db->query("SELECT * FROM users ORDER BY id"); 

			while($user = $result->fetch_assoc()) {

				$name = $user['name']; 
				$u = $this->users->get($name); 

				if($u->id) {
					$this->ok("User '$name' already in system."); 
				} else {
					$this->li("Adding user '$name'"); 

					if($user['id'] == 1) {
						$u = new User();
						$u->id = $this->config->guestUserPageID;
						$u->name = 'guest';

					} else if($user['id'] == 2) { 
						$u = new User();
						$test = $this->users->get($this->config->superUserPageID); 
						if(!$test->id) $u->id = $this->config->superUserPageID; 
						$u->name = $name; 

					} else {
						$u = $this->users->add($name); 
						$u->name = $name; 
					}

					if($user['id'] > 1) { 
						$u->pass->salt = $user['salt']; 
						$u->pass->hash = $user['pass'];
					}
				}

				$result2 = $this->db->query("SELECT roles.name AS role_name FROM users_roles JOIN roles ON users_roles.roles_id=roles.id WHERE users_roles.users_id=$user[id]"); 

				while($role = $result2->fetch_assoc()) {
					$roleName = $role['role_name']; 
					$r = $u->roles->get("name=$roleName"); 
					if($r && $r->id) {
						$this->ok("Role '$roleName' already exists with user '$name'."); 
					} else {
						$r = $this->roles->get($roleName); 
						if($r && $r->id) {
							$this->li("Adding role '{$r->name}' to user '{$u->name}'."); 
							$u->roles->add($r); 
							$u->save('roles');
						}
						$u->trackChange('roles'); 
					}
				}
				$u->save();


			} // while	
		}


		// double check that these minimum roles are attached to the user
		foreach(array($this->config->guestUserPageID => 'guest', $this->config->superUserPageID => 'superuser') as $id => $name) {

			$user = $this->users->get($id); 

			if($user->hasRole('guest')) {
				$this->ok("User '$name' has role 'guest'"); 
			} else {
				$role = $this->roles->get("guest"); 
				$this->li("Adding role 'guest' to user '$name'"); 
				$user->roles->add($role); 
				$user->save('roles');
			}

			if($name == 'superuser') {
				if($user->hasRole('superuser')) {
					$this->ok("User '$name' has role 'superuser'"); 
				} else {
					$role = $this->roles->get("superuser"); 
					$this->li("Adding role 'superuser' to user '$name'"); 
					$user->roles->add($role); 
					$user->save('roles');
				}
			}

		}

	}

	/************************************************************************************************************
	 * Setup the pages_access table and template access settings
	 *
	 */
	function setupPagesAccess() {

		$guestRole = $this->roles->get('guest'); 
		$superuserRole = $this->roles->get("superuser"); 

		$homepage = $this->pages->get("/"); 
		$template = $homepage->template;
		if($template->roles->has($guestRole)) {
			$this->ok("Template '$template' already has role '{$guestRole->name}'"); 
		} else {
			$this->li("Adding role '{$guestRole->name}' to template '$template'"); 
			$template->roles->add($guestRole); 
			$template->trackChange('roles'); 
		}
		$template->useRoles = 1;
		$this->li('Building pages_access table'); 
		$template->save();

		$admin = $this->pages->get($this->config->adminRootPageID); 

		$template = $admin->template; 
		if($template->roles->has($superuserRole)) {
			$this->ok("Template '$template' already has role '{$superuserRole->name}'"); 
		} else {
			$this->li("Adding role '{$superuserRole->name}' to template '$template'"); 
			$template->roles->add($superuserRole); 
			$template->trackChange('roles');
		}
		$template->useRoles = 1; 
		$template->allowPageNum = 1; 
		$template->redirectLogin = $this->config->loginPageID; 
		$template->roles->remove($guestRole); 
		$template->save();
		
	}

	/************************************************************************************************************
	 * Check if there is a custom admin theme installed
	 *
	 */
	function checkTemplatesAdmin() {
		if(is_dir("./site/templates-admin/")) {
			$this->err("Found custom admin theme in /site/templates-admin/. Please rename or remove this temporarily (you can attempt to put it back after you upgrade)."); 
		}
	}

	/************************************************************************************************************
	 * Check that the versions of key files are correct
	 *
	 */
	function checkFileVersions() {

		$signature = "@version 2.1";

		$data = file_get_contents("./.htaccess"); 
		if(!strpos($data, $signature)) {
			$this->err("Your .htaccess file is not the correct version. Please ensure you have renamed the htaccess.txt file included with ProcessWire 2.1 to .htaccess"); 
		} else {
			$this->ok(".htaccess file is the correct version"); 
		}

		$data = file_get_contents("./index.php"); 
		if(!strpos($data, $signature)) {
			$this->err("Your /index.php file is not the correct version. Please ensure you have copied in the new /index.php file from ProcessWire 2.1."); 
		} else {
			$this->ok("index.php file is the correct version"); 
		}

	}

	/************************************************************************************************************
	 * Rename old tables that are no longer needed
	 *
	 */
	function renameOldTables() {
		$this->renameOldTable('users'); 
		$this->renameOldTable('roles'); 
		$this->renameOldTable('permissions'); 
		$this->renameOldTable('users_roles'); 
		$this->renameOldTable('roles_permissions'); 
	}

	function renameOldTable($name) {

		if($this->tableExists($name)) {
			$this->db->query("RENAME table $name TO old_v20_$name"); 
			$this->li("Renamed table '$name' to 'old_v20_$name' - you may delete this table from your database if/when you want to."); 
		} else {
			$this->ok("Table '$name' already renamed to 'old_v20_$name' - you may delete this table from your database if/when you want to."); 
		}
	}

	function tableExists($name) {
		$result = $this->db->query("SHOW TABLES LIKE '$name'"); 
		if($result && $result->num_rows) return true;
		return false;
	}

	function createProfilePage() {
		
		$admin = $this->pages->get($this->config->adminRootPageID); 
		$profile = $this->pages->get("parent=$admin, name=profile");

		if($profile && $profile->id) {
			$this->ok("Profile page already exists '{$profile->path}'"); 
			
		} else { 
			$profile = new Page();
			$profile->template = $this->templates->get("admin"); 
			$profile->parent = $admin;
			$profile->name = "profile";
			$profile->title = "Profile";
			$profile->sort = $admin->numChildren+2;
			$profile->status = Page::statusHidden;
			$profile->save();
			$this->li("Created '{$profile->path}'"); 
		}

		$process = $this->modules->get("ProcessProfile"); 

		if($profile->process != $process) { 
			$this->li("Setting profile process to be ProcessProfile"); 
			$profile->process = $process; 
			$profile->save();
		} else {
			$this->ok("Profile page process already set"); 
		}

		$configData = array('profileFields' => array('pass', 'email')); 
		$this->modules->saveModuleConfigData('ProcessProfile', $configData); 
		$this->modules->uninstall('ProcessLogout'); 
		
	}	

}

$upgrade = new upgradeProcessWire();
$upgrade->execute();
