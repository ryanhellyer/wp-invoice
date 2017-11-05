<?php

/**
 * Register invoice post-type.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @since 1.0
 */
class WP_Invoice_Invoice_Post_Type extends WP_Invoice_Core {

	/*
	 * Class constructor.
	 */
	public function __construct() {

		// Get the invoice ID
		if ( isset( $_GET['post'] ) ) {
			$this->invoice_id = $_GET['post'];
		} else {
			$this->invoice_id = 0;
		}

		add_action( 'init',           array( $this, 'register_post_type' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_client_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_client_boxes_save' ), 10, 2 );

		add_action( 'add_meta_boxes', array( $this, 'add_invoice_date_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_invoice_date_boxes_save' ), 10, 2 );

		add_action( 'add_meta_boxes', array( $this, 'add_invoice_to_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_invoice_to_boxes_save' ), 10, 2 );
	}

	/**
	 ** Register post-type.
	 */
	public function register_post_type() {

		$args = array(
			'public'             => true,
			'publicly_queryable' => true,
			'label'              => __( 'Invoice', 'wp-invoice' ),
			'supports'           => array(
				'title',
			)
		);
		register_post_type( 'invoice', $args );

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
			'invoice', // Post type
			'side', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Output the client meta box.
	 */
	public function client_meta_box() {

		$current_client = get_post_meta( $this->invoice_id, '_client', true );

		$clients_query = new WP_Query(
			array(
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'posts_per_page'         => 100,
				'post_type'              => 'client',
			)
		);
		$clients = array();
		if ( $clients_query->have_posts() ) {
			while ( $clients_query->have_posts() ) {
				$clients_query->the_post();
				$clients[get_the_ID()] = get_the_title( get_the_ID() );
			}
		}

		?>

		<p>
			<label for="_client"><?php _e( 'Client', 'wp-invoice' ); ?></label>
			<br />
			<select name="_client" id="_client"><?php

			foreach ( $clients as $key => $client ) {
				echo '<option ' . selected( $client, $current_client, false )  . ' value="' . esc_attr( $client ) . '">' . esc_html( $client ) . '</option>';
			}

			?></select>

		</p>

		<input type="hidden" id="client-nonce" name="client-nonce" value="<?php echo esc_attr( wp_create_nonce( __FILE__ ) ); ?>">

		<?php
	}

	/**
	 * Save client meta box data.
	 *
	 * @param  int     $post_id  The post ID
	 * @param  object  $post     The post object
	 */
	public function meta_client_boxes_save( $post_id, $post ) {

		// Only save if correct post data sent
		if ( isset( $_POST['_client'] ) ) {

			// Do nonce security check
			if ( ! wp_verify_nonce( $_POST['client-nonce'], __FILE__ ) ) {
				return $post_id;
			}

			// Sanitize and store the data
			$client = wp_kses_post( $_POST['_client'] );

			update_post_meta( $post_id, '_client', $client );

		}

	}

	/**
	 * Add invoice date metabox.
	 */
	public function add_invoice_date_metabox() {
		add_meta_box(
			'invoice-date', // ID
			__( 'Invoice date range', 'wp-invoice' ), // Title
			array(
				$this,
				'invoice_date_meta_box', // Callback to method to display HTML
			),
			'invoice', // Post type
			'side', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Output the invoice date meta box.
	 */
	public function invoice_date_meta_box() {

		$start_date_timestamp = get_post_meta( get_the_ID(), '_start_date', true );
		if ( '' !== $start_date_timestamp ) {
			$start_date = date( self::DATE_FORMAT, (int) $start_date_timestamp );
		} else {
			$start_date = date( self::DATE_FORMAT );
		}

		$end_date = get_the_date( self::DATE_FORMAT, get_the_ID() );

		?>

		<style>#minor-publishing{ display: none; }</style>

		<p>
			<label for="_start_date"><?php _e( 'Start date', 'wp-invoice' ); ?></label>
			<br />
			<input type="date" name="_start_date" id="_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
		</p>

		<p>
			<label for="_end_date"><?php _e( 'End date', 'wp-invoice' ); ?></label>
			<br />
			<input type="date" name="_end_date" id="_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
		</p>

		<input type="hidden" id="date-nonce" name="date-nonce" value="<?php echo esc_attr( wp_create_nonce( __FILE__ ) ); ?>">

		<?php
	}

	/**
	 * Save invoice date meta box data.
	 *
	 * @param  int     $post_id  The post ID
	 * @param  object  $post     The post object
	 */
	public function meta_invoice_date_boxes_save( $post_id, $post ) {

		// Only save if correct post data sent
		if ( isset( $_POST['_start_date'] ) && isset( $_POST['_end_date'] ) ) {

			// Do nonce security check
			if ( ! wp_verify_nonce( $_POST['date-nonce'], __FILE__ ) ) {
				return $post_id;
			}

			// Sanitize and store the data
			$start_date = absint( strtotime( $_POST['_start_date'] . ' 00:00:00' ) );
			$end_date = date( self::DATE_FORMAT, absint( strtotime( $_POST['_end_date'] ) ) ) . ' 23:59:59';

			update_post_meta( $post_id, '_start_date', $start_date );

			remove_action( 'save_post', array($this,'meta_invoice_date_boxes_save' ) );
			wp_update_post(
				array(
					'ID'            => $post_id,
					'post_date'     => $end_date,
					'post_date_gmt' => get_gmt_from_date( $end_date ),
				)
			);
			add_action( 'save_post', array( $this, 'meta_invoice_date_boxes_save' ), 10, 2 );

		}

	}

	/**
	 * Add invoice to metabox.
	 */
	public function add_invoice_to_metabox() {
		add_meta_box(
			'invoice-to', // ID
			__( 'Invoice meta', 'wp-invoice' ), // Title
			array(
				$this,
				'invoice_to_meta_box', // Callback to method to display HTML
			),
			'invoice', // Post type
			'normal', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Output the invoice to meta box.
	 */
	public function invoice_to_meta_box() {

		$meta_keys = array(
			'to',
			'number',
			'details',
			'currency',
			'due_date',
			'hourly_rate',
			'note',
			'paid',
		);

		$meta_data = array();
		foreach ( $meta_keys as $meta_key ) {
			$meta_data[ $meta_key ] = get_post_meta( $this->invoice_id, '_invoice_' . $meta_key, true );
			$client = get_post_meta( $this->invoice_id, '_client', true );
//delete_post_meta( $this->invoice_id, '_invoice_' . $meta_key );

			if (
				'' === $meta_data[ $meta_key ]
				&&
				'' !== $client
			) {

				$clients_query = new WP_Query(
					array(
						'posts_per_page'         => 1,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'fields'                 => 'ids',
						'order'                  => 'DESC',
						'orderby'                => 'date',
						'post_type'              => 'invoice',
						'meta_key'               => '_client',
						'meta_value'             => $client,
						'meta_compare'           => '==',
					)
				);
				if ( $clients_query->have_posts() ) {
					while ( $clients_query->have_posts() ) {
						$clients_query->the_post();

						if ( $this->invoice_id !== get_the_ID() ) {
							$meta_data[ $meta_key ] = get_post_meta( get_the_ID(), '_invoice_' . $meta_key, true );
						}

					}

				}

			}


		}

		?>

		<p>
			<label for="_invoice_to"><?php _e( 'Invoice to', 'wp-invoice' ); ?></label>
			<br />
			<textarea name="_invoice_to" id="_invoice_to"><?php echo esc_textarea( $meta_data['to'] ); ?></textarea>
		</p>

		<p>
			<label for="_invoice_number"><?php _e( 'Invoice number', 'wp-invoice' ); ?></label>
			<br />
			<input type="number" name="_invoice_number" id="_invoice_number" value="<?php echo esc_attr( $meta_data['number'] ); ?>" />
		</p>

		<p>
			<label for="_invoice_details"><?php _e( 'Details', 'wp-invoice' ); ?></label>
			<br />
			<input type="text" name="_invoice_details" id="_invoice_details" value="<?php echo esc_attr( $meta_data['details'] ); ?>" />
		</p>

		<p>
			<label for="_invoice_currency"><?php _e( 'Currency', 'wp-invoice' ); ?></label>
			<br />
			<select name="_invoice_currency" id="_invoice_currency">

			<?php
			$currencies = array(
				'EUR',
				'USD',
				'NZD',
				'NOK',
			);
			$current_currency = $meta_data['currency'];
//			$current_currency = get_post_meta( get_the_ID(), '_invoice_currency', true );
			foreach ( $currencies as $key => $currency ) {
				echo '<option ' . selected( $currency, $current_currency, false )  . ' value="' . esc_attr( $currency ) . '">' . esc_html( $currency ) . '</option>';
			}
			?>
			</select>
		</p>

		<p>
			<label for="_invoice_due_date"><?php _e( 'Due date', 'wp-invoice' ); ?></label>
			<br />
			<input type="date" name="_invoice_due_date" id="_invoice_due_date" value="<?php echo esc_attr( $meta_data['due_date'] ); ?>" />
		</p>
 
		<p>
			<label for="_invoice_hourly_rate"><?php _e( 'Hourly rate ', 'wp-invoice' ); ?></label>
			<br />
			<input type="number" name="_invoice_hourly_rate" id="_invoice_hourly_rate" value="<?php echo esc_attr( $meta_data['hourly_rate'] ); ?>" />
		</p>

		<p>
			<label for="_invoice_note"><?php _e( 'Note', 'wp-invoice' ); ?></label>
			<br />
			<input type="text" name="_invoice_note" id="_invoice_note" value="<?php echo esc_attr( $meta_data['note'] ); ?>" />
		</p>

		<p>
			<label for="_invoice_paid"><?php _e( 'Paid? ', 'wp-invoice' ); ?></label>
			<br />
			<input type="checkbox" <?php checked( $meta_data['paid'], true ); ?> name="_invoice_paid" id="_invoice_paid" value="1" />
		</p>

		<input type="hidden" id="to-nonce" name="to-nonce" value="<?php echo esc_attr( wp_create_nonce( __FILE__ ) ); ?>">

		<?php
	}

	/**
	 * Save invoice to meta box data.
	 *
	 * @param  int     $post_id  The post ID
	 * @param  object  $post     The post object
	 */
	public function meta_invoice_to_boxes_save( $post_id, $post ) {

		// Bail out if not on correct post-type
		if ( 'invoice' !== get_post_type() ) {
			return;
		}

		// Do nonce security check
		if ( isset( $_POST['_invoice_to'] ) ) {

			if ( ! wp_verify_nonce( $_POST['to-nonce'], __FILE__ ) ) {
				return $post_id;
			}
		}

		if ( isset( $_POST['_invoice_to'] ) ) {
			$invoice_to = wp_kses_post( $_POST['_invoice_to'] );
			update_post_meta( $post_id, '_invoice_to', $invoice_to );
		}

		if ( isset( $_POST['_invoice_number'] ) ) {

			if ( is_numeric( $_POST['_invoice_number'] ) ) {
				$invoice_number = $_POST['_invoice_number'];
			} else {
				$invoice_number = 0;
			}
			update_post_meta( $post_id, '_invoice_number', $invoice_number );

		}

		if ( isset( $_POST['_invoice_details'] ) ) {
			$invoice_details = wp_kses_post( $_POST['_invoice_details'] );
			update_post_meta( $post_id, '_invoice_details', $invoice_details );
		}

		if ( isset( $_POST['_invoice_currency'] ) ) {
			$invoice_currency = wp_kses_post( $_POST['_invoice_currency'] );
			update_post_meta( $post_id, '_invoice_currency', $invoice_currency );
		}

		if ( isset( $_POST['_invoice_due_date'] ) ) {
			$invoice_due_date = date( self::DATE_FORMAT, absint( strtotime( $_POST['_invoice_due_date'] ) ) ) . ' 00:00:00';
			update_post_meta( $post_id, '_invoice_due_date', $invoice_due_date );
		}

		if ( isset( $_POST['_invoice_hourly_rate'] ) ) {
			$invoice_hourly_rate = wp_kses_post( $_POST['_invoice_hourly_rate'] );
			update_post_meta( $post_id, '_invoice_hourly_rate', $invoice_hourly_rate );
		}

		if ( isset( $_POST['_invoice_note'] ) ) {
			$invoice_note = wp_kses_post( $_POST['_invoice_note'] );
			update_post_meta( $post_id, '_invoice_note', $invoice_note );
		}

		if ( isset( $_POST['_invoice_paid'] ) ) {
			$invoice_paid = wp_kses_post( $_POST['_invoice_paid'] );
			update_post_meta( $post_id, '_invoice_paid', $invoice_paid );
		} else {
			delete_post_meta( $post_id, '_invoice_paid' );
		}

	}

}
