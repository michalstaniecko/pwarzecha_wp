<?php
/**
 * Image field of classic metaboxes (#49): hidden attachment ID + „Wybierz obraz” button.
 *
 * The button opens `wp.media` through the admin script `build/admin/` (source
 * `src/admin/media-field.js`, no logic beyond wiring the frame). Use `render_media_field()` in
 * the metabox and call `enqueue_media_field()` on the screen that shows it.
 *
 * @package PW
 */

namespace PW\Data\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Markup of the image field.
 *
 * @param string $name          Input name.
 * @param string $id            Input ID (for the label).
 * @param int    $attachment_id Selected attachment, 0 = none.
 */
function render_media_field( string $name, string $id, int $attachment_id ): string {
	$preview = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '';
	return sprintf(
		'<div class="pw-media-field" data-pw-media-field>'
			. '<input type="hidden" name="%1$s" id="%2$s" value="%3$s" data-pw-media-input>'
			. '<div class="pw-media-field__preview" data-pw-media-preview>%4$s</div>'
			. '<p><button type="button" class="button" data-pw-media-select>%5$s</button> '
			. '<button type="button" class="button-link button-link-delete" data-pw-media-remove%6$s>%7$s</button></p>'
			. '</div>',
		esc_attr( $name ),
		esc_attr( $id ),
		esc_attr( $attachment_id ? (string) $attachment_id : '' ),
		$preview,
		esc_html__( 'Wybierz obraz', 'pw-theme' ),
		$attachment_id ? '' : ' hidden',
		esc_html__( 'Usuń obraz', 'pw-theme' )
	);
}

/**
 * Enqueues the media modal and the admin script; nothing in an unbuilt theme.
 */
function enqueue_media_field(): void {
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
