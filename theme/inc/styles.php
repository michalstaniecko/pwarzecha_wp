<?php
/**
 * DS primitives outside theme.json (#41): block styles of core blocks, the shared stylesheet
 * (build/styles/, source theme/src/styles/) and the H1 font preload.
 *
 * @package PW
 */

namespace PW\Styles;

defined( 'ABSPATH' ) || exit;

/**
 * Block styles (`is-style-<name>`) per core block. CSS lives in theme/src/styles/.
 *
 * @return array<string, array<string, string>> Block name => [ style name => label ].
 */
function block_styles(): array {
	return array(
		'core/button'        => array(
			'primary'   => __( 'Główny', 'pw-theme' ),
			'secondary' => __( 'Drugorzędny', 'pw-theme' ),
		),
		'core/paragraph'     => array(
			'sp-link'     => __( 'Link ze strzałką', 'pw-theme' ),
			'badge-quiet' => __( 'Plakietka', 'pw-theme' ),
		),
		'core/table'         => array(
			'sp-table' => __( 'Tabela DS', 'pw-theme' ),
		),
		'core/image'         => array(
			'photo-wide'   => __( 'Zdjęcie 16:9', 'pw-theme' ),
			'photo'        => __( 'Zdjęcie 4:3', 'pw-theme' ),
			'photo-square' => __( 'Zdjęcie 1:1', 'pw-theme' ),
		),
		'core/post-template' => array(
			'sp-results' => __( 'Wyniki wyszukiwania', 'pw-theme' ),
		),
	);
}

/**
 * Registers the theme block styles.
 */
function register_block_styles(): void {
	foreach ( block_styles() as $block => $styles ) {
		foreach ( $styles as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'       => $name,
					'label'      => $label,
					'is_default' => 'core/button' === $block && 'primary' === $name,
				)
			);
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\register_block_styles' );

/**
 * Enqueues build/styles/index.css on the front end and inside the editor canvas.
 */
function enqueue_stylesheet(): void {
	$asset_file = get_theme_file_path( 'build/styles/index.asset.php' );
	if ( ! file_exists( $asset_file ) ) {
		return; // Theme not built (`npm run build`).
	}
	$asset = require $asset_file;
	wp_enqueue_style(
		'pw-styles',
		get_theme_file_uri( 'build/styles/index.css' ),
		array(),
		$asset['version'] ?? null
	);
}
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\enqueue_stylesheet' );

/**
 * Preloads the H1 font (Archivo, latin subset; the variable file covers weight 800).
 * Other faces load on demand through `font-display: swap`.
 */
function preload_heading_font(): void {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_theme_file_uri( 'assets/fonts/archivo-latin.woff2' ) )
	);
}
add_action( 'wp_head', __NAMESPACE__ . '\preload_heading_font', 1 );
