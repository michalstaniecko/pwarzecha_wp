<?php
/**
 * Lokalizacje of a page (#52, ADR 0007): the only way blocks (Grafik, Lokalizacja) and the schema
 * read the page meta `pw_lokalizacja` (inc/data/page-meta.php).
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lokalizacje of a page in the order the editor checked them, normalised by `pw_lokalizacja()`
 * (so through the `pw/data/lokalizacje` filter). Lokalizacje deleted or unpublished since are
 * skipped; `[]` when the page has none.
 *
 * @param int $post_id Page ID.
 * @return array<int, array<string, mixed>> Normalised Lokalizacje (shape: inc/data/lokalizacja-repository.php).
 */
function pw_strona_lokalizacje( int $post_id ): array {
	$ids = get_post_meta( $post_id, \PW\Data\PageMeta\LOKALIZACJA_KEY, true );
	return array_values(
		array_filter(
			array_map(
				static fn( $id ): ?array => pw_lokalizacja( absint( $id ) ),
				is_array( $ids ) ? $ids : array()
			)
		)
	);
}
