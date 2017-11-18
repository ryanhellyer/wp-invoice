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

		add_action( 'add_meta_boxes', array( $this, 'add_client_info_metabox' ) );
		add_action( 'save_post',      array( $this, 'save_post' ), 10, 2 );
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

	/**
	 * Add invoice date metabox.
	 */
	public function add_client_info_metabox() {
		add_meta_box(
			'client-info', // ID
			__( 'Client information', 'wp-invoice' ), // Title
			array(
				$this,
				'client_info_metabox', // Callback to method to display HTML
			),
			'client', // Post type
			'side', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Output the meta box.
	 */
	public function client_info_metabox() {

		$invoice_id = get_the_ID();

		$client_description = get_post_meta( $invoice_id, '_client_description', true );
		$client_website     = get_post_meta( $invoice_id, '_client_website', true );

		?>

		<p>
			<label for="_client_description"><?php _e( 'Client description', 'wp-invoice' ); ?></label>
			<br />
			<input type="text" name="_client_description" id="_client_description" value="<?php echo esc_attr( $client_description ); ?>" />
		</p>

		<p>
			<label for="_client_website"><?php _e( 'Client website', 'wp-invoice' ); ?></label>
			<br />
			<input type="text" name="_client_website" id="_client_website" value="<?php echo esc_attr( $client_website ); ?>" />
		</p>

		<input type="hidden" id="client-info-nonce" name="client-info-nonce" value="<?php echo esc_attr( wp_create_nonce( __FILE__ ) ); ?>">

		<?php
	}

	/**
	 * Save invoice date meta box data.
	 *
	 * @param  int     $post_id  The post ID
	 * @param  object  $post     The post object
	 */
	public function save_post( $invoice_id, $post ) {

		// Only save if correct post data sent
		if ( isset( $_POST['_client_description'] ) && isset( $_POST['_client_website'] ) ) {

			// Do nonce security check
			if ( ! wp_verify_nonce( $_POST['client-info-nonce'], __FILE__ ) ) {
				return $invoice_id;
			}

			$client_description = wp_kses_post( $_POST['_client_description'] );
			$client_website     = wp_kses_post( $_POST['_client_website'] );

			update_post_meta( $invoice_id, '_client_description', $client_description );
			update_post_meta( $invoice_id, '_client_website', $client_website );

		}

	}

}
