import { test } from 'node:test';
import assert from 'node:assert/strict';
import { compileTokensCss } from './compile.mjs';

test( 'header names the system, colors go into :root and the first theme', () => {
	const css = compileTokensCss( {
		name: 'Acme',
		color: {
			themes: [ { id: 'light', name: 'Light' } ],
			tokens: [
				{ name: 'base', value: '#FFFFFF', usage: 'Page background.' },
			],
		},
	} );
	assert.equal(
		css,
		'/* Acme — generated from tokens.json */\n' +
			':root, [data-theme="light"] {\n' +
			'  --base: #ffffff; /* Page background. */\n' +
			'}\n'
	);
} );

const sys = ( extra ) => ( { name: 'T', ...extra } );
const body = ( css ) => css.slice( css.indexOf( '\n' ) + 1 );

test( 'color alias compiles to var() of the aliased token', () => {
	const css = compileTokensCss(
		sys( {
			color: {
				themes: [ { id: 'light' } ],
				tokens: [
					{ name: 'primary', value: '#11213b' },
					{ name: 'link', value: '{primary}' },
				],
			},
		} )
	);
	assert.equal(
		body( css ),
		':root, [data-theme="light"] {\n  --primary: #11213b;\n  --link: var(--primary);\n}\n'
	);
} );

test( 'second theme gets a block with its own values and re-declared aliases', () => {
	const css = compileTokensCss(
		sys( {
			color: {
				themes: [ { id: 'Light' }, { id: 'dark' } ],
				tokens: [
					{ name: 'bg', value: { light: '#fff', dark: '#000' } },
					{ name: 'ink', value: '#111' },
					{ name: 'text', value: '{ink}' },
				],
			},
			shadow: {
				tokens: [
					{
						name: 'shadow-1',
						value: { light: '0 1px 2px #0001', dark: 'none' },
					},
				],
			},
		} )
	);
	assert.equal(
		body( css ),
		':root, [data-theme="light"] {\n' +
			'  --bg: #fff;\n  --ink: #111;\n  --text: var(--ink);\n  --shadow-1: 0 1px 2px #0001;\n' +
			'}\n' +
			'[data-theme="dark"] {\n  --bg: #000;\n  --text: var(--ink);\n  --shadow-1: none;\n}\n'
	);
} );

test( 'a value only in a later theme is borrowed for the first theme', () => {
	const css = compileTokensCss(
		sys( {
			color: {
				themes: [ { id: 'light' }, { id: 'dark' } ],
				tokens: [ { name: 'x', value: { dark: '#222' } } ],
			},
		} )
	);
	assert.equal(
		body( css ),
		':root, [data-theme="light"] {\n  --x: #222;\n}\n[data-theme="dark"] {\n  --x: #222;\n}\n'
	);
} );

test( 'invalid colors, aliases of missing tokens and alias cycles are dropped', () => {
	const css = compileTokensCss(
		sys( {
			color: {
				themes: [ { id: 'light' } ],
				tokens: [
					{ name: 'ok', value: 'rgba(10, 21, 40, 0.72)' },
					{ name: 'named', value: 'red' },
					{ name: 'mixed', value: 'color-mix(in srgb, #fff, #000)' },
					{ name: 'viavar', value: 'var(--ok)' },
					{ name: 'orphan', value: '{nope}' },
					{ name: 'self', value: '{self}' },
					{ name: 'a', value: '{b}' },
					{ name: 'b', value: '{a}' },
					{ name: 'bad name', value: '#fff' },
					{ name: 'ok', value: '#000' },
				],
			},
		} )
	);
	assert.equal(
		body( css ),
		':root, [data-theme="light"] {\n  --ok: rgba(10, 21, 40, 0.72);\n}\n'
	);
} );

test( 'spacing, radius, other families and font stacks go into a second :root', () => {
	const css = compileTokensCss(
		sys( {
			spacing: {
				tokens: [
					{ name: 'space-1.5', value: '6px', usage: 'Tight.' },
					{ name: 'space-2', value: 8 },
				],
			},
			radius: {
				tokens: [
					{ name: 'radius-full', value: 'full' },
					{ name: 'radius-bad', value: '4pt' },
				],
			},
			fluid: {
				tokens: [
					{
						name: 'fluid-1',
						value: 'clamp(1rem, 0.9rem + 0.5vw, 1.5rem)',
					},
				],
			},
			border: { tokens: [ { name: 'border-accent', value: '3px' } ] },
			layout: { tokens: [ { name: 'content-size', value: '760px' } ] },
			ratio: {
				tokens: [
					{ name: 'ratio-wide', value: '16 / 9' },
					{ name: 'bad', value: 'url(x)' },
				],
			},
			type: {
				families: { Heading: '"Archivo", sans-serif', bad: 'x; y' },
			},
		} )
	);
	assert.equal(
		body( css ),
		':root {\n' +
			'  --space-1\\.5: 6px; /* Tight. */\n' +
			'  --space-2: 8px;\n' +
			'  --radius-full: 9999px;\n' +
			'  --fluid-1: clamp(1rem, 0.9rem + 0.5vw, 1.5rem);\n' +
			'  --border-accent: 3px;\n' +
			'  --content-size: 760px;\n' +
			'  --ratio-wide: 16 / 9;\n' +
			'  --font-heading: "Archivo", sans-serif;\n' +
			'}\n'
	);
} );

test( 'a token name is used once across families; the first one wins', () => {
	const css = compileTokensCss(
		sys( {
			color: {
				themes: [ { id: 'light' } ],
				tokens: [ { name: 'gap', value: '#fff' } ],
			},
			spacing: {
				tokens: [
					{ name: 'gap', value: '8px' },
					{ name: 'gap-2', value: '16px' },
				],
			},
			border: { tokens: [ { name: 'gap-2', value: '2px' } ] },
		} )
	);
	assert.equal(
		body( css ),
		':root, [data-theme="light"] {\n  --gap: #fff;\n}\n:root {\n  --gap-2: 16px;\n}\n'
	);
} );

