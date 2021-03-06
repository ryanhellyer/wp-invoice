<?php

/**
 * Register post-type.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @since 1.0
 */
class WP_Invoice_Entry_Post_Type extends WP_Invoice_Core {

	/*
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init',           array( $this, 'register_post_type' ) );

		// Time meta box
		add_action( 'add_meta_boxes', array( $this, 'add_time_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_time_save' ), 10, 2 );

		// Client meta box
		add_action( 'add_meta_boxes', array( $this, 'add_client_metabox' ) );

	}

	/**
	 ** Register post-type.
	 */
	public function register_post_type() {

		$args = array(
			'public'             => true,
			'publicly_queryable' => true,
			'label'              => __( 'Entry', 'wp-invoice' ),
			'supports'           => array(
				'title',
			)
		);
		register_post_type( 'entry', $args );
	}

	/**
	 * Add client metabox.
	 */
	public function add_client_metabox() {
		add_meta_box(
			'client', // ID
			__( 'Client', 'wp-invoice' ), // Title
			array(
				$this,
				'client_meta_box', // Callback to method to display HTML
			),
			'entry', // Post type
			'advanced', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Output the time meta box.
	 */
	public function client_meta_box() {

		$client_id = wp_get_post_parent_id( get_the_ID() );
		?>

		<p>
			<a href="<?php echo esc_url( get_edit_post_link( $client_id ) ); ?>">
				<?php echo esc_html( get_the_title( $client_id ) ); ?>
			</a>
		</p><?php
	}

	/**
	 * Add time metabox.
	 */
	public function add_time_metabox() {
		add_meta_box(
			'entry-time', // ID
			__( 'Invoice date range', 'wp-invoice' ), // Title
			array(
				$this,
				'time_meta_box', // Callback to method to display HTML
			),
			'entry', // Post type
			'side', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Output the time meta box.
	 */
	public function time_meta_box() {

		$start_date_timestamp = get_post_meta( get_the_ID(), '_start_date', true );
		if ( '' !== $start_date_timestamp ) {
			$start_date = date( 'Y-m-d\TH:i:s', (int) $start_date_timestamp );
		} else {
			$start_date = date( 'Y-m-d\TH:i:s' );
		}

		$end_date = get_the_date( 'Y-m-d\TH:i:s', get_the_ID() );

		?>

		<style>#minor-publishing{ display: none; }</style>

		<p>
			<label for="_start_date"><?php _e( 'Start date', 'wp-invoice' ); ?></label>
			<br />
			<input type="datetime-local" name="_start_date" id="_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
		</p>

		<p>
			<label for="_end_date"><?php _e( 'End date', 'wp-invoice' ); ?></label>
			<br />
			<input type="datetime-local" name="_end_date" id="_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
		</p>

		<input type="hidden" id="date-nonce" name="date-nonce" value="<?php echo esc_attr( wp_create_nonce( __FILE__ ) ); ?>">

		<?php
	}

	/**
	 * Save time meta box data.
	 *
	 * @param  int     $post_id  The post ID
	 * @param  object  $post     The post object
	 */
	public function meta_time_save( $post_id, $post ) {

		// Bail out if not on correct post-type
		if ( 'entry' !== get_post_type() ) {
			return;
		}

		// Only save if correct post data sent
		if ( isset( $_POST['_start_date'] ) && isset( $_POST['_end_date'] ) ) {

			// Do nonce security check
			if ( ! wp_verify_nonce( $_POST['date-nonce'], __FILE__ ) ) {
				return $post_id;
			}

			// Sanitize and store the data
			$start_date = absint( strtotime( $_POST['_start_date'] ) );
			$end_date = date( 'Y-m-d\TH:i', absint( strtotime( $_POST['_end_date'] ) ) );

			update_post_meta( $post_id, '_start_date', $start_date );

			remove_action( 'save_post', array($this,'meta_time_save' ) );
			wp_update_post(
				array(
					'ID'            => $post_id,
					'post_date'     => $end_date,
					'post_date_gmt' => get_gmt_from_date( $end_date ),
				)
			);
			add_action( 'save_post', array( $this, 'meta_time_save' ), 10, 2 );

		}

	}

}
