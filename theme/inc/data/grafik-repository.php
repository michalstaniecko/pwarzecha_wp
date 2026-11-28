<?php
/**
 * Repository of Grupy and Grafik rows (#50, ADR 0007, ADR 0013): the only way blocks and the
 * schema read them.
 *
 * Grupa: `id` int (term ID), `nazwa` string, `opis` string (age range, '' when empty),
 * `kolejnosc` int (term meta `pw_kolejnosc`, 0 when unset).
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every Grupa by `pw_kolejnosc`, then by name. Result through the filter `pw/data/grupy`.
 *
 * @return list<array{id: int, nazwa: string, opis: string, kolejnosc: int}>
 */
function pw_grupy(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => \PW\Data\Grafik\TAXONOMY,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	$grupy = array();
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		$grupy[] = array(
			'id'        => (int) $term->term_id,
			'nazwa'     => $term->name,
			'opis'      => $term->description,
			'kolejnosc' => (int) get_term_meta( $term->term_id, \PW\Data\Grafik\ORDER, true ),
		);
	}
	// Stable: Grupy with the same order stay sorted by name.
	usort( $grupy, static fn( array $a, array $b ): int => $a['kolejnosc'] <=> $b['kolejnosc'] );

	/**
	 * Grupy returned by `pw_grupy()`.
	 *
	 * @param list<array{id: int, nazwa: string, opis: string, kolejnosc: int}> $grupy Grupy.
	 */
	return array_values( (array) apply_filters( 'pw/data/grupy', $grupy ) );
}
