// Container breakpoints (theme/src/styles/context.css, theme/README.md): CSS variables cannot
// be used in @container conditions, so every query must use one of the documented values.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { glob, readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '../../', import.meta.url ) );

// Narrow phone (< 480 px) and phone (< 600 px).
const BREAKPOINTS = [ '(max-width: 479px)', '(max-width: 599px)' ];

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
