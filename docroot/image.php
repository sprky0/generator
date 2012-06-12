<?php

include("app/generator.php");

if (isset($_GET['name'])) {

	$name = $_GET['name'];

	$w = isset($_GET['w']) ? (int) $_GET['w'] : 512;
	$h = isset($_GET['h']) ? (int) $_GET['h'] : 512;

	try {

		$generator = new generator($name);
		$generator->output($w, $h);
		
	} catch (Exception $e) {

		echo $e;
		
	}

} else {

	header("HTTP/1.1 406 Missing parameter name");

}
