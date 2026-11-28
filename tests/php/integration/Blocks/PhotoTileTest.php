<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Block pw/photo-tile: tile with a 4:3 photo, H3 and a short text (#60).
 *
 * @package PW
 */

namespace PW\Tests\Integration\Blocks;

use PW\Tests\BlockTestCase;

/**
 * Rendered from block markup, the way the front end does.
 */
class PhotoTileTest extends BlockTestCase {

	/**
	 * 4:3 JPEG attachment with the pw-photo size and an alt text.
	 */
	private function attachment(): int {
		$id = self::factory()->attachment->create_object(
			array(
				'file'           => '2026/11/kafelek.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 1600,
				'height' => 1200,
				'file'   => '2026/11/kafelek.jpg',
				'sizes'  => array(
					'pw-photo' => array(
						'file'      => 'kafelek-800x600.webp',
						'width'     => 800,
						'height'    => 600,
						'mime-type' => 'image/webp',
					),
				),
			)
		);
		update_post_meta( $id, '_wp_attachment_image_alt', 'Ćwiczenia w parach' );
		return $id;
	}

	public function test_full_markup(): void {
		$html = $this->render_block_markup(
			'pw/photo-tile',
			array(
				'zdjecieId' => $this->attachment(),
				'tytul'     => 'Bezpieczeństwo',
				'tekst'     => 'Ćwiczymy w kontrolowanych warunkach.',
			)
		);

		$this->assertHtmlHasSelector( $html, 'article.sp-photo-tile > div.sp-photo.sp-photo--photo > img' );
		$this->assertHtmlHasSelector( $html, 'article.sp-photo-tile > div.sp-photo-tile__body > h3' );
		$this->assertHtmlHasSelector( $html, 'div.sp-photo-tile__body > h3 + p' );
		$this->assertStringContainsString( '>Bezpieczeństwo</h3>', $html );
		$this->assertStringContainsString( '>Ćwiczymy w kontrolowanych warunkach.</p>', $html );
	}

	public function test_photo_is_lazy_with_dimensions_and_alt_from_the_attachment(): void {
		$html = $this->render_block_markup(
			'pw/photo-tile',
			array(
				'zdjecieId' => $this->attachment(),
				'tytul'     => 'Bezpieczeństwo',
			)
		);

		$this->assertHtmlHasSelector( $html, 'img[loading="lazy"][width="800"][height="600"][alt="Ćwiczenia w parach"]' );
	}

	public function test_without_photo_the_tile_has_no_image(): void {
		$html = $this->render_block_markup( 'pw/photo-tile', array( 'tytul' => 'Bezpieczeństwo' ) );

		$this->assertHtmlHasSelector( $html, 'article.sp-photo-tile > div.sp-photo-tile__body:first-child > h3' );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringNotContainsString( 'sp-photo ', $html );
	}

	public function test_without_text_there_is_no_paragraph(): void {
		$html = $this->render_block_markup( 'pw/photo-tile', array( 'tytul' => 'Bezpieczeństwo' ) );

		$this->assertStringNotContainsString( '<p', $html );
	}

	public function test_empty_title_renders_nothing(): void {
		$html = $this->render_block_markup(
			'pw/photo-tile',
			array(
				'zdjecieId' => $this->attachment(),
				'tytul'     => ' ',
				'tekst'     => 'Tekst bez tytułu.',
			)
		);

		$this->assertSame( '', trim( $html ) );
	}

	public function test_title_and_text_are_escaped(): void {
		$html = $this->render_block_markup(
			'pw/photo-tile',
			array(
				'tytul' => 'Tytuł ' . self::XSS,
				'tekst' => 'Tekst <strong>ważny</strong> ' . self::XSS,
			)
		);

		$this->assertNoUnescaped( $html );
		$this->assertHtmlHasSelector( $html, 'p > strong', 'Inline formatting of the text survives' );
	}
}
