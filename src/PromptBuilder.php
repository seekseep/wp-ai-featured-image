<?php
/**
 * Prompt builder.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage;

/**
 * Builds an image generation prompt from a WordPress post.
 */
final class PromptBuilder {

	private const MAX_EXCERPT_LENGTH = 200;

	/**
	 * Build a prompt string from a post.
	 *
	 * @param \WP_Post $post The post to build a prompt from.
	 */
	public function build( \WP_Post $post ): string {
		$title      = $this->sanitize_for_prompt( $post->post_title );
		$excerpt    = $this->get_excerpt( $post );
		$categories = $this->get_taxonomy_terms( $post->ID, 'category' );
		$tags       = $this->get_taxonomy_terms( $post->ID, 'post_tag' );

		$lines   = array();
		$lines[] = 'Create a blog featured image illustration.';
		$lines[] = sprintf( 'Topic: %s.', $title );

		if ( '' !== $excerpt ) {
			$lines[] = sprintf( 'Context: %s.', $excerpt );
		}

		if ( '' !== $categories ) {
			$lines[] = sprintf( 'Categories: %s.', $categories );
		}

		if ( '' !== $tags ) {
			$lines[] = sprintf( 'Tags: %s.', $tags );
		}

		$lines[] = 'Style: modern, clean illustration suitable for a blog header. Horizontal landscape composition.';
		$lines[] = 'Do not include any text, letters, numbers, or words in the image.';

		return implode( "\n", $lines );
	}

	/**
	 * Get an excerpt from the post.
	 *
	 * @param \WP_Post $post The post.
	 */
	private function get_excerpt( \WP_Post $post ): string {
		$excerpt = $post->post_excerpt;

		if ( '' === $excerpt ) {
			$excerpt = wp_strip_all_tags( $post->post_content );
		}

		$excerpt = $this->sanitize_for_prompt( $excerpt );

		if ( mb_strlen( $excerpt ) > self::MAX_EXCERPT_LENGTH ) {
			$excerpt = mb_substr( $excerpt, 0, self::MAX_EXCERPT_LENGTH ) . '...';
		}

		return $excerpt;
	}

	/**
	 * Get comma-separated taxonomy term names.
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $taxonomy The taxonomy name.
	 */
	private function get_taxonomy_terms( int $post_id, string $taxonomy ): string {
		$terms = wp_get_post_terms( $post_id, $taxonomy );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$names = array_map(
			static fn( \WP_Term $term ): string => $term->name,
			$terms
		);

		return implode( ', ', $names );
	}

	/**
	 * Sanitize a string for use in an image generation prompt.
	 *
	 * Removes HTML tags, newlines, tabs, and excess whitespace.
	 *
	 * @param string $text The input text.
	 */
	private function sanitize_for_prompt( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/[\r\n\t]+/', ' ', $text ) ?? $text;
		$text = preg_replace( '/\s{2,}/', ' ', $text ) ?? $text;
		return trim( $text );
	}
}
