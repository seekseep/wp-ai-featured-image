<?php
/**
 * Configuration resolver.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Resolves plugin configuration from constants, environment variables, or database options.
 */
class ConfigResolver {

	private const OPTION_PREFIX = 'wp_ai_featured_image_';

	/**
	 * Get the OpenAI API key.
	 *
	 * Resolution order: PHP constant → environment variable → database option.
	 */
	public function get_api_key(): string {
		if ( defined( 'WP_AI_FEATURED_IMAGE_API_KEY' ) ) {
			return (string) constant( 'WP_AI_FEATURED_IMAGE_API_KEY' );
		}

		$env_key = getenv( 'OPENAI_API_KEY' );
		if ( is_string( $env_key ) && '' !== $env_key ) {
			return $env_key;
		}

		$option = get_option( self::OPTION_PREFIX . 'api_key', '' );
		return is_string( $option ) ? $option : '';
	}

	/**
	 * Check whether an API key is configured.
	 */
	public function has_api_key(): bool {
		return '' !== $this->get_api_key();
	}

	/**
	 * Check whether the API key is set via a constant or environment variable.
	 */
	public function is_api_key_from_external(): bool {
		if ( defined( 'WP_AI_FEATURED_IMAGE_API_KEY' ) ) {
			return true;
		}

		$env_key = getenv( 'OPENAI_API_KEY' );
		return is_string( $env_key ) && '' !== $env_key;
	}

	/**
	 * Get the OpenAI model name.
	 */
	public function get_model(): string {
		$model = get_option( self::OPTION_PREFIX . 'model', 'gpt-image-1' );
		return is_string( $model ) ? $model : 'gpt-image-1';
	}

	/**
	 * Get the image size.
	 */
	public function get_size(): string {
		$size = get_option( self::OPTION_PREFIX . 'size', '1536x1024' );
		return is_string( $size ) ? $size : '1536x1024';
	}

	/**
	 * Get the image quality.
	 */
	public function get_quality(): string {
		$quality = get_option( self::OPTION_PREFIX . 'quality', 'auto' );
		return is_string( $quality ) ? $quality : 'auto';
	}
}
