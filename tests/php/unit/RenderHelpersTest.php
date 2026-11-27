<?php // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API (textContent, …).
/**
 * Render helpers: DS markup of buttons, links, badges, heading ids, tel/mailto hrefs (#40).
 *
 * @package PW
 */

namespace PW\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * Pure helpers from theme/inc/helpers/render.php.
 */
final class RenderHelpersTest extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once self::theme_path( 'inc/helpers/icons.php' );
		require_once self::theme_path( 'inc/helpers/render.php' );
	}

	protected function set_up(): void {
		parent::set_up();
		Functions\stubEscapeFunctions();
		Functions\when( 'esc_url' )->alias( static fn( $url ) => htmlspecialchars( (string) $url, ENT_QUOTES ) );
		Functions\when( 'sanitize_html_class' )->alias( static fn( $c ) => preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c ) );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( $t ) => trim( strip_tags( (string) $t ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- stub of wp_strip_all_tags.
	}

	/**
	 * First element matching a selector.
	 *
	 * @param string $html     Markup.
	 * @param string $selector CSS selector.
	 */
	private function el( string $html, string $selector ): \Dom\Element {
		$doc = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><body>' . $html, LIBXML_NOERROR | \Dom\HTML_NO_DEFAULT_NS );
		$el  = $doc->querySelector( $selector );
		$this->assertNotNull( $el, "No $selector in: $html" );
		return $el;
	}

	public function test_primary_button_is_a_ds_link_with_text(): void {
		$html = pw_button(
			array(
				'text' => 'Umów pierwszy trening',
				'href' => '#kontakt',
			)
		);

		$a = $this->el( $html, 'a' );
		$this->assertSame( 'sp-btn sp-btn--primary', $a->getAttribute( 'class' ) );
		$this->assertSame( '#kontakt', $a->getAttribute( 'href' ) );
		$this->assertSame( 'Umów pierwszy trening', trim( $a->textContent ) );
		$this->assertNull( $a->querySelector( 'svg' ) );
	}

	public function test_secondary_button_on_navy_with_a_decorative_icon_before_the_text(): void {
		$html = pw_button(
			array(
				'variant' => 'secondary',
				'text'    => 'Grafik zajęć',
				'href'    => '/grafik/',
				'icon'    => 'calendar',
				'on'      => 'navy',
			)
		);

		$a = $this->el( $html, 'a' );
		$this->assertSame( 'sp-btn sp-btn--secondary sp-on-navy', $a->getAttribute( 'class' ) );
		$this->assertSame( 'svg', strtolower( $a->firstElementChild->tagName ) );
		$this->assertSame( 'true', $a->firstElementChild->getAttribute( 'aria-hidden' ) );
		$this->assertSame( 'Grafik zajęć', trim( $a->textContent ) );
	}

	public function test_light_context_and_unknown_variant_fall_back_to_ds_defaults(): void {
		$a = $this->el(
			pw_button(
				array(
					'variant' => 'huge',
					'text'    => 'X',
					'href'    => '#',
					'on'      => 'light',
				)
			),
			'a'
		);

		$this->assertSame( 'sp-btn sp-btn--primary sp-on-light', $a->getAttribute( 'class' ) );
	}

	public function test_phone_button_keeps_the_number_on_one_line_and_dials_it(): void {
		$html = pw_button(
			array(
				'variant' => 'phone',
				'text'    => '600 000 000',
			)
		);

		$a = $this->el( $html, 'a.sp-btn.sp-btn--phone' );
		$this->assertSame( 'tel:+48600000000', $a->getAttribute( 'href' ) );
		$this->assertNotNull( $a->querySelector( 'svg.sp-icon' ), 'phone icon by default' );
		$number = $this->el( $html, 'a > span.sp-btn__number.sp-nowrap' );
		$this->assertSame( '600 000 000', $number->textContent );
	}

	public function test_email_button_wraps_the_address_in_text_type_and_mails_it(): void {
		Functions\when( 'sanitize_email' )->returnArg();

		$html = pw_button(
			array(
				'variant' => 'email',
				'text'    => 'kontakt@przemyslawwarzecha.pl',
			)
		);

		$a = $this->el( $html, 'a.sp-btn.sp-btn--email' );
		$this->assertSame( 'mailto:kontakt@przemyslawwarzecha.pl', $a->getAttribute( 'href' ) );
		$this->assertNotNull( $a->querySelector( 'svg.sp-icon' ), 'mail icon by default' );
		$this->assertSame( 'kontakt@przemyslawwarzecha.pl', $this->el( $html, 'a > span.sp-btn__email' )->textContent );
		$this->assertNull( $a->querySelector( '.sp-nowrap' ), 'an address may wrap' );
	}

	public function test_explicit_href_and_empty_icon_override_the_variant_defaults(): void {
		$a = $this->el(
			pw_button(
				array(
					'variant' => 'phone',
					'text'    => '600 000 000',
					'href'    => 'tel:+48111222333',
					'icon'    => '',
				)
			),
			'a'
		);

		$this->assertSame( 'tel:+48111222333', $a->getAttribute( 'href' ) );
		$this->assertNull( $a->querySelector( 'svg' ) );
	}

	public function test_button_escapes_text_and_href(): void {
		$html = pw_button(
			array(
				'text' => 'Zapisz "się" <script>alert(1)</script>',
				'href' => '/a"onmouseover="x',
			)
		);

		$this->assertStringNotContainsString( '<script', $html );
		$a = $this->el( $html, 'a' );
		$this->assertFalse( $a->hasAttribute( 'onmouseover' ) );
		$this->assertSame( '/a"onmouseover="x', $a->getAttribute( 'href' ) );
		$this->assertSame( 'Zapisz "się" <script>alert(1)</script>', $a->textContent );
	}

	public function test_button_extra_attributes_are_escaped_and_event_handlers_dropped(): void {
		$a = $this->el(
			pw_button(
				array(
					'text'  => 'Kontakt',
					'href'  => '#kontakt',
					'attrs' => array(
						'aria-label' => 'Kontakt "teraz"',
						'data-pw'    => 'cta',
						'class'      => 'sp-btn--block',
						'onclick'    => 'alert(1)',
						'bad key"'   => 'x',
						'href'       => 'https://evil.example',
					),
				)
			),
			'a'
		);

		$this->assertSame( 'Kontakt "teraz"', $a->getAttribute( 'aria-label' ) );
		$this->assertSame( 'cta', $a->getAttribute( 'data-pw' ) );
		$this->assertSame( 'sp-btn sp-btn--primary sp-btn--block', $a->getAttribute( 'class' ) );
		$this->assertSame( '#kontakt', $a->getAttribute( 'href' ) );
		$this->assertFalse( $a->hasAttribute( 'onclick' ) );
		$this->assertSame( 4, $a->attributes->length, 'class, href, aria-label, data-pw' );
	}

	public function test_classes_join_always_and_conditional_classes_without_duplicates(): void {
		$this->assertSame(
			'sp-card sp-card--featured is-wide extra',
			pw_classes(
				array(
					'sp-card',
					'sp-card--featured' => true,
					'sp-card--muted'    => false,
					'is-wide'           => 1,
					'is-empty'          => '',
					' extra  sp-card ',
					'',
				)
			)
		);
		$this->assertSame( '', pw_classes( array() ) );
	}

	public function test_link_has_text_hidden_context_and_a_trailing_arrow(): void {
		$html = pw_link(
			array(
				'text'          => 'Czytaj dalej',
				'href'          => '/aktualnosci/nowa-grupa/',
				'hidden_suffix' => 'Nowa grupa "Smyki"',
			)
		);

		$a = $this->el( $html, 'a' );
		$this->assertSame( 'sp-link', $a->getAttribute( 'class' ) );
		$this->assertSame( '/aktualnosci/nowa-grupa/', $a->getAttribute( 'href' ) );
		$this->assertSame( 'Czytaj dalej Nowa grupa "Smyki"', preg_replace( '/\s+/', ' ', trim( $a->textContent ) ) );
		$this->assertSame( ' Nowa grupa "Smyki"', $this->el( $html, 'a > span.sp-visually-hidden' )->textContent );
		$this->assertSame( 'svg', strtolower( $a->lastElementChild->tagName ), 'arrow after the text' );
		$this->assertStringContainsString( '<path', $html, 'arrow-right drawn' );
	}

	public function test_link_without_icon_or_suffix(): void {
		$a = $this->el(
			pw_link(
				array(
					'text' => 'Zobacz cennik',
					'href' => '/cennik/',
					'icon' => '',
				)
			),
			'a'
		);

		$this->assertNull( $a->querySelector( 'svg' ) );
		$this->assertNull( $a->querySelector( '.sp-visually-hidden' ) );
		$this->assertSame( 'Zobacz cennik', $a->textContent );
	}

	public function test_link_button_variant_renders_the_ds_text_link(): void {
		$a = $this->el(
			pw_button(
				array(
					'variant' => 'link',
					'text'    => 'Wszystkie Aktualności',
					'href'    => '/aktualnosci/',
					'on'      => 'navy',
				)
			),
			'a'
		);

		$this->assertSame( 'sp-link sp-on-navy', $a->getAttribute( 'class' ) );
		$this->assertSame( 'svg', strtolower( $a->lastElementChild->tagName ) );
	}

	public function test_heading_id_from_the_attribute_wins_and_is_sanitized(): void {
		$this->assertSame( 'grafik', pw_heading_id( 'Grafik zajęć', 'grafik' ) );
		$this->assertSame( 'grafik-zaj-2026', pw_heading_id( 'Grafik zajęć', ' Grafik Zaję_2026 "><' ) );
	}

	public function test_heading_id_falls_back_to_a_deterministic_hash_of_the_plain_text(): void {
		$this->assertSame( 'pw-h-d01a1d', pw_heading_id( 'Grafik zajęć' ) );
		$this->assertSame( 'pw-h-d01a1d', pw_heading_id( '<em>Grafik</em> zajęć', null ), 'tags ignored' );
		$this->assertSame( 'pw-h-d01a1d', pw_heading_id( 'Grafik zajęć', '' ), 'empty attribute' );
		$this->assertSame( 'pw-h-d01a1d', pw_heading_id( 'Grafik zajęć', '"<>' ), 'nothing left after sanitizing' );
		$this->assertNotSame( pw_heading_id( 'Cennik' ), pw_heading_id( 'Grafik zajęć' ) );
	}

	/**
	 * Badge variant => expected class.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function badge_variants(): array {
		return array(
			'default' => array( 'default', 'sp-badge' ),
			'outline' => array( 'outline', 'sp-badge sp-badge--outline' ),
			'quiet'   => array( 'quiet', 'sp-badge sp-badge--quiet' ),
			'unknown' => array( 'loud', 'sp-badge' ),
		);
	}

	/**
	 * Badge variants.
	 *
	 * @dataProvider badge_variants
	 *
	 * @param string $variant  Variant.
	 * @param string $expected Class attribute.
	 */
	public function test_badge_variant_classes( string $variant, string $expected ): void {
		$span = $this->el( pw_badge( 'Nowa grupa', $variant ), 'span' );

		$this->assertSame( $expected, $span->getAttribute( 'class' ) );
		$this->assertSame( 'Nowa grupa', $span->textContent );
	}

	public function test_badge_escapes_text_and_defaults_to_the_accent_badge(): void {
		$html = pw_badge( '<b>"Od 7 lat"</b>' );

		$this->assertSame( 'sp-badge', $this->el( $html, 'span' )->getAttribute( 'class' ) );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	/**
	 * Displayed number => expected href.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function phone_numbers(): array {
		return array(
			'spaces'             => array( '600 000 000', 'tel:+48600000000' ),
			'dashes'             => array( '600-000-000', 'tel:+48600000000' ),
			'no separators'      => array( '600000000', 'tel:+48600000000' ),
			'with +48'           => array( '+48 600 000 000', 'tel:+48600000000' ),
			'+48 in brackets'    => array( '(+48) 600-000-000', 'tel:+48600000000' ),
			'00 prefix'          => array( '0048 600 000 000', 'tel:+48600000000' ),
			'foreign prefix'     => array( '+49 30 1234567', 'tel:+49301234567' ),
			'surrounding spaces' => array( ' 600 000 000 ', 'tel:+48600000000' ),
			'no digits'          => array( 'brak', '' ),
			'empty'              => array( '', '' ),
		);
	}

	/**
	 * Phone formats.
	 *
	 * @dataProvider phone_numbers
	 *
	 * @param string $phone    Displayed number.
	 * @param string $expected Href.
	 */
	public function test_tel_href_normalizes_the_number( string $phone, string $expected ): void {
		$this->assertSame( $expected, pw_tel_href( $phone ) );
	}

	public function test_mailto_href_is_empty_for_an_invalid_address(): void {
		Functions\when( 'sanitize_email' )->alias( static fn( $e ) => str_contains( (string) $e, '@' ) ? trim( (string) $e ) : '' );

		$this->assertSame( 'mailto:biuro@example.pl', pw_mailto_href( ' biuro@example.pl ' ) );
		$this->assertSame( '', pw_mailto_href( 'nie-adres' ) );
	}
}