test( 'type styles: shorthand variable and class, own family or the group family', () => {
	const css = compileTokensCss(
		sys( {
			type: {
				families: {
					heading: '"Archivo", sans-serif',
					body: '"Source Sans 3", sans-serif',
				},
				groups: [
					{
						name: 'Headings',
						family: 'heading',
						styles: [
							{
								name: 'h1',
								fontSize: '56px',
								lineHeight: 1.08,
								fontWeight: 800,
								letterSpacing: '0.03em',
								usage: 'H1.',
							},
							{
								name: 'quote',
								fontSize: 20,
								lineHeight: '28px',
								fontStyle: 'italic',
								family: 'body',
							},
						],
					},
					{
						name: 'Loose',
						styles: [
							{
								name: 'note',
								fontSize: '14px',
								fontWeight: 'bold',
								opticalSize: 14,
							},
						],
					},
					{
						name: 'Broken',
						styles: [ { name: 'bad', fontSize: 'big' } ],
					},
				],
			},
		} )
	);
	assert.equal(
		body( css ),
		':root {\n' +
			'  --font-heading: "Archivo", sans-serif;\n' +
			'  --font-body: "Source Sans 3", sans-serif;\n' +
			'  --text-h1: 800 56px/1.08 var(--font-heading); /* H1. */\n' +
			'  --text-quote: italic 400 20px/28px var(--font-body);\n' +
			'  --text-note: 700 14px/1.4 inherit;\n' +
			'}\n' +
			'.h1 {\n  font-family: var(--font-heading);\n  font-size: 56px;\n  line-height: 1.08;\n  font-weight: 800;\n  letter-spacing: 0.03em;\n}\n' +
			'.quote {\n  font-family: var(--font-body);\n  font-size: 20px;\n  line-height: 28px;\n  font-weight: 400;\n  letter-spacing: 0;\n  font-style: italic;\n}\n' +
			".note {\n  font-size: 14px;\n  line-height: 1.4;\n  font-weight: 700;\n  letter-spacing: 0;\n  font-variation-settings: 'opsz' 14;\n}\n"
	);
} );

test( '@font-face per font, with a weight range and the format from the extension', () => {
	const css = compileTokensCss(
		sys( {
			type: {
				fonts: [
					{
						family: 'Archivo',
						file: 'fonts/Archivo-Variable.woff2',
						weight: '600 800',
						style: 'normal',
					},
					{
						family: 'Mono',
						file: 'Mono Regular.ttf',
						unicodeRange: 'U+0-FF',
					},
					{ family: 'Evil', file: '../x.woff2' },
				],
			},
		} )
	);
	assert.equal(
		body( css ),
		'@font-face {\n  font-family: "Archivo";\n  src: url("fonts/Archivo-Variable.woff2") format("woff2");\n' +
			'  font-weight: 600 800;\n  font-style: normal;\n  font-display: swap;\n}\n' +
			'@font-face {\n  font-family: "Mono";\n  src: url("fonts/Mono%20Regular.ttf") format("truetype");\n' +
			'  font-weight: 400;\n  font-style: normal;\n  font-display: swap;\n  unicode-range: U+0-FF;\n}\n'
	);
} );

test( 'usage text cannot close the comment it is written into', () => {
	const css = compileTokensCss(
		sys( {
			spacing: {
				tokens: [ { name: 's', value: '1px', usage: 'a */ b\nc' } ],
			},
		} )
	);
	assert.equal( body( css ), ':root {\n  --s: 1px; /* a * / b c */\n}\n' );
} );

test( 'header falls back to "Design system" and accepts a name override', () => {
	assert.equal(
		compileTokensCss( {} ),
		'/* Design system — generated from tokens.json */\n'
	);
	assert.equal(
		compileTokensCss( { name: 'A' }, { name: 'B' } ),
		'/* B — generated from tokens.json */\n'
	);
} );

test( 'compiling the same tokens twice gives identical text', async () => {
	const { readFile } = await import( 'node:fs/promises' );
	const json = await readFile(
		new URL( './fixtures/ds-tokens.json', import.meta.url ),
		'utf8'
	);
	assert.equal(
		compileTokensCss( json ),
		compileTokensCss( JSON.parse( json ) )
	);
	assert.equal( compileTokensCss( json ), compileTokensCss( json ) );
} );

test( 'reference: every var(--…) bundle.css needs is defined by the compiled DS tokens', async () => {
	const { readFile } = await import( 'node:fs/promises' );
	const json = await readFile(
		new URL( './fixtures/ds-tokens.json', import.meta.url ),
		'utf8'
	);
	const bundle = await readFile(
		new URL( './fixtures/ds-bundle.css', import.meta.url ),
		'utf8'
	);
	const css = compileTokensCss( json );
	const defined = new Set(
		[ ...css.matchAll( /^\s*--([A-Za-z0-9_\\.-]+):/gm ) ].map(
			( m ) => m[ 1 ]
		)
	);
	const needed = new Set(
		[ ...bundle.matchAll( /var\(\s*--([A-Za-z0-9_-]+)\s*\)/g ) ]
			.map( ( m ) => m[ 1 ] )
			.filter( ( n ) => ! n.startsWith( 'sp-' ) )
	);
	const missing = [ ...needed ].filter( ( n ) => ! defined.has( n ) );
	assert.deepEqual( missing, [] );
	assert.ok( needed.size > 20, 'bundle.css should use many tokens' );
} );
