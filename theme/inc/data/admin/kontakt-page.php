<?php
/**
 * „Dane strony” screen (#48): top-level menu page, Settings API form of the `pw_kontakt` option.
 *
 * Saving goes through options.php and pw_kontakt_sanitize() (inc/data/kontakt.php).
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu slug of the screen.
 */
const PW_KONTAKT_PAGE = 'pw-dane-strony';

/**
 * Form sections: id => title ('' = none), in display order.
 */
const PW_KONTAKT_SECTIONS = array(
	'pw_kontakt_main'   => '',
	'pw_kontakt_trener' => 'Trener',
);

/**
 * Form fields: key => [ label, input type (`textarea` = multi-line, `checkbox` = value '1'),
 * description, section (PW_KONTAKT_SECTIONS, default `pw_kontakt_main`) ].
 */
const PW_KONTAKT_FIELDS = array(
	'telefon'                => array( 'Telefon', 'tel', 'Np. +48 600 000 000. Cyfry, spacje i „+”.' ),
	'godzinyTelefonu'        => array( 'Godziny odbierania telefonu', 'text', 'Puste = nie pokazujemy.' ),
	'email'                  => array( 'E-mail', 'email', '' ),
	'messenger'              => array( 'Messenger', 'url', 'https://m.me/… lub https://www.messenger.com/…' ),
	'facebook'               => array( 'Facebook', 'url', 'Tylko https://.' ),
	'instagram'              => array( 'Instagram', 'url', 'Tylko https://.' ),
	'youtube'                => array( 'YouTube', 'url', 'Tylko https://.' ),
	'tiktok'                 => array( 'TikTok', 'url', 'Tylko https://.' ),
	'nazwaFirmy'             => array( 'Nazwa firmy', 'text', 'Puste = nazwa witryny w stopce.' ),
	'nip'                    => array( 'NIP', 'text', '10 cyfr, z myślnikami lub bez.' ),
	'adresRejestrowy'        => array( 'Adres rejestrowy', 'textarea', 'Puste = nie pokazujemy.' ),
	'topbarTekst'            => array( 'Tekst w górnym pasku', 'text', 'Puste = ukryty.' ),
	'trenerImie'             => array( 'Imię', 'text', 'W schemie strony Trener. Puste = bez węzła Person.', 'pw_kontakt_trener' ),
	'trenerNazwisko'         => array( 'Nazwisko', 'text', 'Trafia do schemy tylko z włączonym przełącznikiem poniżej.', 'pw_kontakt_trener' ),
	'trenerNazwiskoWSchemie' => array( 'Pokaż nazwisko Trenera w schemie', 'checkbox', 'Włącz tylko za zgodą Trenera [ZL].', 'pw_kontakt_trener' ),
);

/**
 * Adds the top-level „Dane strony” page.
 */
function pw_kontakt_page_menu(): void {
	add_menu_page(
		__( 'Dane strony', 'pw-theme' ),
		__( 'Dane strony', 'pw-theme' ),
		PW_KONTAKT_CAP,
		PW_KONTAKT_PAGE,
		'pw_kontakt_page_render',
		'dashicons-phone'
	);
}
add_action( 'admin_menu', 'pw_kontakt_page_menu' );

/**
 * Registers the sections and one field per key of PW_KONTAKT_FIELDS.
 */
function pw_kontakt_page_fields(): void {
	foreach ( PW_KONTAKT_SECTIONS as $section => $title ) {
		add_settings_section( $section, esc_html( $title ), '__return_false', PW_KONTAKT_PAGE );
	}
	foreach ( PW_KONTAKT_FIELDS as $key => $field ) {
		add_settings_field(
			'pw_kontakt_' . $key,
			esc_html( $field[0] ),
			'pw_kontakt_page_field',
			PW_KONTAKT_PAGE,
			$field[3] ?? 'pw_kontakt_main',
			array(
				'key'       => $key,
				'label_for' => 'pw_kontakt_' . $key,
			)
		);
	}
}
add_action( 'admin_init', 'pw_kontakt_page_fields' );

/**
 * Prints one field with its value from pw_kontakt().
 *
 * @param array{key: string} $args Field args.
 */
function pw_kontakt_page_field( array $args ): void {
	$key   = $args['key'];
	$field = PW_KONTAKT_FIELDS[ $key ];
	$id    = 'pw_kontakt_' . $key;
	$name  = PW_KONTAKT_OPTION . '[' . $key . ']';
	$value = pw_kontakt_pole( $key );

	if ( 'checkbox' === $field[1] ) {
		printf( '<input id="%s" name="%s" type="checkbox" value="1"%s>', esc_attr( $id ), esc_attr( $name ), checked( '1', $value, false ) );
	} elseif ( 'textarea' === $field[1] ) {
		printf( '<textarea id="%s" name="%s" rows="3" class="large-text">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
	} else {
		printf( '<input id="%s" name="%s" type="%s" value="%s" class="regular-text">', esc_attr( $id ), esc_attr( $name ), esc_attr( $field[1] ), esc_attr( $value ) );
	}
	if ( '' !== $field[2] ) {
		printf( '<p class="description">%s</p>', esc_html( $field[2] ) );
	}
}

/**
 * Prints the screen: errors from pw_kontakt_sanitize() and the options.php form.
 */
function pw_kontakt_page_render(): void {
	echo '<div class="wrap"><h1>' . esc_html__( 'Dane strony', 'pw-theme' ) . '</h1>';
	settings_errors( PW_KONTAKT_OPTION );
	echo '<form action="options.php" method="post">';
	settings_fields( PW_KONTAKT_OPTION );
	do_settings_sections( PW_KONTAKT_PAGE );
	submit_button();
	echo '</form></div>';
}
