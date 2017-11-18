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

		// buttons metabox
		add_action( 'add_meta_boxes', array( $this, 'add_buttons_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_buttons_boxes_save' ), 11, 2 );

		// Client metabox
		add_action( 'add_meta_boxes', array( $this, 'add_client_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_client_boxes_save' ), 10, 2 );

		// Date metabox
		add_action( 'add_meta_boxes', array( $this, 'add_invoice_date_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_invoice_date_boxes_save' ), 10, 2 );

		// Invoice meta metabox
		add_action( 'add_meta_boxes', array( $this, 'add_invoice_from_metabox' ) );
		add_action( 'save_post',      array( $this, 'meta_invoice_from_boxes_save' ), 10, 2 );

		// Entries meta box
		add_action( 'add_meta_boxes', array( $this, 'add_entries_metaboxes' ) );
		add_action( 'save_post',      array( $this, 'meta_entries_boxes_save' ), 10, 2 );
		add_action( 'admin_footer',   array( $this, 'scripts' ) );

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
	public function add_buttons_metabox() {
		add_meta_box(
			'buttons', // ID
			__( 'Actions', 'wp-invoice' ), // Title
			array(
				$this,
				'buttons_meta_box', // Callback to method to display HTML
			),
			'invoice', // Post type
			'side', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Add meta box with buttons.
	 */
	public function buttons_meta_box() {
		echo '
		<p>
			<input name="import-entries" type="submit" class="button button-large" value="' . esc_html__( 'Import entries', 'wp-invoice' ) . '" />
		</p>
		<p>
			<input name="removal-all-entries" type="submit" class="button button-large" value="' . esc_html__( 'Removal all entries', 'wp-invoice' ) . '" />
		</p>
		<p>
			<input name="combine-identical-entries" type="submit" class="button button-large" value="' . esc_html__( 'Combine identical entries', 'wp-invoice' ) . '" />
		</p>
		<input type="hidden" id="buttons-nonce" name="buttons-nonce" value="' . esc_attr( wp_create_nonce( __FILE__ ) ) . '">
';
	}

	/**
	 * Save buttons meta box data.
	 *
	 * @param  int     $invoice_id  The post ID
	 * @param  object  $post        The post object
	 */
	public function meta_buttons_boxes_save( $invoice_id, $post ) {

		// Only save if correct post data sent
		if ( isset( $_POST['buttons-nonce'] ) ) {

			// Do nonce security check
			if ( ! wp_verify_nonce( $_POST['buttons-nonce'], __FILE__ ) ) {
				return $post_id;
			}

			// Import entries
			if ( isset( $_POST['import-entries'] ) ) {

				$start_date = esc_html( $_POST['_start_date'] );
				$end_date   = esc_html( $_POST['_end_date'] );

				// Only process the required client (need to run query because we don't know what it's ID is at this point)
				$clients_query = new WP_Query(
					array(
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'posts_per_page'         => 10,
						'post_type'              => 'client',
						'title'                  => esc_html( $_POST['_client'] ),
					)
				);

				$clients = array();
				if ( $clients_query->have_posts() ) {
					while ( $clients_query->have_posts() ) {
						$clients_query->the_post();

						// Get all entries for this client
						$entries_query = new WP_Query(
							array(
								'posts_per_page'         => 1000,
								'no_found_rows'          => true,
								'update_post_meta_cache' => false,
								'update_post_term_cache' => false,
								'fields'                 => 'ids',
								'post_type'              => 'entry',
								'post_parent'            => get_the_ID(),
								'date_query' => array(
									array(
										'after'     => $start_date,
										'before'    => $end_date,
										'inclusive' => true,
									),
								),
							)
						);
						$data = array();
						$count = 0;
						if ( $entries_query->have_posts() ) {
							while ( $entries_query->have_posts() ) {
								$entries_query->the_post();

								$start_date_timestamp = get_post_meta( get_the_ID(), '_start_date', true );
								$start_date = date( 'Y-m-d', $start_date_timestamp );
								$start_time = date( 'H:i:s' );
								$end_date_date = get_the_date( 'Y-m-d', get_the_ID() );
								$end_date_timestamp = get_the_date( 'U', get_the_ID() );
								$end_time = get_the_date( 'H:i:s', get_the_ID() );
								$hours = round( ( $end_date_timestamp - $start_date_timestamp ) / 60 / 15) / 4;

								$data[$count]['entry_ids'][]  = get_the_ID();
								$data[$count]['title']      = get_the_title( get_the_ID() );

$data[$count]['project'] = '';
								$data[$count]['start-date'] = $start_date;
								$data[$count]['start-time'] = $start_time;

								$data[$count]['end-date']   = $end_date_date;
								$data[$count]['end-time']   = $end_time;

								$data[$count]['hours']      = $hours;

								$count++;
							}
						}

						update_post_meta( $invoice_id, '_wp_invoice_entries', $data );

					}
				}

			}

			// Remove all entries
			if ( isset( $_POST['removal-all-entries'] ) ) {
				delete_post_meta( $invoice_id, '_wp_invoice_entries' );
			}

			// Combine identical entries
			if ( isset( $_POST['combine-identical-entries'] ) ) {
				$entries = get_post_meta( $invoice_id, '_wp_invoice_entries', true );
				$entries = $this->combine_entries( $entries );
				update_post_meta( $invoice_id, '_wp_invoice_entries', $entries );
			}

		}

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

		$start_date_timestamp = get_post_meta( $this->invoice_id, '_start_date', true );
		if ( '' !== $start_date_timestamp ) {
			$start_date = date( self::DATE_FORMAT, (int) $start_date_timestamp );
		} else {
			$start_date = date( self::DATE_FORMAT );
		}

		$end_date = get_the_date( self::DATE_FORMAT, $this->invoice_id );

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
	public function meta_invoice_date_boxes_save( $invoice_id, $post ) {

		// Only save if correct post data sent
		if ( isset( $_POST['_start_date'] ) && isset( $_POST['_end_date'] ) ) {

			// Do nonce security check
			if ( ! wp_verify_nonce( $_POST['date-nonce'], __FILE__ ) ) {
				return $invoice_id;
			}

			// Sanitize and store the data
			$start_date = absint( strtotime( $_POST['_start_date'] . ' 00:00:00' ) );
			$end_date = date( self::DATE_FORMAT, absint( strtotime( $_POST['_end_date'] ) ) ) . ' 23:59:59';

			update_post_meta( $invoice_id, '_start_date', $start_date );

			remove_action( 'save_post', array($this,'meta_invoice_date_boxes_save' ) );
			wp_update_post(
				array(
					'ID'            => $invoice_id,
					'post_date'     => $end_date,
					'post_date_gmt' => get_gmt_from_date( $end_date ),
				)
			);
			add_action( 'save_post', array( $this, 'meta_invoice_date_boxes_save' ), 10, 2 );

		}

	}

	/**
	 * Add invoice from metabox.
	 */
	public function add_invoice_from_metabox() {
		add_meta_box(
			'invoice-from', // ID
			__( 'Invoice meta', 'wp-invoice' ), // Title
			array(
				$this,
				'invoice_from_meta_box', // Callback to method to display HTML
			),
			'invoice', // Post type
			'normal', // Context, choose between 'normal', 'advanced', or 'side'
			'high'  // Position, choose between 'high', 'core', 'default' or 'low'
		);
	}

	/**
	 * Output the invoice from meta box.
	 */
	public function invoice_from_meta_box() {

		$meta_keys = array(
			'from',
			'number',
			'details',
			'currency',
			'due_date',
			'hourly_rate',
			'note',
			'paid',
			'bank_details',
		);

		$meta_data = array();
		foreach ( $meta_keys as $meta_key ) {
			$meta_data[ $meta_key ] = get_post_meta( $this->invoice_id, '_invoice_' . $meta_key, true );
			$client = get_post_meta( $this->invoice_id, '_client', true );

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
			<label for="_invoice_from"><?php _e( 'Invoice from', 'wp-invoice' ); ?></label>
			<br />
			<textarea name="_invoice_from" id="_invoice_from"><?php echo esc_textarea( $meta_data['from'] ); ?></textarea>
		</p>

		<p>
			<label for="_invoice_number"><?php _e( 'Invoice number', 'wp-invoice' ); ?></label>
			<br />
			<input type="text" name="_invoice_number" id="_invoice_number" value="<?php echo esc_attr( $meta_data['number'] ); ?>" />
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

		<p>
			<label for="_invoice_bank_details"><?php _e( 'Bank details ', 'wp-invoice' ); ?></label>
			<br />
			<input type="text" name="_invoice_bank_details" id="_invoice_bank_details" value="<?php echo esc_attr( $meta_data['bank_details'] ); ?>" />
		</p>

		<input type="hidden" id="from-nonce" name="from-nonce" value="<?php echo esc_attr( wp_create_nonce( __FILE__ ) ); ?>">

		<?php
	}

	/**
	 * Save invoice from meta box data.
	 *
	 * @param  int     $post_id  The post ID
	 * @param  object  $post     The post object
	 */
	public function meta_invoice_from_boxes_save( $post_id, $post ) {

		// Bail out if not on correct post-type
		if ( 'invoice' !== get_post_type() ) {
			return;
		}

		// Do nonce security check
		if ( isset( $_POST['_invoice_from'] ) ) {

			if ( ! wp_verify_nonce( $_POST['from-nonce'], __FILE__ ) ) {
				return $post_id;
			}
		}

		if ( isset( $_POST['_invoice_from'] ) ) {
			$invoice_from = wp_kses_post( $_POST['_invoice_from'] );
			update_post_meta( $post_id, '_invoice_from', $invoice_from );
		}

		if ( isset( $_POST['_invoice_number'] ) ) {
			$invoice_number = wp_kses_post( $_POST['_invoice_number'] );
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

		if ( isset( $_POST['_invoice_bank_details'] ) ) {
			$invoice_bank_details = wp_kses_post( $_POST['_invoice_bank_details'] );
			update_post_meta( $post_id, '_invoice_bank_details', $invoice_bank_details );
		}

	}

	/**
	 * Add admin metaboxes. 
	 */
	public function add_entries_metaboxes() {

		add_meta_box(
			'entries', // ID
			__( 'Entries', 'wp-invoice' ), // Title
			array(
				$this,
				'entries_meta_box', // Callback to method to display HTML
			),
			'invoice', // Post type
			'normal', // Context, choose between 'normal', 'advanced', or 'side'
			'low'  // Position, choose between 'high', 'core', 'default' or 'low'
		);

	}

	/**
	 * Save opening times meta box data.
	 *
	 * @param  int     $invoice_id The post ID
	 * @param  object  $post       The post object
	 */
	public function meta_entries_boxes_save( $invoice_id, $post ) {

		if ( 'invoice' !== get_post_type() ) {
			return;
		}

		// Don't save anything if we're meant to be removing them all anyway
		if (
			isset( $_POST['removal-all-entries'] )
			||
			isset( $_POST['combine-identical-entries'] )
			||
			isset( $_POST['import-entries'] )
			||
			! isset( $_POST['buttons-nonce'] )
		) {
			return $invoice_id;
		}

		// Do nonce security check
		if ( ! wp_verify_nonce( $_POST['buttons-nonce'], __FILE__ ) ) {
			return $invoice_id;
		}

		// Loop through each entry and sanitize it
		$count = 0;
		foreach ( $_POST['_wp_invoice_entries']['title'] as $key => $x ) {

			$entry_ids = wp_kses_post( $_POST['_wp_invoice_entries']['entry_ids'][$key] );
			$entry_ids_array = explode( ',', $entry_ids );
			$entry_ids = array();
			foreach( $entry_ids_array as $key2 => $entry_id ) {
				$data[$count]['entry_ids'][] = absint( $entry_id );
			}
			$data[$count]['title']      = wp_kses_post( $_POST['_wp_invoice_entries']['title'][$key] );
			$data[$count]['project']    = wp_kses_post( $_POST['_wp_invoice_entries']['project'][$key] );
			$data[$count]['start-date'] = wp_kses_post( $_POST['_wp_invoice_entries']['start-date'][$key] );
			$data[$count]['start-time'] = wp_kses_post( $_POST['_wp_invoice_entries']['start-time'][$key] );
			$data[$count]['end-date']   = wp_kses_post( $_POST['_wp_invoice_entries']['end-date'][$key] );
			$data[$count]['end-time']   = wp_kses_post( $_POST['_wp_invoice_entries']['end-time'][$key] );
			$data[$count]['hours']      = wp_kses_post( $_POST['_wp_invoice_entries']['hours'][$key] );

			$count++;
		}

		// Save the entries
		update_post_meta( $invoice_id, '_wp_invoice_entries', $data );
	}

	/**
	 * Output the admin page.
	 */
	public function entries_meta_box() {

		if ( isset( $_GET['post'] ) ) {
			$invoice_id = $_GET['post'];
		} else {
			$invoice_id = 0;
		}

		?>

			<table class="wp-list-table widefat plugins">
				<thead>
					<tr>
						<th class='column-author'>
							<?php _e( 'Edit', 'wp-invoice' ); ?>
						</th>
						<th class='column-author'>
							<?php _e( 'Title', 'wp-invoice' ); ?>
						</th>
						<th class='column-author'>
							<?php _e( 'Project', 'wp-invoice' ); ?>
						</th>
						<th class='column-author'>
							<?php _e( 'Start', 'wp-invoice' ); ?>
						</th>
						<th class='column-author'>
							<?php _e( 'End', 'wp-invoice' ); ?>
						</th>
						<th class='column-author'>
							<?php _e( 'Hours', 'wp-invoice' ); ?>
						</th>
						<th class='column-author'>
						</th>
					</tr>
				</thead>

				<tbody id="add-rows"><?php

				// Grab options array and output a new row for each setting
				$entries = get_post_meta( $invoice_id, '_wp_invoice_entries', true );

				if ( is_array( $entries ) ) {
					foreach( $entries as $key => $value ) {
						echo $this->get_row( $value );
					}
				}

				// Add a new row by default
				echo $this->get_row();
				?>
				</tbody>
			</table>

			<input type="button" id="add-new-row" value="<?php _e( 'Add new row', 'plugin-slug' ); ?>" /><?php
	}

	/**
	 * Get a single table row.
	 * 
	 * @param  string  $value  Option value
	 * @return string  The table row HTML
	 */
	public function get_row( $value = '' ) {

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		if ( ! isset( $value['entry_ids'] ) ) {
			$value['entry_ids'] = array();
		}

		if ( ! isset( $value['title'] ) ) {
			$value['title'] = '';
		}

		if ( ! isset( $value['project'] ) ) {
			$value['project'] = '';
		}

		if ( ! isset( $value['start-date'] ) ) {
			$value['start-date'] = '';
		}

		if ( ! isset( $value['start-time'] ) ) {
			$value['start-time'] = '';
		}

		if ( ! isset( $value['end-date'] ) ) {
			$value['end-date'] = '';
		}

		if ( ! isset( $value['end-time'] ) ) {
			$value['end-time'] = '';
		}

		if ( ! isset( $value['hours'] ) ) {
			$value['hours'] = '';
		}

		// Get ID lists in strings
		$id_list = '';
		$entry_links = '';
		$count = 1;
		foreach ( $value['entry_ids'] as $key => $entry_id ) {

			$id_list .= $entry_id;

			$entry_links .= '<a href="'. esc_url( get_edit_post_link( $entry_id ) ) . '">' . esc_html( $entry_id ) . '</a>';

			if ( $count !== count( $value['entry_ids'] ) ) {
				$id_list .= ', ';
				$entry_links .= ', ';
			}

			$count++;
		}

		// Create the required HTML
		$row_html = '

					<tr class="sortable inactive">
						<td>
							<input type="hidden" name="_wp_invoice_entries[entry_ids][]" value="' . esc_attr( $id_list ) . '" />
							' . $entry_links . '
						</td>
						<td>
							<input type="text" name="_wp_invoice_entries[title][]" value="' . esc_attr( $value['title'] ) . '" />
						</td>
						<td>
							<input type="text" name="_wp_invoice_entries[project][]" value="' . esc_attr( $value['project'] ) . '" />
						</td>
						<td>
							<input type="date" name="_wp_invoice_entries[start-date][]" value="' . esc_attr( $value['start-date'] ) . '" />
							<input type="time" name="_wp_invoice_entries[start-time][]" value="' . esc_attr( $value['start-time'] ) . '" />
						</td>
						<td>
							<input type="date" name="_wp_invoice_entries[end-date][]" value="' . esc_attr( $value['end-date'] ) . '" />
							<input type="time" name="_wp_invoice_entries[end-time][]" value="' . esc_attr( $value['end-time'] ) . '" />
						</td>
						<td>
							<input type="number" min="0" step="any" name="_wp_invoice_entries[hours][]" value="' . esc_attr( $value['hours'] ) . '" />
						</td>
					</tr>';

		// Strip out white space (need on line line to keep JS happy)
		$row_html = str_replace( '	', '', $row_html );
		$row_html = str_replace( "\n", '', $row_html );

		// Return the final HTML
		return $row_html;
	}

	/**
	 * Output scripts into the footer.
	 * This is not best practice, but is implemented like this here to ensure that it can fit into a single file.
	 */
	public function scripts() {

		if (
			'invoice' !== get_post_type()
			||
			strpos( $_SERVER['REQUEST_URI'], 'post.php') === false
		) {
			return;
		}

		?>
		<style>
		.read-more-text {
			display: none;
		}
		.sortable .toggle {
			display: inline !important;
		}
		</style>
		<script>

			jQuery(function($){ 

				/**
				 * Adding some buttons
				 */
				function add_buttons() {

					// Loop through each row
					$( ".sortable" ).each(function() {

						// If no input field found with class .remove-setting, then add buttons to the row
						if(!$(this).find('input').hasClass('remove-setting')) {

							// Add a remove button
							$(this).append('<td><input type="button" class="remove-setting" value="X" /></td>');

							// Remove button functionality
							$('.remove-setting').click(function () {
								$(this).parent().parent().remove();
							});

						}

					});

				}

				// Create the required HTML (this should be added inline via wp_localize_script() once JS is abstracted into external file)
				var html = '<?php echo $this->get_row( '' ); ?>';

				// Add the buttons
				add_buttons();

				// Add a fresh row on clicking the add row button
				$( "#add-new-row" ).click(function() {
					$( "#add-rows" ).append( html ); // Add the new row
					add_buttons(); // Add buttons tot he new row
				});

				// Allow for resorting rows
				$('#add-rows').sortable({
					axis: "y", // Limit to only moving on the Y-axis
				});

 			});

		</script><?php
	}

	/**
 	* Combine entries.
 	*
 	* @param  array   $entries   The entries
 	* @return array   The combined entries
 	*/
	public function combine_entries( $entries ) {

		// Crudely combining the entries
		foreach ( $entries as $key => $entry ) {
			$array_key = $entry['title'] . $entry['project'];

			$combined_entries[$array_key]['title']   = $entry['title'];
			$combined_entries[$array_key]['project'] = $entry['project'];

			$id_list = '';
			if ( isset( $entry['entry_ids'] ) ) {
				foreach ( $entry['entry_ids'] as $x => $entry_id ) {
					if ( 0 !== $entry_id ) {
						if ( '' !== $id_list ) {
							$id_list .= ',' . $entry_id;
						} else {
							$id_list = $entry_id;
						}
					}
				}
			}
			$combined_entries[$array_key]['entry_ids'][ $id_list ] = $entry['hours'];

			$combined_entries[$array_key]['start'][] = strtotime( $entry['start-date'] . ' ' . $entry['start-time'] );
			$combined_entries[$array_key]['end'][]   = strtotime( $entry['end-date'] . ' ' . $entry['end-time'] );
		}

		// Setting start and end times and hours
		foreach ( $combined_entries as $key => $entry ) {
			$combined_entries[$key]['start'] = min( $entry['start'] );
			$combined_entries[$key]['end']   = max( $entry['end'] );

			$combined_entries[$key]['hours'] = 0;
			$count = 0;
			foreach ( $entry['entry_ids'] as $entry_id => $hours ) {
				$combined_entries[$key]['hours'] = $hours + $combined_entries[$key]['hours'];
				$combined_entries[$key]['entry_ids'][$count] = $entry_id;
				unset( $combined_entries[$key]['entry_ids'][$entry_id] );
				$count++;
			}

		}

		// Adding formatted start and end dates/times
		foreach ( $combined_entries as $key => $entry ) {
			$combined_entries[$key]['start-date'] = date( 'Y-m-d', $entry['start'] );
			$combined_entries[$key]['start-time'] = date( 'H:i:s', $entry['start'] );
			$combined_entries[$key]['end-date'] = date( 'Y-m-d', $entry['end'] );
			$combined_entries[$key]['end-time'] = date( 'H:i:s', $entry['end'] );

			unset( $combined_entries[$key]['start'] );
			unset( $combined_entries[$key]['end'] );
		}

		return $combined_entries;
	}

}
