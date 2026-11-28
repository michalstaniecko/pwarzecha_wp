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
	'telefon'                => '',
	'email'                  => '',
	'messenger'              => '',
	'godzinyTelefonu'        => '',
	'facebook'               => '',
	'instagram'              => '',
	'youtube'                => '',
	'tiktok'                 => '',
	'nazwaFirmy'             => '',
	'nip'                    => '',
	'adresRejestrowy'        => '',
	'topbarTekst'            => 'Pierwszy trening bezpłatny',
	'trenerImie'             => '',
	'trenerNazwisko'         => '',
	'trenerNazwiskoWSchemie' => '',
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

/**
 * Grants PW_KONTAKT_CAP to everyone who can `manage_options` (the `editor` role gets it from
 * migration 0001).
 *
 * @param array<string, bool> $allcaps Capabilities of the user.
 * @return array<string, bool>
 */
function pw_kontakt_grant_cap( array $allcaps ): array {
	if ( ! empty( $allcaps['manage_options'] ) ) {
		$allcaps[ PW_KONTAKT_CAP ] = true;
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'pw_kontakt_grant_cap' );

// options.php checks `manage_options` unless the option page says otherwise.
add_filter( 'option_page_capability_' . PW_KONTAKT_OPTION, static fn() => PW_KONTAKT_CAP );

/**
 * Registers the option with its sanitize callback on every request (Settings API form, REST-less
 * update_option() in WP-CLI and tests alike).
 */
function pw_kontakt_register_setting(): void {
	register_setting(
		PW_KONTAKT_OPTION,
		PW_KONTAKT_OPTION,
		array(
			'type'              => 'array',
			'label'             => __( 'Dane strony', 'pw-theme' ),
			'sanitize_callback' => 'pw_kontakt_sanitize',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'pw_kontakt_register_setting' );

/**
 * Sanitizes the submitted option. Unknown keys are dropped; an invalid value keeps the stored
 * one and adds a settings error `pw_kontakt_<key>`; '' is always allowed (= hidden).
 *
 * @param mixed $input Submitted value.
 * @return array<string, string>
 */
function pw_kontakt_sanitize( $input ): array {
	$input  = is_array( $input ) ? $input : array();
	$stored = get_option( PW_KONTAKT_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();
	$clean  = array();

	foreach ( array_intersect_key( $input, PW_KONTAKT_DEFAULTS ) as $key => $value ) {
		$value         = is_scalar( $value ) ? trim( (string) $value ) : '';
		$error         = '';
		$clean[ $key ] = '' === $value ? '' : pw_kontakt_sanitize_field( $key, $value, $error );
		if ( '' !== $error ) {
			$clean[ $key ] = (string) ( $stored[ $key ] ?? '' );
			pw_kontakt_settings_error( $key, $error );
		}
	}
	return $clean;
}

/**
 * Sanitizes one non-empty field.
 *
 * @param string $key   Option key.
 * @param string $value Trimmed, non-empty value.
 * @param string $error Set to the error message when the value is invalid.
 */
function pw_kontakt_sanitize_field( string $key, string $value, string &$error ): string {
	switch ( $key ) {
		case 'telefon':
			$clean = pw_phone_format( $value );
			$error = '' === $clean ? __( 'Telefon: dozwolone są tylko cyfry, spacje i „+” (co najmniej 9 cyfr).', 'pw-theme' ) : '';
			return $clean;
		case 'email':
			$clean = sanitize_email( $value );
			$error = is_email( $clean ) ? '' : __( 'E-mail: niepoprawny adres.', 'pw-theme' );
			return $clean;
		case 'messenger':
			$clean = esc_url_raw( $value, array( 'https' ) );
			$error = preg_match( '#^https://(m\.me|www\.messenger\.com)/[^/]#', $clean ) ? '' : __( 'Messenger: adres musi zaczynać się od https://m.me/ lub https://www.messenger.com/.', 'pw-theme' );
			return $clean;
		case 'facebook':
		case 'instagram':
		case 'youtube':
		case 'tiktok':
			$clean = esc_url_raw( $value, array( 'https' ) );
			/* translators: %s: social network key, e.g. facebook. */
			$error = '' === $clean ? sprintf( __( '%s: adres musi zaczynać się od https://.', 'pw-theme' ), ucfirst( $key ) ) : '';
			return $clean;
		case 'nip':
			$clean = pw_nip_normalize( $value );
			$error = '' === $clean ? __( 'NIP: wymagane 10 cyfr z poprawną sumą kontrolną.', 'pw-theme' ) : '';
			return $clean;
		case 'adresRejestrowy':
			return sanitize_textarea_field( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Adds a settings error once per request (the option is sanitized twice when it is first added).
 *
 * @param string $key     Option key.
 * @param string $message Message.
 */
function pw_kontakt_settings_error( string $key, string $message ): void {
	$code = PW_KONTAKT_OPTION . '_' . $key;
	if ( ! in_array( $code, array_column( get_settings_errors( PW_KONTAKT_OPTION ), 'code' ), true ) ) {
		add_settings_error( PW_KONTAKT_OPTION, $code, $message );
	}
}

/**
 * The option is always autoloaded: topbar and footer read it on every page.
 *
 * @param bool|null $autoload Default autoload value (null = decided by size).
 * @param string    $option   Option name.
 */
function pw_kontakt_autoload( ?bool $autoload, string $option ): ?bool {
	return PW_KONTAKT_OPTION === $option ? true : $autoload;
}
add_filter( 'wp_default_autoload_value', 'pw_kontakt_autoload', 10, 2 );

/**
 * Fires `pw/data/kontakt/updated` ( $new, $old ) after the option is added or changed – for future
 * integrations; pages render dynamically, so no cache needs purging.
 *
 * @param mixed $old_value Previous value.
 * @param mixed $value     New value.
 */
function pw_kontakt_updated( $old_value, $value ): void {
	$full = static fn( $data ) => array_merge( PW_KONTAKT_DEFAULTS, is_array( $data ) ? $data : array() );
	do_action( 'pw/data/kontakt/updated', $full( $value ), $full( $old_value ) );
}
add_action( 'update_option_' . PW_KONTAKT_OPTION, 'pw_kontakt_updated', 10, 2 );
add_action(
	'add_option_' . PW_KONTAKT_OPTION,
	static fn( $option, $value ) => pw_kontakt_updated( array(), $value ),
	10,
	2
);

/**
 * Keys of the `pw/kontakt` Block Bindings source (`args.key`).
 */
const PW_KONTAKT_BINDING_KEYS = array(
	'telefon',
	'telefonHref',
	'email',
	'emailHref',
	'messenger',
	'godzinyTelefonu',
	'nazwaFirmy',
	'nip',
	'adresRejestrowy',
	'topbarTekst',
	'facebook',
	'instagram',
	'youtube',
	'tiktok',
	'copyright',
);

/**
 * Registers the `pw/kontakt` Block Bindings source (core/paragraph, core/heading, core/button text and URL).
 */
function pw_kontakt_register_bindings(): void {
	register_block_bindings_source(
		'pw/kontakt',
		array(
			'label'              => __( 'Dane strony', 'pw-theme' ),
			'get_value_callback' => 'pw_kontakt_binding_value',
			'uses_context'       => array(),
		)
	);
}
add_action( 'init', 'pw_kontakt_register_bindings' );

/**
 * Value of a `pw/kontakt` binding. Core escapes it when it replaces the attribute.
 *
 * @param array<string, mixed> $source_args Binding args: `key` (PW_KONTAKT_BINDING_KEYS).
 * @return string|null Value; null for an unknown key (the block keeps its own content).
 */
function pw_kontakt_binding_value( array $source_args ): ?string {
	$key = (string) ( $source_args['key'] ?? '' );
	if ( ! in_array( $key, PW_KONTAKT_BINDING_KEYS, true ) ) {
		return null;
	}

	$kontakt = pw_kontakt();
	switch ( $key ) {
		case 'telefonHref':
			return pw_tel_href( $kontakt['telefon'] );
		case 'emailHref':
			return pw_mailto_href( $kontakt['email'] );
		case 'nip':
			return pw_nip_format( $kontakt['nip'] );
		case 'copyright':
			$owner = '' !== $kontakt['nazwaFirmy'] ? $kontakt['nazwaFirmy'] : (string) get_bloginfo( 'name' );
			return '© ' . wp_date( 'Y' ) . ' ' . $owner;
		default:
			return $kontakt[ $key ];
	}
}
