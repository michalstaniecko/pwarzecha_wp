import { describe, expect, it } from 'vitest';
import { normalizeCities } from './normalize-cities.js';

describe( 'normalizeCities', () => {
	it( 'trims city names and drops empty ones', () => {
		expect( normalizeCities( [ ' Bytom ', '', '  ', 'Chorzów' ] ) ).toEqual(
			[ 'Bytom', 'Chorzów' ]
		);
	} );

	it( 'keeps the first of duplicates in the given order', () => {
		expect(
			normalizeCities( [ 'Katowice', 'Bytom', 'Katowice ' ] )
		).toEqual( [ 'Katowice', 'Bytom' ] );
	} );

	it( 'reads token objects of FormTokenField', () => {
		expect( normalizeCities( [ { value: 'Gliwice' }, 'Zabrze' ] ) ).toEqual(
			[ 'Gliwice', 'Zabrze' ]
		);
	} );

	it( 'treats anything but a list as no cities', () => {
		expect( normalizeCities( undefined ) ).toEqual( [] );
		expect( normalizeCities( 'Bytom' ) ).toEqual( [] );
	} );
} );
