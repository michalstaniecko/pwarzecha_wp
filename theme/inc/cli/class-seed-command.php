<?php
/**
 * `wp pw seed [--force] [--only=<czesci>]` (#54, ADR 0006, ADR 0011).
 *
 * One starting state for Playground (e2e blueprint), wp-env (`npm run seed`) and the stage
 * (entrypoint, empty database only). Placeholders `[...]` only, never invented facts (#30).
 * Every element is looked up by its slug/key, so a re-run with `--force` updates, not duplicates.
 *
 * `run()` holds the logic and has no WP-CLI dependency (integration tests call it directly);
 * `__invoke()` is the WP-CLI entry point.
 *
 * @package PW
 */

namespace PW\Cli;

defined( 'ABSPATH' ) || exit;

/**
 * Seeds the global data, Grupy, Grafik, Karnety, menus and Yoast settings.
 */
final class Seed_Command {

	/**
	 * Parts in the order they run (Grupy before Grafik rows: the Grafik sanitizer drops rows of
	 * unknown Grupy).
	 */
	public const PARTS = array( 'kontakt', 'grupy', 'lokalizacje', 'grafik', 'karnety', 'menu', 'yoast' );

	/**
	 * „Dane strony” (#48). The sanitizer rejects `[...]` as a phone, an e-mail, a NIP or a URL,
	 * so the phone and e-mail are obvious dummies and the rest of those fields stay empty
	 * (= hidden). `topbarTekst` is not stored: the default applies.
	 */
	private const KONTAKT = array(
		'telefon'         => '+48 000 000 000', // Working value [ZL]: the client gives the real one.
		'email'           => 'kontakt@example.com', // Working value [ZL]: the client gives the real one.
		'godzinyTelefonu' => '[...]',
		'nazwaFirmy'      => '[...]',
		'adresRejestrowy' => '[...]',
	);

	/**
	 * Lokalizacje: slug (= `post_name` and anchor) => city, in `menu_order`.
	 */
	private const LOKALIZACJE = array(
		'chorzow'  => 'Chorzów',
		'katowice' => 'Katowice',
	);

	/**
	 * Grupy: slug => [name, age range]. Working list from #16/#31 [ZL] – the client confirms it.
	 */
	private const GRUPY = array(
		'dzieci-6-9-lat'   => array( 'Dzieci 6–9 lat', '6–9 lat' ),
		'dzieci-10-13-lat' => array( 'Dzieci 10–13 lat', '10–13 lat' ),
		'mlodziez-14'      => array( 'Młodzież 14+', '14+' ),
	);

	/**
	 * Grafik rows per Lokalizacja slug: [day 1–7, from, to, Grupa slug]. Placeholder hours, not
	 * the real timetable.
	 */
	private const GRAFIK = array(
		'chorzow'  => array(
			array( 1, '17:00', '18:00', 'dzieci-6-9-lat' ),
			array( 3, '18:00', '19:00', 'dzieci-10-13-lat' ),
			array( 5, '19:00', '20:00', 'mlodziez-14' ),
		),
		'katowice' => array(
			array( 2, '17:00', '18:00', 'dzieci-6-9-lat' ),
			array( 4, '18:00', '19:30', 'mlodziez-14' ),
		),
	);

	/**
	 * Karnety: slug => [months, price, label under the price, polecany]. Names and „what it
	 * includes” are `[...]`; prices are working values [ZL], only there so that the Cennik and the
	 * Teaser ceny (#69, #70) show every state (polecany, 1 and 5 months).
	 */
	private const KARNETY = array(
		'karnet-1' => array( 1, 120, '/ mies.', false ),
		'karnet-2' => array( 1, 160, '/ mies.', true ),
		'karnet-3' => array( 5, 700, '/ 5 mies.', false ),
	);

	/**
	 * Offer groups (dropdowns of the main menu, columns of „Stopka – Oferta”): label => links.
	 * Every link is `#` until the pages exist (#93).
	 */
	private const OFERTA = array(
		'Treningi grupowe'      => array( 'Chorzów', 'Katowice' ),
		'Treningi indywidualne' => array( 'Dla dorosłych', 'Dla dzieci' ),
		'Dla firm i instytucji' => array( 'Przedszkola', 'Szkoły i instytucje' ),
	);

