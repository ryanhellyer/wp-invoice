<?php

class WP_Invoice_Toggl {

	const DATE_FORMAT = 'Y-m-d';
	const MAX_TOGGL_PAGES_TO_ACCESS = 50; // Need to cap total number of pages to access in case there's a glitch at Toggl
	const CACHE_TIME = DAY_IN_SECONDS;

	/**
	 * Get entry data.
	 */
	public function get_entry_data( $user_id, $start, $end ) {

		$api_token = get_the_author_meta( 'toggl_api_token', $user_id );
		$workspace_id = get_the_author_meta( 'toggl_workspace_id', $user_id );
		$key = 'toggl-' . $user_id . '-' . $start . '-' . $end; // transient limit is 45 chars which this does not go past
delete_transient( $key );

		if ( false === ( $tasks = get_transient( $key ) ) ) {

			// Get tasks from Toggl
			$tasks = $this->get_raw_toggl_data( $api_token, $workspace_id, $start, $end );

			// If no tasks returned from API, then use stale cache or give up
			if ( false === $tasks ) {
				wp_die( 'No tasks found! ' . esc_html( $key ) );
			}

			$tasks = $this->process_toggl_data( $tasks );

			set_transient( $key, $tasks, self::CACHE_TIME );
		}

		return $tasks;
	}

	/**
	 * Get Toggl data via their API.
	 *
	 * @return  array  The tasks obtained from Toggl
	 */
	public function get_raw_toggl_data( $api_token, $workspace_id, $start, $end ) {

		$start_date = date( self::DATE_FORMAT, $start );
		$end_date   = date( self::DATE_FORMAT, $end );

		$combined_tasks = array();
		$page = 0;
		while ( $page < self::MAX_TOGGL_PAGES_TO_ACCESS ) {
			$page++; // 0 gives same results as 1

			$ch = curl_init();

			$url = "https://toggl.com/reports/api/v2/details?workspace_id=" . $workspace_id . "&since=" . $start_date . "&until=" . $end_date . "&user_agent=api_test&page=" . $page;

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
			curl_setopt( $ch, CURLOPT_USERPWD, $api_token . ":" . "api_token" );

			$tasks = curl_exec( $ch );
			if ( curl_errno( $ch ) ) {
				return false;
			}
			curl_close ( $ch );

			$tasks = json_decode( $tasks, true );

			if ( isset( $tasks['data'] ) ) {
				$combined_tasks = array_merge( $combined_tasks, $tasks['data'] );
			}

			// Bail out once we've loaded all pages
			if ( $tasks['total_count'] < ( $tasks['per_page'] * $page ) ) {
				break;
			}

		}

		return $combined_tasks;
	}

	/**
	 * Process the data acquired from Toggl.
	 * Strips unnecessary data and converts data into more suitable format.
	 *
	 * @param  array  $tasks   Tasks direct from Toggl API
	 * @return array  the tasks after reformatting
	 */
	public function process_toggl_data( $tasks ) {
		$processed_tasks = array();

		foreach ( $tasks as $key => $task ) {
			$processed_tasks[$key]['start']       = intval( strtotime( $task['start'] ) );
			$processed_tasks[$key]['end']         = intval( strtotime( $task['end'] ) );
			$processed_tasks[$key]['description'] = esc_html( $task['description'] );
			$processed_tasks[$key]['user']        = esc_html( $task['user'] );
			$processed_tasks[$key]['client']      = esc_html( $task['client'] );
			$processed_tasks[$key]['project']     = esc_html( $task['project'] );
		}

		return $processed_tasks;
	}

}
