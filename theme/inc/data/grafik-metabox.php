<?php
/**
 * „Grafik” metabox of the Lokalizacja edit screen (#50): a table of repeatable rows.
 *
 * Each row: day select, two `time` inputs, Grupa select (from `pw_grupy()`), „Usuń”. The admin
 * script `src/admin/grafik-rows.js` adds rows from the `<template>` and renumbers the names
 * `pw_grafik[<index>][<field>]`. Saving goes through the meta sanitizer (`pw_grafik_sanitize()`),
 * so invalid rows are dropped and the rest are stored (and shown again) by day, then start time;
 * no valid row = meta deleted.
 *
 * @package PW
 */

namespace PW\Data\Grafik;

use WP_Post;

use function PW\Data\can_save_metabox;

defined( 'ABSPATH' ) || exit;

const NONCE        = 'pw_grafik_nonce';
const NONCE_ACTION = 'pw_grafik_save';

/**
 * Adds the metabox under „Dane Lokalizacji”.
 */
function add_metabox(): void {
	add_meta_box( 'pw_grafik', __( 'Grafik', 'pw-theme' ), __NAMESPACE__ . '\render_metabox', \PW\Data\Lokalizacja\POST_TYPE, 'normal', 'default' );
}

/**
 * Renders the metabox. Without Grupy it only links to their screen (and posts no nonce, so
 * saving the Lokalizacja leaves the Grafik as it is).
 *
 * @param WP_Post $post Edited Lokalizacja.
 */
