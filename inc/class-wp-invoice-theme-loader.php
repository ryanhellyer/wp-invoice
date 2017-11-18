<?php

/**
 * WP Invoice Theme Loader.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @since 1.0
 */
class WP_Invoice_Theme_Loader extends WP_Invoice_Core {

	private $currencies = array(
		'NZD' => array(
			'front'  => true,
			'format' => 'NZ$',
		),
		'USD' => array(
			'front'  => true,
			'format' => 'US$',
		),
		'EUR' => array(
			'front'  => true,
			'format' => '€',
		),
	);

	public function __construct() {

		show_admin_bar( false );

		add_shortcode( 'entries', array( $this, 'entries_shortcode' ) );

		// Add action hooks
		add_action( 'wp',                array( $this, 'error_404' ) );
		add_filter( 'template_redirect', array( $this, 'load_template' ) );

	}

	public function entries_shortcode( $args = null, $content ) {
		$invoice_id = $args['invoice_id'];

		/**
		 * Get client information.
		 */
		$entries = get_post_meta( $invoice_id, '_wp_invoice_entries', true );
		if ( is_array( $entries ) ) {

			$html = '';
			foreach( $entries as $key => $value ) {

				// Set values if they don't exist yet
				$values = array(
					'title',
					'start-date',
					'end-date',
					'hours',
					'amount',
				);
				foreach ( $values as $key ) {
					if ( ! isset( $value[$key] ) ) {
						$value[$key] = '';
					}
				}

				// Convert all template tags to values
				$template_tags = array(
					'title'       => $value['title'],
					'start_date'  => date( get_option( 'date_format' ), strtotime( $value['start-date'] ) ),
					'end_date'    => date( get_option( 'date_format' ), strtotime( $value['end-date'] ) ),
					'hours'       => $value['hours'],
					'amount'      => $this->get_amount( $invoice_id, ( $value['hours'] * get_post_meta( $invoice_id, '_invoice_hourly_rate', true ) ) ),
				);
				$html .= $content;
				foreach ( $template_tags as $template_tag => $value ) {
					$html = str_replace( '{entry_' . $template_tag . '}', $value, $html );
				}

			}

		}

		return $html;
	}

