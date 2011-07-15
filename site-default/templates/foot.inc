				
				<?php 

				// Output navigation for any children below the bodycopy.
				// This navigation cycles through the page's children and prints
				// a link and summary: 

				if($page->numChildren) {

					echo '<ul class="nav">';

					foreach($page->children as $child) {
						echo "<li><p><a href=\"{$child->url}\">{$child->title}</a><br /><span class=\"summary\">{$child->summary}</span></p></li>"; 
					}

					echo "</ul>";
				}

				?>


			</div><!--/bodycopy-->

		</div><!--/container-->

	</div><!--/content-->

	<div id="footer" class="footer">
		<div class="container">
			<p>&copy; <?php echo date("Y"); ?> <a href="http://www.ryancramer.com">Ryan Cramer Design, LLC</a></p>
		</div>
	</div>

	<?php 

	// If the page is editable, then output a link that takes us straight to the page edit screen:
	if($page->editable()) {
		echo "<div><a class=\"nav\" id=\"editpage\" href=\"{$config->urls->admin}page/edit/?id={$page->id}\">Edit</a></div>"; 
	}

	?>

</body>
</html>