	/**
	 * Slugs of the default WordPress page („Przykładowa strona”), which does not make a database
	 * non-empty.
	 */
	private const SAMPLE_PAGES = array( 'sample-page', 'przykladowa-strona' );

	/**
	 * Seeds the starting state: Dane strony, Grupy, Lokalizacje, Grafik, Karnety, menus, Yoast.
	 *
	 * Refuses (exit 1) when the database already has content, unless --force is given. Every
	 * element is found by its slug, so a re-run updates instead of duplicating.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Seed even when Lokalizacje, Karnety or published pages already exist.
	 *
	 * [--only=<czesci>]
	 * : Comma-separated parts: kontakt, grupy, lokalizacje, grafik, karnety, menu, yoast.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pw seed
	 *     wp pw seed --force --only=karnety,menu
	 *
	 * @param array<int, string>         $args       Positional arguments (unused).
	 * @param array<string, string|bool> $assoc_args Flags.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		try {
			$report = $this->run( $args, $assoc_args );
		} catch ( \Exception $e ) { // Refusal or an unknown part.
			\WP_CLI::error( $e->getMessage() );
			return;
		}
		foreach ( $report as $part => $count ) {
			\WP_CLI::log( sprintf( '%s: %d', $part, $count ) );
		}
		\WP_CLI::success( sprintf( 'Seeded %s.', implode( ', ', array_keys( $report ) ) ) );
	}

	/**
	 * Seeds the selected parts.
	 *
	 * @param array<int, string>         $args       Positional arguments (unused).
	 * @param array<string, string|bool> $assoc_args `force`, `only` (comma-separated PARTS).
	 * @return array<string, int> Number of seeded elements per part, in run order.
	 * @throws \RuntimeException When the database is not empty and `--force` is missing (also
	 *                           \InvalidArgumentException from parts() for an unknown part).
	 */
	public function run( array $args, array $assoc_args ): array {
		unset( $args );

		$parts = $this->parts( (string) ( $assoc_args['only'] ?? '' ) );

		if ( empty( $assoc_args['force'] ) && ! $this->is_empty_database() ) {
			throw new \RuntimeException( 'The database is not empty (Lokalizacje, Karnety or published pages exist). Use --force to seed anyway.' );
		}

		$seeders = array(
			'kontakt'     => array( $this, 'seed_kontakt' ),
			'grupy'       => array( $this, 'seed_grupy' ),
			'lokalizacje' => array( $this, 'seed_lokalizacje' ),
			'grafik'      => array( $this, 'seed_grafik' ),
			'karnety'     => array( $this, 'seed_karnety' ),
			'menu'        => array( $this, 'seed_menu' ),
			'yoast'       => array( $this, 'seed_yoast' ),
		);

		$report = array();
		foreach ( array_intersect( $parts, array_keys( $seeders ) ) as $part ) {
			$report[ $part ] = call_user_func( $seeders[ $part ] );
		}
		return $report;
	}

	/**
	 * Parts selected by `--only` (all when empty), in PARTS order.
	 *
	 * @param string $only Comma-separated part names.
	 * @return list<string>
	 * @throws \InvalidArgumentException When a name is not in PARTS.
	 */
	private function parts( string $only ): array {
		$names = array_filter( array_map( 'trim', explode( ',', $only ) ) );
		if ( array() === $names ) {
			return self::PARTS;
		}
		$unknown = array_diff( $names, self::PARTS );
		if ( array() !== $unknown ) {
			throw new \InvalidArgumentException(
				esc_html( sprintf( 'Unknown part(s): %s. Available: %s.', implode( ', ', $unknown ), implode( ', ', self::PARTS ) ) )
			);
		}
		return array_values( array_intersect( self::PARTS, $names ) );
	}

