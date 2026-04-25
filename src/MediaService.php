<?php
/**
 * Media library service.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Saves generated images to the WordPress media library.
 */
final class MediaService {

	/**
	 * Save a base64-encoded image to the media library and set it as the post's featured image.
	 *
	 * @param string $base64_data Base64-encoded image data.
	 * @param int    $post_id     The post ID to attach the image to.
	 * @return int The attachment ID.
	 *
	 * @throws \RuntimeException If the save operation fails.
	 */
	public function save_from_base64( string $base64_data, int $post_id ): int {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Required to decode API response.
		$image_data = base64_decode( $base64_data, true );

		if ( false === $image_data ) {
			throw new \RuntimeException(
				esc_html__( 'Failed to decode base64 image data.', 'wp-ai-featured-image' )
			);
		}

		$filename = sprintf( 'ai-featured-%d-%d.png', $post_id, time() );
		$upload   = wp_upload_bits( $filename, null, $image_data );

		if ( ! empty( $upload['error'] ) ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to upload image: %s', 'wp-ai-featured-image' ),
					esc_html( (string) $upload['error'] )
				)
			);
		}

		$attachment = array(
			'post_mime_type' => $upload['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );

		if ( 0 === $attachment_id ) {
			throw new \RuntimeException(
				esc_html__( 'Failed to create attachment.', 'wp-ai-featured-image' )
			);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $attachment_id;
	}
}
