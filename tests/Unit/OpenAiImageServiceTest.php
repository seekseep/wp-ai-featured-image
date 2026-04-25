<?php
/**
 * Tests for OpenAiImageService.
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
use WpAiFeaturedImage\ConfigResolver;
use WpAiFeaturedImage\OpenAiImageService;

/**
 * @covers \WpAiFeaturedImage\OpenAiImageService
 */
final class OpenAiImageServiceTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function create_config_mock(
		string $api_key = 'sk-test',
		bool $has_key = true,
		string $model = 'gpt-image-1',
		string $size = '1536x1024',
		string $quality = 'auto',
	): ConfigResolver {
		$config = Mockery::mock( ConfigResolver::class );
		$config->shouldReceive( 'has_api_key' )->andReturn( $has_key );
		$config->shouldReceive( 'get_api_key' )->andReturn( $api_key );
		$config->shouldReceive( 'get_model' )->andReturn( $model );
		$config->shouldReceive( 'get_size' )->andReturn( $size );
		$config->shouldReceive( 'get_quality' )->andReturn( $quality );
		return $config;
	}

	public function test_generate_throws_when_no_api_key(): void {
		$config  = $this->create_config_mock( api_key: '', has_key: false );

		Functions\expect( 'esc_html__' )
			->andReturnUsing( fn( string $text ): string => $text );

		$service = new OpenAiImageService( $config );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'API key is not configured' );
		$service->generate( 'test prompt' );
	}

	public function test_generate_returns_base64_on_success(): void {
		$config = $this->create_config_mock();

		$expected_base64 = base64_encode( 'fake-image-data' );

		Functions\expect( 'wp_json_encode' )
			->once()
			->andReturnUsing( fn( $data ): string|false => json_encode( $data ) );

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				array(
					'response' => array( 'code' => 200 ),
					'body'     => json_encode(
						array(
							'data' => array(
								array( 'b64_json' => $expected_base64 ),
							),
						)
					),
				)
			);

		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn(
			json_encode(
				array(
					'data' => array(
						array( 'b64_json' => $expected_base64 ),
					),
				)
			)
		);

		$service = new OpenAiImageService( $config );
		$result  = $service->generate( 'test prompt' );

		$this->assertSame( $expected_base64, $result );
	}

	public function test_generate_throws_on_wp_error(): void {
		$config = $this->create_config_mock();

		$wp_error = Mockery::mock( \WP_Error::class );
		$wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Connection timeout' );

		Functions\expect( 'wp_json_encode' )
			->once()
			->andReturnUsing( fn( $data ): string|false => json_encode( $data ) );

		Functions\expect( 'wp_remote_post' )->once()->andReturn( $wp_error );
		Functions\expect( 'is_wp_error' )->once()->andReturn( true );

		Functions\expect( 'esc_html__' )
			->andReturnUsing( fn( string $text ): string => $text );

		Functions\expect( 'esc_html' )
			->andReturnUsing( fn( string $text ): string => $text );

		$service = new OpenAiImageService( $config );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Connection timeout' );
		$service->generate( 'test prompt' );
	}

	public function test_generate_throws_on_api_error(): void {
		$config = $this->create_config_mock();

		Functions\expect( 'wp_json_encode' )
			->once()
			->andReturnUsing( fn( $data ): string|false => json_encode( $data ) );

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				array(
					'response' => array( 'code' => 401 ),
					'body'     => json_encode(
						array(
							'error' => array( 'message' => 'Invalid API key' ),
						)
					),
				)
			);

		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 401 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn(
			json_encode(
				array(
					'error' => array( 'message' => 'Invalid API key' ),
				)
			)
		);

		Functions\expect( 'esc_html__' )
			->andReturnUsing( fn( string $text ): string => $text );

		Functions\expect( 'esc_html' )
			->andReturnUsing( fn( string $text ): string => $text );

		$service = new OpenAiImageService( $config );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid API key' );
		$service->generate( 'test prompt' );
	}
}
