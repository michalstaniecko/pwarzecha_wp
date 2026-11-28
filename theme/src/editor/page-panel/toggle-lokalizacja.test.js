import { describe, expect, it } from 'vitest';
import { toggleLokalizacja } from './toggle-lokalizacja.js';

describe( 'toggleLokalizacja', () => {
	it( 'appends a newly checked Lokalizacja at the end (order of selection)', () => {
		expect( toggleLokalizacja( [ 7 ], 3 ) ).toEqual( [ 7, 3 ] );
		expect( toggleLokalizacja( [], 3 ) ).toEqual( [ 3 ] );
	} );

	it( 'removes an unchecked Lokalizacja and keeps the order of the rest', () => {
		expect( toggleLokalizacja( [ 7, 3, 9 ], 3 ) ).toEqual( [ 7, 9 ] );
	} );

	it( 'never stores duplicates', () => {
		expect( toggleLokalizacja( [ 7, 3, 7 ], 9 ) ).toEqual( [ 7, 3, 9 ] );
		expect( toggleLokalizacja( [ 7, 3, 7 ], 7 ) ).toEqual( [ 3 ] );
	} );

	it( 'drops IDs that are not among the available Lokalizacje', () => {
		expect( toggleLokalizacja( [ 7, 42, 3 ], 9, [ 3, 7, 9 ] ) ).toEqual( [
			7, 3, 9,
		] );
		expect( toggleLokalizacja( [ 7 ], 42, [ 3, 7 ] ) ).toEqual( [ 7 ] );
	} );

	it( 'tolerates a missing or malformed meta value', () => {
		expect( toggleLokalizacja( undefined, 3 ) ).toEqual( [ 3 ] );
		expect( toggleLokalizacja( [ '7', 0, null ], 3 ) ).toEqual( [ 7, 3 ] );
	} );
} );
