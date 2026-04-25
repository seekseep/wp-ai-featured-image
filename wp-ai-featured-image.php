<?php
/**
 * Plugin Name: WP AI Featured Image Generator
 * Plugin URI:  https://github.com/seekseep/wp-ai-featured-image-generator
 * Description: Auto-generates featured images from post content using OpenAI Images API.
 * Version:     1.0.0
 * Requires PHP: 8.2
 * Author:      seekseep
 * Author URI:  https://github.com/seekseep
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wp-ai-featured-image
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_AI_FEATURED_IMAGE_VERSION', '1.0.0' );
define( 'WP_AI_FEATURED_IMAGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_AI_FEATURED_IMAGE_URL', plugin_dir_url( __FILE__ ) );

if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: required PHP version */
						__( 'WP AI Featured Image Generator requires PHP %s or higher.', 'wp-ai-featured-image' ),
						'8.2'
					)
				)
			);
		}
	);
	return;
}

$wp_ai_featured_image_autoloader = WP_AI_FEATURED_IMAGE_DIR . 'vendor/autoload.php';

if ( ! file_exists( $wp_ai_featured_image_autoloader ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'WP AI Featured Image Generator requires Composer autoloader. Please run "composer install".', 'wp-ai-featured-image' )
			);
		}
	);
	return;
}

require_once $wp_ai_featured_image_autoloader;

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'wp-ai-featured-image', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

( new \WpAiFeaturedImage\Plugin() )->register();
