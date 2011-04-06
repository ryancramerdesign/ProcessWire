<?php

define("PROCESSWIRE_INSTALL", 1); 
$title = "ProcessWire Upgrade";
$GLOBALS['numErrors'] = 0;

require("./wire/templates-admin/install-head.inc"); 
require("./index.php"); 

function err($str) { echo "\n<li class='ui-state-error'><span class='ui-icon ui-icon-alert'></span>$str</li>"; $GLOBALS['numErrors']++; }
function li($str) { echo "\n<li class='ui-state-highlight'><span class='ui-icon ui-icon-check'></span>$str</li>"; }
function ok($str) { echo "\n<li class='ui-state-highlight ui-state-disabled'><span class='ui-icon ui-icon-check'></span>$str</li>"; }

$wire->config->debug = true; 
error_reporting(E_ALL); 


function upgradePW($wire) { 

	/************************************************************************************************************
	 * Add new columns to necessary tables
	 *
	 */

	foreach(array('templates') as $table) {
		$result = $wire->db->query("SELECT * FROM $table LIMIT 1"); 
		$row = $result->fetch_assoc();
		if(!isset($row['flags'])) {
			li("Adding 'flags' column to '$table' table.");
			$wire->db->query("ALTER TABLE $table ADD flags UNSIGNED INT NOT NULL DEFAULT 0"); 
		} else {
			ok("Field 'flags' already present on '$table' table.");
		}
	}

	/************************************************************************************************************
	 * Update pages for new 'system' status
	 *
	 */

	$systemPageIDs = array(1, 
		$wire->config->trashPageID, 
		$wire->config->adminRootPageID,
		$wire->config->http404PageID,
		$wire->config->loginPageID,
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
			$page = $wire->pages->get($id); 
			$status = $pageIDs == 'systemPageIDs' ? Page::statusSystem : Page::statusSystem2;
			if(!$page->id) {
				err("Unable to load page ID $id"); 
				continue; 
			}
			if($page->status & $status) {
				ok("Page '{$page->path}' already has system status.");
				continue; 
			}
			li("Adding 'system' status to page '{$page->path}'.");
			$page->status = $page->status | $status;
			$page->save();
		}
	}


	/************************************************************************************************************
	 * Remove existing 'access' section
	 *
	 */

	$removePageIDs = array(25, 26, 5); // users, roles, access
	foreach($removePageIDs as $id) {
		$page = $wire->pages->get($id); 
		if($page->id) { 
			li("Removed page ID $id: {$page->path}"); 
			$page->delete();
		} else {
			ok("Page ID $id already removed."); 
		}
	}



	/************************************************************************************************************
	 * Make existing admin template a system template
	 *
	 */

	$template = $wire->templates->get('admin'); 
	if($template->flags & Template::flagSystem) {
		ok("Template 'admin' already has system status."); 	
	} else {
		$template->flags = $template->flags | Template::flagSystem; 
		$template->save(); 
		li("Added 'system' status to template 'admin'."); 
	}


	/************************************************************************************************************
	 * Make existing 'title' and 'process' fields system fields
	 *
	 */

	$field = $wire->fields->get('title'); 
	if($field && $field->id) {
		if($field->flags & Field::flagSystem) {
			ok("Field 'title' already has the system flag."); 
		} else {
			$field->flags = $field->flags | Field::flagSystem;
			$field->save();
			li("Added 'system' flag to field 'title'"); 
		}
	} else {
		err("Your system is missing a required 'title' field."); 
		return false;
	}
	$fieldTitle = $field;

	$field = $wire->fields->get('process'); 
	if($field && $field->id) {
		if($field->flags & Field::flagSystem) {
			ok("Field 'process' already has the system flag."); 
		} else {
			$field->flags = $field->flags | Field::flagSystem;
			$field->save();
			li("Added 'system' flag to field 'process'"); 
		}
	} else {
		err("Your system is missing a required 'process' field."); 
		return false;
	}
	$fieldProcess = $field;

	/************************************************************************************************************
	 * Add new user system fields
	 *
	 */

	$field = $wire->fields->get('roles'); 
	if($field && $field->id) { 
		ok("Your system already has the 'roles' field"); 
		if($field->type != 'FieldtypePage' || $field->parent_id != 30) {
			err("You already have a field named 'roles' and the new PW version needs that name."); 
			return false;
		}

		if(($field->flags & Field::flagSystem) && ($field->flags & Field::flagPermanent)) {
			ok("Field 'roles' already has the permanent flag."); 
		} else {
			$field->flags = Field::flagSystem | Field::flagPermanent;
			$field->save();
			li("Added 'permanent' flag to field 'roles'"); 
		}
	} else { 
		$field = new Field();
		$field->name = 'roles';	
		$field->type = $wire->modules->get('FieldtypePage'); 
		$field->label = "Roles";
		$field->parent_id = 30; 
		$field->inputfield = 'InputfieldCheckboxes';
		$field->flags = Field::flagSystem | Field::flagPermanent; 
		$field->save();
		li("Added new system field 'roles'"); 
	}
	$fieldRoles = $field;

	$field = $wire->fields->get('permissions'); 
	if($field && $field->id) { 
		ok("Your system already has the 'permissions' field"); 
		if($field->type != 'FieldtypePage' || $field->parent_id != 31) {
			err("You already have a field named 'permissions' and the new PW version needs that name."); 
			return false;
		}

		if(($field->flags & Field::flagSystem) && ($field->flags & Field::flagPermanent)) {
			ok("Field 'permissions' already has the permanent flag."); 
		} else {
			$field->flags = Field::flagSystem | Field::flagPermanent;
			$field->save();
			li("Added 'permanent' flag to field 'permissions'"); 
		}
	} else { 
		$field = new Field();
		$field->name = 'permissions';	
		$field->type = $wire->modules->get('FieldtypePage'); 
		$field->label = "Permissions";
		$field->parent_id = 31; 
		$field->inputfield = 'InputfieldCheckboxes';
		$field->flags = Field::flagSystem | Field::flagPermanent; 
		$field->save();
		li("Added new system field 'permissions'"); 
	}
	$fieldPermissions = $field;

	$field = $wire->fields->get('pass'); 
	if($field && $field->id) { 
		ok("Your system already has the 'pass' field"); 
		if($field->type != 'FieldtypePassword') {
			err("You already have a field named 'pass' and the new PW version needs that name."); 
			return false;
		}

		if(($field->flags & Field::flagSystem) && ($field->flags & Field::flagPermanent)) {
			ok("Field 'pass' already has the permanent flag."); 
		} else {
			$field->flags = Field::flagSystem | Field::flagPermanent;
			$field->save();
			li("Added 'permanent' flag to field 'pass'"); 
		}
	} else { 
		$field = new Field();
		$field->name = 'pass';	
		$field->type = $wire->modules->get('FieldtypePassword'); 
		$field->label = "Password";
		$field->flags = Field::flagSystem | Field::flagPermanent; 
		$field->save();
		li("Added new system field 'pass'"); 
	}
	$fieldPass = $field;


	/************************************************************************************************************
	 * Add new system fieldgroups and templates: system_user, system_role, system_permission, system_login
	 *
	 */

	// fieldgroup: system_user

	$fieldgroup = $wire->fieldgroups->get('system_user'); 
	if($fieldgroup && $fieldgroup->id) {
		ok("Fieldgroup 'system_user' already exists"); 
		if($fieldgroup->has("pass")) {
			ok("Fieldgroup 'system_user' already has field 'pass'"); 
		}  else {
			$fieldgroup->add($fieldPass); 
			li("Added field 'pass' to fieldgroup 'system_user'"); 
		}
	} else {
		$fieldgroup = new Fieldgroup();
		$fieldgroup->name = 'system_user';
		$fieldgroup->add($fieldPass); 
		$fieldgroup->save();
		li("Added fieldgroup 'system_user'"); 
	}
	$fieldgroupUser = $fieldgroup;

	// template: system_user

	$template = $wire->templates->get('system_user'); 
	if($template && $template->id) {
		ok("Template 'system_user' already exists."); 
		if($template->flags & Template::flagSystem) {
			ok("Template 'system_user' already has the system flag."); 
		} else {
			li("Adding system flag to template 'system_user'."); 
			$template->flags = $template->flags | Template::flagSystem; 
			$template->save();
		}
	} else {
		$template = new Template();
		$template->name = 'system_user';
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
		li("Added new template 'system_user'"); 
	}
	$templateUser = $template;

	// fieldgroup: system_role

	$fieldgroup = $wire->fieldgroups->get('system_role'); 
	if($fieldgroup && $fieldgroup->id) {
		ok("Fieldgroup 'system_role' already exists"); 
		if($fieldgroup->has("permissions")) {
			ok("Fieldgroup 'system_role' already has field 'permissions'"); 
		}  else {
			$fieldgroup->add($fieldPermissions); 
			$fieldgroup->save();
			li("Added field 'permissions' to fieldgroup 'system_role'"); 
		}
	} else {
		$fieldgroup = new Fieldgroup();
		$fieldgroup->name = 'system_role';
		$fieldgroup->add($fieldPermissions); 
		$fieldgroup->save();
		li("Added fieldgroup 'system_role'"); 
	}
	$fieldgroupRole = $fieldgroup;

	// template: system_role

	$template = $wire->templates->get('system_role'); 
	if($template && $template->id) {
		ok("Template 'system_role' already exists."); 
		if($template->flags & Template::flagSystem) {
			ok("Template 'system_role' already has the system flag."); 
		} else {
			li("Adding system flag to template 'system_role'."); 
			$template->flags = $template->flags | Template::flagSystem; 
			$template->save();
		}
	} else {
		$template = new Template();
		$template->name = 'system_role';
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
		li("Added new template 'system_role'"); 
	}
	$templateRole = $template;

	// fieldgroup: system_permission

	$fieldgroup = $wire->fieldgroups->get('system_permission'); 
	if($fieldgroup && $fieldgroup->id) {
		ok("Fieldgroup 'system_permission' already exists"); 
	} else {
		$fieldgroup = new Fieldgroup();
		$fieldgroup->name = 'system_permission';
		$fieldgroup->save();
		li("Added fieldgroup 'system_permission'"); 
	}
	$fieldgroupPermission = $fieldgroup;

	// template: system_permission

	$template = $wire->templates->get('system_permission'); 
	if($template && $template->id) {
		ok("Template 'system_permission' already exists."); 
		if($template->flags & Template::flagSystem) {
			ok("Template 'system_permission' already has the system flag."); 
		} else {
			li("Adding system flag to template 'system_permission'."); 
			$template->flags = $template->flags | Template::flagSystem; 
			$template->save();
		}
	} else {
		$template = new Template();
		$template->name = 'system_permission';
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
		li("Added new template 'system_permission'"); 
	}
	$templatePermission = $template;

	// fieldgroup: system_login

	$fieldgroup = $wire->fieldgroups->get('system_login'); 
	if($fieldgroup && $fieldgroup->id) {
		ok("Fieldgroup 'system_login' already exists"); 
	} else {
		$fieldgroup = new Fieldgroup();
		$fieldgroup->name = 'system_login';
		$fieldgroup->save();
		li("Added fieldgroup 'system_login'"); 
	}
	if($fieldgroup->has("title")) {
		ok("Fieldgroup 'system_login' has field 'title'"); 	
	} else {
		li("Adding field 'title' to template 'system_login'"); 	
		$fieldgroup->add($wire->fields->get('title')); 
		$fieldgroup->save();
	}
	if($fieldgroup->has("process")) {
		ok("Fieldgroup 'system_login' has field 'process'"); 	
	} else {
		li("Adding field 'process' to template 'system_login'"); 	
		$fieldgroup->add($wire->fields->get('process')); 
		$fieldgroup->save();
	}
	$fieldgroupLogin = $fieldgroup;

	// template: system_login

	$template = $wire->templates->get('system_login'); 
	if($template && $template->id) {
		ok("Template 'system_login' already exists."); 
		if($template->flags & Template::flagSystem) {
			ok("Template 'system_login' already has the system flag."); 
		} else {
			li("Adding system flag to template 'system_login'."); 
			$template->flags = $template->flags | Template::flagSystem; 
			$template->save();
		}
	} else {
		$template = new Template();
		$template->name = 'system_login';
		$template->fieldgroup = $fieldgroupLogin; 
		$template->flags = Template::flagSystem; 
		$template->slashUrls = 1; 
		$template->noChangeTemplate = 1; 
		$template->altFilename = 'admin';
		$template->save();
		li("Added new template 'system_login'"); 
	}
	$templatePermission = $template;

	/************************************************************************************************************
	 * Add new admin pages
	 *
	 */

	// page: access

	$status = Page::statusOn | Page::statusLocked | Page::statusSystem | Page::statusSystem2; 

	$id = 28; 
	$page = $wire->pages->get($id); 
	if($page->id) {
		ok("Page {$page->path} already exists."); 
	} else {
		$sql = "INSERT INTO pages SET id=$id, name='access', status=$status, parent_id=2, templates_id=2, created=NOW(), sort=3";
		$result = $wire->db->query($sql); 
		$page = $pages->get($id); 
		li("Created page {$page->path}"); 
	}
	$page->name = "access";
	$page->title = "Access";
	$page->process = "ProcessList";
	$page->save();
	$pageAccess = $page; 

	// page: access/users

	$id = 29; 
	$page = $wire->pages->get($id); 
	if($page->id) {
		ok("Page {$page->path} already exists."); 
	} else {
		$sql = "INSERT INTO pages SET id=$id, name='users', status=$status, parent_id=28, templates_id=2, created=NOW(), sort=0";
		$result = $wire->db->query($sql); 
		$page = $pages->get($id); 
		li("Added page '{$page->url}'"); 
	}
	$page->title = "Users";
	$page->process = "ProcessUser";
	$page->save();
	$pageUsers = $page; 

	// page: access/roles

	$id = 30; 
	$page = $wire->pages->get($id); 
	if($page->id) {
		ok("Page {$page->path} already exists."); 
	} else {
		$sql = "INSERT INTO pages SET id=$id, name='roles', status=$status, parent_id=28, templates_id=2, created=NOW(), sort=1";
		$result = $wire->db->query($sql); 
		$page = $pages->get($id); 
		li("Added page '{$page->url}'"); 
	}
	$page->title = "Roles";
	$page->process = "ProcessRole";
	$page->save();
	$pageRoles = $page; 

	// page: access/permissions

	$id = 31; 
	$page = $wire->pages->get($id); 
	if($page->id) {
		ok("Page {$page->path} already exists."); 
	} else {
		$sql = "INSERT INTO pages SET id=$id, name='permissions', status=$status, parent_id=28, templates_id=2, created=NOW(), sort=2";
		$result = $wire->db->query($sql); 
		$page = $pages->get($id); 
		li("Added page '{$page->url}'"); 
	}
	$page->title = "Permissions";
	$page->process = "ProcessPermission";
	$page->save();
	$pagePermissions = $page; 

	/************************************************************************************************************
	 * Add new permissions pages
	 *
	 */

	$permissions = array(
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
		); 

	$sort = 0; 
	foreach($permissions as $name) {
		$page = $wire->pages->get("name=$name, template=system_permission, parent=$pagePermissions"); 
		if($page->id) {
			ok("Permission '$name' already exists."); 
			if($page->status & Page::statusSystem) {
				ok("Permission '$name' has system status"); 
			} else {
				li("Adding system status to permission '$name'."); 
				$page->status = $page->status | Page::statusSystem | Page::statusSystem2; 
				$page->save();
			}
		} else {
			$page = new Page();
			$page->name = $name; 
			$page->parent = $pagePermissions; 
			$page->template = $templatePermission; 
			$page->sort = $sort;
			$page->save();
			$page->status = $page->status | Page::statusSystem | Page::statusSystem2; 
			li("Added permission '$name'."); 
		}

		$sort++;
	}



	/************************************************************************************************************
	 * Add new roles pages and give them the appropriate permissions
	 *
	 */

	$result = $wire->db->query("SELECT * FROM roles ORDER BY id"); 

	while($role = $result->fetch_assoc()) {

		if($role['name'] == 'owner') continue; 

		$r = $wire->roles->get("name=$role[name]");

		if($r->id) {
			ok("Role '$role[name]' already exists."); 
		} else {
			li("Adding role '$role[name]'."); 
			$r = $wire->roles->add($role['name']); 
			if(!$r || !$r->id) {
				err("Unable to add role '$role[name]'"); 
				continue; 
			}
		}

		$result2 = $wire->db->query("SELECT permissions.name AS permission_name FROM roles_permissions JOIN permissions ON permissions.id=roles_permissions.permissions_id WHERE roles_permissions.roles_id=$role[id]");

		while($permission = $result2->fetch_assoc()) {
			$name = $permission['permission_name'];
			if(!isset($permissions[$name])) {
				ok("Skipping permission '$name' - not applicable in new system."); 
				continue; 
			}
			$name = $permissions[$name]; 
			$p = $r->permissions->get("name=$name");
			if($p && $p->id) {
				ok("Permission '$name' already exists with role '$role[name]'."); 
			} else { 
				li("Adding permission '$name' to role '$role[name]'.");
				$p = $wire->permissions->get("name=$name"); 
				if($p && $p->id) $r->permissions->add($p); 		
			}
		}

		$r->save();
	}
	

	/************************************************************************************************************
	 * Add new users pages and give them the appropriate roles
	 *
	 */

	$result = $wire->db->query("SELECT * FROM users ORDER BY id"); 

	while($user = $result->fetch_assoc()) {

		$name = $user['name']; 
		$u = $wire->users->get($name); 

		if($u->id) {
			ok("User '$name' already in system."); 
		} else {
			li("Adding user '$name'"); 

			if($user['id'] == 1) {
				$u = new User();
				$u->id = $wire->config->guestUserPageID;
				$u->name = 'guest';

			} else if($user['id'] == 2) { 
				$u = new User();
				$test = $wire->users->get($wire->config->superUserPageID); 
				if(!$test->id) $u->id = $wire->config->superUserPageID; 
				$u->name = $name; 

			} else {
				$u = $wire->users->add($name); 
				$u->name = $name; 
			}

			if($user['id'] > 1) { 
				$u->pass->salt = $user['salt']; 
				$u->pass->hash = $user['pass'];
			}
		}

		$result2 = $wire->db->query("SELECT roles.name AS role_name FROM users_roles JOIN roles ON users_roles.roles_id=roles.id WHERE users_roles.users_id=$user[id]"); 

		while($role = $result2->fetch_assoc()) {
			$roleName = $role['role_name']; 
			$r = $u->roles->get("name=$roleName"); 
			if($r && $r->id) {
				ok("Role '$roleName' already exists with user '$name'."); 
			} else {
				li("Adding role '$roleName' to user '$name'."); 
				$r = $wire->roles->get($roleName); 
				if($r && $r->id) $u->roles->add($r); 
			}
		}

		$u->save();
	}	

	return true; 

}

echo "<ul>";
if(upgradePW($wire)) li("Upgrade successful"); 
	else err("Upgrade aborted"); 
echo "</ul>";

require("./wire/templates-admin/install-foot.inc"); 


