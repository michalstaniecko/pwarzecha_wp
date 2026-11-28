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
	 * Seeds the selected parts.
	 *
	 * @param array<int, string>         $args       Positional arguments (unused).
	 * @param array<string, string|bool> $assoc_args `force`, `only` (comma-separated PARTS).
	 * @return array<string, int> Number of seeded elements per part, in run order.
	 */
	public function run( array $args, array $assoc_args ): array {
		unset( $args );

		return array( 'lokalizacje' => $this->seed_lokalizacje() );
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
	 * Creates or updates the published post of a type with the given slug.
	 *
	 * @param string               $post_type Post type.
	 * @param string               $slug      `post_name`.
	 * @param array<string, mixed> $postarr   Fields for wp_insert_post().
	 * @return int Post ID.
	 */
	private function upsert_post( string $post_type, string $slug, array $postarr ): int {
		$existing = get_posts(
			array(
				'post_type'        => $post_type,
				'name'             => $slug,
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);

		$postarr = array_merge(
			$postarr,
			array(
				'ID'          => $existing[0] ?? 0,
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
