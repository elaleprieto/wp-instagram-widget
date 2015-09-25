<?php
/*
Plugin Name: WP Instagram Widget
Plugin URI: https://github.com/scottsweb/wp-instagram-widget
Description: A WordPress widget for showing your latest Instagram photos.
Version: 1.8
Author: Scott Evans
Author URI: http://scott.ee
Text Domain: wp-instagram-widget
Domain Path: /assets/languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright © 2013 Scott Evans

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

function wpiw_init() {

	// define some constants
	define( 'WP_INSTAGRAM_WIDGET_JS_URL', plugins_url( '/assets/js', __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_CSS_URL', plugins_url( '/assets/css', __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_IMAGES_URL', plugins_url( '/assets/images', __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_PATH', dirname( __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_BASE', plugin_basename( __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_FILE', __FILE__ );

	// load language files
	load_plugin_textdomain( 'wp-instagram-widget', false, dirname( WP_INSTAGRAM_WIDGET_BASE ) . '/assets/languages/' );
}
add_action( 'init', 'wpiw_init' );

function wpiw_widget() {
	register_widget( 'null_instagram_widget' );
}
add_action( 'widgets_init', 'wpiw_widget' );

class null_instagram_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'null-instagram-feed',
			__( 'Instagram', 'wp-instagram-widget' ),
			array( 'classname' => 'null-instagram-feed', 'description' => __( 'Displays your latest Instagram photos', 'wp-instagram-widget' ) )
		);
	}

	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );

		$title = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] );
		$username = empty( $instance['username'] ) ? '' : $instance['username'];
		$limit = empty( $instance['number'] ) ? 9 : $instance['number'];
		$size = empty( $instance['size'] ) ? 'large' : $instance['size'];
		$target = empty( $instance['target'] ) ? '_self' : $instance['target'];
		$link = empty( $instance['link'] ) ? '' : $instance['link'];

		echo $before_widget;
		if ( ! empty( $title ) ) { echo $before_title . $title . $after_title; };

		do_action( 'wpiw_before_widget', $instance );

		if ( $username != '' ) {

			$media_array = $this->scrape_instagram( $username, $limit );

			if ( is_wp_error( $media_array ) ) {

				echo $media_array->get_error_message();

			} else {

				// filter for images only?
				if ( $images_only = apply_filters( 'wpiw_images_only', FALSE ) )
					$media_array = array_filter( $media_array, array( $this, 'images_only' ) );

				// filters for custom classes
				$ulclass = esc_attr( apply_filters( 'wpiw_list_class', 'instagram-pics instagram-size-' . $size ) );
				$liclass = esc_attr( apply_filters( 'wpiw_item_class', '' ) );
				$aclass = esc_attr( apply_filters( 'wpiw_a_class', '' ) );
				$imgclass = esc_attr( apply_filters( 'wpiw_img_class', '' ) );

				?><ul class="<?php echo esc_attr( $ulclass ); ?>"><?php
				foreach ( $media_array as $item ) {
					// copy the else line into a new file (parts/wp-instagram-widget.php) within your theme and customise accordingly
					if ( locate_template( 'parts/wp-instagram-widget.php' ) != '' ) {
						include locate_template( 'parts/wp-instagram-widget.php' );
					} else {
						echo '<li class="'. $liclass .'"><a href="'. esc_url( $item['link'] ) .'" target="'. esc_attr( $target ) .'"  class="'. $aclass .'"><img src="'. esc_url( $item[$size] ) .'"  alt="'. esc_attr( $item['description'] ) .'" title="'. esc_attr( $item['description'] ).'"  class="'. $imgclass .'"/></a></li>';
					}
				}
				?></ul><?php
			}
		}

		if ( $link != '' ) {
			?><p class="clear"><a href="//instagram.com/<?php echo esc_attr( trim( $username ) ); ?>" rel="me" target="<?php echo esc_attr( $target ); ?>"><?php echo $link; ?></a></p><?php
		}

		do_action( 'wpiw_after_widget', $instance );

		echo $after_widget;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'Instagram', 'wp-instagram-widget' ), 'username' => '', 'size' => 'large', 'link' => __( 'Follow Me!', 'wp-instagram-widget' ), 'number' => 9, 'target' => '_self' ) );
		$title = esc_attr( $instance['title'] );
		$username = esc_attr( $instance['username'] );
		$number = absint( $instance['number'] );
		$size = esc_attr( $instance['size'] );
		$target = esc_attr( $instance['target'] );
		$link = esc_attr( $instance['link'] );
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'wp-instagram-widget' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Username', 'wp-instagram-widget' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php echo $username; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of photos', 'wp-instagram-widget' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e( 'Photo size', 'wp-instagram-widget' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" class="widefat">
				<option value="thumbnail" <?php selected( 'thumbnail', $size ) ?>><?php _e( 'Thumbnail', 'wp-instagram-widget' ); ?></option>
				<option value="small" <?php selected( 'small', $size ) ?>><?php _e( 'Small', 'wp-instagram-widget' ); ?></option>
				<option value="large" <?php selected( 'large', $size ) ?>><?php _e( 'Large', 'wp-instagram-widget' ); ?></option>
				<option value="original" <?php selected( 'original', $size ) ?>><?php _e( 'Original', 'wp-instagram-widget' ); ?></option>
			</select>
		</p>
		<p><label for="<?php echo $this->get_field_id( 'target' ); ?>"><?php _e( 'Open links in', 'wp-instagram-widget' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'target' ); ?>" name="<?php echo $this->get_field_name( 'target' ); ?>" class="widefat">
				<option value="_self" <?php selected( '_self', $target ) ?>><?php _e( 'Current window (_self)', 'wp-instagram-widget' ); ?></option>
				<option value="_blank" <?php selected( '_blank', $target ) ?>><?php _e( 'New window (_blank)', 'wp-instagram-widget' ); ?></option>
			</select>
		</p>
		<p><label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php _e( 'Link text', 'wp-instagram-widget' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'link' ); ?>" name="<?php echo $this->get_field_name( 'link' ); ?>" type="text" value="<?php echo $link; ?>" /></label></p>
		<?php

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['username'] = trim( strip_tags( $new_instance['username'] ) );
		$instance['number'] = ! absint( $new_instance['number'] ) ? 9 : $new_instance['number'];
		$instance['size'] = ( ( $new_instance['size'] == 'thumbnail' || $new_instance['size'] == 'large' || $new_instance['size'] == 'small' || $new_instance['size'] == 'original' ) ? $new_instance['size'] : 'large' );
		$instance['target'] = ( ( $new_instance['target'] == '_self' || $new_instance['target'] == '_blank' ) ? $new_instance['target'] : '_self' );
		$instance['link'] = strip_tags( $new_instance['link'] );
		return $instance;
	}

	// based on https://gist.github.com/cosmocatalano/4544576
	function scrape_instagram( $username, $slice = 9 ) {

		$username = strtolower( $username );
		$username = str_replace( '@', '', $username );

		if ( false === ( $instagram = get_transient( 'instagram-media-5-'.sanitize_title_with_dashes( $username ) ) ) ) {

			$remote = wp_remote_get( 'http://instagram.com/'.trim( $username ) );

			if ( is_wp_error( $remote ) )
				return new WP_Error( 'site_down', __( 'Unable to communicate with Instagram.', 'wp-instagram-widget' ) );

			if ( 200 != wp_remote_retrieve_response_code( $remote ) )
				return new WP_Error( 'invalid_response', __( 'Instagram did not return a 200.', 'wp-instagram-widget' ) );

			$shards = explode( 'window._sharedData = ', $remote['body'] );
			$insta_json = explode( ';</script>', $shards[1] );
			$insta_array = json_decode( $insta_json[0], TRUE );

			if ( ! $insta_array )
				return new WP_Error( 'bad_json', __( 'Instagram has returned invalid data.', 'wp-instagram-widget' ) );

			if ( isset( $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'] ) ) {
				$images = $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'];
			} else {
				return new WP_Error( 'bad_json_2', __( 'Instagram has returned invalid data.', 'wp-instagram-widget' ) );
			}

			if ( ! is_array( $images ) )
				return new WP_Error( 'bad_array', __( 'Instagram has returned invalid data.', 'wp-instagram-widget' ) );

			$instagram = array();

			foreach ( $images as $image ) {

				$image['thumbnail_src'] = preg_replace( "/^https:/i", "", $image['thumbnail_src'] );
				$image['thumbnail'] = str_replace( 's640x640', 's160x160', $image['thumbnail_src'] );
				$image['small'] = str_replace( 's640x640', 's320x320', $image['thumbnail_src'] );
				$image['large'] = $image['thumbnail_src'];
				$image['display_src'] = preg_replace( "/^https:/i", "", $image['display_src'] );

				if ( $image['is_video'] == true ) {
					$type = 'video';
				} else {
					$type = 'image';
				}

				$caption = __( 'Instagram Image', 'wp-instagram-widget' );
				if ( ! empty( $image['caption'] ) ) {
					$caption = $image['caption'];
				}

				$instagram[] = array(
					'description'   => $caption,
					'link'		  	=> '//instagram.com/p/' . $image['code'],
					'time'		  	=> $image['date'],
					'comments'	  	=> $image['comments']['count'],
					'likes'		 	=> $image['likes']['count'],
					'thumbnail'	 	=> $image['thumbnail'],
					'small'			=> $image['small'],
					'large'			=> $image['large'],
					'original'		=> $image['display_src'],
					'type'		  	=> $type
				);
			}

			// do not set an empty transient - should help catch private or empty accounts
			if ( ! empty( $instagram ) ) {
				$instagram = base64_encode( serialize( $instagram ) );
				set_transient( 'instagram-media-5-'.sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'null_instagram_cache_time', HOUR_IN_SECONDS*2 ) );
			}
		}

		if ( ! empty( $instagram ) ) {

			$instagram = unserialize( base64_decode( $instagram ) );
			return array_slice( $instagram, 0, $slice );

		} else {

			return new WP_Error( 'no_images', __( 'Instagram did not return any images.', 'wp-instagram-widget' ) );

		}
	}

	function images_only( $media_item ) {

		if ( $media_item['type'] == 'image' )
			return true;

		return false;
	}
}
