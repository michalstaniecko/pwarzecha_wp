<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * Styleguide page `/?pw_styleguide=1` (#42, ADR 0010): enablement, route, document, fixtures.
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_UnitTestCase;

/**
 * Observed through the public functions and the `template_redirect` route.
 */
class StyleguideTest extends WP_UnitTestCase {

	/**
	 * Environment type, WP_DEBUG => expected default.
	 *
	 * @return array<string, array{string, bool, bool}>
	 */
	public static function environments(): array {
		return array(
			'local'                 => array( 'local', false, true ),
			'development'           => array( 'development', false, true ),
			'staging'               => array( 'staging', false, false ),
			'staging + WP_DEBUG'    => array( 'staging', true, true ),
			'production'            => array( 'production', false, false ),
			'production + WP_DEBUG' => array( 'production', true, false ),
		);
	}

	/**
	 * Default: on in local/development, on stage only with WP_DEBUG, never on production.
	 *
	 * @dataProvider environments
	 *
	 * @param string $environment wp_get_environment_type().
	 * @param bool   $debug       WP_DEBUG.
	 * @param bool   $expected    Enabled.
	 */
	public function test_enabled_by_environment( string $environment, bool $debug, bool $expected ): void {
		$this->assertSame( $expected, \PW\Styleguide\enabled_by_default( $environment, $debug ) );
	}

	public function test_filter_overrides_the_default(): void {
		add_filter( 'pw/styleguide/enabled', '__return_false' );
		$this->assertFalse( pw_styleguide_enabled() );

		add_filter( 'pw/styleguide/enabled', '__return_true', 20 );
		$this->assertTrue( pw_styleguide_enabled() );
	}

	public function test_route_runs_early_on_template_redirect(): void {
		$this->assertSame( 5, has_action( 'template_redirect', 'PW\Styleguide\route' ) );
	}

	public function test_disabled_styleguide_is_a_plain_404(): void {
		add_filter( 'pw/styleguide/enabled', '__return_false' );
		$this->go_to( home_url( '/?pw_styleguide=1' ) );

		\PW\Styleguide\route();

		$this->assertTrue( is_404() );
	}

	public function test_route_ignores_requests_without_the_parameter(): void {
		add_filter( 'pw/styleguide/enabled', '__return_false' );
		$this->go_to( home_url( '/' ) );

		\PW\Styleguide\route();

		$this->assertFalse( is_404() );
	}

	public function test_headers_forbid_indexing_and_caching(): void {
		$headers = \PW\Styleguide\headers();

		$this->assertSame( 'noindex, nofollow', $headers['X-Robots-Tag'] );
		$this->assertStringContainsString( 'no-cache', $headers['Cache-Control'] );
	}

	public function test_document_is_a_full_noindex_page_with_production_assets(): void {
		$html = $this->document();

		$this->assertMatchesRegularExpression( '/^<!DOCTYPE html>/i', $html );
		$this->assertMatchesRegularExpression( '/<meta name=[\'"]robots[\'"] content=[\'"]noindex, nofollow/', $html );
		$this->assertStringContainsString( 'id="global-styles-inline-css"', $html, 'theme.json styles from wp_head()' );
		$this->assertStringContainsString( 'inc/styleguide/style.css', $html, 'chrome stylesheet' );
		$this->assertNotNull( $this->parse( $html )->querySelector( 'body > .wp-site-blocks .sg-page' ), 'container queries need .wp-site-blocks' );
	}

	public function test_every_registered_group_has_its_section_and_toc_entry(): void {
		$groups = pw_sg_groups();
		$doc    = $this->parse( $this->document() );

		$this->assertGreaterThanOrEqual( 9, count( $groups ) );
		foreach ( $groups as $group ) {
			$slug    = $group['slug'];
			$section = $doc->querySelector( "section.sg-section#sg-{$slug}[data-sg-section=\"{$slug}\"]" );
			$this->assertNotNull( $section, "No section for group $slug" );
			$this->assertNotNull( $section->querySelector( '[data-sg-state]' ), "Group $slug has no row" );
			$this->assertNotNull( $doc->querySelector( ".sg-toc a[href=\"#sg-$slug\"]" ), "No TOC entry for $slug" );
		}
	}

	public function test_primitive_groups_are_registered(): void {
		$slugs = array_column( pw_sg_groups(), 'slug' );

		foreach ( array( 'tokeny', 'typografia', 'ikony', 'przyciski', 'plakietki', 'zdjecia', 'tresc', 'paginacja', 'kontekst-navy' ) as $slug ) {
			$this->assertContains( $slug, $slugs );
		}
	}

	public function test_a_group_added_by_filter_renders_in_order_with_its_rows(): void {
		add_filter(
			'pw/styleguide/groups',
			static function ( array $groups ): array {
				$groups[] = array(
					'slug'   => 'zz-test',
					'title'  => 'Blok testowy',
					'note'   => 'Notatka projektowa',
					'order'  => -1,
					'render' => static function (): void {
						pw_sg_row( 'Pełne dane', 'pelne', 'navy' );
						echo '<p class="zz">demo</p>';
						pw_sg_row_end();
					},
				);
				return $groups;
			}
		);

		$this->assertSame( 'zz-test', pw_sg_groups()[0]['slug'], 'sorted by order' );
		$doc     = $this->parse( $this->document() );
		$section = $doc->querySelector( '[data-sg-section="zz-test"]' );
		$this->assertNotNull( $section );
		$this->assertSame( 'Blok testowy', trim( $section->querySelector( 'h2' )->textContent ) );
		$this->assertSame( 'Notatka projektowa', trim( $section->querySelector( '.sg-section__note' )->textContent ) );
		$row = $section->querySelector( '.sg-row[data-sg-state="pelne"][data-sg-bg="navy"].sp-on-navy' );
		$this->assertNotNull( $row );
		$this->assertSame( 'Pełne dane', trim( $row->querySelector( '.sg-row__label' )->textContent ) );
		$this->assertNotNull( $row->querySelector( '.sg-demo > p.zz' ) );
	}

	public function test_group_parameter_renders_only_that_group(): void {
		$doc = $this->parse( $this->document( 'ikony' ) );

		$this->assertSame( 1, $doc->querySelectorAll( '[data-sg-section]' )->length );
		$this->assertNotNull( $doc->querySelector( '[data-sg-section="ikony"]' ) );
	}

	public function test_fixture_data_does_not_leak_outside_the_row(): void {
		$seen = null;
		pw_sg_with_data(
			'kontakt',
			array( 'email' => 'styleguide@example.com' ),
			static function () use ( &$seen ): void {
				$seen = apply_filters( 'pw/data/kontakt', array( 'email' => 'stored@example.com' ) );
			}
		);

		$this->assertSame( array( 'email' => 'styleguide@example.com' ), $seen );
		$this->assertSame( array( 'email' => 'stored@example.com' ), apply_filters( 'pw/data/kontakt', array( 'email' => 'stored@example.com' ) ) );
	}

	public function test_fixture_data_is_removed_even_when_the_render_throws(): void {
		try {
			pw_sg_with_data(
				'kontakt',
				null,
				static function (): void {
					throw new \RuntimeException( 'render failed' );
				}
			);
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'render failed', $e->getMessage() );
		}

		$this->assertFalse( has_filter( 'pw/data/kontakt' ) );
	}

	public function test_fixture_photo_is_imported_once_with_theme_sizes(): void {
		$id = pw_sg_fixture_image( 'wide' );

		$this->assertGreaterThan( 0, $id );
		$this->assertSame( $id, pw_sg_fixture_image( 'wide' ), 'idempotent' );
		$this->assertTrue( wp_attachment_is_image( $id ) );
		$this->assertArrayHasKey( 'pw-wide', wp_get_attachment_metadata( $id )['sizes'] ?? array() );
		$this->assertNotSame( '', (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		wp_delete_attachment( $id, true );
	}

	public function test_no_fixture_photo_when_disabled_or_unknown(): void {
		$this->assertSame( 0, pw_sg_fixture_image( 'panorama' ) );

		add_filter( 'pw/styleguide/enabled', '__return_false' );
		$this->assertSame( 0, pw_sg_fixture_image( 'wide' ) );
	}

	/**
	 * Styleguide document as served (without headers and exit).
	 *
	 * @param string $group Group slug, '' = all.
	 */
	private function document( string $group = '' ): string {
		$this->go_to( home_url( '/?pw_styleguide=1' ) );
		return \PW\Styleguide\document( $group );
	}

	/**
	 * Parses a full HTML document.
	 *
	 * @param string $html Markup.
	 */
	private function parse( string $html ): \Dom\HTMLDocument {
		return \Dom\HTMLDocument::createFromString( $html, LIBXML_NOERROR );
	}
}
