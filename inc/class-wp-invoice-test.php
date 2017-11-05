<?php

/**
 * Seasons.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @package SRC Theme
 * @since SRC Theme 1.0
 */
class WP_Invoice_Test {

	/**
	 * Constructor.
	 * Add methods to appropriate hooks and filters.
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post',      array( $this, 'meta_boxes_save' ), 10, 2 );
		add_action( 'admin_footer',   array( $this, 'scripts' ) );

	}

	/**
	 * Add admin metaboxes. 
	 */
	public function add_metaboxes() {

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
	public function meta_boxes_save( $invoice_id, $post ) {


		// Only save if correct post data sent
		if ( isset( $_POST['_wp_invoice_entries'] ) ) {

			// Do nonce security check
			if ( ! wp_verify_nonce( $_POST['wp-invoice-nonce'], __FILE__ ) ) {
				return;
			}

		}

		// Loop through each entry and sanitize it
		$count = 0;
		foreach ( $_POST['_wp_invoice_entries']['title'] as $key => $x ) {

			if ( '' !== $_POST['_wp_invoice_entries']['title'][$key] ) {
				$data[$count]['title']      = wp_kses_post( $_POST['_wp_invoice_entries']['title'][$key] );
				$data[$count]['project']    = wp_kses_post( $_POST['_wp_invoice_entries']['project'][$key] );
				$data[$count]['start-date'] = wp_kses_post( $_POST['_wp_invoice_entries']['start-date'][$key] );
				$data[$count]['start-time'] = wp_kses_post( $_POST['_wp_invoice_entries']['start-time'][$key] );
				$data[$count]['end-date']   = wp_kses_post( $_POST['_wp_invoice_entries']['end-date'][$key] );
				$data[$count]['end-time']   = wp_kses_post( $_POST['_wp_invoice_entries']['end-time'][$key] );
				$data[$count]['hours']      = wp_kses_post( $_POST['_wp_invoice_entries']['hours'][$key] );

				$count++;
			}

		}

		// If no entries, then start trying to import automatically
		if ( empty( $data ) ) {
			echo 'empty';
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
		<form method="post" action="options.php" enctype="multipart/form-data">

			<table class="wp-list-table widefat plugins">
				<thead>
					<tr>
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

			<input type="button" id="add-new-row" value="<?php _e( 'Add new row', 'plugin-slug' ); ?>" />

			<input type="hidden" id="wp-invoice-nonce" name="wp-invoice-nonce" value="<?php echo esc_attr( wp_create_nonce( __FILE__ ) ); ?>">
		</form><?php
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
			$value['entry_ids'] = '';
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

		// Create the required HTML
		$row_html = '

					<tr class="sortable inactive">
						<td>
							<input type="text" name="_wp_invoice_entries[entry_ids][]" value="' . esc_attr( $value['entry_ids'] ) . '" />
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
							<input type="number" min="0" value="0" step="any" name="_wp_invoice_entries[hours][]" value="' . esc_attr( $value['hours'] ) . '" />
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

		if ( 'invoice' !== get_post_type() ) {
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

}
