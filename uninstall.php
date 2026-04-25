<?php
/**
 * Uninstall handler.
 *
 * Fired when the plugin is deleted from the WordPress admin.
 *
 * @package WpAiFeaturedImage
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wp_ai_featured_image_api_key' );
delete_option( 'wp_ai_featured_image_model' );
delete_option( 'wp_ai_featured_image_size' );
delete_option( 'wp_ai_featured_image_quality' );
