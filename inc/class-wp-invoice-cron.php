<?php

/**
 * Cron jobs.
 */
class WP_Invoice_Cron extends WP_Invoice_Core {

	const TIME_BEFORE_UPDATING_TOGGL_DATA = 60;//WEEK_IN_SECONDS;

	/**
	 * Class constructor.
	 */
	public function __construct() {

if ( isset( $_GET['cron'] ) && 'activate'   === $_GET['cron'] ) {add_action( 'init', array( $this, 'activate'   ) );}
if ( isset( $_GET['cron'] ) && 'deactivate' === $_GET['cron'] ) {add_action( 'init', array( $this, 'deactivate' ) );}
if ( isset( $_GET['cron'] ) && 'init'       === $_GET['cron'] ) {add_action( 'init', array( $this, 'process_another_user' ) );}

		add_action( 'pull_entries',  array( $this, 'process_another_user' ) );
	}

	/*
	 * Activate Cron job
	 */
	public function activate() {
		wp_schedule_event( time(), 'hourly', 'pull_entries' );
	}

	/*
	 * Deactivate Cron job
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'pull_entries' );
	}

	/*
	 * Process a user and pull their entries into WordPress.
	 */
	public function process_another_user() {

		// Only do one user at a time - avoids overloading system
		if ( false === ( $users = get_option( 'wp-freelance-users' ) ) ) {

			$users = array();
			foreach ( get_users() as $key => $user ) {
				$users[] = $user->data->ID;
			}

			update_option( 'wp-freelance-users', $users );
		}

		foreach ( $users as $key => $user_id ) {

			// Check all possible dates
			foreach ( $this->get_dates_to_check() as $key2 => $dates ) {
				$start = strtotime( $dates['start'] );
				$end = strtotime( $dates['end'] );

				// Only pull entries if not checked recently
				$syncd_data = get_user_meta( $user_id, 'syncd-data', true );
				$start_date = date( 'Y-m-d', $start );
				$end_date   = date( 'Y-m-d', $end );
				if (
					! isset( $syncd_data[$start_date . '|' . $end_date] )
					||
					( time() - self::TIME_BEFORE_UPDATING_TOGGL_DATA ) > $syncd_data[$start_date . '|' . $end_date]
				) {
					$this->pull_entries( $user_id, $start, $end );
				}

			}

			unset( $users[$key] );
			if ( 0 === count( $users ) ) {
				delete_option( 'wp-freelance-users' );
			} else {
				update_option( 'wp-freelance-users', $users );
			}

			// Stop now coz only want to do one user
			break;

		}

		die( 'FINISHED IMPORTING ENTRIES' );

	}

