import { describe, expect, it } from 'vitest';
import { hasMixedTiles, nextTileName } from './tile-types.js';

describe( 'nextTileName', () => {
	it( 'adds a photo tile to an empty grid', () => {
		expect( nextTileName( [] ) ).toBe( 'pw/photo-tile' );
	} );

	it( 'adds the same kind of tile as the first one', () => {
		expect( nextTileName( [ 'pw/icon-tile', 'pw/icon-tile' ] ) ).toBe(
			'pw/icon-tile'
		);
	} );
} );

describe( 'hasMixedTiles', () => {
	it( 'is false for one kind of tile or an empty grid', () => {
		expect( hasMixedTiles( [] ) ).toBe( false );
		expect( hasMixedTiles( [ 'pw/photo-tile', 'pw/photo-tile' ] ) ).toBe(
			false
		);
	} );

	it( 'is true for photo and icon tiles in one grid', () => {
		expect( hasMixedTiles( [ 'pw/photo-tile', 'pw/icon-tile' ] ) ).toBe(
			true
		);
	} );
} );
