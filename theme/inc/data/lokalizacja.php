<?php
/**
 * CPT Lokalizacja (#49, ADR 0007): non-public post type with its meta and the repository.
 *
 * Name = post title. Address, map, directions and film live in meta `pw_*` (one place, NAP
 * identical to the Google listing). Blocks, the footer and the schema read Lokalizacje only
 * through `pw_lokalizacje()` / `pw_lokalizacja()` (filter `pw/data/lokalizacje`).
 * Panel editing: inc/data/lokalizacja-metabox.php. Pure helpers: inc/data/lokalizacja-helpers.php.
 *
 * @package PW
 */

namespace PW\Data\Lokalizacja;

defined( 'ABSPATH' ) || exit;

const POST_TYPE = 'lokalizacja';

/**
 * Registers the post type: panel and REST only, no front URLs, no archive.
 */
function register_post_type(): void {
	\register_post_type(
		POST_TYPE,
		array(
			'labels'                => array(
				'name'               => __( 'Lokalizacje', 'pw-theme' ),
				'singular_name'      => __( 'Lokalizacja', 'pw-theme' ),
				'menu_name'          => __( 'Lokalizacje', 'pw-theme' ),
				'all_items'          => __( 'Wszystkie Lokalizacje', 'pw-theme' ),
				'add_new'            => __( 'Dodaj Lokalizację', 'pw-theme' ),
				'add_new_item'       => __( 'Dodaj Lokalizację', 'pw-theme' ),
				'edit_item'          => __( 'Edytuj Lokalizację', 'pw-theme' ),
				'new_item'           => __( 'Nowa Lokalizacja', 'pw-theme' ),
				'search_items'       => __( 'Szukaj Lokalizacji', 'pw-theme' ),
				'not_found'          => __( 'Brak Lokalizacji.', 'pw-theme' ),
				'not_found_in_trash' => __( 'Brak Lokalizacji w koszu.', 'pw-theme' ),
			),
			'public'                => false,
			'publicly_queryable'    => false,
			'exclude_from_search'   => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'show_in_rest'          => true,
			'has_archive'           => false,
			'rewrite'               => false,
			'query_var'             => false,
			'hierarchical'          => false,
			'supports'              => array( 'title', 'page-attributes', 'custom-fields' ), // custom-fields: meta in REST.
			'menu_icon'             => 'dashicons-location',
			'rest_controller_class' => \PW\Data\Private_Posts_Controller::class,
		)
	);
}

/**
 * Meta fields: key => type and sanitizer. The sanitizer also runs on REST writes.
 *
 * @return array<string, array{type: string, sanitize: callable(mixed): (string|int|float)}>
 */
function fields(): array {
	$text = static fn( $value ): string => sanitize_text_field( (string) $value );
	$id   = static fn( $value ): int => max( 0, (int) $value );

	return array(
		'pw_ulica'             => array(
			'type'     => 'string',
			'sanitize' => $text,
		),
		'pw_kod'               => array(
			'type'     => 'string',
			'sanitize' => __NAMESPACE__ . '\sanitize_kod',
		),
		'pw_miasto'            => array(
			'type'     => 'string',
			'sanitize' => $text,
		),
		'pw_kotwica'           => array(
			'type'     => 'string',
			'sanitize' => static fn( $value ): string => sanitize_title( (string) $value ),
		),
		'pw_geo_lat'           => array(
			'type'     => 'number',
			'sanitize' => static fn( $value ) => sanitize_coordinate( $value, 90 ),
		),
		'pw_geo_lng'           => array(
			'type'     => 'number',
			'sanitize' => static fn( $value ) => sanitize_coordinate( $value, 180 ),
		),
		'pw_mapa_url'          => array(
			'type'     => 'string',
			'sanitize' => static fn( $value ): string => esc_url_raw( trim( (string) $value ), array( 'https', 'http' ) ),
		),
		'pw_mapa_embed'        => array(
			'type'     => 'string',
			'sanitize' => static fn( $value ): string => pw_lokalizacja_mapa_embed( (string) $value ),
		),
		'pw_dojazd'            => array(
			'type'     => 'string',
			'sanitize' => static fn( $value ): string => sanitize_textarea_field( (string) $value ),
		),
		'pw_podglad_id'        => array(
			'type'     => 'integer',
			'sanitize' => $id,
		),
		'pw_film_youtube_id'   => array(
			'type'     => 'string',
			'sanitize' => static fn( $value ): string => pw_youtube_id( (string) $value ),
		),
		'pw_film_miniatura_id' => array(
			'type'     => 'integer',
			'sanitize' => $id,
		),
		'pw_strona_oferty'     => array(
			'type'     => 'integer',
			'sanitize' => $id,
		),
	);
}

