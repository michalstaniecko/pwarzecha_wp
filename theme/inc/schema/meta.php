<?php
/**
 * Page meta of the schema (#53): `pw_schema_typ` and `pw_area_served`.
 *
 * The page type does not follow from the template (every page uses page.html), so the editor
 * picks it in the document panel, section „Schema” (src/editor/page-lokalizacja/):
 * - `pw_schema_typ` (one of TYPY): `brak` (default, only Yoast's WebPage – e.g. Grafik, also
 *   when the page has Lokalizacje), `oferta` (Strona oferty: the Lokalizacje of the page),
 *   `kontakt` (the Kontakt page – not „Dane strony”: every Lokalizacja), `trener` (Person),
 *   `service` (Service, pages 1:1 and B2B, plus the Lokalizacje of the page);
 * - `pw_area_served`: cities of the Service (`array<string>`); empty = AREA_SERVED_DEFAULT.
 *
 * @package PW
 */

namespace PW\Schema;

defined( 'ABSPATH' ) || exit;

const TYP_KEY  = 'pw_schema_typ';
const AREA_KEY = 'pw_area_served';

const TYP_BRAK    = 'brak';
const TYP_OFERTA  = 'oferta';
const TYP_KONTAKT = 'kontakt';
const TYP_TRENER  = 'trener';
const TYP_SERVICE = 'service';

/**
 * Every schema page type, the REST enum of `pw_schema_typ`. The editor list mirrors it
 * (src/editor/page-lokalizacja/SchemaSection.js).
 */
const TYPY = array( TYP_BRAK, TYP_OFERTA, TYP_KONTAKT, TYP_TRENER, TYP_SERVICE );

/**
 * Cities served when the page sets none [ZL].
 */
const AREA_SERVED_DEFAULT = array( 'Chorzów', 'Katowice' );

/**
 * Registers both keys on pages (single, in REST, edited by users who can edit the page).
 */
function register_meta(): void {
	$auth = static fn( $allowed, $meta_key, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id );

	register_post_meta(
		'page',
		TYP_KEY,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type' => 'string',
					'enum' => TYPY,
				),
			),
			'default'           => TYP_BRAK,
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_typ',
			'auth_callback'     => $auth,
		)
	);

	register_post_meta(
		'page',
		AREA_KEY,
		array(
			'type'              => 'array',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
			'default'           => array(),
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_area_served',
			'auth_callback'     => $auth,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\register_meta' );

/**
 * One of TYPY, anything else is TYP_BRAK.
 *
 * @param mixed $value Raw value.
 */
function sanitize_typ( $value ): string {
	return is_string( $value ) && in_array( $value, TYPY, true ) ? $value : TYP_BRAK;
}

/**
 * Trimmed, non-empty, unique city names in the given order; not a list = `[]`.
 *
 * @param mixed $value Raw value.
 * @return string[]
 */
function sanitize_area_served( $value ): array {
	$cities = array();
	foreach ( is_array( $value ) ? $value : array() as $city ) {
		$city = is_scalar( $city ) ? sanitize_text_field( (string) $city ) : '';
		if ( '' !== $city && ! in_array( $city, $cities, true ) ) {
			$cities[] = $city;
		}
	}
	return $cities;
}

/**
 * Schema type of a page (`brak` for other post types).
 *
 * @param int $post_id Post ID.
 */
function typ( int $post_id ): string {
	if ( 'page' !== get_post_type( $post_id ) ) {
		return TYP_BRAK;
	}
	return sanitize_typ( get_post_meta( $post_id, TYP_KEY, true ) );
}

/**
 * Cities served by a Service page: the stored list or AREA_SERVED_DEFAULT.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function area_served( int $post_id ): array {
	$cities = sanitize_area_served( get_post_meta( $post_id, AREA_KEY, true ) );
	return array() !== $cities ? $cities : AREA_SERVED_DEFAULT;
}
