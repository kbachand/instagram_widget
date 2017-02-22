<?php

	add_action( 'admin_notices', 'tc_ig_add_id_nag', 10 );
	function tc_ig_add_id_nag() {
		if ( ! empty( $_REQUEST['tc_ig_hide_id_notice'] ) )
			update_option( 'tc-ig-hide-id-notice', true );

		if ( isset( $_GET['tc_ig_show_id_notice'] ) )
			delete_option( 'tc-ig-hide-id-notice' );

		$settings = get_option( 'tc_ig_settings' );
		$client_id = $settings['tc_ig_client_id'];

		$screen = get_current_screen();

		if ( true == (boolean) get_option( 'tc-ig-hide-id-notice' ) || ! empty( $client_id ) || 'settings_page_tc_ig_settings' === get_current_screen()->id )
			return;

		?>
		<div id="message" class="tc-ig-client-id-notice notice error">
			<?php
				$link    = add_query_arg( array( 'page' => 'tc_ig_settings' ), admin_url( 'options-general.php' ) );
				$dismiss = add_query_arg( array( 'tc_ig_hide_id_notice' => true ) );
			?>
			<p><strong>Simple Instagram Widget</strong>: It looks like you have not supplied an Instagram Client ID.</p>
			<p>Visit the <a href="<?php echo esc_url( $link ); ?>">Settings</a> page now, or <a href="<?php echo esc_url( $dismiss ); ?>">dismiss</a> and set it up later.</p>
		</div>
	<?php
	}