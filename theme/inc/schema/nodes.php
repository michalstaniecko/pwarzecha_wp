<?php
/**
 * Pure builders of the theme's schema nodes (#53): arrays only, no Yoast, no queries.
 *
 * No side effects at load time, so unit tests load this file directly. The Yoast pieces
 * (inc/schema/pieces/) gather the data and call these.
 *
 * @package PW
 */

namespace PW\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * `@id` of a Lokalizacja node: `https://…/#/schema/lokalizacja/chorzow`.
 *
 * @param array<string, mixed> $l Normalised Lokalizacja (`pw_lokalizacja()`).
 */
function location_id( array $l ): string {
	return home_url( '/#/schema/lokalizacja/' . (string) ( $l['kotwica'] ?? '' ) );
}

/**
 * `PostalAddress` of a Lokalizacja; empty fields are skipped.
 *
 * @param array<string, mixed> $l Normalised Lokalizacja (`pw_lokalizacja()`).
 * @return array<string, string>
 */
function postal_address( array $l ): array {
	$fields = array(
		'streetAddress'   => 'ulica',
		'postalCode'      => 'kod',
		'addressLocality' => 'miasto',
	);

	$address = array( '@type' => 'PostalAddress' );
	foreach ( $fields as $property => $key ) {
		$value = trim( (string) ( $l[ $key ] ?? '' ) );
		if ( '' !== $value ) {
			$address[ $property ] = $value;
		}
	}
	$address['addressCountry'] = 'PL';
	return $address;
}
