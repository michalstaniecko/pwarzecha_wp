// Theme ZIP build (#95, ADR 0006/0007): whitelist copy of theme/ into dist/pw-theme/ and zip.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { mkdtemp, mkdir, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { basename, dirname, join } from 'node:path';
import { promisify } from 'node:util';
import { buildZip } from './build-zip.mjs';
import { verifyZip } from './verify-zip.mjs';

const run = promisify( execFile );

const THEME = {
	'style.css': '/*\nTheme Name: Samoobrona Przemek\nVersion: 3.4.5\n*/\n',
	'functions.php': '<?php\n',
	'theme.json': '{}',
	'screenshot.png': 'png',
	'readme.txt': '=== Samoobrona Przemek ===\n',
	'README.md': '# dev notes',
	'templates/index.html': '<!-- wp:post-content /-->',
	'parts/header.html': '',
	'patterns/hero.php': '<?php\n',
	'inc/bootstrap.php': '<?php\n',
	'inc/styleguide/route.php': '<?php\n',
	'inc/styleguide/assets/photo.jpg': 'jpg',
	'build/blocks-manifest.php': '<?php return array();\n',
	'build/blocks/x/index.js': '',
	'build/blocks/x/index.js.map': '{}',
	'assets/fonts/a.woff2': 'font',
	'src/blocks/x/edit.js': '',
	'.DS_Store': '',
};

async function fixture( t ) {
	const root = await mkdtemp( join( tmpdir(), 'pw-build-zip-' ) );
	t.after( () => rm( root, { recursive: true, force: true } ) );
	await writeFile(
		join( root, 'package.json' ),
		JSON.stringify( { version: '3.4.5' } )
	);
	for ( const [ path, content ] of Object.entries( THEME ) ) {
		await mkdir( dirname( join( root, 'theme', path ) ), {
			recursive: true,
		} );
		await writeFile( join( root, 'theme', path ), content );
	}
	return root;
}

async function entries( zip ) {
	const { stdout } = await run( 'unzip', [ '-Z1', zip ] );
	return stdout
		.split( '\n' )
		.filter( Boolean )
		.filter( ( e ) => ! e.endsWith( '/' ) )
		.sort();
}

test( 'buildZip packs only the whitelist under pw-theme/ and passes verify-zip', async ( t ) => {
	const root = await fixture( t );
	const zip = await buildZip( { root, build: false } );

	assert.equal( zip, join( root, 'dist', 'pw-theme-3.4.5.zip' ) );
	assert.deepEqual( await entries( zip ), [
		'pw-theme/assets/fonts/a.woff2',
		'pw-theme/build/blocks-manifest.php',
		'pw-theme/build/blocks/x/index.js',
		'pw-theme/functions.php',
		'pw-theme/inc/bootstrap.php',
		'pw-theme/inc/styleguide/route.php',
		'pw-theme/parts/header.html',
		'pw-theme/patterns/hero.php',
		'pw-theme/readme.txt',
		'pw-theme/screenshot.png',
		'pw-theme/style.css',
		'pw-theme/templates/index.html',
		'pw-theme/theme.json',
	] );
	assert.deepEqual( await verifyZip( zip ), [] );
} );

test( 'buildZip replaces a stale dist/pw-theme/ and zip', async ( t ) => {
	const root = await fixture( t );
	await mkdir( join( root, 'dist/pw-theme/old' ), { recursive: true } );
	await writeFile( join( root, 'dist/pw-theme/old/stale.php' ), '' );
	await writeFile( join( root, 'dist/pw-theme-3.4.5.zip' ), 'not a zip' );

	const zip = await buildZip( { root, build: false } );
	assert.equal( basename( zip ), 'pw-theme-3.4.5.zip' );
	assert.ok(
		! ( await entries( zip ) ).some( ( e ) => e.includes( 'stale' ) )
	);
} );

test( 'buildZip refuses when style.css Version differs from package.json', async ( t ) => {
	const root = await fixture( t );
	await writeFile(
		join( root, 'package.json' ),
		JSON.stringify( { version: '9.9.9' } )
	);
	await assert.rejects( buildZip( { root, build: false } ), /9\.9\.9/ );
} );
