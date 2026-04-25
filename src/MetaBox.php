<?php
/**
 * Post editor meta box.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Adds a meta box to the post editor for generating featured images.
 */
final class MetaBox {

	/**
	 * Register the meta box.
	 */
	public function register(): void {
		add_meta_box(
			'wp-ai-featured-image',
			__( 'AI Featured Image', 'wp-ai-featured-image' ),
			array( $this, 'render' ),
			array( 'post', 'page' ),
			'side',
			'low',
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @param \WP_Post $post The current post.
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'wp_ai_generate_featured_image', 'wp_ai_featured_image_nonce' );
		?>
		<div id="wp-ai-featured-image-box">
			<p class="description">
				<?php esc_html_e( 'Generate a featured image from the post title, content, categories, and tags using OpenAI.', 'wp-ai-featured-image' ); ?>
			</p>
			<p>
				<button type="button" id="wp-ai-generate-btn" class="button button-primary" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">
					<?php esc_html_e( 'Generate Featured Image', 'wp-ai-featured-image' ); ?>
				</button>
			</p>
			<div id="wp-ai-generate-status"></div>
			<div id="wp-ai-generate-preview"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts on the post editor screen.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'wp-ai-featured-image',
			WP_AI_FEATURED_IMAGE_URL . 'assets/js/generate-featured-image.js',
			array( 'jquery' ),
			WP_AI_FEATURED_IMAGE_VERSION,
			true,
		);

		wp_localize_script(
			'wp-ai-featured-image',
			'wpAiFeaturedImage',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_ai_generate_featured_image' ),
			),
		);
	}
}
