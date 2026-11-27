<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Theme image sizes, big image threshold and WebP output (#40).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_UnitTestCase;

/**
 * Registered sizes as WordPress sees them.
 */
class ImageSizesTest extends WP_UnitTestCase {

	/**
	 * Size => [ width, height ].
	 *
	 * @return array<string, array{string, int, int}>
	 */
	public static function theme_sizes(): array {
		return array(
			'hero'    => array( 'pw-hero', 1920, 1080 ),
			'hero-md' => array( 'pw-hero-md', 1280, 720 ),
			'wide'    => array( 'pw-wide', 960, 540 ),
			'photo'   => array( 'pw-photo', 800, 600 ),
			'square'  => array( 'pw-square', 600, 600 ),
		);
	}

	/**
	 * Hard-cropped theme sizes.
	 *
	 * @dataProvider theme_sizes
	 *
	 * @param string $name   Size name.
	 * @param int    $width  Width.
	 * @param int    $height Height.
	 */
	public function test_theme_size_is_registered_and_cropped( string $name, int $width, int $height ): void {
		$sizes = wp_get_registered_image_subsizes();

		$this->assertArrayHasKey( $name, $sizes );
		$this->assertSame(
			array(
				'width'  => $width,
				'height' => $height,
				'crop'   => true,
			),
			$sizes[ $name ]
		);
	}

	public function test_unused_core_sizes_are_removed(): void {
		$sizes = wp_get_registered_image_subsizes();

		$this->assertArrayNotHasKey( '1536x1536', $sizes );
		$this->assertArrayNotHasKey( '2048x2048', $sizes );
	}

	public function test_big_image_threshold_is_2560(): void {
		$this->assertSame( 2560, (int) apply_filters( 'big_image_size_threshold', 2560 * 2, array( 5000, 4000 ), '/tmp/x.jpg', 0 ) );
	}

	public function test_jpeg_is_written_as_webp(): void {
		$formats = apply_filters( 'image_editor_output_format', array(), '/tmp/x.jpg', 'image/jpeg' );

		$this->assertSame( 'image/webp', $formats['image/jpeg'] ?? null );
		$this->assertArrayNotHasKey( 'image/png', $formats );
	}
}
