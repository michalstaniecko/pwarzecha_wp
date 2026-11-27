<?php
/**
 * Enqueues the editor entry `build/editor/` (source `src/editor/index.js`, #43).
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
}

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
