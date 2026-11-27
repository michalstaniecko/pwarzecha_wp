<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * WordPress sees every theme.json setting the DS token map points at (#33).
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use WP_UnitTestCase;

/**
 * Loops over `tools/ds/theme-map.json` (export of `tools/ds/theme-map.mjs`).
 */
class ThemeJsonPresetsTest extends WP_UnitTestCase {

	/**
	 * Entries of the DS ↔ theme.json map.
	 *
	 * @return array<string, array{0: array<int, string|array{slug: string}>}>
	 */
	public static function map_entries(): array {
		$json  = file_get_contents( dirname( __DIR__, 3 ) . '/tools/ds/theme-map.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		$cases = array();
		foreach ( json_decode( $json, true ) as $entry ) {
			$ds    = $entry['ds'];
			$label = $ds['family'] . '/' . $ds['token'] . ( isset( $ds['prop'] ) ? '.' . $ds['prop'] : '' );

			$cases[ $label ] = array( $entry['theme'] );
		}
		return $cases;
	}

	/**
	 * A preset (`{ slug }` segment) is looked up among the theme-origin presets.
	 *
	 * @param array<int, string|array{slug: string}> $path theme.json path from the root.
	 */
	#[DataProvider( 'map_entries' )]
	public function test_global_settings_contain_mapped_value( array $path ): void {
		$this->assertSame( 'settings', array_shift( $path ) );

		$node = wp_get_global_settings();
		foreach ( $path as $segment ) {
			if ( is_array( $segment ) ) {
				$presets = $node['theme'] ?? array();
				$found   = array_values(
					array_filter(
						$presets,
						static fn( $preset ) => ( $preset['slug'] ?? null ) === $segment['slug']
					)
				);
				$this->assertCount( 1, $found, 'preset ' . $segment['slug'] );
				$node = $found[0];
				continue;
			}
			$this->assertIsArray( $node );
			$this->assertArrayHasKey( $segment, $node );
			$node = $node[ $segment ];
		}
		$this->assertNotEmpty( $node );
	}
}