	/**
	 * `wp_navigation` menus read by `pw/site-nav` and `pw/menu-list` (ADR 0014).
	 */
	private function seed_menu(): int {
		$oferta = array();
		foreach ( self::OFERTA as $grupa => $linki ) {
			$oferta[] = $this->nav_submenu( $grupa, array_map( array( $this, 'nav_link' ), $linki ) );
		}
		$menus = array(
			// Label „Trener” (Q5).
			'menu-glowne'      => array( 'Menu główne', array_merge( $oferta, array_map( array( $this, 'nav_link' ), array( 'Trener', 'Cennik', 'Grafik' ) ) ) ),
			'stopka-oferta'    => array( 'Stopka – Oferta', $oferta ),
			'stopka-na-skroty' => array( 'Stopka – Na skróty', array_map( array( $this, 'nav_link' ), array( 'Trener', 'Cennik', 'Grafik', 'Aktualności', 'Kontakt' ) ) ),
		);

		foreach ( $menus as $slug => list( $title, $blocks ) ) {
			$this->upsert_post(
				'wp_navigation',
				$slug,
				array(
					'post_title'   => $title,
					'post_content' => serialize_blocks( $blocks ),
				)
			);
		}
		return count( $menus );
	}

	/**
	 * Yoast SEO settings: no author, date, category and tag archives (Yoast cannot remove the
	 * last two, so they are noindex), separator „|” (titles in #31), breadcrumbs with the posts
	 * page in the trail (#79). No-op (0) when Yoast is inactive.
	 */
	private function seed_yoast(): int {
		if ( ! class_exists( \WPSEO_Options::class ) ) {
			return 0;
		}
		$settings = array(
			'disable-author'                => true,
			'disable-date'                  => true,
			'noindex-tax-category'          => true,
			'noindex-tax-post_tag'          => true,
			'separator'                     => 'sc-pipe',
			'breadcrumbs-enable'            => true,
			'breadcrumbs-display-blog-page' => true,
		);
		foreach ( $settings as $key => $value ) {
			\WPSEO_Options::set( $key, $value );
		}
		return count( $settings );
	}

