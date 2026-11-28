<?php
/**
 * Page meta `pw_lokalizacja` (#52, decision Q11): IDs of the Lokalizacje a page is about, in the
 * order the editor checked them (`array<int>`, `[]` = none). A page about both Lokalizacje checks
 * both – there is no special "all" value; "all" is a block attribute, not meta.
 * Pages also support the excerpt (`add_excerpt_support()`).
 *
 * Edited in the document panel „Lokalizacje i schema” (src/editor/page-lokalizacja/), read by
 * blocks and the schema only through `pw_strona_lokalizacje()` (inc/data/page-meta-repository.php).
 *
 * @package PW
 */

namespace PW\Data\PageMeta;

defined( 'ABSPATH' ) || exit;

const LOKALIZACJA_KEY = 'pw_lokalizacja';

/**
 * Registers `pw_lokalizacja` on pages (single, in REST, edited by users who can edit the page).
 */
function register_meta(): void {
	register_post_meta(
		'page',
		LOKALIZACJA_KEY,
		array(
			'type'              => 'array',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			),
			'default'           => array(),
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_lokalizacje',
			'auth_callback'     => static fn( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id ),
		)
	);
}

/**
 * Sanitises the meta value: IDs of existing, published Lokalizacje, unique, in the given order.
 * Anything that is not a list becomes `[]`.
 *
 * @param mixed $value Raw value.
 * @return int[]
 */
function sanitize_lokalizacje( $value ): array {
	$ids = array_unique( array_map( 'absint', is_array( $value ) ? $value : array() ) );
	return array_values( array_filter( $ids, __NAMESPACE__ . '\is_published_lokalizacja' ) );
}

/**
 * Whether the ID is a published Lokalizacja (raw post check, without the repository filter).
 *
 * @param int $id Post ID.
 */
function is_published_lokalizacja( int $id ): bool {
	return $id > 0
		&& \PW\Data\Lokalizacja\POST_TYPE === get_post_type( $id )
		&& 'publish' === get_post_status( $id );
}

/**
 * Pages get an excerpt: the Hero lead of the Aktualności list comes from the excerpt of the
 * page „Aktualności” (home.html, docs/design/decyzje-ds-i-layoutow.md §4).
 */
function add_excerpt_support(): void {
	add_post_type_support( 'page', 'excerpt' );
}

add_action( 'init', __NAMESPACE__ . '\register_meta' );
add_action( 'init', __NAMESPACE__ . '\add_excerpt_support' );
