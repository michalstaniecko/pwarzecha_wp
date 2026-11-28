import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { IMAGE, dockerArgs } from './visual.mjs';

const base = {
	root: '/repo',
	cache: '/home/u/.wordpress-playground',
	user: '501:20',
	args: [],
};

test( 'runs only the visual project in the pinned Playwright image as the host user', () => {
	const args = dockerArgs( base );

	assert.deepEqual( args.slice( 0, 5 ), [
		'run',
		'--rm',
		'--ipc=host',
		'--user',
		'501:20',
	] );
	assert.ok( args.includes( 'PW_VISUAL=1' ) );
	assert.ok( args.includes( '/repo:/work' ) );
	assert.ok(
		args.includes(
			'/home/u/.wordpress-playground:/tmp/pw-home/.wordpress-playground'
		)
	);
	assert.deepEqual( args.slice( args.indexOf( IMAGE ) ), [
		IMAGE,
		'npx',
		'playwright',
		'test',
		'--project=visual',
	] );
	assert.ok( ! args.includes( '--update-snapshots' ) );
} );

test( 'update mode and extra arguments go to playwright test', () => {
	const args = dockerArgs( {
		...base,
		update: true,
		args: [ '--grep', 'x' ],
	} );

	assert.deepEqual( args.slice( -3 ), [
		'--update-snapshots',
		'--grep',
		'x',
	] );
} );

test( 'image matches the pinned @playwright/test version', () => {
	const pkg = JSON.parse(
		readFileSync( new URL( '../../package.json', import.meta.url ) )
	);

	assert.equal(
		IMAGE,
		`mcr.microsoft.com/playwright:v${ pkg.devDependencies[ '@playwright/test' ] }-noble`
	);
} );
