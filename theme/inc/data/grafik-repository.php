<?php
/**
 * Repository of Grupy and Grafik rows (#50, ADR 0007, ADR 0013): the only way blocks and the
 * schema read them.
 *
 * Grupa: `id` int (term ID), `nazwa` string, `opis` string (age range, '' when empty),
 * `kolejnosc` int (term meta `pw_kolejnosc`, 0 when unset).
 *
 * Grafik row: `dzien` int 1–7 (1 = Monday), `od` / `do` "HH:MM", `grupa` int (term ID),
 * `dzienNazwa` string (Polish day name), `grupaNazwa` string. Values raw – escape when rendering.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Grafik rows of a Lokalizacja by day, then by start time (any post status – the caller decides
 * whether the Lokalizacja is shown). Rows whose Grupa no longer exists are skipped. Result
 * through the filter `pw/data/grafik`.
 *
 * @param int $lokalizacja_id Lokalizacja ID.
 * @return list<array{dzien: int, od: string, do: string, grupa: int, dzienNazwa: string, grupaNazwa: string}>
 */
function pw_grafik( int $lokalizacja_id ): array {
	$wiersze = array();
	if ( $lokalizacja_id > 0 && \PW\Data\Lokalizacja\POST_TYPE === get_post_type( $lokalizacja_id ) ) {
		$stored = get_post_meta( $lokalizacja_id, \PW\Data\Grafik\META, true );
		// Sanitised again on read: drops rows of a Grupa deleted outside the panel.
		foreach ( pw_grafik_sort( pw_grafik_sanitize( is_array( $stored ) ? $stored : array() ) ) as $wiersz ) {
			$grupa     = get_term( $wiersz['grupa'], \PW\Data\Grafik\TAXONOMY );
			$wiersze[] = $wiersz + array(
				'dzienNazwa' => pw_grafik_dzien_nazwa( $wiersz['dzien'] ),
				'grupaNazwa' => $grupa instanceof WP_Term ? $grupa->name : '',
			);
		}
	}

	/**
	 * Grafik rows returned by `pw_grafik()`.
	 *
	 * @param list<array<string, mixed>> $wiersze        Sorted rows with day and Grupa names.
	 * @param int                        $lokalizacja_id Lokalizacja ID.
	 */
	return array_values( (array) apply_filters( 'pw/data/grafik', $wiersze, $lokalizacja_id ) );
}

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