	/**
	 * `core/navigation-link` block (custom link, `#` until #93 links the pages).
	 *
	 * @param string $label Label.
	 * @return array<string, mixed> Parsed block.
	 */
	private function nav_link( string $label ): array {
		return array(
			'blockName'    => 'core/navigation-link',
			'attrs'        => array(
				'label' => $label,
				'url'   => '#',
				'kind'  => 'custom',
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}

	/**
	 * `core/navigation-submenu` block without a link (a group label, ADR 0014).
	 *
	 * @param string                           $label Label.
	 * @param array<int, array<string, mixed>> $links Inner `core/navigation-link` blocks.
	 * @return array<string, mixed> Parsed block.
	 */
	private function nav_submenu( string $label, array $links ): array {
		return array(
			'blockName'    => 'core/navigation-submenu',
			'attrs'        => array( 'label' => $label ),
			'innerBlocks'  => $links,
			'innerHTML'    => '',
			'innerContent' => array_fill( 0, count( $links ), null ),
		);
	}

	/**
	 * True when no Lokalizacja, no Karnet (any status but trash) and no published page other
	 * than the default sample page exist (ADR 0006: the stage seeds an empty database only).
	 */
	public function is_empty_database(): bool {
		$content = get_posts(
			array(
				'post_type'        => array( \PW\Data\Lokalizacja\POST_TYPE, PW_KARNET_POST_TYPE ),
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		if ( array() !== $content ) {
			return false;
		}

		$pages = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => count( self::SAMPLE_PAGES ) + 1,
				'suppress_filters' => true,
			)
		);
		foreach ( $pages as $page ) {
			if ( ! in_array( $page->post_name, self::SAMPLE_PAGES, true ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Replaces the „Dane strony” option.
	 */
	private function seed_kontakt(): int {
		update_option( PW_KONTAKT_OPTION, self::KONTAKT );
		return count( self::KONTAKT );
	}

	/**
	 * Grupy terms with their order (`pw_kolejnosc` 1, 2, 3).
	 *
	 * @throws \RuntimeException When a term cannot be saved.
	 */
	private function seed_grupy(): int {
		$order = 0;
		foreach ( self::GRUPY as $slug => list( $nazwa, $wiek ) ) {
			$term   = get_term_by( 'slug', $slug, \PW\Data\Grafik\TAXONOMY );
			$args   = array(
				'slug'        => $slug,
				'description' => $wiek,
			);
			$result = $term instanceof \WP_Term
				? wp_update_term( $term->term_id, \PW\Data\Grafik\TAXONOMY, array( 'name' => $nazwa ) + $args )
				: wp_insert_term( $nazwa, \PW\Data\Grafik\TAXONOMY, $args );
			if ( is_wp_error( $result ) ) {
				throw new \RuntimeException( esc_html( "grupa {$slug}: " . $result->get_error_message() ) );
			}
			update_term_meta( (int) $result['term_id'], \PW\Data\Grafik\ORDER, ++$order );
		}
		return count( self::GRUPY );
	}

	/**
	 * Lokalizacje Chorzów and Katowice with placeholder addresses.
	 */
	private function seed_lokalizacje(): int {
		$order = 0;
		foreach ( self::LOKALIZACJE as $slug => $miasto ) {
			$this->upsert_post(
				\PW\Data\Lokalizacja\POST_TYPE,
				$slug,
				array(
					'post_title' => $miasto,
					'menu_order' => ++$order,
					'meta_input' => array(
						\PW\Data\Lokalizacja\ULICA   => '[...]',
						\PW\Data\Lokalizacja\MIASTO  => $miasto,
						\PW\Data\Lokalizacja\KOTWICA => $slug,
					),
				)
			);
		}
		return count( self::LOKALIZACJE );
	}

	/**
	 * Grafik rows of the seeded Lokalizacje (replaces the stored rows).
	 */
	private function seed_grafik(): int {
		$count = 0;
		foreach ( self::GRAFIK as $slug => $wiersze ) {
			$lokalizacja = $this->find_post( \PW\Data\Lokalizacja\POST_TYPE, $slug );
			if ( 0 === $lokalizacja ) {
				continue;
			}
			$rows = array();
			foreach ( $wiersze as list( $dzien, $od, $do, $grupa ) ) {
				$term   = get_term_by( 'slug', $grupa, \PW\Data\Grafik\TAXONOMY );
				$rows[] = array(
					'dzien' => $dzien,
					'od'    => $od,
					'do'    => $do,
					'grupa' => $term instanceof \WP_Term ? $term->term_id : 0,
				);
			}
			update_post_meta( $lokalizacja, \PW\Data\Grafik\META, $rows );
			$count += count( pw_grafik( $lokalizacja ) );
		}
		return $count;
	}

	/**
	 * Karnety `[...]`, exactly one polecany.
	 */
	private function seed_karnety(): int {
		$order = 0;
		foreach ( self::KARNETY as $slug => list( $miesiace, $cena, $etykieta, $polecany ) ) {
			$id = $this->upsert_post(
				PW_KARNET_POST_TYPE,
				$slug,
				array(
					'post_title' => '[...] ' . ( ++$order ),
					'menu_order' => $order,
					'meta_input' => array(
						PW_KARNET_CENA           => $cena,
						PW_KARNET_OKRES_MIESIACE => $miesiace,
						PW_KARNET_OKRES_ETYKIETA => $etykieta,
						PW_KARNET_OBEJMUJE       => array( '[...]', '[...]', '[...]' ),
					),
				)
			);
			if ( $polecany ) {
				update_post_meta( $id, PW_KARNET_POLECANY, true );
			} else {
				delete_post_meta( $id, PW_KARNET_POLECANY );
			}
		}
		return count( self::KARNETY );
	}

	/**
	 * ID of the post of a type with the given slug (any status), 0 when missing.
	 *
	 * @param string $post_type Post type.
	 * @param string $slug      `post_name`.
	 */
	private function find_post( string $post_type, string $slug ): int {
		$ids = get_posts(
			array(
				'post_type'        => $post_type,
				'name'             => $slug,
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		return (int) ( $ids[0] ?? 0 );
	}

	/**
	 * Creates or updates the published post of a type with the given slug.
	 *
	 * @param string               $post_type Post type.
	 * @param string               $slug      `post_name`.
	 * @param array<string, mixed> $postarr   Fields for wp_insert_post().
	 * @return int Post ID.
	 * @throws \RuntimeException When the post cannot be saved.
	 */
	private function upsert_post( string $post_type, string $slug, array $postarr ): int {
		$postarr = array_merge(
			$postarr,
			array(
				'ID'          => $this->find_post( $post_type, $slug ),
				'post_type'   => $post_type,
				'post_name'   => $slug,
				'post_status' => 'publish',
			)
		);

		$id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $id ) ) {
			throw new \RuntimeException( esc_html( "{$post_type} {$slug}: " . $id->get_error_message() ) );
		}
		return $id;
	}
}
