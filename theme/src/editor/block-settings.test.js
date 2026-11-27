import { describe, expect, it } from 'vitest';
import { restrictBlockSettings } from './block-settings.js';

const heading = {
	name: 'core/heading',
	attributes: {
		level: { type: 'number', default: 2 },
		levelOptions: { type: 'array' },
	},
};

const embed = {
	name: 'core/embed',
	variations: [
		{ name: 'twitter', title: 'Twitter' },
		{ name: 'youtube', title: 'YouTube' },
		{ name: 'vimeo', title: 'Vimeo' },
	],
};

describe( 'restrictBlockSettings', () => {
	it( 'limits heading levels to H2 and H3 by default (#21)', () => {
		const settings = restrictBlockSettings( heading, 'core/heading' );

		expect( settings.attributes.levelOptions.default ).toEqual( [ 2, 3 ] );
		expect( settings.attributes.level.default ).toBe( 2 );
	} );

	it( 'removes the YouTube embed variation and keeps the others', () => {
		const settings = restrictBlockSettings( embed, 'core/embed' );

		expect( settings.variations.map( ( { name } ) => name ) ).toEqual( [
			'twitter',
			'vimeo',
		] );
	} );

	it( 'leaves other blocks untouched', () => {
		const paragraph = { name: 'core/paragraph', attributes: {} };

		expect( restrictBlockSettings( paragraph, 'core/paragraph' ) ).toBe(
			paragraph
		);
	} );

	it( 'does not mutate the settings it receives', () => {
		restrictBlockSettings( heading, 'core/heading' );
		restrictBlockSettings( embed, 'core/embed' );

		expect( heading.attributes.levelOptions.default ).toBeUndefined();
		expect( embed.variations ).toHaveLength( 3 );
	} );
} );
