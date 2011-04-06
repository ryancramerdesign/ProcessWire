
	<div id="debug">

		<div class="container">
			<h3><a href='#'>Pages Loaded</a></h3>
			<div>
			<ul>
			<?php 
			foreach($pages->getCache() as $p) {
				echo "\n<li>" . $p->url . "</li>";
			}
			?>
			</ul>
			</div>
		</div>

		<div class="container">
			<h3><a href='#'>Session</a></h3>
			<div><?php print_r($_SESSION); ?></div>
		</div>

		<div class="container">
			<h3><a href='#'>Modules Loaded</a></h3>
			<div>
			<ol>
			<?php foreach($modules as $module) if(!$module instanceof ModulePlaceholder) echo "\n<li>" . $module->className() . ": " . get_class($module) . "</li>"; ?>
			</ol>
			</div>
		</div>

		<div class="container">
			<h3><a href='#'>Queries</a></h3>
			<div>
			<ol>
			<?php foreach($db->getQueryLog() as $n => $sql) echo "\n<li>$n. $sql</li>"; ?>
			</ol>
			</div>
		</div>

		<div class="container">
			<h3><a href='#'>Fuel</a></h3>
			<div>
			<ol>
			<?php foreach($fuel as $key => $value) echo "\n<li>$key</li>"; ?>
			</ol>
			</div>
		</div>

		<div class="container">
			<h3><a href='#'>User (<?php echo $user->name;?>)</a></h3>
			<div>
			<h4>Current User's Roles</h4>
			<ol>
			<?php foreach($user->roles as $role) echo "\n<li>{$role->name}</li>"; ?>
			</ol>
			<h4>Current User's Permissions</h4>
			<ol>
			<?php foreach($user->getPermissions() as $permission) echo "\n<li>{$permission->name}</li>"; ?>
			</ol>
			<h4>Current User's Permissions on this page</h4>
			<ol>
			<?php foreach($user->getPermissions($page) as $permission) echo "\n<li>{$permission->name}</li>"; ?>
			</ol>
			</div>
		</div>

		<div class="container">
			<h3><a href='#'>$_POST</a></h3>
			<pre>
			<?php echo htmlspecialchars(print_r($_POST, true));  ?>
			</pre>
		</div>

	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			$("#debug").accordion({ 
				header: 'h3',
				autoHeight: false
			 }); 
		}); 
	</script>

