<?php include("./_head.php"); // include header markup ?>

<div id='content'>

	<?php 

	// output 'headline' if available, otherwise 'title'
	echo "<h1>" . $page->get('headline|title') . "</h1>";

	// output bodycopy
	echo $page->body; 

	// if the page has visible children, output navigation to child pages 
	if($page->hasChildren) {

		// start the navigation list
		echo "<ul class='nav'>";

		// cycle through all the children
		foreach($page->children as $item) {

			// output markup for the list item...
			if($item->id == $page->id) {
				// current item is the same as the page being viewed
				echo "<li class='current'>"; 
			} else {
				// current item is another page
				echo "<li>";
			}

			// output the link markup
			echo "<a href='$item->url'>$item->title</a>";
			// output the page's summary text
			echo "<div class='summary'>$item->summary</div>";
			// end the list item
			echo "</li>";
		}

		// end the navigation list
		echo "</ul>";
	}

	?>

</div><!-- end content -->

<div id='sidebar'>

	<?php

	if($page->path == '/') { 

		// HOMEPAGE
		// if there are images here, show one randomly

		if(count($page->images)) {

			// if the page has images on it, grab one of them randomly... 
			$image = $page->images->getRandom();
			// resize it to 400 pixels wide
			$image = $image->width(400); 
			// output the image at the top of the sidebar...
			echo "<img src='$image->url' alt='$image->description' />";
		}

	} else {

		// OTHER PAGE
		// we'll show sidebar navigation instead...

		// rootParent is the parent page closest to the homepage
		// you can think of this as the "section" that the user is in
		// so we'll assign it to a $section variable for clarity
		$section = $page->rootParent; 

		// if there's more than 1 page in this section...
		if($section->hasChildren > 1) {
			// output sidebar navigation
			// see _init.php for the renderNavTree function
			echo renderNavTree($section);
		}
		
	}

	// output sidebar text if the page has it
	echo $page->sidebar; 

	?>

</div><!-- end sidebar -->

<?php include("./_foot.php"); // include footer markup ?>

