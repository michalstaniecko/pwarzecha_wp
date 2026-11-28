import { describe, expect, it } from 'vitest';
import { filterIcons } from './filter-icons.js';

const icons = [
	{ name: 'wallet', label: 'Portfel', meaning: 'Cena, Karnet', svg: '' },
	{ name: 'phone', label: 'Telefon', meaning: 'Telefon kontaktowy', svg: '' },
	{
		name: 'brand-google',
		label: 'Google',
		meaning: 'Źródło Opinii',
		svg: '',
	},
	{ name: 'x', label: 'Krzyżyk', meaning: 'Zamknięcie', svg: '' },
];

const names = ( list ) => list.map( ( icon ) => icon.name );

describe( 'filterIcons', () => {
	it( 'returns every icon for an empty or blank query', () => {
		expect( names( filterIcons( icons, '' ) ) ).toEqual( names( icons ) );
		expect( names( filterIcons( icons, '   ' ) ) ).toEqual(
			names( icons )
		);
	} );

	it( 'matches the Polish label case-insensitively', () => {
		expect( names( filterIcons( icons, 'tele' ) ) ).toEqual( [ 'phone' ] );
		expect( names( filterIcons( icons, 'PORT' ) ) ).toEqual( [ 'wallet' ] );
	} );

	it( 'ignores Polish diacritics in label and query', () => {
		expect( names( filterIcons( icons, 'krzyz' ) ) ).toEqual( [ 'x' ] );
		expect( names( filterIcons( icons, 'KRZYŻ' ) ) ).toEqual( [ 'x' ] );
	} );

	it( 'also matches the meaning from the DS icon map', () => {
		expect( names( filterIcons( icons, 'karnet' ) ) ).toEqual( [
			'wallet',
		] );
		expect( names( filterIcons( icons, 'zrodlo' ) ) ).toEqual( [
			'brand-google',
		] );
	} );

	it( 'returns nothing when no label matches', () => {
		expect( filterIcons( icons, 'rakieta' ) ).toEqual( [] );
	} );
} );
