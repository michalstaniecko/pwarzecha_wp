<?php
/**
 * Icon helper pw_icon(): inline SVG icons from theme/assets/icons (#39, ADR 0004).
 *
 * @package PW
 */

namespace PW\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * Decorative vs labelled icons, unknown names, escaping, manifest coverage.
 */
final class IconsTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/helpers/icons.php' );
	}

	protected function set_up(): void {
		parent::set_up();
		Functions\stubEscapeFunctions();
	}

	/**
	 * Root <svg> element of the markup.
	 *
	 * @param string $html Markup.
	 */
	private function svg( string $html ): \Dom\Element {
		$doc = \Dom\HTMLDocument::createFromString( $html, LIBXML_NOERROR | \Dom\HTML_NO_DEFAULT_NS );
		$svg = $doc->querySelector( 'svg' );
		$this->assertNotNull( $svg, "No <svg> in: $html" );
		return $svg;
	}

	public function test_decorative_icon_is_hidden_from_assistive_tech(): void {
		$svg = $this->svg( pw_icon( 'phone' ) );

		$this->assertSame( 'sp-icon', $svg->getAttribute( 'class' ) );
		$this->assertSame( 'true', $svg->getAttribute( 'aria-hidden' ) );
		$this->assertSame( 'false', $svg->getAttribute( 'focusable' ) );
		$this->assertSame( '0 0 24 24', $svg->getAttribute( 'viewBox' ) );
		$this->assertSame( 'currentColor', $svg->getAttribute( 'stroke' ) );
		$this->assertFalse( $svg->hasAttribute( 'role' ) );
		$this->assertNotNull( $svg->querySelector( 'path' ) );
	}

	public function test_icon_has_no_width_or_height(): void {
		$svg = $this->svg( pw_icon( 'gift' ) );

		$this->assertFalse( $svg->hasAttribute( 'width' ) );
		$this->assertFalse( $svg->hasAttribute( 'height' ) );
	}

	public function test_labelled_icon_has_an_accessible_name(): void {
		$svg = $this->svg( pw_icon( 'phone', array( 'label' => 'Telefon' ) ) );

		$this->assertSame( 'img', $svg->getAttribute( 'role' ) );
		$this->assertSame( 'Telefon', $svg->getAttribute( 'aria-label' ) );
		$this->assertSame( 'Telefon', $svg->querySelector( 'title' )?->textContent );
		$this->assertFalse( $svg->hasAttribute( 'aria-hidden' ) );
	}

	public function test_extra_classes_and_size_modifier(): void {
		$svg = $this->svg(
			pw_icon(
				'map-pin',
				array(
					'class' => 'sp-contact__icon',
					'size'  => 'small',
				)
			)
		);

		$this->assertSame( 'sp-icon sp-icon--small sp-contact__icon', $svg->getAttribute( 'class' ) );
	}

	public function test_unknown_size_is_ignored(): void {
		$svg = $this->svg( pw_icon( 'map-pin', array( 'size' => '3rem' ) ) );

		$this->assertSame( 'sp-icon', $svg->getAttribute( 'class' ) );
	}

	public function test_brand_icon_is_filled_with_current_color(): void {
		$svg = $this->svg( pw_icon( 'brand-facebook', array( 'label' => 'Facebook' ) ) );

		$this->assertSame( 'currentColor', $svg->getAttribute( 'fill' ) );
		$this->assertSame( 1, substr_count( pw_icon( 'brand-facebook', array( 'label' => 'Facebook' ) ), '<title>' ) );
	}

	public function test_unknown_icon_returns_empty_string_and_reports_misuse(): void {
		Functions\expect( '_doing_it_wrong' )
			->once()
			->with( 'pw_icon', \Mockery::pattern( '/nope/' ), \Mockery::any() );

		$this->assertSame( '', pw_icon( 'nope' ) );
	}

	public function test_attributes_are_escaped(): void {
		$xss  = '"><script>alert(1)</script><x onload="y';
		$html = pw_icon(
			'phone',
			array(
				'label' => $xss,
				'class' => $xss,
			)
		);

		$svg = $this->svg( $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertNull( $svg->querySelector( 'x' ) );
		$this->assertFalse( $svg->hasAttribute( 'onload' ) );
		$this->assertSame( $xss, $svg->getAttribute( 'aria-label' ) );
		$this->assertSame( $xss, $svg->querySelector( 'title' )?->textContent );
	}

	public function test_every_manifest_icon_renders_with_current_color(): void {
		$names = array_keys( pw_icon_names() );
		$this->assertContains( 'brand-google', $names );
		$this->assertContains( 'chevron-down', $names );

		foreach ( $names as $name ) {
			$html = pw_icon( $name );
			$this->assertStringContainsString( 'currentColor', $html, $name );
			$this->assertSame( 'sp-icon', $this->svg( $html )->getAttribute( 'class' ), $name );
		}
	}

	public function test_icon_names_carry_polish_label_and_meaning(): void {
		$this->assertSame(
			array(
				'label'   => 'Portfel',
				'meaning' => 'Cena, Karnet',
			),
			pw_icon_names()['wallet']
		);
	}
}
