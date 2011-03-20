<?php

if(!empty($_POST['submit'])) {

	echo "<html>\n<body style='width: 400px; margin: 2em auto; font-family: Arial;'>";

	if(!empty($_POST['cities'])) {

		echo "\n<p><strong>You selected the following cities:</strong></p>\n<ul>";

		foreach($_POST['cities'] as $city) {

			// exclude any items with chars we don't want, just in case someone is playing
			if(!preg_match('/^[-A-Z0-9\., ]+$/iD', $city)) continue; 

			// print the city
			echo "\n\t<li>" . htmlspecialchars($city) . "</li>";
		}

		echo "\n</ul>";

	} else {
		echo "\n<p>No items selected</p>";
	}

	echo "\n<p><a href='example1.html'>Try Again?</a></p>"; 

	echo "\n</body>\n</html>";

} else {
	// if someone arrived here not having started at example.html
	// then show example.html instead
	require("example1.html"); 	

}

