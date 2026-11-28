import { describe, expect, it } from 'vitest';
import { TEKST_LIMIT, tekstCounter } from './counter.js';

describe( 'tekstCounter', () => {
	it( 'has a 160 character limit', () => {
		expect( TEKST_LIMIT ).toBe( 160 );
	} );

	it( 'counts an empty text as 0 of 160', () => {
		expect( tekstCounter( '' ) ).toEqual( {
			count: 0,
			limit: 160,
			over: false,
		} );
	} );

	it( 'counts visible characters, not formatting tags or entities', () => {
		expect(
			tekstCounter( 'Ćwicz <strong>bez</strong> stresu &amp; bólu' ).count
		).toBe( 23 );
	} );

	it( 'counts a non-breaking space entity as one character', () => {
		expect( tekstCounter( 'w&nbsp;parach' ).count ).toBe( 8 );
	} );

	it( 'allows exactly 160 characters', () => {
		expect( tekstCounter( 'a'.repeat( 160 ) ).over ).toBe( false );
	} );

	it( 'is over the limit at 161 characters', () => {
		expect( tekstCounter( 'a'.repeat( 161 ) ) ).toEqual( {
			count: 161,
			limit: 160,
			over: true,
		} );
	} );
} );
