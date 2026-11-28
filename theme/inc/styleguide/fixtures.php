<?php
/**
 * Styleguide fixtures (#42): global data per row and test photos, without touching stored data.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Test photos in inc/styleguide/assets/: ratio => file.
 */
const PW_SG_FIXTURE_IMAGES = array(
	'wide'   => 'styleguide-wide.jpg',
	'photo'  => 'styleguide-photo.jpg',
	'square' => 'styleguide-square.jpg',
);

/**
 * Renders with global data replaced: filter `pw/data/<name>` returns `$value` only while
 * `$render` runs (ADR 0007), also when it throws.
 *
 * @param string   $name   Data set, e.g. `kontakt`.
 * @param mixed    $value  Data for this row: full, empty (`null`, `array()`), long values.
 * @param callable $render Prints the row content.
 */
function pw_sg_with_data( string $name, $value, callable $render ): void {
	$hook     = 'pw/data/' . $name;
	$callback = static fn() => $value;
	add_filter( $hook, $callback, PHP_INT_MAX );
	try {
		$render();
	} finally {
		remove_filter( $hook, $callback, PHP_INT_MAX );
	}
}

/**
 * Photo of that ratio: pw_photo() of the test photo; an empty `sp-photo` frame when it could not be
 * imported (pw_photo() itself renders nothing for a missing image).
 *
 * @param string               $ratio `wide` | `photo` | `square`.
 * @param array<string, mixed> $args  pw_photo() arguments (`ratio` is set from `$ratio`).
 */
function pw_sg_photo( string $ratio, array $args = array() ): string {
	$html = pw_photo( pw_sg_fixture_image( $ratio ), array( 'ratio' => $ratio ) + $args );
	return '' !== $html ? $html : '<div class="sp-photo sp-photo--' . esc_attr( $ratio ) . '"></div>';
}

/**
 * Attachment ID of a test photo, imported into the media library on first use (idempotent:
 * found again by the `_pw_sg_fixture` meta). Only while the styleguide is enabled.
 *
 * @param string $ratio `wide` | `photo` | `square`.
 * @return int Attachment ID, 0 when disabled, unknown or the import failed.
 */
function pw_sg_fixture_image( string $ratio ): int {
	$file = PW_SG_FIXTURE_IMAGES[ $ratio ] ?? '';
	if ( '' === $file || ! pw_styleguide_enabled() ) {
		return 0;
	}
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_pw_sg_fixture', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- dev page, a handful of attachments.
			'meta_value'     => $file, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above.
		)
	);
	if ( $found ) {
		return (int) $found[0];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// media_handle_sideload() moves the file: give it a copy.
	$tmp = wp_tempnam( $file );
	if ( ! copy( __DIR__ . '/assets/' . $file, $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload(
		array(
			'name'     => $file,
			'tmp_name' => $tmp,
		),
		0,
		'Styleguide: zdjęcie testowe ' . $ratio
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_pw_sg_fixture', $file );
	update_post_meta( $id, '_wp_attachment_image_alt', 'Zdjęcie testowe styleguide (' . $ratio . ')' );
	return (int) $id;
}
