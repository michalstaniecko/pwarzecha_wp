<?php
/**
 * Grafik of a Lokalizacja and the Grupy (#50, ADR 0007).
 *
 * Grupy are a fixed list: the non-public taxonomy `grupa`, managed in „Lokalizacje → Grupy”
 * (term name = Grupa name, description = age range, term meta `pw_kolejnosc` = order). The
 * taxonomy is attached to `lokalizacja` only so that WordPress shows it under that menu; posts
 * are never assigned to Grupy. Grafik rows live in the post meta `pw_grafik` of a Lokalizacja
 * and point to Grupy by term ID. Read through `pw_grafik()` / `pw_grupy()`
 * (inc/data/grafik-repository.php). Pure helpers: inc/data/grafik-helpers.php.
 *
 * @package PW
 */

namespace PW\Data\Grafik;

defined( 'ABSPATH' ) || exit;

const TAXONOMY = 'grupa';
const META     = 'pw_grafik';
const ORDER    = 'pw_kolejnosc';

/**
 * Registers the `grupa` taxonomy: panel and REST only, no front URLs, no box on the post screen.
 */
function register_taxonomy(): void {
	\register_taxonomy(
		TAXONOMY,
		\PW\Data\Lokalizacja\POST_TYPE,
		array(
			'labels'             => array(
				'name'          => __( 'Grupy', 'pw-theme' ),
				'singular_name' => __( 'Grupa', 'pw-theme' ),
				'menu_name'     => __( 'Grupy', 'pw-theme' ),
				'all_items'     => __( 'Wszystkie Grupy', 'pw-theme' ),
				'add_new_item'  => __( 'Dodaj Grupę', 'pw-theme' ),
				'new_item_name' => __( 'Nazwa nowej Grupy', 'pw-theme' ),
				'edit_item'     => __( 'Edytuj Grupę', 'pw-theme' ),
				'update_item'   => __( 'Zapisz Grupę', 'pw-theme' ),
				'search_items'  => __( 'Szukaj Grup', 'pw-theme' ),
				'not_found'     => __( 'Brak Grup.', 'pw-theme' ),
				'back_to_items' => __( '← Wróć do Grup', 'pw-theme' ),
			),
			'description'        => __( 'Stała lista Grup wybieranych w wierszach Grafiku. Opis = przedział wieku.', 'pw-theme' ),
			'public'             => false,
			'publicly_queryable' => false,
			'hierarchical'       => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'show_admin_column'  => false,
			'meta_box_cb'        => false,
			'show_in_rest'       => true,
			'rewrite'            => false,
			'query_var'          => false,
		)
	);
}

/**
 * Registers the term meta of Grupy and the Grafik meta of Lokalizacje.
 */
function register_meta(): void {
	register_term_meta(
		TAXONOMY,
		ORDER,
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => 0,
			'sanitize_callback' => static fn( $value ): int => (int) $value,
		)
	);

	register_post_meta(
		\PW\Data\Lokalizacja\POST_TYPE,
		META,
		array(
			'type'              => 'array',
			'single'            => true,
			'default'           => array(),
			'show_in_rest'      => array( 'schema' => row_schema() ),
			// Also runs on REST writes and update_post_meta() (seed): only valid rows are stored.
			'sanitize_callback' => static fn( $value ): array => pw_grafik_sanitize( is_array( $value ) ? $value : array() ),
			'auth_callback'     => static fn( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id ),
		)
	);
}

/**
 * REST schema of `pw_grafik`: a list of rows. Times accept "H:MM" (stored as "HH:MM").
 *
 * @return array<string, mixed>
 */
function row_schema(): array {
	$time = array(
		'type'    => 'string',
		'pattern' => '^\d{1,2}:\d{2}$',
	);
	return array(
		'type'  => 'array',
		'items' => array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'dzien', 'od', 'do', 'grupa' ),
			'properties'           => array(
				'dzien' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 7,
				),
				'od'    => $time,
				'do'    => $time,
				'grupa' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		),
	);
}

/**
 * Titles of the Lokalizacje (any status, trash included) whose Grafik has a row of the Grupa.
 *
 * @param int $grupa_id Grupa term ID.
 * @return string[]
 */
function lokalizacje_using( int $grupa_id ): array {
	$ids   = get_posts(
		array(
			'post_type'      => \PW\Data\Lokalizacja\POST_TYPE,
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$nazwy = array();
	foreach ( $ids as $id ) {
		$rows = get_post_meta( (int) $id, META, true );
		if ( is_array( $rows ) && in_array( $grupa_id, array_map( 'intval', array_column( $rows, 'grupa' ) ), true ) ) {
			$nazwy[] = get_the_title( (int) $id );
		}
	}
	return $nazwy;
}

/**
 * `pre_delete_term` callback: a Grupa used in a Grafik is not deleted, so no row is orphaned.
 * Stops the request with a message naming the Lokalizacje that use it.
 *
 * @param int    $term_id  Term about to be deleted.
 * @param string $taxonomy Its taxonomy.
 */
function block_used_grupa_delete( int $term_id, string $taxonomy ): void {
	if ( TAXONOMY !== $taxonomy ) {
		return;
	}
	$lokalizacje = lokalizacje_using( $term_id );
	if ( array() === $lokalizacje ) {
		return;
	}
	$term = get_term( $term_id, TAXONOMY );
	wp_die(
		esc_html(
			sprintf(
				/* translators: 1: Grupa name, 2: comma-separated Lokalizacja names. */
				__( 'Nie można usunąć Grupy „%1$s”: jest w Grafiku Lokalizacji: %2$s. Najpierw usuń albo zmień te wiersze Grafiku.', 'pw-theme' ),
				$term instanceof \WP_Term ? $term->name : (string) $term_id,
				implode( ', ', $lokalizacje )
			)
		),
		esc_html__( 'Grupa jest używana', 'pw-theme' ),
		array(
			'response'  => 409,
			'back_link' => true,
		)
	);
}

/**
 * `grupa_row_actions` callback: a Grupa used in a Grafik has no „Usuń” link on the Grupy list
 * (deleting it would only fail in block_used_grupa_delete()). Bulk delete stays guarded there.
 *
 * @param array<string, string> $actions Row actions.
 * @param \WP_Term              $term    Listed Grupa.
 * @return array<string, string>
 */
function hide_used_grupa_delete( array $actions, \WP_Term $term ): array {
	if ( isset( $actions['delete'] ) && array() !== lokalizacje_using( $term->term_id ) ) {
		unset( $actions['delete'] );
	}
	return $actions;
}

add_action( 'init', __NAMESPACE__ . '\register_taxonomy' );
add_filter( TAXONOMY . '_row_actions', __NAMESPACE__ . '\hide_used_grupa_delete', 10, 2 );
add_action( 'pre_delete_term', __NAMESPACE__ . '\block_used_grupa_delete', 10, 2 );
add_action( 'init', __NAMESPACE__ . '\register_meta' );
