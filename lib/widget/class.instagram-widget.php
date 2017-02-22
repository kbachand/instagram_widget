<?php

class TC_Simple_Instagram_Widget extends WP_Widget {

	private $instance_count = 0;

	public function __construct() {
		$widget_ops = array( 'classname' => 'simple-instagram-widget', 'description' => 'A widget that displays a set of Instagram photos' );
		parent::__construct( 'simple-instagram-widget', 'Simple Instagram Widget', $widget_ops );

		$instance = $this;
		if ( is_active_widget(false, false, $this->id_base) ){
			add_action( 'wp_head', array(&$this, 'simple_instagram_enqueue_style') );
		}
	}

	public function simple_instagram_enqueue_style() {
		wp_enqueue_style( 'simple-instagram-style' );
	}

	public function widget( $args, $instance ) {
		extract( $args );

		$settings = get_option('tc_ig_settings');

		//Our variables from the widget settings.
		if ( ! empty( $instance['userID_converted'] ) ) {
			$userID = esc_html( $instance['userID_converted'] );
		} else if ( ! empty( $instance['userID'] ) ) {
			$userID = esc_html( $instance['userID'] );
		}
		if ( ! empty( $instance['hashtag'] ) ) {
			$hashtag = esc_html( $instance['hashtag'] );
		}

		$instagram = $this->get_photos( array(
			'user_id'   => $userID,
			'client_id' => $settings['tc_ig_client_id'],
			'hashtag'   => ( !empty( $hashtag ) ) ? $hashtag : '',
			'count'     => !empty( $instance['count'] ) ? esc_html( $instance['count'] ) : '5',
			'flush'     => false
		) );

		$this->instance_count++;

		echo $before_widget;
		$this->widget_output( $instagram, $this->instance_count );
		echo $after_widget;
	}


	public function widget_output( $instagram, $instance_count ) { 
	?>
		<div class="simple-instagram-widget-wrapper simple-instagram-widget-wrapper-<?php echo $this->instance_count; ?>">
			<?php if ( $instagram['data'] ) : ?>
				<?php foreach( $instagram['data'] as $photo ) : ?>
					<div class="simple-instagram-widget-image">
						<a href="<?php echo esc_url( $photo['link'] ); ?>" target="_blank">
							<img src="<?php echo esc_url( $photo['images']['standard_resolution']['url'] ); ?>" alt="<?php echo esc_html( $photo['caption'] ); ?>" >
						</a>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p>No photos found.</p>
			<?php endif; ?>
		</div>
	<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['userID']  = sanitize_text_field( $new_instance['userID'] );
		$instance['count']   = sanitize_text_field( $new_instance['count'] );
		$instance['hashtag'] = sanitize_text_field( $new_instance['hashtag'] );

		$username_response = wp_remote_get( 'https://api.instagram.com/v1/users/search?q=' . $instance['userID'] . '&client_id=972fed4ff0d5444aa21645789adb0eb0' );
		$username_response_data = json_decode( $username_response['body'], true );
		
		$instance['userID_converted'] = '';
		foreach ( $username_response_data['data'] as $data ) {
			if ( $data['username'] == $instance['userID'] ) {
				$instance['userID_converted'] = $data['id'];
			}
		}

		return $instance;
	}


	public function form( $instance ) {
		?>
		<div class="item-wrapper" id="username">
			<p><label for="<?php echo $this->get_field_id( 'userID' ); ?>">Username:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'userID' ); ?>" name="<?php echo $this->get_field_name( 'userID' ); ?>" value="<?php if ( isset( $instance['userID'] ) ) { echo esc_html( $instance['userID'] ); } ?>" type="text"  /></p>
		</div>
		<p>or</p>
		<div class="item-wrapper " id="hashtag">
			<p><label for="<?php echo $this->get_field_id( 'hashtag' ); ?>">Hashtag:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'hashtag' ); ?>" name="<?php echo $this->get_field_name( 'hashtag' ); ?>" value="<?php if ( isset( $instance['hashtag'] ) ) { echo esc_html( $instance['hashtag'] ); } ?>" type="text"  /></p>
		</div>
		
		<div class="item-wrapper">
			<p><label for="<?php echo $this->get_field_id( 'count' ); ?>">Number of photos to show:</label>
			<input id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php if ( isset( $instance['count'] ) ) { echo esc_html( $instance['count'] ); } ?>" type="text" size="3" /></p>
		</div>
	<?php
	}

	public function get_photos( $args = array() ) {
		$user_id   = ( ! empty( $args['user_id'] ) ) ? $args['user_id'] : '';
		$client_id = ( ! empty( $args['client_id'] ) ) ? $args['client_id'] : '';
		$count     = ( ! empty( $args['count'] ) ) ? $args['count'] : '';
		$hashtag   = ( ! empty( $args['hashtag'] ) ) ? $args['hashtag'] : '';
		$flush     = ( ! empty( $args['flush'] ) ) ? $args['flush'] : '';

		// bail early if we don't have a client id
		if ( empty( $client_id ) ) {
			return false;
		}

		if ( ! empty( $user_id ) ) {
			$api_url = 'https://api.instagram.com/v1/users/' . esc_html( $user_id ) . '/media/recent/';
		}
		if ( ! empty( $hashtag ) ) {
			$hashtag = ltrim( $hashtag, '#' );
			$api_url = 'https://api.instagram.com/v1/tags/' . esc_html( $hashtag ) . '/media/recent/';
		}

		// see if we have stored data
		$transient_key = $this->id;
		$data = get_transient( $transient_key );

		//wp_die( print_r( $transient_key ) );

		// get data if we're flushing or if transient is empty
		if ( $flush || false === ( $data ) ) {
			$response = wp_remote_get( add_query_arg( array(
				'client_id' => esc_html( $client_id ),
				'count'     => absint( $count )
			), $api_url ) );

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $data ) ) {
				return false;
			}

			$data = maybe_unserialize( $data );
			set_transient( $transient_key, $data, apply_filters( 'simple_instagram_widget_cache', 30 * MINUTE_IN_SECONDS ) );
		}
		return $data;
	}
}

?>
