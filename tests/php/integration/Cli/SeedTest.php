<?php
/**
 * `wp pw seed` (#54): starting state of every environment, called without the WP-CLI runner.
 *
 * @package PW
 */

namespace PW\Tests\Integration\Cli;

use PW\Cli\Seed_Command;
use WP_UnitTestCase;

/**
 * Seed on a real WordPress: what it creates, idempotency and the empty-database guard.
 */
class SeedTest extends WP_UnitTestCase {

	use \PW\Tests\RegistersThemeMeta;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		require_once get_theme_file_path( 'inc/cli/class-seed-command.php' );
	}

	public function set_up(): void {
		parent::set_up();
		$this->register_theme_meta();
		$GLOBALS['wp_settings_errors'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- settings errors are not reset between tests.
	}

	public function test_seed_on_an_empty_database_creates_lokalizacje_in_order(): void {
		( new Seed_Command() )->run( array(), array() );

		$lokalizacje = pw_lokalizacje();

		$this->assertSame( array( 'Chorzów', 'Katowice' ), array_column( $lokalizacje, 'nazwa' ) );
		$this->assertSame( array( 'chorzow', 'katowice' ), array_column( $lokalizacje, 'kotwica' ) );
		$this->assertSame( array( 'Chorzów', 'Katowice' ), array_column( $lokalizacje, 'miasto' ) );
		$this->assertSame( array( '[...]', '[...]' ), array_column( $lokalizacje, 'ulica' ) );
	}

	/**
	 * Data that makes the database non-empty.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function non_empty_databases(): array {
		return array(
			'Lokalizacja (draft)' => array( 'lokalizacja', 'draft' ),
			'Karnet'              => array( 'karnet', 'publish' ),
			'published page'      => array( 'page', 'publish' ),
		);
	}

	/**
	 * Any Lokalizacja or Karnet, or a published page, blocks the seed.
	 *
	 * @dataProvider non_empty_databases
	 *
	 * @param string $post_type Post type of the existing content.
	 * @param string $status    Its status.
	 */
	public function test_seed_refuses_a_non_empty_database_without_force( string $post_type, string $status ): void {
		self::factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_status' => $status,
				'post_title'  => 'Treść Zleceniodawcy',
			)
		);

		try {
			( new Seed_Command() )->run( array(), array() );
			$this->fail( 'Seed ran on a non-empty database.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( '--force', $e->getMessage() );
		}
		$this->assertSame(
			array(),
			get_terms(
				array(
					'taxonomy'   => 'grupa',
					'hide_empty' => false,
				)
			)
		);
	}

	public function test_default_sample_page_and_draft_pages_do_not_count_as_content(): void {
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'przykladowa-strona',
				'post_title'  => 'Przykładowa strona',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);

		( new Seed_Command() )->run( array(), array() );

		$this->assertCount( 2, pw_lokalizacje() );
	}

	public function test_force_seeds_a_non_empty_database(): void {
		self::factory()->post->create( array( 'post_type' => 'karnet' ) );

		( new Seed_Command() )->run( array(), array( 'force' => true ) );

		$this->assertCount( 2, pw_lokalizacje() );
	}

	public function test_seed_creates_the_working_grupy_and_a_grafik_per_lokalizacja(): void {
		( new Seed_Command() )->run( array(), array() );

		$this->assertSame(
			array( 'Dzieci 6–9 lat', 'Dzieci 10–13 lat', 'Młodzież 14+' ),
			array_column( pw_grupy(), 'nazwa' )
		);

		foreach ( pw_lokalizacje() as $lokalizacja ) {
			$grafik = pw_grafik( $lokalizacja['id'] );
			$this->assertGreaterThanOrEqual( 2, count( $grafik ), $lokalizacja['nazwa'] );
			$this->assertLessThanOrEqual( 3, count( $grafik ), $lokalizacja['nazwa'] );
		}
	}

	public function test_seed_creates_three_karnety_with_one_polecany_and_both_periods(): void {
		( new Seed_Command() )->run( array(), array() );

		$karnety = pw_karnety();

		$this->assertCount( 3, $karnety );
		$this->assertSame( 1, count( array_filter( array_column( $karnety, 'polecany' ) ) ) );
		$this->assertSame( array( 1, 5 ), array_values( array_unique( array_column( $karnety, 'okresMiesiace' ) ) ) );
		foreach ( $karnety as $karnet ) {
			// get_the_title() texturizes „...” into an ellipsis.
			$this->assertStringContainsString( '[…]', html_entity_decode( $karnet['nazwa'] ) );
			$this->assertGreaterThan( 0, $karnet['cena'] );
		}
	}

	public function test_seed_prices_karnety_with_obvious_placeholders(): void {
		( new Seed_Command() )->run( array(), array() );

		$ceny = array();
		foreach ( pw_karnety() as $karnet ) {
			$ceny[] = array( $karnet['okresMiesiace'], $karnet['polecany'], $karnet['cena'] );
		}
		sort( $ceny );

		$this->assertSame( array( array( 1, false, 111 ), array( 1, true, 222 ), array( 5, false, 999 ) ), $ceny );
		$this->assertSame( 111, pw_karnet_min_cena_miesieczna() );
	}

	public function test_seed_fills_dane_strony_with_placeholders_only(): void {
		( new Seed_Command() )->run( array(), array() );

		$kontakt = pw_kontakt();

		// The Dane strony sanitizer only accepts a real phone number and e-mail address, so these
		// two are obvious dummies instead of `[...]`.
		$this->assertSame( '+48 000 000 000', $kontakt['telefon'] );
		$this->assertSame( 'kontakt@example.com', $kontakt['email'] );
		$this->assertSame( '[...]', $kontakt['godzinyTelefonu'] );
		$this->assertSame( '[...]', $kontakt['nazwaFirmy'] );
		$this->assertSame( '[...]', $kontakt['adresRejestrowy'] );
		$this->assertSame( 'Pierwszy trening bezpłatny', $kontakt['topbarTekst'] );
		$this->assertSame( '', $kontakt['nip'] );
		$this->assertSame( 'Przemek', $kontakt['trenerImie'] );
		$this->assertSame( '', $kontakt['trenerNazwisko'] );
		$this->assertSame( '', $kontakt['trenerNazwiskoWSchemie'] );
		$this->assertSame( array(), get_settings_errors( 'pw_kontakt' ) );
	}

	public function test_seed_creates_the_main_menu_with_groups_and_the_trener_link(): void {
		( new Seed_Command() )->run( array(), array() );

		$this->assertSame( 'Menu główne', $this->navigation( 'menu-glowne' )->post_title );
		$this->assertSame(
			array(
				'Treningi grupowe ▾ Chorzów, Katowice',
				'Treningi indywidualne ▾ Dla dorosłych, Dla dzieci',
				'Dla firm i instytucji ▾ Przedszkola, Szkoły i instytucje',
				'Trener → #',
				'Cennik → #',
				'Grafik → #',
			),
			$this->menu_outline( 'menu-glowne' )
		);
	}

	public function test_seed_creates_both_footer_menus(): void {
		( new Seed_Command() )->run( array(), array() );

		$this->assertSame( 'Stopka – Oferta', $this->navigation( 'stopka-oferta' )->post_title );
		$this->assertSame(
			array(
				'Treningi grupowe ▾ Chorzów, Katowice',
				'Treningi indywidualne ▾ Dla dorosłych, Dla dzieci',
				'Dla firm i instytucji ▾ Przedszkola, Szkoły i instytucje',
			),
			$this->menu_outline( 'stopka-oferta' )
		);
		$this->assertSame( 'Stopka – Na skróty', $this->navigation( 'stopka-na-skroty' )->post_title );
		$this->assertSame(
			array( 'Trener → #', 'Cennik → #', 'Grafik → #', 'Aktualności → #', 'Kontakt → #' ),
			$this->menu_outline( 'stopka-na-skroty' )
		);
	}

	public function test_second_run_with_force_updates_instead_of_duplicating(): void {
		$seed = new Seed_Command();
		$seed->run( array(), array() );
		$before = $this->counts();

		$lokalizacja = pw_lokalizacje()[0]['id'];
		update_post_meta( $lokalizacja, 'pw_ulica', 'ul. Zmieniona 1' );
		$report = $seed->run( array(), array( 'force' => true ) );

		$this->assertSame( $before, $this->counts() );
		$this->assertSame( '[...]', pw_lokalizacja( $lokalizacja )['ulica'] );
		$this->assertSame(
			array(
				'kontakt'     => 8,
				'grupy'       => 3,
				'lokalizacje' => 2,
				'grafik'      => 5,
				'karnety'     => 3,
				'menu'        => 3,
				'yoast'       => 7,
			),
			$report
		);
	}

	public function test_seed_configures_yoast_archives_separator_and_breadcrumbs(): void {
		$this->assertTrue( class_exists( \WPSEO_Options::class ), 'Yoast SEO is active in the integration suite.' );

		$report = ( new Seed_Command() )->run( array(), array( 'only' => 'yoast' ) );

		$this->assertSame( array( 'yoast' => 7 ), $report );
		$this->assertTrue( \WPSEO_Options::get( 'disable-author' ) );
		$this->assertTrue( \WPSEO_Options::get( 'disable-date' ) );
		$this->assertTrue( \WPSEO_Options::get( 'noindex-tax-category' ) );
		$this->assertTrue( \WPSEO_Options::get( 'noindex-tax-post_tag' ) );
		// Titles in #31: „… | Przemek”.
		$this->assertSame( 'sc-pipe', \WPSEO_Options::get( 'separator' ) );
		$this->assertTrue( \WPSEO_Options::get( 'breadcrumbs-enable' ) );
		$this->assertTrue( \WPSEO_Options::get( 'breadcrumbs-display-blog-page' ) );
	}

	public function test_only_karnety_seeds_nothing_else(): void {
		$report = ( new Seed_Command() )->run( array(), array( 'only' => 'karnety' ) );

		$this->assertSame( array( 'karnety' => 3 ), $report );
		$this->assertSame(
			array(
				'grupy'       => 0,
				'lokalizacje' => 0,
				'grafik'      => 0,
				'karnety'     => 3,
				'menu'        => 0,
			),
			$this->counts()
		);
		$this->assertSame( '', pw_kontakt_pole( 'telefon' ) );
	}

	public function test_only_runs_the_listed_parts_in_dependency_order(): void {
		$report = ( new Seed_Command() )->run( array(), array( 'only' => 'grafik, lokalizacje,grupy' ) );

		$this->assertSame( array( 'grupy', 'lokalizacje', 'grafik' ), array_keys( $report ) );
		$this->assertSame( 5, $report['grafik'] );
	}

	public function test_unknown_part_is_rejected_before_anything_is_seeded(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'strony' );

		( new Seed_Command() )->run( array(), array( 'only' => 'karnety,strony' ) );
	}

	/**
	 * Number of seeded elements of every kind, as stored.
	 *
	 * @return array<string, int>
	 */
	private function counts(): array {
		$posts  = static fn( string $type ): int => count(
			get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			)
		);
		$grafik = 0;
		foreach ( pw_lokalizacje() as $lokalizacja ) {
			$grafik += count( pw_grafik( $lokalizacja['id'] ) );
		}
		return array(
			'grupy'       => count( pw_grupy() ),
			'lokalizacje' => $posts( 'lokalizacja' ),
			'grafik'      => $grafik,
			'karnety'     => $posts( 'karnet' ),
			'menu'        => $posts( 'wp_navigation' ),
		);
	}

	/**
	 * Published `wp_navigation` post with the given slug.
	 *
	 * @param string $slug `post_name`.
	 */
	private function navigation( string $slug ): \WP_Post {
		$posts = get_posts(
			array(
				'post_type' => 'wp_navigation',
				'name'      => $slug,
			)
		);
		$this->assertCount( 1, $posts, $slug );
		return $posts[0];
	}

	/**
	 * Top-level items of a menu: „Label → url” for links, „Label ▾ child, child” for groups
	 * (`core/navigation-submenu` without a link).
	 *
	 * @param string $slug `post_name` of the menu.
	 * @return list<string>
	 */
	private function menu_outline( string $slug ): array {
		$items = array();
		foreach ( parse_blocks( $this->navigation( $slug )->post_content ) as $block ) {
			if ( 'core/navigation-submenu' === $block['blockName'] ) {
				$this->assertArrayNotHasKey( 'url', $block['attrs'], $block['attrs']['label'] );
				$children = array_column( array_column( $block['innerBlocks'], 'attrs' ), 'label' );
				$items[]  = $block['attrs']['label'] . ' ▾ ' . implode( ', ', $children );
			} elseif ( 'core/navigation-link' === $block['blockName'] ) {
				$items[] = $block['attrs']['label'] . ' → ' . $block['attrs']['url'];
			}
		}
		return $items;
	}
}
