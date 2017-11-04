<?php

/**
 * Primary class used to load the WP Invoice theme.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @package WP Invoice
 * @since WP Invoice 1.0
 */
class WP_Invoice_Setup extends WP_Invoice_Core {

	/**
	 * Constructor.
	 * Add methods to appropriate hooks and filters.
	 *
	 * @global  int  $content_width  Sets the media widths (unfortunately required as a global due to WordPress core requirements) 
	 */
	public function __construct() {

		add_action( 'template_redirect',  array( $this, 'redirect' ) );
		show_admin_bar( false );

		// Add action hooks
		add_action( 'after_setup_theme',  array( $this, 'theme_setup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'stylesheet' ) );
	}

	public function redirect() {

		// Redirect if not logged in
		if ( ! is_user_logged_in() || is_404() ) {
//			wp_redirect( 'https://geek.hellyer.kiwi', 302 );
		}

	}

	/**
	 * Load stylesheet.
	 */
	public function stylesheet() {
		if ( ! is_admin() ) {
			wp_enqueue_style( self::THEME_NAME, get_stylesheet_directory_uri() . '/style.css', array(), self::VERSION_NUMBER );
		}
	}

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 */
	public function theme_setup() {

		// Add title tags
		add_theme_support( 'title-tag' );

	}

}
