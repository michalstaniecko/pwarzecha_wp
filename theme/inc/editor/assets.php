<?php
/**
 * Enqueues the editor entry `build/editor/` (source `src/editor/index.js`, #43) with its
 * configuration `window.pwEditor`.
 *
 * @package PW
 */

namespace PW\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the built editor script; does nothing in an unbuilt theme.
 */
function enqueue_editor_assets(): void {
	$asset_file = get_template_directory() . '/build/editor/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$asset = require $asset_file;

	wp_enqueue_script(
		'pw-editor',
		get_template_directory_uri() . '/build/editor/index.js',
		$asset['dependencies'],
		$asset['version'],
		false
	);
	wp_add_inline_script( 'pw-editor', 'window.pwEditor = ' . wp_json_encode( editor_config() ) . ';', 'before' );
}

/**
 * Configuration of the editor script (`window.pwEditor`): `postType` of the post editor
 * screen, '' in the Site Editor and elsewhere. src/editor/ limits heading levels in `post` only.
 *
 * @return array{postType: string}
 */
function editor_config(): array {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return array( 'postType' => $screen ? (string) $screen->post_type : '' );
}

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
