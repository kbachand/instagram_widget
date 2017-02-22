<?php

/**
 * Plugin Name: Instagram  Widget
 * Description: A WordPress widget to display Instagram photos
 * Version: 1.3.0
 * Author: Keith Bachand
 * Author URI: http://www.keithbachand.com
 */

/* register and build widget */
function tc_register_simple_instagram_widget() {
	register_widget( 'TC_Simple_Instagram_Widget' );
}
add_action( 'widgets_init', 'tc_register_simple_instagram_widget' );

/* Load the widget class */
require_once( plugin_dir_path( __FILE__ ) . '/lib/widget/class.instagram-widget.php' );
require_once( plugin_dir_path( __FILE__ ) . 'settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'upgrade-notice.php' );


function tc_simple_instagram_plugin_row_actions( $actions, $plugin_file, $plugin_data, $context ) {
	$actions['settings'] = '<a href="' . get_admin_url( NULL, 'options-general.php?page=tc_ig_settings' ) . '">Settings</a>';
	return $actions;
}
add_filter( 'plugin_action_links_simple-instagram-widget/init.php', 'tc_simple_instagram_plugin_row_actions', 10, 4 );

// Register front end styles
function tc_simple_instagram_register_frontend_style() {
	wp_register_style(
		'simple-instagram-style',
		plugins_url( '/lib/css/simple-instagram-widget.css', __FILE__ )
	);
}
add_action( 'wp_enqueue_scripts', 'tc_simple_instagram_register_frontend_style' );


function tc_simple_instagram_shortcode($atts = '') {

	wp_enqueue_style('simple-instagram-style');

	$atts = shortcode_atts( array(
		'hashtag' 	=> '',
		'username' 	=> '',
		'count' 	=> '5'
	), $atts );

	$instance_count = 0;
	$instance_count++;

	$settings = get_option('tc_ig_settings');
	$client_id = $settings['tc_ig_client_id'];

	if ( $atts['username'] ) {
		$username_response = wp_remote_get( 'https://api.instagram.com/v1/users/search?q=' . $atts['username'] . '&client_id=' . $client_id );
		$username_response_data = json_decode( $username_response['body'], true );

		$atts['username_converted'] = '';
		if ( isset( $username_response_data['data'] ) ){
			foreach ( $username_response_data['data'] as $data ) {
				if ( $data['username'] == $atts['username'] ) {
					$atts['username_converted'] = $data['id'];
				}
			}
		}
	}

	if ( ! empty( $atts['username_converted'] ) ) {
		$userID = esc_html( $atts['username_converted'] );
	} else { $userID = ''; }

	if ( ! empty( $atts['hashtag'] ) ) {
		$hashtag = esc_html( $atts['hashtag'] );
	} else { $hashtag = ''; }

	$widget = new TC_Simple_Instagram_Widget();
	$instagram = $widget->get_photos( array(
		'user_id'   => $userID,
		'client_id' => $client_id,
		'hashtag'   => $hashtag,
		'count'     => ( !empty( $atts['count'] ) ) ? $atts['count'] : '5',
		'flush'     => false
	) );

	return $widget->widget_output( $instagram, $instance_count );
}
add_shortcode('simple_instagram', 'tc_simple_instagram_shortcode');
