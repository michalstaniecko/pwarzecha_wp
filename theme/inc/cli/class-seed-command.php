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
	 * Slugs of the default WordPress page („Przykładowa strona”), which does not make a database
	 * non-empty.
	 */
	private const SAMPLE_PAGES = array( 'sample-page', 'przykladowa-strona' );

	/**
	 * Seeds the selected parts.
	 *
	 * @param array<int, string>         $args       Positional arguments (unused).
	 * @param array<string, string|bool> $assoc_args `force`, `only` (comma-separated PARTS).
	 * @return array<string, int> Number of seeded elements per part, in run order.
	 * @throws \RuntimeException When the database is not empty and `--force` is missing.
	 */
	public function run( array $args, array $assoc_args ): array {
		unset( $args );

		if ( empty( $assoc_args['force'] ) && ! $this->is_empty_database() ) {
			throw new \RuntimeException( 'The database is not empty (Lokalizacje, Karnety or published pages exist). Use --force to seed anyway.' );
		}

		return array(
			'grupy'       => $this->seed_grupy(),
			'lokalizacje' => $this->seed_lokalizacje(),
			'grafik'      => $this->seed_grafik(),
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
						'pw_ulica'   => '[...]',
						'pw_miasto'  => $miasto,
						'pw_kotwica' => $slug,
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