/**
 * Postcode `41-500`, '' for anything else.
 *
 * @param mixed $value Typed value.
 */
function sanitize_kod( $value ): string {
	$value = sanitize_text_field( (string) $value );
	return 1 === preg_match( '/^\d{2}-\d{3}$/', $value ) ? $value : '';
}

/**
 * Latitude / longitude within ±$max, '' when empty or out of range.
 *
 * @param mixed $value Typed value (a comma as the decimal separator is accepted).
 * @param int   $max   90 for latitude, 180 for longitude.
 * @return float|string
 */
function sanitize_coordinate( $value, int $max ) {
	$value = str_replace( ',', '.', trim( (string) $value ) );
	if ( ! is_numeric( $value ) || abs( (float) $value ) > $max ) {
		return '';
	}
	return (float) $value;
}

/**
 * Registers the meta of the post type (single, in REST, edited by users who can edit the post).
 */
function register_meta(): void {
	foreach ( fields() as $key => $field ) {
		register_post_meta(
			POST_TYPE,
			$key,
			array(
				'type'              => $field['type'],
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 'string' === $field['type'] ? '' : 0,
				'sanitize_callback' => $field['sanitize'],
				'auth_callback'     => '\PW\Data\can_edit_post_meta',
			)
		);
	}
}

/**
 * `added_post_meta` / `updated_post_meta` callback: the anchor of a Lokalizacja stays unique on
 * every write (metabox, REST, code). After a write of `pw_kotwica` or `pw_miasto` the effective
 * anchor (`pw_kotwica`, else the city slug) is compared with the other Lokalizacje; a taken one
 * is stored with a suffix („chorzow-2”). The nested write sees a unique anchor and stops.
 *
 * @param int    $meta_id    Meta row ID (unused).
 * @param int    $post_id    Post the meta belongs to.
 * @param string $meta_key   Meta key.
 */
function keep_unique_anchor( $meta_id, $post_id, $meta_key ): void {
	if ( ! in_array( $meta_key, array( 'pw_kotwica', 'pw_miasto' ), true ) || POST_TYPE !== get_post_type( (int) $post_id ) ) {
		return;
	}
	$post_id = (int) $post_id;
	$kotwica = anchor( $post_id );
	if ( '' === $kotwica ) {
		return;
	}
	$unique = pw_lokalizacja_unikalna_kotwica( $kotwica, other_anchors( $post_id ) );
	if ( $unique !== $kotwica ) {
		update_post_meta( $post_id, 'pw_kotwica', $unique );
	}
}

/**
 * Effective anchor of a Lokalizacja: `pw_kotwica`, else the city slug.
 *
 * @param int $post_id Lokalizacja ID.
 */
function anchor( int $post_id ): string {
	return pw_lokalizacja_kotwica( (string) get_post_meta( $post_id, 'pw_kotwica', true ), (string) get_post_meta( $post_id, 'pw_miasto', true ) );
}

/**
 * Anchors of the other Lokalizacje (any status but trash).
 *
 * @param int $post_id Lokalizacja being saved.
 * @return string[]
 */
