<?php
// Global php functions for Protein|Clpper
// Matthias Stahl, TU Muenchen
// Version 0.1
// December 2014

// Some constants
define('ROOT', 'http://localhost/clpp_digest');


// This function is only for debugging and prompts messages on the browser console via javascript
function debug_to_console( $data ) {
	$output = '';

	// new and smaller version, easier to maintain
	$output .= 'console.info( \'Debug in Console via Debug Objects Plugin:\' );';
	$output .= 'console.log(' . json_encode( $data ) . ');';

	echo '';
}
?>