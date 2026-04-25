<?php
/**
 * Admin settings page.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Registers and renders the plugin settings page.
 */
final class AdminSettings {

	private const OPTION_GROUP = 'wp_ai_featured_image_settings';
	private const PAGE_SLUG    = 'wp-ai-featured-image';
	private const SECTION_ID   = 'wp_ai_featured_image_main';

	/**
	 * Constructor.
	 *
	 * @param ConfigResolver $config The configuration resolver.
	 */
	public function __construct(
		private readonly ConfigResolver $config,
	) {}

	/**
	 * Add the settings page to the admin menu.
	 */
	public function add_menu_page(): void {
		add_options_page(
			__( 'AI Featured Image', 'wp-ai-featured-image' ),
			__( 'AI Featured Image', 'wp-ai-featured-image' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings(): void {
		add_settings_section(
			self::SECTION_ID,
			__( 'OpenAI API Settings', 'wp-ai-featured-image' ),
			array( $this, 'render_section_description' ),
			self::PAGE_SLUG,
		);

		// API Key.
		register_setting( self::OPTION_GROUP, 'wp_ai_featured_image_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'wp_ai_featured_image_api_key',
			__( 'API Key', 'wp-ai-featured-image' ),
			array( $this, 'render_api_key_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID,
		);

		// Model.
		register_setting( self::OPTION_GROUP, 'wp_ai_featured_image_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'wp_ai_featured_image_model',
			__( 'Model', 'wp-ai-featured-image' ),
			array( $this, 'render_model_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID,
		);

		// Image Size.
		register_setting( self::OPTION_GROUP, 'wp_ai_featured_image_size', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'wp_ai_featured_image_size',
			__( 'Image Size', 'wp-ai-featured-image' ),
			array( $this, 'render_size_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID,
		);

		// Quality.
		register_setting( self::OPTION_GROUP, 'wp_ai_featured_image_quality', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'wp_ai_featured_image_quality',
			__( 'Quality', 'wp-ai-featured-image' ),
			array( $this, 'render_quality_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID,
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

		if ( ! $this->config->has_api_key() ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'OpenAI API key is not configured. Please set it below or define WP_AI_FEATURED_IMAGE_API_KEY in wp-config.php.', 'wp-ai-featured-image' );
			echo '</p></div>';
		} elseif ( $this->config->is_api_key_from_external() ) {
			echo '<div class="notice notice-info"><p>';
			echo esc_html__( 'API key is configured via a PHP constant or environment variable. The field below is ignored.', 'wp-ai-featured-image' );
			echo '</p></div>';
		}

		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTION_GROUP );
		do_settings_sections( self::PAGE_SLUG );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the section description.
	 */
	public function render_section_description(): void {
		echo '<p>';
		echo esc_html__( 'Configure the OpenAI API settings for image generation.', 'wp-ai-featured-image' );
		echo '</p>';
		echo '<p>';
		printf(
			/* translators: %s: URL to OpenAI platform */
			esc_html__( 'You can obtain an API key from %s.', 'wp-ai-featured-image' ),
			'<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">https://platform.openai.com/api-keys</a>'
		);
		echo '</p>';
		echo '<p>';
		echo esc_html__( 'Recommended: Define the API key in wp-config.php for security:', 'wp-ai-featured-image' );
		echo '</p>';
		echo "<pre><code>define( 'WP_AI_FEATURED_IMAGE_API_KEY', 'sk-your-api-key' );</code></pre>";
	}

	/**
	 * Render the API key field.
	 */
	public function render_api_key_field(): void {
		$is_external = $this->config->is_api_key_from_external();
		$value       = $is_external ? '' : get_option( 'wp_ai_featured_image_api_key', '' );

		echo '<input type="password" name="wp_ai_featured_image_api_key" value="' . esc_attr( is_string( $value ) ? $value : '' ) . '" class="regular-text"';
		if ( $is_external ) {
			echo ' disabled="disabled"';
		}
		echo ' />';

		if ( $is_external ) {
			echo '<p class="description">' . esc_html__( 'Configured via constant or environment variable.', 'wp-ai-featured-image' ) . '</p>';
		} else {
			echo '<p class="description">' . esc_html__( 'Optional. It is recommended to use wp-config.php or environment variables instead.', 'wp-ai-featured-image' ) . '</p>';
		}
	}

	/**
	 * Render the model field.
	 */
	public function render_model_field(): void {
		$current = $this->config->get_model();
		$models  = array(
			'gpt-image-1' => 'GPT Image 1',
			'dall-e-3'    => 'DALL-E 3',
			'dall-e-2'    => 'DALL-E 2',
		);

		echo '<select name="wp_ai_featured_image_model">';
		foreach ( $models as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render the size field.
	 */
	public function render_size_field(): void {
		$current = $this->config->get_size();
		$sizes   = array(
			'1536x1024' => __( '1536x1024 (Landscape)', 'wp-ai-featured-image' ),
			'1024x1024' => __( '1024x1024 (Square)', 'wp-ai-featured-image' ),
			'1024x1536' => __( '1024x1536 (Portrait)', 'wp-ai-featured-image' ),
		);

		echo '<select name="wp_ai_featured_image_size">';
		foreach ( $sizes as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Landscape is recommended for blog featured images.', 'wp-ai-featured-image' ) . '</p>';
	}

	/**
	 * Render the quality field.
	 */
	public function render_quality_field(): void {
		$current   = $this->config->get_quality();
		$qualities = array(
			'auto'   => __( 'Auto', 'wp-ai-featured-image' ),
			'high'   => __( 'High', 'wp-ai-featured-image' ),
			'medium' => __( 'Medium', 'wp-ai-featured-image' ),
			'low'    => __( 'Low', 'wp-ai-featured-image' ),
		);

		echo '<select name="wp_ai_featured_image_quality">';
		foreach ( $qualities as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}
}
