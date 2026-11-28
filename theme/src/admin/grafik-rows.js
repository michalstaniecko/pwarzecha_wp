/**
 * Repeatable rows of the „Grafik” metabox (`PW\Data\Grafik\render_metabox()`, #50).
 *
 * „Dodaj wiersz” clones the row from `<template data-pw-grafik-template>`, „Usuń” removes its
 * row; after both the field names are renumbered (`pw_grafik[<index>][<field>]`), so the form
 * posts a list without gaps. Markup hooks: `data-pw-grafik`, `-rows`, `-row`, `-template`,
 * `-add`, `-remove`.
 */

const ROW_INDEX = /^([^[\]]+)\[(?:\d+|__i__)\]/;

/**
 * Field name with the row index in its first bracket (`pw_grafik[3][od]` → `pw_grafik[0][od]`,
 * template placeholder `__i__` included). Names without a row index stay as they are.
 *
 * @param {string} name  Field name.
 * @param {number} index Row index.
 * @return {string} Renumbered name.
 */
export function renumberName( name, index ) {
	return name.replace( ROW_INDEX, `$1[${ index }]` );
}

/**
 * Renumbers the fields of every row of the table body.
 *
 * @param {HTMLElement} body `[data-pw-grafik-rows]` element.
 */
function renumberRows( body ) {
	body.querySelectorAll( '[data-pw-grafik-row]' ).forEach( ( row, index ) => {
		row.querySelectorAll( '[name]' ).forEach( ( field ) => {
			field.name = renumberName( field.name, index );
		} );
	} );
}

/**
 * Wires one Grafik table.
 *
 * @param {HTMLElement} root `[data-pw-grafik]` element.
 */
function initGrafik( root ) {
	const body = root.querySelector( '[data-pw-grafik-rows]' );
	const template = root.querySelector( '[data-pw-grafik-template]' );
	const add = root.querySelector( '[data-pw-grafik-add]' );
	if ( ! body || ! template || ! add ) {
		return;
	}

	add.addEventListener( 'click', () => {
		const row = template.content.firstElementChild.cloneNode( true );
		body.append( row );
		renumberRows( body );
		row.querySelector( 'select, input' )?.focus();
	} );

	body.addEventListener( 'click', ( event ) => {
		const remove = event.target.closest( '[data-pw-grafik-remove]' );
		if ( ! remove ) {
			return;
		}
		remove.closest( '[data-pw-grafik-row]' )?.remove();
		renumberRows( body );
		add.focus();
	} );
}

export function initGrafikRows( root = document ) {
	root.querySelectorAll( '[data-pw-grafik]' ).forEach( initGrafik );
}
