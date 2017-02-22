<?php

	// Add the settings page
	function tc_ig_admin_add_page() {
		add_options_page( 'Simple Instagram Widget Settings', 'Simple Instagram Widget', 'manage_options', 'tc_ig_settings', 'plugin_options_page');
	}
	add_action('admin_menu', 'tc_ig_admin_add_page');

	// Add markup and settings to page
	function plugin_options_page() {
		?>
		<div class="wrap">
			<h2>Simple Instagram Widget Settings</h2>
			
			<form action="options.php" method="post">
				<?php settings_fields('tc_ig_settings'); ?>
				<?php do_settings_sections('tc_ig_settings'); ?>
				<input name="Submit" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
			</form>
		</div>
		<?php
	}

	// Definining settings
	function plugin_admin_init(){
		register_setting(
			'tc_ig_settings',
			'tc_ig_settings',
			'tc_ig_settings_validate'
		);

		add_settings_section(
			'tc_ig_settings_main',
			'',
			'',
			'tc_ig_settings'
		);
		
		add_settings_field(
			'tc_ig_client_id',
			'Instagram Client ID',
			'tc_ig_client_id_output',
			'tc_ig_settings',
			'tc_ig_settings_main'
		);
	}
	add_action('admin_init', 'plugin_admin_init');


	function tc_ig_settings_validate( $input ) {
		$newinput['tc_ig_client_id'] = trim($input['tc_ig_client_id']);
		return $newinput;
	}
	
	function tc_ig_client_id_output() {
		$options = get_option('tc_ig_settings');
		echo '<input id="tc_ig_client_id" name="tc_ig_settings[tc_ig_client_id]" size="40" type="text" value="' . $options['tc_ig_client_id'] . '" />';
		echo '<p class="description">To get an Instagram Client ID:
				<ol class="description">
					<li>Visit the <a href="https://instagram.com/developer" target="_blank">Instagram Developer page</a></li>
					<li>Login to your Instagram account</li>
					<li>Click "Register Your Application"</li>
					<li>Follow steps to create a new client and get your ID</li>
				<ol>
			</p>';
	}