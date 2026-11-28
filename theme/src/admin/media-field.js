/**
 * Image field of classic metaboxes (`PW\Data\Admin\render_media_field()`, #49): „Wybierz obraz”
 * opens the media modal, the chosen attachment ID goes to the hidden input. No other logic.
 */

/**
 * Wires one field.
 *
 * @param {HTMLElement} field `[data-pw-media-field]` element.
 */
function initMediaField( field ) {
	const input = field.querySelector( '[data-pw-media-input]' );
	const preview = field.querySelector( '[data-pw-media-preview]' );
	const remove = field.querySelector( '[data-pw-media-remove]' );
	let frame;

	field
		.querySelector( '[data-pw-media-select]' )
		.addEventListener( 'click', () => {
			frame ??= window.wp.media( {
				library: { type: 'image' },
				multiple: false,
			} );
			frame.off( 'select' ).on( 'select', () => {
				const image = frame.state().get( 'selection' ).first().toJSON();
				const img = document.createElement( 'img' );
				img.src = image.sizes?.thumbnail?.url ?? image.url;
				img.alt = '';
				preview.replaceChildren( img );
				input.value = String( image.id );
				remove.hidden = false;
			} );
			frame.open();
		} );

	remove.addEventListener( 'click', () => {
		input.value = '';
		preview.replaceChildren();
		remove.hidden = true;
	} );
}

export function initMediaFields( root = document ) {
	root.querySelectorAll( '[data-pw-media-field]' ).forEach( initMediaField );
}