function render_metabox( WP_Post $post ): void {
	$grupy = pw_grupy();
	if ( array() === $grupy ) {
		printf(
			'<p>%1$s <a href="%2$s">%3$s</a></p>',
			esc_html__( 'Grafik wybiera Grupy ze stałej listy, która jest jeszcze pusta.', 'pw-theme' ),
			esc_url( admin_url( 'edit-tags.php?taxonomy=' . TAXONOMY . '&post_type=' . \PW\Data\Lokalizacja\POST_TYPE ) ),
			esc_html__( 'Dodaj Grupy w „Lokalizacje → Grupy”.', 'pw-theme' )
		);
		return;
	}

	$stored = get_post_meta( $post->ID, META, true );
	$rows   = is_array( $stored ) ? array_values( $stored ) : array();

	wp_nonce_field( NONCE_ACTION, NONCE );
	echo '<div data-pw-grafik>';
	echo '<p class="description">' . esc_html__( 'Wiersz bez Grupy albo z godziną „do” nie późniejszą niż „od” nie zostanie zapisany.', 'pw-theme' ) . '</p>';
	printf(
		'<table class="widefat striped"><thead><tr><th scope="col">%1$s</th><th scope="col">%2$s</th><th scope="col">%3$s</th><th scope="col">%4$s</th><td></td></tr></thead><tbody data-pw-grafik-rows>',
		esc_html__( 'Dzień', 'pw-theme' ),
		esc_html__( 'Od', 'pw-theme' ),
		esc_html__( 'Do', 'pw-theme' ),
		esc_html__( 'Grupa', 'pw-theme' )
	);
	foreach ( $rows as $index => $row ) {
		echo row_html( (string) $index, is_array( $row ) ? $row : array(), $grupy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in row_html().
	}
	echo '</tbody></table>';
	echo '<template data-pw-grafik-template>' . row_html( '__i__', array(), $grupy ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in row_html().
	echo '<p><button type="button" class="button" data-pw-grafik-add>' . esc_html__( 'Dodaj wiersz', 'pw-theme' ) . '</button></p>';
	echo '</div>';
}

/**
 * One table row of the form.
 *
 * @param string                              $index Row index in the field names (`__i__` in the template).
 * @param array<string, mixed>                $row   Stored row, empty for a new one.
 * @param list<array{id: int, nazwa: string}> $grupy Grupy to choose from.
 */
function row_html( string $index, array $row, array $grupy ): string {
	$name  = static fn( string $field ): string => esc_attr( META . '[' . $index . '][' . $field . ']' );
	$dzien = (int) ( $row['dzien'] ?? 1 );
	$grupa = (int) ( $row['grupa'] ?? 0 );

	$dni = '';
	foreach ( pw_grafik_dni() as $numer => $nazwa ) {
		$dni .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', $numer, selected( $dzien, $numer, false ), esc_html( $nazwa ) );
	}
	$opcje = '<option value="">' . esc_html__( '— wybierz —', 'pw-theme' ) . '</option>';
	foreach ( $grupy as $item ) {
		$opcje .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', $item['id'], selected( $grupa, $item['id'], false ), esc_html( $item['nazwa'] ) );
	}
	$time = static fn( string $field, string $label ): string => sprintf(
		'<input type="time" name="%1$s" value="%2$s" aria-label="%3$s">',
		$name( $field ),
		esc_attr( pw_grafik_godzina( $row[ $field ] ?? '' ) ),
		esc_attr( $label )
	);

	return '<tr data-pw-grafik-row>'
		. sprintf( '<td><select name="%1$s" aria-label="%2$s">%3$s</select></td>', $name( 'dzien' ), esc_attr__( 'Dzień', 'pw-theme' ), $dni )
		. '<td>' . $time( 'od', __( 'Od', 'pw-theme' ) ) . '</td>'
		. '<td>' . $time( 'do', __( 'Do', 'pw-theme' ) ) . '</td>'
		. sprintf( '<td><select name="%1$s" aria-label="%2$s">%3$s</select></td>', $name( 'grupa' ), esc_attr__( 'Grupa', 'pw-theme' ), $opcje )
		. sprintf( '<td><button type="button" class="button-link button-link-delete" data-pw-grafik-remove>%s</button></td>', esc_html__( 'Usuń wiersz', 'pw-theme' ) )
		. '</tr>';
}

/**
 * `save_post_lokalizacja` callback: saves the rows after nonce and capability checks.
 *
 * @param int $post_id Lokalizacja ID.
 */
function save_metabox( int $post_id ): void {
	if ( ! can_save_metabox( $post_id, NONCE, NONCE_ACTION ) ) {
		return;
	}
	// Sanitised by pw_grafik_sanitize() below (and again by the meta sanitizer).
	$form = isset( $_POST[ META ] ) && is_array( $_POST[ META ] ) ? wp_unslash( $_POST[ META ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$rows = pw_grafik_sanitize( $form );

	if ( array() === $rows ) {
		delete_post_meta( $post_id, META );
	} else {
		update_post_meta( $post_id, META, $rows );
	}
}

/**
 * „Kolejność” field of the „Dodaj Grupę” form (`grupa_add_form_fields`).
 */
function render_order_add_field(): void {
	printf(
		'<div class="form-field"><label for="%1$s">%2$s</label><input type="number" name="%1$s" id="%1$s" value="0" step="1" class="small-text" aria-describedby="%1$s-description"><p id="%1$s-description">%3$s</p></div>',
		esc_attr( ORDER ),
		esc_html__( 'Kolejność', 'pw-theme' ),
		esc_html__( 'Mniejsza liczba = wyżej na listach wyboru i w Grafiku.', 'pw-theme' )
	);
}

/**
 * „Kolejność” field of the „Edytuj Grupę” form (`grupa_edit_form_fields`).
 *
 * @param \WP_Term $term Edited Grupa.
 */
function render_order_edit_field( \WP_Term $term ): void {
	printf(
		'<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="number" name="%1$s" id="%1$s" value="%3$d" step="1" class="small-text" aria-describedby="%1$s-description"><p class="description" id="%1$s-description">%4$s</p></td></tr>',
		esc_attr( ORDER ),
		esc_html__( 'Kolejność', 'pw-theme' ),
		(int) get_term_meta( $term->term_id, ORDER, true ),
		esc_html__( 'Mniejsza liczba = wyżej na listach wyboru i w Grafiku.', 'pw-theme' )
	);
}

/**
 * `created_grupa` / `edited_grupa` callback: stores „Kolejność” from the term form. The core
 * term screens verify their nonce before saving; terms created in code (seed) set the meta
 * themselves.
 *
 * @param int $term_id Grupa term ID.
 */
function save_order( int $term_id ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by edit-tags.php / term.php (add-tag, update-tag_<ID>).
	if ( ! isset( $_POST[ ORDER ] ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
	update_term_meta( $term_id, ORDER, (int) sanitize_text_field( wp_unslash( $_POST[ ORDER ] ) ) );
}

add_action( TAXONOMY . '_add_form_fields', __NAMESPACE__ . '\render_order_add_field' );
add_action( TAXONOMY . '_edit_form_fields', __NAMESPACE__ . '\render_order_edit_field' );
add_action( 'created_' . TAXONOMY, __NAMESPACE__ . '\save_order' );
add_action( 'edited_' . TAXONOMY, __NAMESPACE__ . '\save_order' );
add_action( 'add_meta_boxes_' . \PW\Data\Lokalizacja\POST_TYPE, __NAMESPACE__ . '\add_metabox' );
add_action( 'save_post_' . \PW\Data\Lokalizacja\POST_TYPE, __NAMESPACE__ . '\save_metabox' );
