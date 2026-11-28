<?php
/**
 * Block registration from the wp-scripts manifest and the `pw` block category (#36, ADR 0013).
 *
 * @package PW
 */

namespace PW\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every block built into build/blocks/ (manifest: build/blocks-manifest.php).
 */
function register(): void {
	register_collection( get_theme_file_path( 'build/blocks' ), get_theme_file_path( 'build/blocks-manifest.php' ) );
}
add_action( 'init', __NAMESPACE__ . '\register' );

/**
 * Registers the blocks of a wp-scripts manifest; nothing when the manifest is missing (theme
 * not built, `npm run build`).
 *
 * @param string $dir      Directory holding one sub-directory per block (manifest keys).
 * @param string $manifest Path of the manifest (`<?php return [ dir => block.json ];`).
 */
function register_collection( string $dir, string $manifest ): void {
	if ( ! file_exists( $manifest ) ) {
		return;
	}
	wp_register_block_types_from_metadata_collection( $dir, $manifest );
}

/**
 * Adds the "Samoobrona Przemek" category (slug `pw`) in front of the core ones.
 *
 * @param array<int, array<string, mixed>> $categories Registered block categories.
 * @return array<int, array<string, mixed>>
 */
function add_category( array $categories ): array {
	array_unshift(
		$categories,
		array(
			'slug'  => 'pw',
			'title' => __( 'Samoobrona Przemek', 'pw-theme' ),
			'icon'  => null,
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', __NAMESPACE__ . '\add_category' );
