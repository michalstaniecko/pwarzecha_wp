<?php
/**
 * „Dane strony” (#48, ADR 0007): option `pw_kontakt`, repository and Block Bindings source `pw/kontakt`.
 *
 * Blocks read the data only through pw_kontakt() / pw_kontakt_pole() or the `pw/kontakt` source,
 * never get_option( 'pw_kontakt' ). Self-contained apart from inc/helpers (pw_tel_href(),
 * pw_mailto_href()) and kontakt-format.php, so it can move to a plugin without renaming anything.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option name (one array, autoloaded).
 */
const PW_KONTAKT_OPTION = 'pw_kontakt';

/**
 * Capability of the „Dane strony” screen (administrators via `user_has_cap`, `editor` via migration 0001).
 */
const PW_KONTAKT_CAP = 'pw_manage_site_data';

/**
 * Keys of the option => default value.
 */
const PW_KONTAKT_DEFAULTS = array(
	'telefon'         => '',
	'email'           => '',
	'messenger'       => '',
	'godzinyTelefonu' => '',
	'facebook'        => '',
	'instagram'       => '',
	'youtube'         => '',
	'tiktok'          => '',
	'nazwaFirmy'      => '',
	'nip'             => '',
	'adresRejestrowy' => '',
	'topbarTekst'     => 'Pierwszy trening bezpłatny',
);

/**
 * Site data: every key of PW_KONTAKT_DEFAULTS, filtered by `pw/data/kontakt`.
 *
 * Defaults fill keys never stored; a stored '' stays '' (empty = hidden). After the filter,
 * keys it dropped are '' and values are strings.
 *
 * @return array<string, string>
 */
function pw_kontakt(): array {
	$stored = get_option( PW_KONTAKT_OPTION, array() );
	$data   = array_merge( PW_KONTAKT_DEFAULTS, array_intersect_key( is_array( $stored ) ? $stored : array(), PW_KONTAKT_DEFAULTS ) );

	$filtered = apply_filters( 'pw/data/kontakt', $data );
	$filtered = is_array( $filtered ) ? $filtered : array();

	$result = array();
	foreach ( array_keys( PW_KONTAKT_DEFAULTS ) as $key ) {
		$result[ $key ] = is_scalar( $filtered[ $key ] ?? null ) ? (string) $filtered[ $key ] : '';
	}
	return $result;
}

/**
 * One field of pw_kontakt().
 *
 * @param string $klucz Key, e.g. `telefon`.
 * @return string Value, '' for an unknown key.
 */
function pw_kontakt_pole( string $klucz ): string {
	return pw_kontakt()[ $klucz ] ?? '';
}
