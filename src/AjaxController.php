<?php
/**
 * AJAX controller.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Handles the AJAX request for generating a featured image.
 */
final class AjaxController {

	/**
	 * Constructor.
	 *
	 * @param ConfigResolver     $config         The configuration resolver.
	 * @param OpenAiImageService $openai_service The OpenAI image service.
	 * @param MediaService       $media_service  The media service.
	 * @param PromptBuilder      $prompt_builder The prompt builder.
	 */
	public function __construct(
		private readonly ConfigResolver $config,
		private readonly OpenAiImageService $openai_service,
		private readonly MediaService $media_service,
		private readonly PromptBuilder $prompt_builder,
	) {}

	/**
	 * Handle the generate image AJAX request.
	 */
	public function handle(): void {
		check_ajax_referer( 'wp_ai_generate_featured_image' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( 0 === $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'wp-ai-featured-image' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this post.', 'wp-ai-featured-image' ) ), 403 );
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'wp-ai-featured-image' ) ) );
		}

		if ( ! $this->config->has_api_key() ) {
			wp_send_json_error( array( 'message' => __( 'OpenAI API key is not configured. Please set it in Settings > AI Featured Image.', 'wp-ai-featured-image' ) ) );
		}

		try {
			$prompt        = $this->prompt_builder->build( $post );
			$base64_data   = $this->openai_service->generate( $prompt );
			$attachment_id = $this->media_service->save_from_base64( $base64_data, $post_id );

			wp_send_json_success(
				array(
					'attachment_id' => $attachment_id,
					'url'           => wp_get_attachment_url( $attachment_id ),
				)
			);
		} catch ( \RuntimeException $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle the set thumbnail AJAX request.
	 */
	public function handle_set_thumbnail(): void {
		check_ajax_referer( 'wp_ai_generate_featured_image' );

		$post_id       = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( 0 === $post_id || 0 === $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'wp-ai-featured-image' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this post.', 'wp-ai-featured-image' ) ), 403 );
		}

		$result = set_post_thumbnail( $post_id, $attachment_id );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to set featured image.', 'wp-ai-featured-image' ) ) );
		}

		wp_send_json_success();
	}
}
