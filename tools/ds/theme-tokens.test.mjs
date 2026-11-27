import { test } from 'node:test';
import assert from 'node:assert/strict';
import { diffThemeTokens, themeToTokens } from './theme-tokens.mjs';

const color = ( slug ) => ( {
	ds: { family: 'color', token: slug },
	theme: [ 'settings', 'color', 'palette', { slug }, 'color' ],
} );

test( 'theme-to-tokens copies a palette color into the DS token value and keeps usage', () => {
	const theme = {
		settings: {
			color: {
				palette: [ { slug: 'base', name: 'Base', color: '#fefefe' } ],
			},
		},
	};
	const ds = {
		name: 'DS',
		color: {
			tokens: [ { name: 'base', value: '#ffffff', usage: 'Tło.' } ],
		},
	};
	const { tokens, warnings } = themeToTokens( theme, ds, [
		color( 'base' ),
	] );
	assert.deepEqual( tokens, {
		name: 'DS',
		color: {
			tokens: [ { name: 'base', value: '#fefefe', usage: 'Tło.' } ],
		},
	} );
	assert.deepEqual( warnings, [] );
	assert.equal( ds.color.tokens[ 0 ].value, '#ffffff', 'input not mutated' );
} );

test( 'a mapped token missing from tokens.json is a warning, not a new token', () => {
	const theme = {
		settings: {
			color: {
				palette: [
					{ slug: 'base', color: '#ffffff' },
					{ slug: 'brand-new', color: '#123456' },
				],
			},
		},
	};
	const ds = {
		color: { tokens: [ { name: 'base', value: '#ffffff' } ] },
	};
	const { tokens, warnings } = themeToTokens( theme, ds, [
		color( 'base' ),
		color( 'brand-new' ),
	] );
	assert.deepEqual( tokens, ds );
	assert.equal( warnings.length, 1 );
	assert.match( warnings[ 0 ], /brand-new/ );
} );

test( 'a mapped path missing from theme.json is a warning and leaves the DS value', () => {
	const ds = { color: { tokens: [ { name: 'base', value: '#ffffff' } ] } };
	const { tokens, warnings } = themeToTokens( { settings: {} }, ds, [
		color( 'base' ),
	] );
	assert.deepEqual( tokens, ds );
	assert.equal( warnings.length, 1 );
	assert.match( warnings[ 0 ], /theme\.json/ );
} );

test( 'format px: the DS gets the largest value of a clamp, a font-size preset or a rem length, in px', () => {
	const theme = {
		settings: {
			typography: {
				fontSizes: [
					{
						slug: 'large',
						size: '1.375rem',
						fluid: { min: '1.1875rem', max: '1.375rem' },
					},
				],
			},
			spacing: {
				spacingSizes: [
					{ slug: '20', size: '8px' },
					{
						slug: '50',
						size: 'clamp(1.5rem, 1.3239rem + 0.7512vw, 2rem)',
					},
				],
			},
			custom: {
				typography: {
					lead: { fontSize: 'var(--wp--preset--font-size--large)' },
					eyebrow: { fontSize: '0.9375rem' },
				},
			},
		},
	};
	const spacing = ( slug ) => ( {
		ds: { family: 'spacing', token: `spacing-${ slug }` },
		theme: [ 'settings', 'spacing', 'spacingSizes', { slug }, 'size' ],
		format: 'px',
	} );
	const style = ( name ) => ( {
		ds: { family: 'type.styles', token: name, prop: 'fontSize' },
		theme: [ 'settings', 'custom', 'typography', name, 'fontSize' ],
		format: 'px',
	} );
	const ds = {
		type: {
			groups: [
				{
					styles: [
						{
							name: 'lead',
							fontSize: '1px',
							sample: 'S',
							usage: 'U',
						},
						{ name: 'eyebrow', fontSize: '1px' },
					],
				},
			],
		},
		spacing: {
			tokens: [
				{ name: 'spacing-20', value: '1px' },
				{ name: 'spacing-50', value: '1px' },
			],
		},
	};
	const { tokens, warnings } = themeToTokens( theme, ds, [
		spacing( '20' ),
		spacing( '50' ),
		style( 'lead' ),
		style( 'eyebrow' ),
	] );
	assert.deepEqual( warnings, [] );
	assert.deepEqual( tokens.spacing.tokens, [
		{ name: 'spacing-20', value: '8px' },
		{ name: 'spacing-50', value: '32px' },
	] );
	assert.deepEqual( tokens.type.groups[ 0 ].styles, [
		{ name: 'lead', fontSize: '22px', sample: 'S', usage: 'U' },
		{ name: 'eyebrow', fontSize: '15px' },
	] );
} );

test( 'format fluid-clamp: a fluid font size becomes the DS clamp() for the theme.json viewport range', () => {
	const theme = {
		settings: {
			typography: {
				fluid: {
					minViewportWidth: '375px',
					maxViewportWidth: '1440px',
				},
				fontSizes: [
					{
						slug: 'small',
						size: '1rem',
						fluid: { min: '0.9375rem', max: '1rem' },
					},
					{
						slug: 'xxx-large',
						size: '3.5rem',
						fluid: { min: '2.125rem', max: '3.5rem' },
					},
				],
			},
		},
	};
	const fluid = ( slug ) => ( {
		ds: { family: 'fluid', token: `fluid-font-${ slug }` },
		theme: [ 'settings', 'typography', 'fontSizes', { slug } ],
		format: 'fluid-clamp',
	} );
	const ds = {
		fluid: {
			tokens: [
				{ name: 'fluid-font-small', value: '' },
				{ name: 'fluid-font-xxx-large', value: '' },
			],
		},
	};
	const { tokens } = themeToTokens( theme, ds, [
		fluid( 'small' ),
		fluid( 'xxx-large' ),
	] );
	// Expected values: the DS tokens.json written before theme.json existed.
	assert.deepEqual(
		tokens.fluid.tokens.map( ( t ) => t.value ),
		[
			'clamp(0.9375rem, 0.9155rem + 0.0939vw, 1rem)',
			'clamp(2.125rem, 1.6408rem + 2.0657vw, 3.5rem)',
		]
	);
} );

test( 'diff is empty when tokens.json holds the theme.json values', () => {
	const theme = {
		settings: {
			color: { palette: [ { slug: 'base', color: '#ffffff' } ] },
		},
	};
	const ds = { color: { tokens: [ { name: 'base', value: '#ffffff' } ] } };
	assert.deepEqual( diffThemeTokens( theme, ds, [ color( 'base' ) ] ), [] );
} );

test( 'diff lists each differing or missing value with both sides', () => {
	const theme = {
		settings: {
			color: {
				palette: [
					{ slug: 'base', color: '#ffffff' },
					{ slug: 'contrast', color: '#000000' },
					{ slug: 'extra', color: '#123456' },
				],
			},
		},
	};
	const ds = {
		color: {
			tokens: [
				{ name: 'base', value: '#ffffff' },
				{ name: 'contrast', value: '#111111' },
				{ name: 'orphan', value: '#222222' },
			],
		},
	};
	const diff = diffThemeTokens( theme, ds, [
		color( 'base' ),
		color( 'contrast' ),
		color( 'extra' ),
		color( 'orphan' ),
	] );
	assert.deepEqual( diff, [
		{ token: 'color/contrast', theme: '#000000', tokens: '#111111' },
		{ token: 'color/extra', theme: '#123456', tokens: undefined },
		{ token: 'color/orphan', theme: undefined, tokens: '#222222' },
	] );
} );
