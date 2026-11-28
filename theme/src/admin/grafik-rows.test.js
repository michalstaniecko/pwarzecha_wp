import { describe, expect, it } from 'vitest';
import { renumberName } from './grafik-rows.js';

describe( 'renumberName', () => {
	it( 'puts the row index into the first bracket of a field name', () => {
		expect( renumberName( 'pw_grafik[3][od]', 0 ) ).toBe(
			'pw_grafik[0][od]'
		);
		expect( renumberName( 'pw_grafik[0][grupa]', 12 ) ).toBe(
			'pw_grafik[12][grupa]'
		);
	} );

	it( 'fills the placeholder of the row template', () => {
		expect( renumberName( 'pw_grafik[__i__][dzien]', 4 ) ).toBe(
			'pw_grafik[4][dzien]'
		);
	} );

	it( 'leaves names without a row index untouched', () => {
		expect( renumberName( 'pw_grafik_nonce', 2 ) ).toBe(
			'pw_grafik_nonce'
		);
		expect( renumberName( 'pw_grafik[od]', 2 ) ).toBe( 'pw_grafik[od]' );
		expect( renumberName( 'pw_grafik[1][2]', 5 ) ).toBe(
			'pw_grafik[5][2]'
		);
	} );
} );
