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

/**
 * `SportsActivityLocation` of a Lokalizacja. Phone and e-mail are the site's (`pw_kontakt()`);
 * empty values, a missing geo point and a missing Organization are skipped.
 *
 * @param array<string, mixed>      $l            Normalised Lokalizacja (`pw_lokalizacja()`).
 * @param array<string, string>     $kontakt      Site data (`pw_kontakt()`).
 * @param array{"@id": string}|null $organization Reference of the Organization node.
 * @return array<string, mixed>
 */
function location_node( array $l, array $kontakt, ?array $organization ): array {
	$node = array(
		'@type'   => 'SportsActivityLocation',
		'@id'     => location_id( $l ),
		'name'    => (string) ( $l['nazwa'] ?? '' ),
		'address' => postal_address( $l ),
	);

	$geo = $l['geo'] ?? null;
	if ( is_array( $geo ) && isset( $geo['lat'], $geo['lng'] ) ) {
		$node['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $geo['lat'],
			'longitude' => (float) $geo['lng'],
		);
	}

	$optional = array(
		'telephone' => $kontakt['telefon'] ?? '',
		'email'     => $kontakt['email'] ?? '',
		'url'       => $l['stronaOfertyUrl'] ?? '',
	);
	foreach ( $optional as $property => $value ) {
		if ( '' !== (string) $value ) {
			$node[ $property ] = (string) $value;
		}
	}

	if ( null !== $organization ) {
		$node['parentOrganization'] = $organization;
	}
	return $node;
}

/**
 * First name of the Trener (GLOSSARY.md).
 */
const TRENER_IMIE = 'Przemek';

/**
 * `@id` of the Trener: `https://…/#/schema/trener`.
 */
function trener_id(): string {
	return home_url( '/#/schema/trener' );
}

/**
 * `Person` of the Trener; the surname is appended only when given.
 *
 * @param string $imie     First name.
 * @param string $nazwisko Surname, '' = none.
 * @param string $url      URL of the Trener page, '' = none.
 * @return array<string, string>
 */
function person_node( string $imie, string $nazwisko, string $url ): array {
	$node = array(
		'@type' => 'Person',
		'@id'   => trener_id(),
		'name'  => trim( $imie . ' ' . trim( $nazwisko ) ),
	);
	if ( '' !== $url ) {
		$node['url'] = $url;
	}
	return $node;
}
