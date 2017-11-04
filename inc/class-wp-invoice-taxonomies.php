<?php

/**
 * Register taxonomies.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @since 1.0
 */
class WP_Invoice_Taxonomies extends WP_Invoice_Core {

	/*
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 ** Register taxonomies.
	 */
	public function register_taxonomies() {
/*
		register_taxonomy(
			'task',
			array( 'client', 'entry' ),
			array(
				'label'        => __( 'Task', 'wp-invoice' ),
				'hierarchical' => false,
				'public'       => false,
				'show_ui'      => true,
			)
		);
*/
	}

}
