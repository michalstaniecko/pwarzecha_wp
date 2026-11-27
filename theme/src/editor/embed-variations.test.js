import { describe, expect, it } from 'vitest';
import { embedVariationsToRemove } from './embed-variations.js';

// Shape of `wp.blocks.getBlockVariations( 'core/embed' )` (trimmed).
const coreEmbedVariations = [
	{ name: 'twitter', title: 'Twitter' },
	{ name: 'youtube', title: 'YouTube' },
	{ name: 'vimeo', title: 'Vimeo' },
	{ name: 'wordpress', title: 'WordPress' },
];

describe( 'embedVariationsToRemove', () => {
	it( 'selects the YouTube variation (ADR 0008: films only via VideoFacade)', () => {
		expect( embedVariationsToRemove( coreEmbedVariations ) ).toEqual( [
			'youtube',
		] );
	} );

	it( 'returns nothing when YouTube is not registered', () => {
		expect(
			embedVariationsToRemove( [ { name: 'vimeo', title: 'Vimeo' } ] )
		).toEqual( [] );
	} );

	it( 'tolerates a missing variation list', () => {
		expect( embedVariationsToRemove( undefined ) ).toEqual( [] );
	} );
} );
