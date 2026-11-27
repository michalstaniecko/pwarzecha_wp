// Self-hosted fonts contract (#41): every font family is served from the theme, never from Google.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile, stat } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '../../', import.meta.url ) );

async function fontFamilies() {
	const json = JSON.parse(
		await readFile( join( root, 'theme/theme.json' ), 'utf8' )
	);
	return json.settings.typography.fontFamilies;
}

test( 'Archivo and Source Sans 3 each ship latin and latin-ext woff2 faces', async () => {
	const families = await fontFamilies();
	const faces = ( slug ) =>
		families.find( ( family ) => family.slug === slug )?.fontFace ?? [];

	for ( const slug of [ 'archivo', 'source-sans-3' ] ) {
		const srcs = faces( slug ).flatMap( ( face ) => face.src );
		assert.deepEqual(
			srcs.map( ( src ) => src.replace( /^.*\//, '' ) ).sort(),
			[ `${ slug }-latin-ext.woff2`, `${ slug }-latin.woff2` ],
			slug
		);
	}
	assert.deepEqual(
		faces( 'archivo' ).map( ( face ) => face.fontWeight ),
		[ '600 800', '600 800' ]
	);
	assert.deepEqual(
		faces( 'source-sans-3' ).map( ( face ) => face.fontWeight ),
		[ '400 600', '400 600' ]
	);
} );

test( 'every font face is a local theme file with font-display: swap', async () => {
	const families = await fontFamilies();
	const faces = families.flatMap( ( family ) => family.fontFace ?? [] );
	assert.ok( faces.length > 0, 'no fontFace declared' );

	for ( const face of faces ) {
		assert.equal( face.fontDisplay, 'swap', face.fontFamily );
		assert.ok( face.unicodeRange, `${ face.fontFamily }: unicodeRange` );
		for ( const src of [ face.src ].flat() ) {
			assert.match( src, /^file:\.\/assets\/fonts\/[\w-]+\.woff2$/ );
			const file = join( root, 'theme', src.replace( 'file:./', '' ) );
			assert.ok( ( await stat( file ) ).size > 0, file );
		}
	}
} );
