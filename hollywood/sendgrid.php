<?php
	// Save the archived video OpenTok ID 
	$fn = "temp.txt";
	$fh = fopen($fn, 'w+') or die("can't open file");
	$s = $_GET['id'];
	fwrite($fh, $s);
	fclose($fh);
?>