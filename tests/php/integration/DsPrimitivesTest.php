<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * DS primitives wired into WordPress (#41): fonts, editor restrictions, block styles, preload.
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_Block_Styles_Registry;
use WP_UnitTestCase;

/**
 * Observed only through WordPress APIs (global settings, block styles registry, wp_head).
 */
class DsPrimitivesTest extends WP_UnitTestCase {

	/**
	 * Theme font families with their faces.
	 *
	 * @return array<string, array<string, mixed>> Keyed by slug.
	 */
	private function theme_font_families(): array {
		$families = wp_get_global_settings( array( 'typography', 'fontFamilies' ) )['theme'] ?? array();
		return array_column( $families, null, 'slug' );
	}

	/**
	 * Both families have latin + latin-ext woff2 faces from the theme with font-display: swap.
	 */
	public function test_fonts_are_self_hosted_woff2_with_swap(): void {
		$families = $this->theme_font_families();

		foreach ( array( 'archivo', 'source-sans-3' ) as $slug ) {
			$this->assertArrayHasKey( $slug, $families );
			$faces = $families[ $slug ]['fontFace'] ?? array();
			$this->assertCount( 2, $faces, "$slug: latin + latin-ext" );
			foreach ( $faces as $face ) {
				$this->assertSame( 'swap', $face['fontDisplay'] );
				foreach ( (array) $face['src'] as $src ) {
					$this->assertStringStartsWith( 'file:./assets/fonts/', $src );
					$this->assertStringEndsWith( '.woff2', $src );
				}
			}
		}
	}

	/**
	 * Printed @font-face rules load theme files only, never Google Fonts.
	 */
	public function test_font_face_css_points_at_theme_files_only(): void {
		ob_start();
		wp_print_font_faces();
		$css = (string) ob_get_clean();

		$this->assertStringContainsString( '/assets/fonts/archivo-latin.woff2', $css );
		$this->assertStringContainsString( '/assets/fonts/source-sans-3-latin-ext.woff2', $css );
		$this->assertStringContainsString( 'font-display:swap', str_replace( ' ', '', $css ) );
		$this->assertStringNotContainsString( 'fonts.googleapis.com', $css );
		$this->assertStringNotContainsString( 'fonts.gstatic.com', $css );
	}

	/**
	 * Settings the editor must not let the Zleceniodawca override.
	 *
	 * @return array<string, array{0: array<int, string>}>
	 */
	public static function disabled_custom_values(): array {
		return array(
			'custom colors'        => array( array( 'color', 'custom' ) ),
			'custom gradients'     => array( array( 'color', 'customGradient' ) ),
			'custom font sizes'    => array( array( 'typography', 'customFontSize' ) ),
			'custom spacing sizes' => array( array( 'spacing', 'customSpacingSize' ) ),
			'image lightbox'       => array( array( 'blocks', 'core/image', 'lightbox', 'enabled' ) ),
			'image lightbox UI'    => array( array( 'blocks', 'core/image', 'lightbox', 'allowEditing' ) ),
			'gallery lightbox'     => array( array( 'blocks', 'core/gallery', 'lightbox', 'enabled' ) ),
		);
	}

	/**
	 * The setting is switched off for the editor.
	 *
	 * @dataProvider disabled_custom_values
	 *
	 * @param array<int, string> $path Path in wp_get_global_settings().
	 */
	public function test_editor_customisation_is_disabled( array $path ): void {
		$this->assertFalse( wp_get_global_settings( $path ) );
	}

	/**
	 * Theme block styles (`is-style-<name>`).
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function block_styles(): array {
		return array(
			'button primary'        => array( 'core/button', 'primary' ),
			'button secondary'      => array( 'core/button', 'secondary' ),
			'paragraph sp-link'     => array( 'core/paragraph', 'sp-link' ),
			'paragraph badge-quiet' => array( 'core/paragraph', 'badge-quiet' ),
			'table sp-table'        => array( 'core/table', 'sp-table' ),
			'image photo-wide'      => array( 'core/image', 'photo-wide' ),
			'image photo'           => array( 'core/image', 'photo' ),
			'image photo-square'    => array( 'core/image', 'photo-square' ),
			'post-template results' => array( 'core/post-template', 'sp-results' ),
		);
	}

	/**
	 * The block style is in the registry.
	 *
	 * @dataProvider block_styles
	 *
	 * @param string $block Block name.
	 * @param string $style Style name (without is-style-).
	 */
	public function test_block_style_is_registered( string $block, string $style ): void {
		$this->assertTrue(
			WP_Block_Styles_Registry::get_instance()->is_registered( $block, $style ),
			"$block: is-style-$style"
		);
	}

	/**
	 * The wp_head output preloads exactly one font file: Archivo latin (H1).
	 */
	public function test_only_the_h1_font_is_preloaded(): void {
		ob_start();
		do_action( 'wp_head' );
		$head = (string) ob_get_clean();

		preg_match_all( '/<link[^>]+rel=["\']preload["\'][^>]*>/', $head, $links );
		$fonts = array_values( array_filter( $links[0], static fn( $link ) => str_contains( $link, '.woff2' ) ) );

		$this->assertCount( 1, $fonts );
		$this->assertStringContainsString( '/assets/fonts/archivo-latin.woff2', $fonts[0] );
		$this->assertStringContainsString( 'as="font"', $fonts[0] );
		$this->assertStringContainsString( 'crossorigin', $fonts[0] );
	}
}
