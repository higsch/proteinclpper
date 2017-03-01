<?php
	session_start();
	
	// Set session variable from POST request
	$_SESSION[$_POST['request']][$_POST['fieldname']] = $_POST['value'];
?>