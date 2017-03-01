<?php
	session_start();
	
	if ($_GET['request'] == "all") {
		$json = $_SESSION;
	} else {
		$json = $_SESSION[$_GET['request']];
	}

	$json = json_encode($json);
	header('Content-Type: application/json');
	echo($json);
?>