function other_anchors( int $post_id ): array {
	$ids = get_posts(
		array(
			'post_type'      => POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'post__not_in'   => array( $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- a handful of Lokalizacje.
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	return array_map( static fn( $id ): string => anchor( (int) $id ), $ids );
}

/**
 * `posts_results` callback: the front main query never shows a Lokalizacja. Without it
 * `/?p=<id>` would render one through the single template (WP_Query does not restrict
 * the post type of a singular query by ID), even though the type is not publicly queryable.
 *
 * @param \WP_Post[] $posts Found posts.
 * @param \WP_Query  $query Query.
 * @return \WP_Post[]
 */
function hide_from_front( array $posts, \WP_Query $query ): array {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_singular() ) {
		return $posts;
	}
	return array_values( array_filter( $posts, static fn( $post ) => POST_TYPE !== $post->post_type ) );
}

/**
 * Published Lokalizacje by `menu_order` (then title), normalised, before the filter.
 *
 * @param int[]|null $ids Only these posts; null = all.
 * @return array<int, array<string, mixed>>
 */
function query( ?array $ids ): array {
	$query = array(
		'post_type'        => POST_TYPE,
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'orderby'          => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'    => true,
		'suppress_filters' => true,
	);
	if ( null !== $ids ) {
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( array() === $ids ) {
			return array();
		}
		$query['post__in'] = $ids;
	}
	return array_map( __NAMESPACE__ . '\normalize', get_posts( $query ) );
}

/**
 * Normalised Lokalizacja of a post (shape: inc/data/lokalizacja-repository.php).
 *
 * @param \WP_Post $post Lokalizacja post.
 * @return array<string, mixed>
 */
function normalize( \WP_Post $post ): array {
	$meta   = static fn( string $key ): string => (string) get_post_meta( $post->ID, $key, true );
	$miasto = $meta( 'pw_miasto' );
	// The registered default (0) would place an unset Lokalizacja in the Gulf of Guinea.
	$lat  = metadata_exists( 'post', $post->ID, 'pw_geo_lat' ) ? $meta( 'pw_geo_lat' ) : '';
	$lng  = metadata_exists( 'post', $post->ID, 'pw_geo_lng' ) ? $meta( 'pw_geo_lng' ) : '';
	$film = pw_youtube_id( $meta( 'pw_film_youtube_id' ) );

	return array(
		'id'              => $post->ID,
		'nazwa'           => $post->post_title,
		'ulica'           => $meta( 'pw_ulica' ),
		'kod'             => $meta( 'pw_kod' ),
		'miasto'          => $miasto,
		'kotwica'         => pw_lokalizacja_kotwica( $meta( 'pw_kotwica' ), $miasto ),
		'geo'             => is_numeric( $lat ) && is_numeric( $lng )
			? array(
				'lat' => (float) $lat,
				'lng' => (float) $lng,
			)
			: null,
		'mapaUrl'         => $meta( 'pw_mapa_url' ),
		'mapaEmbed'       => pw_lokalizacja_mapa_embed( $meta( 'pw_mapa_embed' ) ),
		'dojazd'          => $meta( 'pw_dojazd' ),
		'podgladId'       => absint( $meta( 'pw_podglad_id' ) ),
		'film'            => '' !== $film
			? array(
				'youtubeId'   => $film,
				'miniaturaId' => absint( $meta( 'pw_film_miniatura_id' ) ),
			)
			: null,
		'stronaOfertyUrl' => strona_oferty_url( absint( $meta( 'pw_strona_oferty' ) ) ),
	);
}

/**
 * Permalink of a published page from its ID (a slug change keeps links working), '' otherwise.
 *
 * @param int $page_id Page ID.
 */
function strona_oferty_url( int $page_id ): string {
	if ( 0 === $page_id || 'page' !== get_post_type( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
		return '';
	}
	return (string) get_permalink( $page_id );
}

add_action( 'init', __NAMESPACE__ . '\register_post_type' );
add_action( 'init', __NAMESPACE__ . '\register_meta' );
add_filter( 'posts_results', __NAMESPACE__ . '\hide_from_front', 10, 2 );
add_action( 'added_post_meta', __NAMESPACE__ . '\keep_unique_anchor', 10, 3 );
add_action( 'updated_post_meta', __NAMESPACE__ . '\keep_unique_anchor', 10, 3 );
