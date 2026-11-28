import { describe, expect, it } from 'vitest';
import {
	HEADING_MAX_LENGTH,
	collectHeadingIds,
	generateHeadingId,
	headingLength,
	isHeadingTooLong,
	nextHeadingId,
} from './heading-id.js';

const block = ( clientId, naglowekId, innerBlocks = [] ) => ( {
	clientId,
	attributes: naglowekId === undefined ? {} : { naglowekId },
	innerBlocks,
} );

describe( 'generateHeadingId', () => {
	it( 'is pw-h- followed by 6 lowercase letters or digits', () => {
		expect( generateHeadingId() ).toMatch( /^pw-h-[a-z0-9]{6}$/ );
	} );

	it( 'uses the given random source', () => {
		expect( generateHeadingId( () => 0 ) ).toBe( 'pw-h-aaaaaa' );
		expect( generateHeadingId( () => 0.9999 ) ).toBe( 'pw-h-999999' );
	} );
} );

describe( 'collectHeadingIds', () => {
	it( 'collects naglowekId of every other block in the tree, nested too', () => {
		const blocks = [
			block( 'a', 'pw-h-aaaaaa' ),
			block( 'section', undefined, [
				block( 'b', 'pw-h-bbbbbb' ),
				block( 'self', 'pw-h-selfff' ),
				block( 'empty', '' ),
			] ),
		];

		expect( [ ...collectHeadingIds( blocks, 'self' ) ].sort() ).toEqual( [
			'pw-h-aaaaaa',
			'pw-h-bbbbbb',
		] );
	} );
} );

describe( 'nextHeadingId', () => {
	const fixed = () => 'pw-h-new123';

	it( 'generates an id when the block has none (first insert)', () => {
		expect( nextHeadingId( '', new Set(), fixed ) ).toBe( 'pw-h-new123' );
		expect( nextHeadingId( undefined, new Set(), fixed ) ).toBe(
			'pw-h-new123'
		);
	} );

	it( 'keeps a unique id', () => {
		expect(
			nextHeadingId( 'pw-h-abc123', new Set( [ 'pw-h-other1' ] ), fixed )
		).toBeNull();
	} );

	it( 'regenerates an id another block already has (duplicated block)', () => {
		expect(
			nextHeadingId( 'pw-h-abc123', new Set( [ 'pw-h-abc123' ] ), fixed )
		).toBe( 'pw-h-new123' );
	} );

	it( 'never returns an id that is already taken', () => {
		const ids = [ 'pw-h-abc123', 'pw-h-abc123', 'pw-h-fresh1' ];
		const generate = () => ids.shift();

		expect(
			nextHeadingId(
				'pw-h-abc123',
				new Set( [ 'pw-h-abc123' ] ),
				generate
			)
		).toBe( 'pw-h-fresh1' );
	} );
} );

describe( 'heading length', () => {
	it( 'counts characters of the plain text, without tags and entities', () => {
		expect( headingLength( 'Zajęcia <strong>dla</strong> każdego' ) ).toBe(
			19
		);
		expect( headingLength( 'Tom&amp;Jerry&nbsp;!' ) ).toBe( 11 );
		expect( headingLength( '' ) ).toBe( 0 );
		expect( headingLength( undefined ) ).toBe( 0 );
	} );

	it( 'warns above 60 characters only', () => {
		expect( HEADING_MAX_LENGTH ).toBe( 60 );
		expect( isHeadingTooLong( 'x'.repeat( 60 ) ) ).toBe( false );
		expect( isHeadingTooLong( 'x'.repeat( 61 ) ) ).toBe( true );
		expect(
			isHeadingTooLong( `<a href="#">${ 'x'.repeat( 60 ) }</a>` )
		).toBe( false );
	} );
} );
