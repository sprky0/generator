<?php

include("templates/form.html.php");

if (isset($_GET['name'])) {

	$name = strip_tags($_GET['name']);
	include("templates/image.html.php");

}
