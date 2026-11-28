<?php
/**
 * Pure Lokalizacja helpers (#49): one-line address, Grafik link, anchor and field validation.
 *
 * No side effects at load time, so unit tests load this file directly. Repository and CPT:
 * inc/data/lokalizacja.php.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Address in one line: „ul. Wolności 1, 41-500 Chorzów”. `ulica` is stored with its prefix
 * (ul., al., pl.); empty parts are skipped.
 *
 * @param array<string, mixed> $l Normalised Lokalizacja (`pw_lokalizacja()`).
 */
function pw_lokalizacja_adres( array $l ): string {
	$city  = trim( (string) ( $l['kod'] ?? '' ) . ' ' . (string) ( $l['miasto'] ?? '' ) );
	$ulica = trim( (string) ( $l['ulica'] ?? '' ) );
	return implode( ', ', array_filter( array( $ulica, $city ), static fn( string $part ): bool => '' !== $part ) );
}

/**
 * Link to the Lokalizacja on the Grafik page: `/grafik/#chorzow` (absolute URL).
 *
 * @param array<string, mixed> $l Normalised Lokalizacja (`pw_lokalizacja()`).
 */
function pw_lokalizacja_grafik_url( array $l ): string {
	$kotwica = (string) ( $l['kotwica'] ?? '' );
	return home_url( '/grafik/' ) . ( '' !== $kotwica ? '#' . $kotwica : '' );
}

/**
 * Anchor slug (`chorzow`): the one typed by the editor, otherwise the city.
 *
 * @param string $kotwica Typed anchor, may be empty.
 * @param string $miasto  City.
 */
function pw_lokalizacja_kotwica( string $kotwica, string $miasto ): string {
	$kotwica = trim( $kotwica );
	return sanitize_title( '' !== $kotwica ? $kotwica : $miasto );
}

/**
 * Anchor not used by any other Lokalizacja: `chorzow`, then `chorzow-2`, `chorzow-3`…
 *
 * @param string   $kotwica Anchor slug.
 * @param string[] $zajete  Anchors of the other Lokalizacje.
 */
function pw_lokalizacja_unikalna_kotwica( string $kotwica, array $zajete ): string {
	if ( '' === $kotwica ) {
		return '';
	}
	$kandydat = $kotwica;
	$n        = 1;
	while ( in_array( $kandydat, $zajete, true ) ) {
		$kandydat = $kotwica . '-' . ( ++$n );
	}
	return $kandydat;
}

/**
 * YouTube film ID (11 characters `[A-Za-z0-9_-]`), '' when the value is anything else
 * (also a full URL – the field takes the ID only).
 *
 * @param string $value Typed value.
 */
function pw_youtube_id( string $value ): string {
	$value = trim( $value );
	return 1 === preg_match( '/^[A-Za-z0-9_-]{11}$/', $value ) ? $value : '';
}

/**
 * Address of the „Pokaż mapę” iframe: only `https://www.google.com/maps/embed?…`, otherwise ''
 * (ADR 0008 – the facade loads nothing else).
 *
 * @param string $value Typed value.
 */
function pw_lokalizacja_mapa_embed( string $value ): string {
	$value = trim( $value );
	return 1 === preg_match( '#^https://www\.google\.com/maps/embed\?[^\s"\'<>]+$#', $value ) ? $value : '';
}
