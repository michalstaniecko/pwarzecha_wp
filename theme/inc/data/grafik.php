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
}

add_action( 'init', __NAMESPACE__ . '\register_taxonomy' );
add_action( 'init', __NAMESPACE__ . '\register_meta' );
