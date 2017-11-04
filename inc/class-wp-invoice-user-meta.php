<?php

class WP_Invoice_User_Meta {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'show_user_profile',        array( $this, 'profile_fields' ) );
		add_action( 'edit_user_profile',        array( $this, 'profile_fields' ) );
		add_action( 'personal_options_update',  array( $this, 'save_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile_fields' ) );
	}

	/**
	 * Get Toggl data via their API.
	 *
	 * @return  array  The tasks obtained from Toggl
	 */
	public function profile_fields( $user ) {
		?>

		<h3><?php _e( 'Toggl authentication credentials', 'wp-invoice' ); ?></h3>

		<table class="form-table">

			<tr>
				<th><label for="toggl_api_token"><?php _e( 'Toggl API token', 'wp-invoice' ); ?></label></th>
				<td><input type="text" name="toggl_api_token" id="toggl_api_token" value="<?php echo esc_attr( get_the_author_meta( 'toggl_api_token', $user->ID ) ); ?>" class="regular-text" /><br /></td>
			</tr>

			<tr>
				<th><label for="toggl_workspace_id"><?php _e( 'Toggl workspace ID', 'wp-invoice' ); ?></label></th>
				<td><input type="text" name="toggl_workspace_id" id="toggl_workspace_id" value="<?php echo esc_attr( get_the_author_meta( 'toggl_workspace_id', $user->ID ) ); ?>" class="regular-text" /><br /></td>
			</tr>

			<tr>
				<th><?php _e( "Sync'd data from Toggle", 'wp-invoice' ); ?></th>
				<td><?php
				$syncd_data = get_user_meta( $user->ID, 'syncd-data', true );
				echo '<textarea style="font-size:10px;font-family:monospace;line-height:12px;width:100%;height:600px;">' . print_r( $syncd_data, true ) . '</textarea>';

				?></td>
			</tr>

		</table><?php
	}

	/**
	 * Save the profile fields.
	 *
	 * @param  int  $user_id  The user ID
	 */
	public function save_profile_fields( $user_id ) {

		// Bail out if user isn't allowed to edit this user
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// Save the meta data
		update_usermeta( $user_id, 'toggl_api_token', wp_kses_post( $_POST['toggl_api_token'] ) );
		update_usermeta( $user_id, 'toggl_workspace_id', wp_kses_post( $_POST['toggl_workspace_id'] ) );

	}

}
