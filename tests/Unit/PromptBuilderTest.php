<?php
/**
 * Tests for PromptBuilder.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WpAiFeaturedImage\PromptBuilder;

/**
 * @covers \WpAiFeaturedImage\PromptBuilder
 */
final class PromptBuilderTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Create a mock WP_Post object.
	 *
	 * @param array<string, mixed> $props Post properties.
	 * @return \WP_Post
	 */
	private function create_mock_post( array $props = array() ): \WP_Post {
		$post                = Mockery::mock( \WP_Post::class );
		$post->ID            = $props['ID'] ?? 1;
		$post->post_title    = $props['post_title'] ?? 'Test Post Title';
		$post->post_excerpt  = $props['post_excerpt'] ?? '';
		$post->post_content  = $props['post_content'] ?? 'Test post content for the blog.';
		return $post;
	}

	public function test_build_with_full_data(): void {
		Functions\expect( 'wp_strip_all_tags' )
			->andReturnUsing( fn( string $text ): string => strip_tags( $text ) );

		$term1       = Mockery::mock( \WP_Term::class );
		$term1->name = 'Technology';
		$term2       = Mockery::mock( \WP_Term::class );
		$term2->name = 'AI';

		$tag1       = Mockery::mock( \WP_Term::class );
		$tag1->name = 'OpenAI';

		Functions\expect( 'wp_get_post_terms' )
			->andReturnUsing( function ( int $post_id, string $taxonomy ) use ( $term1, $term2, $tag1 ): array {
				if ( 'category' === $taxonomy ) {
					return array( $term1, $term2 );
				}
				return array( $tag1 );
			} );

		Functions\expect( 'is_wp_error' )
			->andReturn( false );

		$builder = new PromptBuilder();
		$prompt  = $builder->build( $this->create_mock_post() );

		$this->assertStringContainsString( 'Test Post Title', $prompt );
		$this->assertStringContainsString( 'Test post content for the blog.', $prompt );
		$this->assertStringContainsString( 'Technology, AI', $prompt );
		$this->assertStringContainsString( 'OpenAI', $prompt );
		$this->assertStringContainsString( 'Do not include any text', $prompt );
		$this->assertStringContainsString( 'Horizontal landscape', $prompt );
	}

	public function test_build_omits_categories_when_empty(): void {
		Functions\expect( 'wp_strip_all_tags' )
			->andReturnUsing( fn( string $text ): string => strip_tags( $text ) );

		Functions\expect( 'wp_get_post_terms' )
			->with( 1, 'category' )
			->andReturn( array() );

		Functions\expect( 'wp_get_post_terms' )
			->with( 1, 'post_tag' )
			->andReturn( array() );

		Functions\expect( 'is_wp_error' )
			->andReturn( false );

		$builder = new PromptBuilder();
		$prompt  = $builder->build( $this->create_mock_post() );

		$this->assertStringNotContainsString( 'Categories:', $prompt );
		$this->assertStringNotContainsString( 'Tags:', $prompt );
	}

	public function test_build_uses_excerpt_when_available(): void {
		Functions\expect( 'wp_strip_all_tags' )
			->andReturnUsing( fn( string $text ): string => strip_tags( $text ) );

		Functions\expect( 'wp_get_post_terms' )
			->andReturn( array() );

		Functions\expect( 'is_wp_error' )
			->andReturn( false );

		$post   = $this->create_mock_post( array( 'post_excerpt' => 'Custom excerpt text' ) );
		$builder = new PromptBuilder();
		$prompt  = $builder->build( $post );

		$this->assertStringContainsString( 'Custom excerpt text', $prompt );
	}

	public function test_build_truncates_long_content(): void {
		Functions\expect( 'wp_strip_all_tags' )
			->andReturnUsing( fn( string $text ): string => strip_tags( $text ) );

		Functions\expect( 'wp_get_post_terms' )
			->andReturn( array() );

		Functions\expect( 'is_wp_error' )
			->andReturn( false );

		$long_content = str_repeat( 'a', 300 );
		$post         = $this->create_mock_post( array( 'post_content' => $long_content ) );
		$builder      = new PromptBuilder();
		$prompt       = $builder->build( $post );

		$this->assertStringContainsString( '...', $prompt );
	}

	public function test_build_strips_html_from_content(): void {
		Functions\expect( 'wp_strip_all_tags' )
			->andReturnUsing( fn( string $text ): string => strip_tags( $text ) );

		Functions\expect( 'wp_get_post_terms' )
			->andReturn( array() );

		Functions\expect( 'is_wp_error' )
			->andReturn( false );

		$post   = $this->create_mock_post( array( 'post_content' => '<p>Hello <strong>World</strong></p>' ) );
		$builder = new PromptBuilder();
		$prompt  = $builder->build( $post );

		$this->assertStringNotContainsString( '<p>', $prompt );
		$this->assertStringNotContainsString( '<strong>', $prompt );
		$this->assertStringContainsString( 'Hello World', $prompt );
	}
}
