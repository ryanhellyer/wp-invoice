<?php

/**
 * Autoload the classes.
 * Includes the classes, and automatically instantiates them via spl_autoload_register().
 *
 * @param  string  $class  The class being instantiated
 */
function autoload_fli( $class ) {

	// Bail out if not loading an appropriate class
	if ( 'WP_Invoice_' != substr( $class, 0, 11 ) ) {
		return;
	}

	// Convert from the class name, to the classes file name
	$file_data = strtolower( $class );
	$file_data = str_replace( '_', '-', $file_data );
	$file_name = 'class-' . $file_data . '.php';

	// Get the classes file path
	$path = get_template_directory() . '/inc/' . $file_name;

	// Include the class (spl_autoload_register will automatically instantiate it for us)
	require( $path );
}
spl_autoload_register( 'autoload_fli' );

new WP_Invoice_Entry_Post_Type;
new WP_Invoice_Invoice_Post_Type;
new WP_Invoice_Client_Post_Type;
new WP_Invoice_Taxonomies;
new WP_Invoice_Admin;
new WP_Invoice_User_Meta;
new WP_Invoice_Cron;
new WP_Invoice_Theme_Loader;
