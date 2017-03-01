<?php
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Protein|Clpper</title>
	
	<link rel="stylesheet" href="reset.css" type="text/css" />
	<link rel="stylesheet" href="style.css" type="text/css" />
	
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js" type="text/javascript"></script>
	<script src="jquery.canvasjs.min.js" type="text/javascript"></script>
	<script src="functions.js" type="text/javascript"></script>
</head>

<body>
	<?php require('functions.php'); ?>
	<div id="header">
		<div id="header_bow">
			<div id="header_content">
				<div id="title_box">
					<a href="index.php"><h1>Protein<span class="red">|</span>Clpper</h1></a>
					<h3 class="title">The Protein Cleavage Analysis Tool</h3>
				</div>
			</div>
		</div>
	</div><!-- header -->
	<div id="wrapper">
		<div id="main">