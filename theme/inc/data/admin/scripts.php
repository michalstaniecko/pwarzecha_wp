<?php
/**
 * Admin bundle of the panel screens (#49, #50): `build/admin/` (source `src/admin/index.js`) with
 * every admin script – image fields (`media-field.js`) and the Grafik rows (`grafik-rows.js`) –
 * plus the media modal. Enqueued on the Lokalizacja edit screen (inc/data/lokalizacja-metabox.php).
 *
 * @package PW
 */

namespace PW\Data\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the media modal and the whole admin bundle `pw-admin`; nothing in an unbuilt theme.
 */
function enqueue_admin_scripts(): void {
	$asset_file = get_template_directory() . '/build/admin/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$asset = require $asset_file;

	wp_enqueue_media();
	wp_enqueue_script(
		'pw-admin',
		get_template_directory_uri() . '/build/admin/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
}
