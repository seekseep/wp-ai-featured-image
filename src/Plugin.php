<?php
/**
 * Plugin orchestrator.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Main plugin class that wires up all components and registers hooks.
 */
final class Plugin {

	/**
	 * Register all plugin hooks.
	 */
	public function register(): void {
		$config         = new ConfigResolver();
		$openai_service = new OpenAiImageService( $config );
		$media_service  = new MediaService();
		$prompt_builder = new PromptBuilder();
		$ajax           = new AjaxController( $config, $openai_service, $media_service, $prompt_builder );
		$settings       = new AdminSettings( $config );
		$meta_box       = new MetaBox();

		add_action( 'admin_menu', array( $settings, 'add_menu_page' ) );
		add_action( 'admin_init', array( $settings, 'register_settings' ) );
		add_action( 'add_meta_boxes', array( $meta_box, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $meta_box, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wp_ai_generate_featured_image', array( $ajax, 'handle' ) );
		add_action( 'wp_ajax_wp_ai_set_thumbnail', array( $ajax, 'handle_set_thumbnail' ) );
	}
}
