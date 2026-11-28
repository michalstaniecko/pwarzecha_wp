<?php
/**
 * CPT Karnet (#51, ADR 0007): non-public post type edited in the panel, ordered by `menu_order`.
 *
 * Read only through the repository pw_karnety() (filter `pw/data/karnety`).
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/karnet-cena.php';

const PW_KARNET_POST_TYPE = 'karnet';

add_action( 'init', 'pw_karnet_register' );

/**
 * Registers the post type and its meta.
 */
function pw_karnet_register(): void {
	register_post_type(
		PW_KARNET_POST_TYPE,
		array(
			'labels'       => array(
				'name'          => __( 'Karnety', 'pw-theme' ),
				'singular_name' => __( 'Karnet', 'pw-theme' ),
				'add_new_item'  => __( 'Dodaj Karnet', 'pw-theme' ),
				'edit_item'     => __( 'Edytuj Karnet', 'pw-theme' ),
				'new_item'      => __( 'Nowy Karnet', 'pw-theme' ),
				'all_items'     => __( 'Wszystkie Karnety', 'pw-theme' ),
				'search_items'  => __( 'Szukaj Karnetów', 'pw-theme' ),
				'not_found'     => __( 'Brak Karnetów.', 'pw-theme' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-tickets-alt',
			'supports'     => array( 'title', 'page-attributes' ),
		)
	);

	$meta = array(
		'pw_cena'           => array(
			'type'              => 'integer',
			'description'       => __( 'Cena w pełnych złotych.', 'pw-theme' ),
			'default'           => 0,
			'sanitize_callback' => static fn( $value ): int => max( 0, (int) $value ),
			'show_in_rest'      => array( 'schema' => array( 'minimum' => 0 ) ),
		),
		'pw_okres_miesiace' => array(
			'type'              => 'integer',
			'description'       => __( 'Okres płatności w miesiącach.', 'pw-theme' ),
			'default'           => 1,
			'sanitize_callback' => static fn( $value ): int => max( 1, (int) $value ),
			'show_in_rest'      => array( 'schema' => array( 'minimum' => 1 ) ),
		),
		'pw_okres_etykieta' => array(
			'type'              => 'string',
			'description'       => __( 'Tekst pod ceną, np. „/ mies.”.', 'pw-theme' ),
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
		),
		'pw_obejmuje'       => array(
			'type'              => 'array',
			'description'       => __( 'Co obejmuje Karnet, punkt po punkcie.', 'pw-theme' ),
			'default'           => array(),
			'sanitize_callback' => 'pw_karnet_sanitize_obejmuje',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		),
		'pw_polecany'       => array(
			'type'              => 'boolean',
			'description'       => __( 'Polecany Karnet (najwyżej jeden).', 'pw-theme' ),
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => true,
		),
	);

	foreach ( $meta as $key => $args ) {
		register_post_meta(
			PW_KARNET_POST_TYPE,
			$key,
			$args + array(
				'single'        => true,
				'auth_callback' => static fn( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', $post_id ),
			)
		);
	}
}

/**
 * „Co obejmuje” points: a list or text with one point per line → trimmed non-empty strings.
 *
 * @param mixed $value Raw value.
 * @return list<string>
 */
function pw_karnet_sanitize_obejmuje( $value ): array {
	if ( is_string( $value ) ) {
		$value = preg_split( '/\R/', $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$points = array_map( static fn( $point ): string => sanitize_text_field( (string) $point ), $value );

	return array_values( array_filter( $points, static fn( string $point ): bool => '' !== $point ) );
}

/**
 * Published Karnety in Cennik order (`menu_order` ASC), filtered by `pw/data/karnety`.
 *
 * @return list<array{id: int, nazwa: string, cena: int, okresMiesiace: int, okresEtykieta: string, obejmuje: list<string>, polecany: bool}>
 */
function pw_karnety(): array {
	$posts = get_posts(
		array(
			'post_type'      => PW_KARNET_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);

	$karnety = array_map(
		static function ( WP_Post $post ): array {
			$obejmuje = get_post_meta( $post->ID, 'pw_obejmuje', true );

			return array(
				'id'            => $post->ID,
				'nazwa'         => get_the_title( $post ),
				'cena'          => max( 0, (int) get_post_meta( $post->ID, 'pw_cena', true ) ),
				'okresMiesiace' => max( 1, (int) get_post_meta( $post->ID, 'pw_okres_miesiace', true ) ),
				'okresEtykieta' => (string) get_post_meta( $post->ID, 'pw_okres_etykieta', true ),
				'obejmuje'      => is_array( $obejmuje ) ? array_values( array_map( 'strval', $obejmuje ) ) : array(),
				'polecany'      => (bool) get_post_meta( $post->ID, 'pw_polecany', true ),
			);
		},
		$posts
	);

	/**
	 * Karnety for the Cennik and Teaser ceny (ADR 0007).
	 *
	 * @param array $karnety Karnety in Cennik order.
	 */
	return apply_filters( 'pw/data/karnety', $karnety );
}
