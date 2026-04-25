<?php
/**
 * OpenAI image generation service.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Calls the OpenAI Images API to generate images.
 */
final class OpenAiImageService {

	private const API_ENDPOINT = 'https://api.openai.com/v1/images/generations';

	/**
	 * Constructor.
	 *
	 * @param ConfigResolver $config The configuration resolver.
	 */
	public function __construct(
		private readonly ConfigResolver $config,
	) {}

	/**
	 * Generate an image from a prompt.
	 *
	 * @param string $prompt The image generation prompt.
	 * @return string Base64-encoded image data.
	 *
	 * @throws \RuntimeException If the API request fails.
	 */
	public function generate( string $prompt ): string {
		if ( ! $this->config->has_api_key() ) {
			throw new \RuntimeException(
				esc_html__( 'OpenAI API key is not configured.', 'wp-ai-featured-image' )
			);
		}

		$body = array(
			'model'           => $this->config->get_model(),
			'prompt'          => $prompt,
			'n'               => 1,
			'size'            => $this->config->get_size(),
			'quality'         => $this->config->get_quality(),
			'response_format' => 'b64_json',
		);

		$response = wp_remote_post(
			self::API_ENDPOINT,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->config->get_api_key(),
					'Content-Type'  => 'application/json',
				),
				'body'    => (string) wp_json_encode( $body ),
				'timeout' => 120,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'OpenAI API request failed: %s', 'wp-ai-featured-image' ),
					esc_html( $response->get_error_message() )
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			$error_body    = wp_remote_retrieve_body( $response );
			$error_data    = json_decode( $error_body, true );
			$error_message = is_array( $error_data ) && isset( $error_data['error']['message'] )
				? $error_data['error']['message']
				: sprintf( 'HTTP %d', $status_code );

			throw new \RuntimeException(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'OpenAI API error: %s', 'wp-ai-featured-image' ),
					esc_html( (string) $error_message )
				)
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( ! is_array( $data ) || ! isset( $data['data'][0]['b64_json'] ) ) {
			throw new \RuntimeException(
				esc_html__( 'Unexpected response format from OpenAI API.', 'wp-ai-featured-image' )
			);
		}

		return $data['data'][0]['b64_json'];
	}
}
