// Installable theme ZIP validation (#95, ADR 0006/0007): one root `pw-theme/`, required files,
// version consistency, no development code, size limit.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { mkdtemp, mkdir, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { promisify } from 'node:util';
import { verifyZip } from './verify-zip.mjs';

const run = promisify( execFile );
const script = fileURLToPath( new URL( './verify-zip.mjs', import.meta.url ) );

const VALID = {
	'pw-theme/style.css':
		'/*\nTheme Name: Samoobrona Przemek\nVersion: 1.2.3\n*/\n',
	'pw-theme/theme.json': '{}',
	'pw-theme/functions.php': '<?php\n',
	'pw-theme/templates/index.html': '<!-- wp:post-content /-->',
	'pw-theme/build/blocks-manifest.php': '<?php return array();\n',
};

/**
 * Builds a ZIP from a map of entry path → content in a fresh temp dir.
 *
 * @param {import('node:test').TestContext} t
 * @param {Record<string, string>}          files
 * @param {string}                          name
 */
async function makeZip( t, files, name = 'pw-theme-1.2.3.zip' ) {
	const dir = await mkdtemp( join( tmpdir(), 'pw-verify-zip-' ) );
	t.after( () => rm( dir, { recursive: true, force: true } ) );
	const src = join( dir, 'src' );
	for ( const [ path, content ] of Object.entries( files ) ) {
		await mkdir( dirname( join( src, path ) ), { recursive: true } );
		await writeFile( join( src, path ), content );
	}
	const zip = join( dir, name );
	await run( 'zip', [ '-r', '-X', '-q', zip, '.' ], { cwd: src } );
	return zip;
}

test( 'accepts a well-formed theme ZIP', async ( t ) => {
	const zip = await makeZip( t, VALID );
	assert.deepEqual( await verifyZip( zip ), [] );
} );

test( 'rejects two root directories', async ( t ) => {
	const zip = await makeZip( t, { ...VALID, 'theme/style.css': '' } );
	const errors = await verifyZip( zip );
	assert.ok(
		errors.some( ( e ) => /root/i.test( e ) ),
		errors.join( '\n' )
	);
} );

test( 'rejects a root directory other than pw-theme/', async ( t ) => {
	const files = Object.fromEntries(
		Object.entries( VALID ).map( ( [ k, v ] ) => [
			k.replace( 'pw-theme/', 'theme/' ),
			v,
		] )
	);
	const errors = await verifyZip( await makeZip( t, files ) );
	assert.ok(
		errors.some( ( e ) => /root/i.test( e ) ),
		errors.join( '\n' )
	);
} );

test( 'rejects a ZIP without style.css', async ( t ) => {
	const files = { ...VALID };
	delete files[ 'pw-theme/style.css' ];
	const errors = await verifyZip( await makeZip( t, files ) );
	assert.ok(
		errors.some( ( e ) => e.includes( 'pw-theme/style.css' ) ),
		errors.join( '\n' )
	);
} );

test( 'rejects each missing required file', async ( t ) => {
	const files = { 'pw-theme/style.css': VALID[ 'pw-theme/style.css' ] };
	const errors = await verifyZip( await makeZip( t, files ) );
	for ( const required of [
		'pw-theme/theme.json',
		'pw-theme/templates/index.html',
		'pw-theme/build/blocks-manifest.php',
		'pw-theme/functions.php',
	] ) {
		assert.ok(
			errors.some( ( e ) => e.includes( required ) ),
			`${ required }: ${ errors.join( '\n' ) }`
		);
	}
} );

test( 'rejects node_modules/', async ( t ) => {
	const zip = await makeZip( t, {
		...VALID,
		'pw-theme/node_modules/x/index.js': '',
	} );
	const errors = await verifyZip( zip );
	assert.ok(
		errors.some( ( e ) => e.includes( 'pw-theme/node_modules/' ) ),
		errors.join( '\n' )
	);
} );

test( 'rejects every forbidden path', async ( t ) => {
	const forbidden = [
		'pw-theme/vendor/autoload.php',
		'pw-theme/tests/a.php',
		'pw-theme/src/blocks/x/edit.js',
		'pw-theme/.gitignore',
		'pw-theme/.github/workflows/ci.yml',
		'pw-theme/build/blocks/x/index.js.map',
		'pw-theme/inc/x.test.js',
		'pw-theme/assets/.DS_Store',
		'pw-theme/docker/Dockerfile',
		'pw-theme/inc/pw-preview-mode.php',
		'pw-theme/.env.local',
		'pw-theme/composer.json',
		'pw-theme/package-lock.json',
	];
	const zip = await makeZip(
		t,
		Object.fromEntries( [
			...Object.entries( VALID ),
			...forbidden.map( ( p ) => [ p, 'x' ] ),
		] )
	);
	const errors = await verifyZip( zip );
	for ( const path of forbidden ) {
		assert.ok(
			errors.some( ( e ) => e.includes( path ) ),
			`${ path }: ${ errors.join( '\n' ) }`
		);
	}
} );

test( 'rejects __MACOSX/', async ( t ) => {
	const zip = await makeZip( t, {
		...VALID,
		'__MACOSX/pw-theme/._style.css': 'x',
	} );
	const errors = await verifyZip( zip );
	assert.ok(
		errors.some( ( e ) => e.includes( '__MACOSX/' ) ),
		errors.join( '\n' )
	);
} );

test( 'rejects style.css Version different from the file name', async ( t ) => {
	const zip = await makeZip( t, VALID, 'pw-theme-1.2.4.zip' );
	const errors = await verifyZip( zip );
	assert.ok(
		errors.some( ( e ) => /1\.2\.3/.test( e ) && /1\.2\.4/.test( e ) ),
		errors.join( '\n' )
	);
} );

test( 'rejects a file name that is not pw-theme-X.Y.Z.zip', async ( t ) => {
	const errors = await verifyZip( await makeZip( t, VALID, 'theme.zip' ) );
	assert.ok(
		errors.some( ( e ) => e.includes( 'pw-theme-X.Y.Z.zip' ) ),
		errors.join( '\n' )
	);
} );

test( 'checks the --tag against style.css Version', async ( t ) => {
	const zip = await makeZip( t, VALID );
	assert.deepEqual( await verifyZip( zip, { tag: 'v1.2.3' } ), [] );
	const errors = await verifyZip( zip, { tag: 'v1.3.0' } );
	assert.ok(
		errors.some( ( e ) => e.includes( 'v1.3.0' ) ),
		errors.join( '\n' )
	);
} );

test( 'rejects a ZIP above the size limit', async ( t ) => {
	const zip = await makeZip( t, VALID );
	const errors = await verifyZip( zip, { maxBytes: 10 } );
	assert.ok(
		errors.some( ( e ) => /MB|bytes/.test( e ) ),
		errors.join( '\n' )
	);
} );

test( 'CLI exits 1 and lists errors; exits 0 on a valid ZIP', async ( t ) => {
	const good = await makeZip( t, VALID );
	await run( process.execPath, [ script, good ] );

	const bad = await makeZip( t, { ...VALID, 'pw-theme/tests/a.php': '' } );
	await assert.rejects(
		run( process.execPath, [ script, bad ] ),
		( error ) => {
			assert.equal( error.code, 1 );
			assert.match( error.stderr, /pw-theme\/tests\// );
			return true;
		}
	);
} );