	/*
	 * Pull entries in from Toggl and store in DB.
	 */
	public function pull_entries( $user_id = null, $start, $end ) {

		// Loop through each task
		$toggl = new WP_Invoice_Toggl;
		$tasks = $toggl->get_entry_data( $user_id, $start, $end );

		if ( is_array( $tasks ) ) {
			foreach ( $tasks as $key2 => $task ) {

				// If no client set, then set as the "No client" client
				if ( ! isset( $task['client'] ) ) {
					$client = 'No client';
				} else {
					$client = $task['client'];
				}

				// If client does not exist, then add it
				$clients = new WP_Query(
					array(
						'posts_per_page'         => 1,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'fields'                 => 'ids',
						'title'                  => $client,
						'post_type'              => 'client',
					)
				);

				// Insert new client
				if ( isset( $clients->posts ) && 0 === count( $clients->posts ) ) {

					$client_id = wp_insert_post(
						array(
							'post_title'    => wp_strip_all_tags( $client ),
							'post_status'   => 'publish',
							'post_author'   => $user_id,
							'post_type'     => 'client',
						)
					);

				} else if ( isset( $clients->posts[0] ) ) {
					$client_id = $clients->posts[0];
				} else {
					wp_die( 'ERROR: No client ID detected' );
				}

				// Insert entries
				$entry_title          = $task['description'];
				$publish_date         = date( self::WORDPRESS_DATE_FORMAT, $task['end'] );
				$start_date_timestamp = absint( $task['start'] );

				// Check if entry exists
				$identical_entries = new WP_Query(
					array(
						'posts_per_page'         => 100,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'title'                  => $entry_title,
						'post_type'              => 'entry',
						'post_status'            => 'publish',
					)
				);
				if ( isset( $identical_entries->posts[0] ) ) {
					foreach ( $identical_entries->posts as $key3 => $entry_check ) {
						$entry_id = $entry_check->ID;

						// Check if start and end times match
						if (
							$start_date_timestamp == get_post_meta( $entry_id, '_start_date', true )
							&&
							$publish_date == $entry_check->post_date_gmt
						) {
							$entry_already_exists = true;
						}

					}
				}

				// Add entry if it doesn't exist
				if ( ! isset( $entry_already_exists ) ) {
					$entry_id = wp_insert_post(
						array(
							'post_title'    => wp_strip_all_tags( $entry_title ),
							'post_status'   => 'publish',
							'post_author'   => $user_id,
							'post_type'     => 'entry',
							'post_date'     => $publish_date,
							'post_parent'   => $client_id,
						)
					);
					update_post_meta( $entry_id, '_start_date', $start_date_timestamp );
				}

			}
		}

		// Update syncd info data
		$syncd_data = get_user_meta( $user_id, 'syncd-data', true );
		if ( ! is_array( $syncd_data ) ) {
			$syncd_data = array();
		}

		$start_date = date( 'Y-m-d', $start );
		$end_date = date( 'Y-m-d', $end );
		$syncd_data[$start_date . '|' . $end_date] = time();
		foreach ( $syncd_data as $key => $data ) {

			if ( empty( $data ) ) {
				unset( $syncd_data[$key] );
			}

		}
		ksort( $syncd_data );
		update_user_meta( $user_id, 'syncd-data', $syncd_data );

	}

	/**
	 * Get list of dates to check.
	 * Check by month, but only check current month up to current day.
	 */
	private function get_dates_to_check() {
		$dates_to_check = array();

		$current_date = date( self::DATE_FORMAT );
		$current_date_exploded = explode( '-', $current_date );
		$current_year = (int) $current_date_exploded[0];
		$current_month = (int) $current_date_exploded[1];
		$current_day = (int) $current_date_exploded[2];

		$dates_to_check[0]['start'] = $current_year . '-' . $current_month . '-01';
		if ( 1 == strlen( $current_day ) ) {
			$dates_to_check[0]['end'] = $current_year . '-' . $current_month . '-0' . $current_day;
		} else {
			$dates_to_check[0]['end'] = $current_year . '-' . $current_month . '-' . $current_day;
		}

		$day   = $current_day;
		if ( 1 === $current_month ) {
			$month = 12;
			$year = $current_year - 1;
		} else {
			$month = $current_month - 1;
			$year = $current_year;
		}
		$count = 1;
		while ( $year > 2014 ) {

			while ( $month > 0 ) {

				if ( 1 === strlen( $month ) ) {
					$month_text = '0' . $month;
				} else {
					$month_text = $month;
				}

				if (
					2 === $month
					||
					4 === $month
					||
					6 === $month
					||
					8 === $month
					||
					9 === $month
					||
					11 === $month
					||
					13 === $month
				) {
					$day = 31;
				} else if (
					// leap years
					( 3 === $month && 2016 === $year )
					||
					( 3 === $month && 2020 === $year )
					||
					( 3 === $month && 2024 === $year )
					||
					( 3 === $month && 2028 === $year )
					||
					( 3 === $month && 2032 === $year )
					||
					( 3 === $month && 2036 === $year )
					||
					( 3 === $month && 2040 === $year )
					||
					( 3 === $month && 2044 === $year )
					||
					( 3 === $month && 2048 === $year )
				) {
					$day = 29;
				} else if ( 3 === $month ) {
					$day = 28;
				} else {
					$day = 30;
				}

				$dates_to_check[$count]['start'] = $year . '-' . $month . '-01';
				$dates_to_check[$count]['end'] = $year . '-' . $month . '-31';

				$month = $month - 1;
				$count++;
			}
			if ( 0 == $month ) {
				$month = 12;
			}

			$year = $year - 1;
		}

		return $dates_to_check;
	}

}
