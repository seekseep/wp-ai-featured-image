<?php
/**
 * Tests for ConfigResolver.
 *
 * @package WpAiFeaturedImage
 */

declare(strict_types=1);

namespace WpAiFeaturedImage\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WpAiFeaturedImage\ConfigResolver;

/**
 * @covers \WpAiFeaturedImage\ConfigResolver
 */
final class ConfigResolverTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_api_key_returns_empty_when_nothing_configured(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_ai_featured_image_api_key', '' )
			->andReturn( '' );

		$resolver = new ConfigResolver();

		$this->assertSame( '', $resolver->get_api_key() );
	}

	public function test_has_api_key_returns_false_when_empty(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_ai_featured_image_api_key', '' )
			->andReturn( '' );

		$resolver = new ConfigResolver();

		$this->assertFalse( $resolver->has_api_key() );
	}

	public function test_get_api_key_returns_db_option(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_ai_featured_image_api_key', '' )
			->andReturn( 'sk-test-key-from-db' );

		$resolver = new ConfigResolver();

		$this->assertSame( 'sk-test-key-from-db', $resolver->get_api_key() );
	}

	public function test_has_api_key_returns_true_when_set(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_ai_featured_image_api_key', '' )
			->andReturn( 'sk-test-key' );

		$resolver = new ConfigResolver();

		$this->assertTrue( $resolver->has_api_key() );
	}

	public function test_get_model_returns_default(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_ai_featured_image_model', 'gpt-image-1' )
			->andReturn( 'gpt-image-1' );

		$resolver = new ConfigResolver();

		$this->assertSame( 'gpt-image-1', $resolver->get_model() );
	}

	public function test_get_size_returns_default(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_ai_featured_image_size', '1536x1024' )
			->andReturn( '1536x1024' );

		$resolver = new ConfigResolver();

		$this->assertSame( '1536x1024', $resolver->get_size() );
	}

	public function test_get_quality_returns_default(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_ai_featured_image_quality', 'auto' )
			->andReturn( 'auto' );

		$resolver = new ConfigResolver();

		$this->assertSame( 'auto', $resolver->get_quality() );
	}
}
