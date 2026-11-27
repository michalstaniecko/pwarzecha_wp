// Theme skeleton contract (#36, ADR 0013): style.css header, Version kept in sync with package.json.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '../../', import.meta.url ) );

async function themeHeader() {
	const css = await readFile( join( root, 'theme/style.css' ), 'utf8' );
	const header = {};
	for ( const match of css.matchAll( /^\s*([A-Za-z ]+):\s*(.+?)\s*$/gm ) ) {
		header[ match[ 1 ].trim() ] = match[ 2 ];
	}
	return header;
}

test( 'style.css declares the theme identity and platform requirements', async () => {
	const header = await themeHeader();
	assert.equal( header[ 'Theme Name' ], 'Samoobrona Przemek' );
	assert.equal( header[ 'Text Domain' ], 'pw-theme' );
	assert.equal( header[ 'Requires at least' ], '7.1' );
	assert.equal( header[ 'Requires PHP' ], '8.4' );
} );

test( 'style.css Version equals package.json version (theme migrations key off it)', async () => {
	const pkg = JSON.parse(
		await readFile( join( root, 'package.json' ), 'utf8' )
	);
	const header = await themeHeader();
	assert.equal( header.Version, pkg.version );
} );
