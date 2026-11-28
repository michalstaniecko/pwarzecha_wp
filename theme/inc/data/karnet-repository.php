<?php
/**
 * Repository of Karnety (#51, ADR 0007, ADR 0013): the only way the Cennik and the Teaser ceny
 * read them. Result through the filter `pw/data/karnety`.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

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
