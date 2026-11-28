<?php
/**
 * Repository of Lokalizacje (#49, ADR 0007, ADR 0013): the only way blocks, the footer and the
 * schema read them. Result through the filter `pw/data/lokalizacje`.
 *
 * Shape of a Lokalizacja (values raw – escape when rendering):
 * - `id` int, `nazwa` string (post title), `ulica`, `kod`, `miasto`, `kotwica` string
 *   (`kotwica` falls back to the city slug);
 * - `geo` `array{lat: float, lng: float}` or null when either coordinate is missing (0,0 counts as missing);
 * - `mapaUrl`, `mapaEmbed`, `dojazd` string ('' when empty);
 * - `podgladId` int (0 = none);
 * - `film` `array{youtubeId: string, miniaturaId: int}` or null without a YouTube ID;
 * - `stronaOfertyUrl` string: permalink of the published Strona oferty, computed from its ID,
 *   '' when unset or not published.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Published Lokalizacje by `menu_order` (then title).
 *
 * @param array{ids?: int[]} $args `ids` – only these Lokalizacje (still in `menu_order`).
 * @return array<int, array<string, mixed>> Normalised Lokalizacje.
 */
function pw_lokalizacje( array $args = array() ): array {
	$lokalizacje = \PW\Data\Lokalizacja\query( isset( $args['ids'] ) ? (array) $args['ids'] : null );

	/**
	 * Lokalizacje returned by `pw_lokalizacje()` and `pw_lokalizacja()`.
	 *
	 * @param array<int, array<string, mixed>> $lokalizacje Normalised Lokalizacje.
	 * @param array{ids?: int[]}               $args        Arguments of `pw_lokalizacje()`.
	 */
	return (array) apply_filters( 'pw/data/lokalizacje', $lokalizacje, $args );
}

/**
 * One published Lokalizacja, null when the ID is not a published Lokalizacja.
 *
 * @param int $id Lokalizacja ID.
 * @return array<string, mixed>|null Normalised Lokalizacja.
 */
function pw_lokalizacja( int $id ): ?array {
	foreach ( pw_lokalizacje( array( 'ids' => array( $id ) ) ) as $lokalizacja ) {
		if ( (int) ( $lokalizacja['id'] ?? 0 ) === $id ) {
			return $lokalizacja;
		}
	}
	return null;
}
