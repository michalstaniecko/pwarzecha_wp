import { test } from 'node:test';
import assert from 'node:assert/strict';
import { writeFile, readFile } from 'node:fs/promises';
import { join } from 'node:path';
import { cli, tempDir } from './cli-fixture.mjs';

const run = cli( new URL( './tokens-css.mjs', import.meta.url ).pathname );

const tokens = {
	name: 'T',
	color: {
		themes: [ { id: 'light' } ],
		tokens: [
			{ name: 'base', value: '#fff' },
			{ name: 'ink', value: '#111' },
		],
	},
};
const expected =
	'/* T — generated from tokens.json */\n:root, [data-theme="light"] {\n  --base: #fff;\n  --ink: #111;\n}\n';

async function dir( t ) {
	const d = await tempDir( t, 'tokens-css-' );
	await writeFile( join( d, 'tokens.json' ), JSON.stringify( tokens ) );
	return d;
}

test( 'compiles tokens.json into the given tokens.css', async ( t ) => {
	const d = await dir( t );
	const r = await run( [
		join( d, 'tokens.json' ),
		join( d, 'tokens.css' ),
	] );
	assert.equal( r.code, 0 );
	assert.equal( await readFile( join( d, 'tokens.css' ), 'utf8' ), expected );
} );

test( 'without an output path prints tokens.css to stdout', async ( t ) => {
	const d = await dir( t );
	const r = await run( [ join( d, 'tokens.json' ) ] );
	assert.equal( r.code, 0 );
	assert.equal( r.stdout, expected );
} );

test( '--check passes when tokens.css matches tokens.json', async ( t ) => {
	const d = await dir( t );
	await writeFile( join( d, 'tokens.css' ), expected );
	const r = await run( [
		'--check',
		join( d, 'tokens.json' ),
		join( d, 'tokens.css' ),
	] );
	assert.equal( r.code, 0 );
} );

test( '--check fails and lists the differing lines when tokens.css is stale', async ( t ) => {
	const d = await dir( t );
	await writeFile(
		join( d, 'tokens.css' ),
		expected.replace( '--ink: #111;', '--ink: #222;' )
	);
	const r = await run( [
		'--check',
		join( d, 'tokens.json' ),
		join( d, 'tokens.css' ),
	] );
	assert.equal( r.code, 1 );
	assert.match( r.stderr, /- {3}--ink: #111;/ );
	assert.match( r.stderr, /\+ {3}--ink: #222;/ );
	assert.doesNotMatch( r.stderr, /--base/ );
} );

test( 'wrong usage exits with 2 and prints usage', async () => {
	const r = await run( [ '--check', 'only-one.json' ] );
	assert.equal( r.code, 2 );
	assert.match( r.stderr, /Usage/ );
} );

test( 'a missing or unreadable input exits with 2, not as drift', async ( t ) => {
	const d = await dir( t );
	const missing = await run( [
		'--check',
		join( d, 'nope.json' ),
		join( d, 'tokens.css' ),
	] );
	assert.equal( missing.code, 2 );
	assert.match( missing.stderr, /nope\.json/ );
	assert.doesNotMatch( missing.stderr, /at .*\.mjs:\d+/ );
	await writeFile( join( d, 'broken.json' ), '{ not json' );
	const broken = await run( [ join( d, 'broken.json' ) ] );
	assert.equal( broken.code, 2 );
	assert.match( broken.stderr, /broken\.json/ );
} );
