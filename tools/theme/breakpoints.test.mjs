// Container breakpoints (theme/src/styles/context.css, theme/README.md): CSS variables cannot
// be used in @container conditions, so every query must use one of the documented values.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { glob, readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '../../', import.meta.url ) );

// The DS bundle's container breakpoints (tools/ds/fixtures/ds-bundle.css). Its single
// `(max-width: 480px)` is ported as 479px, so "below 480" has one spelling.
const BREAKPOINTS = [
	// Max-width: logo row on very narrow phones, narrow phone (< 480 px), phone (< 600 px).
	'(max-width: 419px)',
	'(max-width: 479px)',
	'(max-width: 599px)',
	// Min-width: component layouts that widen with the page.
	'(min-width: 600px)',
	'(min-width: 640px)',
	'(min-width: 720px)',
	'(min-width: 760px)',
	'(min-width: 820px)',
	'(min-width: 880px)',
	'(min-width: 900px)',
	'(min-width: 960px)',
	'(min-width: 1100px)',
	'(min-width: 1240px)',
];

test( 'every @container query in the theme CSS uses a documented breakpoint', async () => {
	const found = [];
	for await ( const file of glob( 'theme/{src,inc}/**/*.css', {
		cwd: root,
	} ) ) {
		const css = ( await readFile( root + file, 'utf8' ) ).replace(
			/\/\*[\s\S]*?\*\//g,
			''
		);
		for ( const match of css.matchAll( /@container\s+([^{]+?)\s*\{/g ) ) {
			found.push( `${ file }: ${ match[ 1 ] }` );
			assert.ok(
				BREAKPOINTS.includes( match[ 1 ] ),
				`${ file }: @container ${ match[ 1 ] } is not one of ${ BREAKPOINTS.join( ', ' ) }`
			);
		}
	}
	assert.ok(
		found.length > 0,
		'no @container query found – is the glob right?'
	);
} );
