<?php
/**
 * Block pw/section-heading (#56): eyebrow, H2 with a stable id and lead of a Sekcja.
 *
 * @package PW
 */

namespace PW\Tests\Integration\Blocks;

use PW\Tests\BlockTestCase;

/**
 * Front-end markup of the Sekcja heading and its id contract with pw/section (#55).
 */
class SectionHeadingTest extends BlockTestCase {

	private const FULL = array(
		'nadtytul'   => 'Oferta',
		'tytul'      => 'Zajęcia dla <strong>każdego</strong>',
		'lead'       => 'Wybierz formę zajęć.',
		'naglowekId' => 'pw-h-abc123',
	);

	public function test_renders_eyebrow_h2_with_id_and_lead_in_ds_markup(): void {
		$html = $this->render_block_markup( 'pw/section-heading', self::FULL );

		$this->assertHtmlHasSelector( $html, 'header.sp-section-head > p.sp-eyebrow:first-child' );
		$this->assertHtmlHasSelector( $html, 'header.sp-section-head > p.sp-eyebrow + h2.sp-h2#pw-h-abc123' );
		$this->assertHtmlHasSelector( $html, 'h2.sp-h2 > strong' );
		$this->assertHtmlHasSelector( $html, 'header.sp-section-head > h2 + p.sp-lead:last-child' );
		$this->assertStringContainsString( 'Oferta</p>', $html );
		$this->assertStringContainsString( 'Wybierz formę zajęć.</p>', $html );
		$this->assertStringNotContainsString( 'sp-section-head--center', $html );
		$this->assertSame( 1, substr_count( $html, '<h2' ) );
	}

	public function test_without_eyebrow_and_lead_renders_only_the_h2(): void {
		$html = $this->render_block_markup(
			'pw/section-heading',
			array(
				'tytul'    => 'Grafik zajęć',
				'nadtytul' => ' ',
				'lead'     => '<br>',
			)
		);

		$this->assertHtmlHasSelector( $html, 'header.sp-section-head > h2.sp-h2:only-child' );
		$this->assertStringNotContainsString( '<p', $html );
	}

	public function test_center_adds_the_modifier(): void {
		$html = $this->render_block_markup( 'pw/section-heading', self::FULL + array( 'wyrownanie' => 'center' ) );

		$this->assertHtmlHasSelector( $html, 'header.sp-section-head.sp-section-head--center' );
	}

	public function test_empty_title_renders_nothing(): void {
		$this->assertSame( '', trim( $this->render_block_markup( 'pw/section-heading', array( 'tytul' => '' ) + self::FULL ) ) );
		$this->assertSame( '', trim( $this->render_block_markup( 'pw/section-heading', array( 'tytul' => '<strong> </strong>' ) ) ) );
	}

	public function test_without_naglowek_id_the_id_is_derived_from_the_title(): void {
		$attrs = array( 'tytul' => 'Grafik zajęć' );

		$first = $this->render_block_markup( 'pw/section-heading', $attrs );

		// md5( 'Grafik zajęć' ) starts with d01a1d (RenderHelpersTest).
		$this->assertHtmlHasSelector( $first, 'h2#pw-h-d01a1d' );
		$this->assertSame( $first, $this->render_block_markup( 'pw/section-heading', $attrs ) );
	}

	public function test_rich_text_is_filtered(): void {
		$html = $this->render_block_markup(
			'pw/section-heading',
			array(
				'nadtytul'   => self::XSS,
				'tytul'      => 'Tytuł ' . self::XSS,
				'lead'       => self::XSS,
				'naglowekId' => self::XSS,
			)
		);

		$this->assertNoUnescaped( $html );
		$this->assertHtmlHasSelector( $html, 'h2.sp-h2[id="script-alert-1-script-b-onclick-alert-1-x-b"]' );
	}

	/**
	 * Acceptance #56 + #55: the Sekcja points `aria-labelledby` at the heading's H2, with the
	 * editor id and with the fallback id. Runs once `pw/section` (#55) is registered.
	 *
	 * @dataProvider heading_ids
	 *
	 * @param array<string, string> $attrs Heading attributes.
	 * @param string                $id    Expected H2 id.
	 */
	public function test_section_is_labelled_by_the_heading( array $attrs, string $id ): void {
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'pw/section' ) ) {
			$this->markTestSkipped( 'pw/section (#55) is not merged yet.' );
		}

		$html = do_blocks(
			'<!-- wp:pw/section -->'
			. serialize_block(
				array(
					'blockName'    => 'pw/section-heading',
					'attrs'        => $attrs,
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				)
			)
			. '<!-- /wp:pw/section -->'
		);

		$this->assertHtmlHasSelector( $html, sprintf( 'section[aria-labelledby="%1$s"] h2#%1$s', $id ) );
	}

	/**
	 * Heading attributes and the H2 id they produce.
	 *
	 * @return array<string, array{0: array<string, string>, 1: string}>
	 */
	public static function heading_ids(): array {
		return array(
			'editor id'   => array(
				array(
					'tytul'      => 'Grafik zajęć',
					'naglowekId' => 'pw-h-abc123',
				),
				'pw-h-abc123',
			),
			'fallback id' => array( array( 'tytul' => 'Grafik zajęć' ), 'pw-h-d01a1d' ),
		);
	}
}
