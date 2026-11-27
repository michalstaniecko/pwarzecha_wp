#!/usr/bin/env node
/**
 * Test-environment housekeeping (ADR 0015).
 *
 *   node tools/env/env.mjs status   what this project runs in Docker and how much it takes
 *   node tools/env/env.mjs clean    remove test leftovers (wp-env tests instance, pw-test*), never the dev instance
 */
import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { TEST_PREFIX, testResources } from './resources.mjs';

const root = fileURLToPath( new URL( '../../', import.meta.url ) );
const TESTS_CONFIG = '.wp-env.tests.json';
const DEV_CONFIG = '.wp-env.json';

function run( cmd, args, { quiet = false } = {} ) {
	const r = spawnSync( cmd, args, {
		cwd: root,
		encoding: 'utf8',
		stdio: quiet ? 'pipe' : 'inherit',
	} );
	return { code: r.status ?? 1, out: r.stdout ?? '' };
}

function lines( out ) {
	return out
		.split( '\n' )
		.map( ( l ) => l.trim() )
		.filter( Boolean );
}

function list( kind ) {
	const args = {
		container: [ 'ps', '-a', '--format', '{{.Names}}' ],
		volume: [ 'volume', 'ls', '--format', '{{.Name}}' ],
		network: [ 'network', 'ls', '--format', '{{.Name}}' ],
	}[ kind ];
	return testResources( lines( run( 'docker', args, { quiet: true } ).out ) );
}

function wpEnv( args, config ) {
	return run( 'npx', [ 'wp-env', ...args, '--config', config ] );
}

function status() {
	for ( const config of [ DEV_CONFIG, TESTS_CONFIG ] ) {
		if ( existsSync( join( root, config ) ) ) {
			console.log( `\n# wp-env (${ config })` );
			wpEnv( [ 'status' ], config );
		}
	}
	console.log( `\n# ${ TEST_PREFIX }* leftovers (should be empty)` );
	for ( const kind of [ 'container', 'volume', 'network' ] ) {
		const names = list( kind );
		console.log(
			`${ kind }s: ${ names.length ? names.join( ', ' ) : '-' }`
		);
	}
	console.log( '\n# docker system df' );
	run( 'docker', [ 'system', 'df' ] );
}

function clean() {
	console.log( '# docker system df (before)' );
	run( 'docker', [ 'system', 'df' ] );

	if ( existsSync( join( root, TESTS_CONFIG ) ) ) {
		console.log( `\n# wp-env destroy (${ TESTS_CONFIG })` );
		wpEnv( [ 'destroy', '--force' ], TESTS_CONFIG );
	}

	const removals = [
		[ 'container', [ 'rm', '-f', '-v' ] ],
		[ 'network', [ 'network', 'rm' ] ],
		[ 'volume', [ 'volume', 'rm', '-f' ] ],
	];
	for ( const [ kind, cmd ] of removals ) {
		const names = list( kind );
		if ( names.length ) {
			console.log( `\n# removing ${ kind }s: ${ names.join( ', ' ) }` );
			run( 'docker', [ ...cmd, ...names ] );
		}
	}

	console.log( '\n# docker system df (after)' );
	run( 'docker', [ 'system', 'df' ] );
}

if ( run( 'docker', [ 'info' ], { quiet: true } ).code !== 0 ) {
	console.log( 'Docker is not running: nothing to check or clean.' );
	process.exit( 0 );
}

const command = process.argv[ 2 ];
if ( command === 'status' ) {
	status();
} else if ( command === 'clean' ) {
	clean();
} else {
	console.error( 'Usage: node tools/env/env.mjs <status|clean>' );
	process.exit( 2 );
}
