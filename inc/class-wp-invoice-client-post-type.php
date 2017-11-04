<?php

/**
 * Register post-type.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @since 1.0
 */
class WP_Invoice_Client_Post_Type extends WP_Invoice_Core {

	/*
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init',           array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_entries_metabox' ) );
	}

	/**
	 ** Register post-type.
	 */
	public function register_post_type() {

		$args = array(
			'public'             => true,
			'publicly_queryable' => false,
			'label'              => __( 'Client', 'wp-invoice' ),
			'supports'           => array(
				'title',
			)
		);
		register_post_type( 'client', $args );

	}

	/**
	 ** Add entries meta box.
	 */
	public function add_entries_metabox() {

		add_meta_box(
			'entries', // ID
			__( 'Latest entries', 'wp-invoice' ), // Title
			array(
				$this,
				'entries_meta_box', // Callback to method to display HTML
			),
			'client', // Post type
			'advanced', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);

	}

	/**
	 ** Entries meta box.
	 */
	public function entries_meta_box() {

		?>

		<table>
			<thead>
				<tr>
					<th>Title</th>
					<th>Date</th>
				</tr>
			</thead><?php



			?>
			<tfoot>
				<tr>
					<th>Title</th>
					<th>Date</th>
				</tr>
			</tfoot>
		</table><?php
	}

}
