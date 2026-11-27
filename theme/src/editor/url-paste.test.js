import { describe, expect, it } from 'vitest';
import { create } from '@wordpress/rich-text';
import { pastedUrl, urlPasteFormat } from './url-paste.js';

const URL_ = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
const paste = ( value, plainText ) =>
	urlPasteFormat.__unstablePasteRule( value, { html: '', plainText } );

describe( 'pastedUrl', () => {
	it( 'returns a pasted http(s) URL, trimmed', () => {
		expect( pastedUrl( { plainText: ` ${ URL_ }\n` } ) ).toBe( URL_ );
		expect( pastedUrl( { html: `<a href="x">${ URL_ }</a>` } ) ).toBe(
			URL_
		);
	} );

	it( 'ignores text, other protocols and several words', () => {
		expect( pastedUrl( { plainText: 'hello world' } ) ).toBeNull();
		expect( pastedUrl( { plainText: 'mailto:a@b.pl' } ) ).toBeNull();
		expect( pastedUrl( { plainText: `${ URL_ } ${ URL_ }` } ) ).toBeNull();
	} );
} );

describe( 'pw/url-paste rule', () => {
	it( 'inserts the URL as a core/link at a collapsed caret', () => {
		const value = { ...create( { text: 'Film: ' } ), start: 6, end: 6 };

		const pasted = paste( value, URL_ );

		expect( pasted.text ).toBe( `Film: ${ URL_ }` );
		expect( pasted.formats[ 5 ] ).toBeUndefined();
		expect( pasted.formats[ 6 ] ).toEqual( [
			{ type: 'core/link', attributes: { url: URL_ } },
		] );
		expect( pasted.formats.at( -1 ) ).toEqual( pasted.formats[ 6 ] );
	} );

	it( 'leaves a selection and non-URL pastes to the other rules', () => {
		const selected = { ...create( { text: 'abc' } ), start: 0, end: 3 };
		const caret = { ...create( { text: '' } ), start: 0, end: 0 };

		expect( paste( selected, URL_ ) ).toBe( selected );
		expect( paste( caret, 'hello' ) ).toBe( caret );
	} );
} );
