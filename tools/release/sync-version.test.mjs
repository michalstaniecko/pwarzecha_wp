// Theme version sync (#95): package.json `version` is the only source, style.css follows.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { mkdtemp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { promisify } from 'node:util';
import { readHeaderVersion, setHeaderVersion } from './sync-version.mjs';

const run = promisify( execFile );
const script = fileURLToPath(
	new URL( './sync-version.mjs', import.meta.url )
);

const CSS = `/*
Theme Name: Samoobrona Przemek
Version: 0.1.0
Requires at least: 7.1
Text Domain: pw-theme
*/
`;

test( 'readHeaderVersion reads the Version header', () => {
	assert.equal( readHeaderVersion( CSS ), '0.1.0' );
	assert.equal( readHeaderVersion( '/* Theme Name: X */' ), null );
} );

test( 'setHeaderVersion rewrites only the Version line', () => {
	const out = setHeaderVersion( CSS, '1.2.3' );
	assert.equal( out, CSS.replace( 'Version: 0.1.0', 'Version: 1.2.3' ) );
} );

test( 'setHeaderVersion fails when the header has no Version line', () => {
	assert.throws(
		() => setHeaderVersion( '/* Theme Name: X */', '1.0.0' ),
		/Version/
	);
} );

async function fixture( pkgVersion, cssVersion ) {
	const root = await mkdtemp( join( tmpdir(), 'pw-sync-version-' ) );
	await mkdir( join( root, 'theme' ) );
	await writeFile(
		join( root, 'package.json' ),
		JSON.stringify( { version: pkgVersion } )
	);
	await writeFile(
		join( root, 'theme/style.css' ),
		CSS.replace( '0.1.0', cssVersion )
	);
	return root;
}

test( 'CLI writes package.json version into theme/style.css', async ( t ) => {
	const root = await fixture( '2.0.0', '0.1.0' );
	t.after( () => rm( root, { recursive: true, force: true } ) );
	await run( process.execPath, [ script, '--root', root ] );
	const css = await readFile( join( root, 'theme/style.css' ), 'utf8' );
	assert.equal( readHeaderVersion( css ), '2.0.0' );
} );

test( 'CLI --check exits 1 on mismatch and leaves style.css untouched', async ( t ) => {
	const root = await fixture( '2.0.0', '0.1.0' );
	t.after( () => rm( root, { recursive: true, force: true } ) );
	await assert.rejects(
		run( process.execPath, [ script, '--check', '--root', root ] ),
		( error ) => {
			assert.equal( error.code, 1 );
			assert.match( error.stderr, /0\.1\.0.*2\.0\.0|2\.0\.0.*0\.1\.0/ );
			return true;
		}
	);
	const css = await readFile( join( root, 'theme/style.css' ), 'utf8' );
	assert.equal( readHeaderVersion( css ), '0.1.0' );
} );

test( 'CLI --check passes when versions match', async ( t ) => {
	const root = await fixture( '2.0.0', '2.0.0' );
	t.after( () => rm( root, { recursive: true, force: true } ) );
	await run( process.execPath, [ script, '--check', '--root', root ] );
} );
