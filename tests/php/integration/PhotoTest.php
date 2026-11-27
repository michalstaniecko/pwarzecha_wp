<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Photo helper pw_photo(): DS `sp-photo` frame around a responsive attachment image (#40).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use PW\Tests\BlockTestCase;

/**
 * Attachment created with the factory; metadata lists the theme sizes (no image processing).
 */
class PhotoTest extends BlockTestCase {

	/**
	 * 16:9 JPEG attachment with theme sub-sizes and an alt text.
	 *
	 * @param string $alt Alt text from the media library.
	 */
	private function attachment( string $alt = 'Trening dzieci w parach' ): int {
		$id    = self::factory()->attachment->create_object(
			array(
				'file'           => '2026/11/trening.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$sizes = array();
		foreach ( array(
			'pw-hero'    => array( 1920, 1080 ),
			'pw-hero-md' => array( 1280, 720 ),
			'pw-wide'    => array( 960, 540 ),
			'pw-photo'   => array( 800, 600 ),
			'pw-square'  => array( 600, 600 ),
		) as $name => $size ) {
			$sizes[ $name ] = array(
				'file'      => "trening-{$size[0]}x{$size[1]}.webp",
				'width'     => $size[0],
				'height'    => $size[1],
				'mime-type' => 'image/webp',
			);
		}
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 2560,
				'height' => 1440,
				'file'   => '2026/11/trening.jpg',
				'sizes'  => $sizes,
			)
		);
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		return $id;
	}

	/**
	 * The <img> inside the frame.
	 *
	 * @param string $html Markup.
	 */
	private function img( string $html ): \Dom\Element {
		$doc = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><body>' . $html, LIBXML_NOERROR );
		$img = $doc->querySelector( 'div.sp-photo > img' );
		$this->assertNotNull( $img, "No div.sp-photo > img in: $html" );
		return $img;
	}

	public function test_lazy_photo_in_a_wide_frame_with_dimensions_and_srcset(): void {
		$html = pw_photo( $this->attachment(), array( 'ratio' => 'wide' ) );

		$this->assertHtmlHasSelector( $html, 'div.sp-photo.sp-photo--wide > img' );
		$img = $this->img( $html );
		$this->assertSame( '960', $img->getAttribute( 'width' ), 'pw-wide by default for ratio wide' );
		$this->assertSame( '540', $img->getAttribute( 'height' ) );
		$this->assertStringContainsString( 'trening-960x540.webp', $img->getAttribute( 'src' ) );
		$this->assertStringContainsString( 'trening-1920x1080.webp 1920w', $img->getAttribute( 'srcset' ) );
		$this->assertSame( 'lazy', $img->getAttribute( 'loading' ) );
		$this->assertSame( 'async', $img->getAttribute( 'decoding' ) );
		$this->assertFalse( $img->hasAttribute( 'fetchpriority' ) );
		$this->assertSame( 'Trening dzieci w parach', $img->getAttribute( 'alt' ) );
	}

	public function test_priority_photo_loads_eagerly_with_high_fetch_priority(): void {
		$img = $this->img(
			pw_photo(
				$this->attachment(),
				array(
					'ratio'    => 'wide',
					'size'     => 'pw-hero',
					'sizes'    => '100vw',
					'priority' => true,
				)
			)
		);

		$this->assertSame( 'eager', $img->getAttribute( 'loading' ) );
		$this->assertSame( 'high', $img->getAttribute( 'fetchpriority' ) );
		$this->assertSame( '1920', $img->getAttribute( 'width' ) );
		$this->assertSame( '1080', $img->getAttribute( 'height' ) );
		$this->assertSame( '100vw', $img->getAttribute( 'sizes' ) );
	}

	/**
	 * Ratio => default size width x height.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public static function ratios(): array {
		return array(
			'photo (default)' => array( '', 'sp-photo--photo', '800' ),
			'square'          => array( 'square', 'sp-photo--square', '600' ),
			'unknown'         => array( 'tall', 'sp-photo--photo', '800' ),
		);
	}

	/**
	 * Ratio classes and default sizes.
	 *
	 * @dataProvider ratios
	 *
	 * @param string $ratio Ratio argument.
	 * @param string $frame Frame modifier.
	 * @param string $width Default size width.
	 */
	public function test_ratio_sets_the_frame_and_default_size( string $ratio, string $frame, string $width ): void {
		$html = pw_photo( $this->attachment(), '' === $ratio ? array() : array( 'ratio' => $ratio ) );

		$this->assertHtmlHasSelector( $html, "div.sp-photo.$frame > img" );
		$this->assertSame( $width, $this->img( $html )->getAttribute( 'width' ) );
	}

	public function test_extra_class_and_escaped_alt(): void {
		$html = pw_photo( $this->attachment( self::XSS ), array( 'class' => 'sp-photo--bleed "x" <y>' ) );

		$this->assertHtmlHasSelector( $html, 'div.sp-photo.sp-photo--photo.sp-photo--bleed' );
		$this->assertNoUnescaped( $html );
	}

	public function test_missing_or_non_image_attachment_renders_nothing(): void {
		$pdf = self::factory()->attachment->create_object(
			array(
				'file'           => '2026/11/regulamin.pdf',
				'post_mime_type' => 'application/pdf',
			)
		);

		$this->assertSame( '', pw_photo( 0, array() ) );
		$this->assertSame( '', pw_photo( 999999, array() ) );
		$this->assertSame( '', pw_photo( $pdf, array() ) );
	}
}
