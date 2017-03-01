<?php
	session_start();
	// JSON Connector
	// Matthias Stahl, TU Muenchen
	// Version 0.1
	// December 2014
	
	$json_output = json_encode($_SESSION['results_json']);
	header('Content-Type: application/json');
	echo $json_output;
?>