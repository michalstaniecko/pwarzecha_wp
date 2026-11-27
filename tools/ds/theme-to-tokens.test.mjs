import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { mkdtemp, writeFile, readFile, copyFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { promisify } from 'node:util';

const script = new URL( './theme-to-tokens.mjs', import.meta.url ).pathname;
const fixture = new URL( './fixtures/ds-tokens.json', import.meta.url )
	.pathname;
const themeFile = new URL( '../../theme/theme.json', import.meta.url ).pathname;
const run = ( args ) =>
	promisify( execFile )( process.execPath, [ script, ...args ] ).then(
		( { stdout, stderr } ) => ( { code: 0, stdout, stderr } ),
		( e ) => ( { code: e.code, stdout: e.stdout, stderr: e.stderr } )
	);

// A copy of the DS fixture and a theme.json with the primary color changed.
async function workdir() {
	const d = await mkdtemp( join( tmpdir(), 'theme-to-tokens-' ) );
	await copyFile( fixture, join( d, 'tokens.json' ) );
	const theme = JSON.parse( await readFile( themeFile, 'utf8' ) );
	theme.settings.color.palette.find( ( p ) => p.slug === 'primary' ).color =
		'#123456';
	await writeFile( join( d, 'theme.json' ), JSON.stringify( theme ) );
	return d;
}

test( 'updates only the changed value in tokens.json and keeps the rest byte for byte', async () => {
	const d = await workdir();
	const r = await run( [
		join( d, 'theme.json' ),
		join( d, 'tokens.json' ),
	] );
	assert.equal( r.code, 0 );
	const before = await readFile( fixture, 'utf8' );
	const after = await readFile( join( d, 'tokens.json' ), 'utf8' );
	assert.equal(
		after,
		before.replace(
			'"name": "primary",\n        "value": "#11213b"',
			'"name": "primary",\n        "value": "#123456"'
		)
	);
	assert.notEqual( after, before );
} );

test( 'with a tokens.css path also compiles tokens.css from the updated tokens.json', async () => {
	const d = await workdir();
	const r = await run( [
		join( d, 'theme.json' ),
		join( d, 'tokens.json' ),
		join( d, 'tokens.css' ),
	] );
	assert.equal( r.code, 0 );
	const css = await readFile( join( d, 'tokens.css' ), 'utf8' );
	assert.match( css, /--primary: #123456;/ );
} );

test( 'is deterministic: a second run changes nothing', async () => {
	const d = await workdir();
	const args = [ join( d, 'theme.json' ), join( d, 'tokens.json' ) ];
	await run( args );
	const first = await readFile( join( d, 'tokens.json' ), 'utf8' );
	await run( args );
	assert.equal( await readFile( join( d, 'tokens.json' ), 'utf8' ), first );
} );

test( '--check passes for the repo theme.json and the DS fixture', async () => {
	const r = await run( [ '--check', themeFile, fixture ] );
	assert.equal( r.code, 0, r.stderr );
} );

test( '--check fails with 1 and names the differing token and both values', async () => {
	const d = await workdir();
	const r = await run( [
		'--check',
		join( d, 'theme.json' ),
		join( d, 'tokens.json' ),
	] );
	assert.equal( r.code, 1 );
	assert.match( r.stderr, /color\/primary/ );
	assert.match( r.stderr, /#123456/ );
	assert.match( r.stderr, /#11213b/ );
	assert.doesNotMatch( r.stderr, /color\/base/ );
} );

test( 'wrong usage or an unreadable file exits with 2', async () => {
	const usage = await run( [ '--check', themeFile ] );
	assert.equal( usage.code, 2 );
	assert.match( usage.stderr, /Usage/ );
	const missing = await run( [ '--check', themeFile, '/nope/tokens.json' ] );
	assert.equal( missing.code, 2 );
	assert.match( missing.stderr, /nope/ );
} );