	public function error_404() {
		global $post, $wp_query;

		if ( ! is_user_logged_in() ) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}

	}

	public function load_template() {

		if ( is_404() ) {

			// 404 template
			$path = dirname( dirname( __FILE__ ) ) . '/templates/404.tpl';

		} else {

			// Invoice template
			$path = dirname( dirname( __FILE__ ) ) . '/templates/invoice.tpl';

		}

		$template = file_get_contents( $path );

		$html = $this->process_template( $template );

		echo $html;
		die;
	}

	/**
	 * Get the client data.
	 *
	 * @param  string  $invoice_id  The invoice ID
	 * @return array   The client information
	 */
	public function get_client_data( $invoice_id ) {

		$client_data['name'] = get_post_meta( $invoice_id, '_client', true );
		$client_query = new WP_Query( array(
			'posts_per_page'         => 1,
			'post_type'              => 'client',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'title'                  => $client_data['name'],
		) );
		if ( $client_query->have_posts() ) {
			while ( $client_query->have_posts() ) {
				$client_query->the_post();

				$client_data['description'] = get_post_meta( get_the_ID(), '_client_description', true );
				$client_data['website']     = get_post_meta( get_the_ID(), '_client_website', true );

				$client_data['domain'] = str_replace( 'http://', '', $client_data['website'] );
				$client_data['domain'] = str_replace( 'https://', '', $client_data['domain'] );
				$client_data['domain'] = untrailingslashit( $client_data['domain'] );

			}
		}

		return $client_data;
	}


	/**
	 * Process template.
	 *
	 * @param  string  $html  The page template
	 */
	public function process_template( $html ) {
		global $post;
		$author_id = $post->post_author;
		$invoice_id = get_the_ID();

		/**
		 * Get client information.
		 */
		$client_data = $this->get_client_data( $invoice_id );

		/**
		 * Get tbody HTML.
		 */
		$html = do_shortcode( $html );

		/**
		 * Set template tags to rewrite.
		 */
		$tags = array(
			'entries' => array(
				'escape' => '',
				'string' => '[entries invoice_id="' . esc_attr( $invoice_id ) . '"]',
			),
			'/entries' => array(
				'escape' => '',
				'string' => '[/entries]',
			),
			'css' => array(
				'escape' => '',
				'string' => str_replace( 'theme_directory_url/', get_template_directory_uri() . '/', file_get_contents( dirname( dirname( __FILE__ ) ) . '/style.css' ) ),
			),
			'first_name' => array(
				'escape' => 'esc_html',
				'string' =>  get_the_author_meta( 'first_name', $author_id ),
			),
			'last_name' => array(
				'escape' => 'esc_html',
				'string' =>  get_the_author_meta( 'last_name', $author_id ),
			),
			'display_name' => array(
				'escape' => 'esc_html',
				'string' =>  get_the_author_meta( 'display_name', $author_id ),
			),
			'invoice_number' => array(
				'escape' => 'esc_html',
				'string' => get_post_meta( $invoice_id, '_invoice_number', true ),
			),
			'client_name' => array(
				'escape' => 'esc_html',
				'string' => $client_data['name'],
			),
			'client_description' => array(
				'escape' => 'esc_html',
				'string' => $client_data['description'],
			),
			'client_website' => array(
				'escape' => 'esc_url',
				'string' => $client_data['website'],
			),
			'client_domain' => array(
				'escape' => 'esc_html',
				'string' => $client_data['domain'],
			),
			'invoice_from' => array(
				'escape' => 'esc_html',
				'string' => get_post_meta( $invoice_id, '_invoice_from', true ),
			),
			'currency' => array(
				'escape' => 'esc_html',
				'string' => get_post_meta( $invoice_id, '_invoice_currency', true ),
			),
			'total_amount' => array(
				'escape' => 'esc_html',
				'string' => $this->get_amount( $invoice_id, $this->get_total_amount( $invoice_id ) ),
			),
			'bank_details' => array(
				'escape' => 'esc_html',
				'string' => get_post_meta( $invoice_id, '_invoice_bank_details', true ),
			),
			'footer_comment_1' => array(
				'escape' => 'wp_kses_post',
				'string' => 'Thank your for your business!',
			),
			'footer_comment_2' => array(
				'escape' => 'wp_kses_post',
				'string' => 'ryanhellyer@gmail.com | geek.hellyer.kiwi',
			),
			'note' => array(
				'escape' => 'esc_html',
				'string' => get_post_meta( $invoice_id, '_invoice_note', true ),
			),
		);

		/**
		 * Rewrite template tags.
		 */
		foreach ( $tags as $tag => $data ) {

			if ( '' !== $data['escape'] ) {
				$data['string'] = call_user_func ( $data['escape'], $data['string'] );
			}

			$html = str_replace( '{' . $tag . '}', $data['string'], $html );			
		}

		/**
		 * Do the shortcodes.
		 * Required for {entries} to work correctly.
		 */
		$html = do_shortcode( $html );

		return $html;
	}

	public function get_total_amount( $invoice_id ) {

		$entries = get_post_meta( $invoice_id, '_wp_invoice_entries', true );
		$total_hours = 0;
		if ( is_array( $entries ) ) {

			foreach( $entries as $key => $value ) {

				$hours = $value['hours'];
				$total_hours = $total_hours + $hours;

			}

		}

		$hourly_rate = get_post_meta( $invoice_id, '_invoice_hourly_rate', true );

		$total_amount = $total_hours * $hourly_rate;

		return $total_amount;
	}

	public function get_amount( $invoice_id, $amount ) {
		$currency = get_post_meta( $invoice_id, '_invoice_currency', true );

		foreach ( $this->currencies as $currency_code => $options ) {
			if ( $currency === $currency_code ) {

				if ( true === $options['front'] ) {
					$amount = $options['format'] . $amount;
				} else {
					$amount = $amount . $options['format'];
				}

			}
		}

		return $amount;
	}

}
