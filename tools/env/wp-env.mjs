#!/usr/bin/env node
/**
 * wp-env wrapper for npm scripts (ADR 0011 environment (b), ADR 0015 cleanup).
 *
 *   start | stop                 dev instance (.wp-env.json, 8888, persistent DB)
 *   reset                        destroy the tests instance (.wp-env.tests.json); never dev
 *   cli [args…]                  `wp …` in the dev instance
 *   db:export                    dev DB → .data/pw-<date>.sql + .data/latest.sql
 *   db:import [file] [--yes]     .data/<file> (default latest.sql) → dev DB, asks first
 *   seed [args…]                 `wp pw seed …` in the dev instance
 *   test:php                     PHPUnit integration suite on a fresh tests instance, then destroy
 *   test:e2e                     Playwright against a fresh tests instance, then destroy
 */
import { spawn } from 'node:child_process';
import { copyFileSync, existsSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { createInterface } from 'node:readline/promises';
import { fileURLToPath } from 'node:url';
import { DATA_DIR, DATA_MOUNT, LATEST, dumpName, importTarget } from './db.mjs';
import {
	destroyTestsInstance,
	runInTestsInstance,
	testsWorkDir,
	wpEnv,
} from './tests-instance.mjs';

/** Where `.wp-env.tests.json` mounts the repository (relative to the WordPress root). */
export const REPO_MOUNT = 'pw-repo';

const root = fileURLToPath( new URL( '../../', import.meta.url ) );
const TESTS_PORT = Number( process.env.WP_ENV_PORT ?? 8889 );
const workDir = () => testsWorkDir( root );

function exec( argv, env = {} ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( argv[ 0 ], argv.slice( 1 ), {
			cwd: root,
			stdio: 'inherit',
			env: { ...process.env, ...env },
		} );
		child.on( 'error', reject );
		child.on( 'exit', ( code, signal ) =>
			resolve( code ?? ( signal === 'SIGINT' ? 130 : 1 ) )
		);
	} );
}

const dev = ( ...args ) => exec( [ 'npx', 'wp-env', ...args ] );
const devWp = ( ...args ) => dev( 'run', 'cli', 'wp', ...args );

async function dbExport() {
	mkdirSync( join( root, DATA_DIR ), { recursive: true } );
	const name = dumpName( new Date() );
	const code = await devWp( 'db', 'export', `${ DATA_MOUNT }/${ name }` );
	if ( code === 0 ) {
		copyFileSync(
			join( root, DATA_DIR, name ),
			join( root, DATA_DIR, LATEST )
		);
		console.log(
			`Saved ${ DATA_DIR }/${ name } and ${ DATA_DIR }/${ LATEST }`
		);
	}
	return code;
}

async function dbImport( args ) {
	const { host, container } = importTarget(
		args.find( ( a ) => a !== '--yes' ),
		root
	);
	if ( ! existsSync( host ) ) {
		console.error( `No such dump: ${ host }` );
		return 1;
	}
	if ( ! args.includes( '--yes' ) ) {
		if ( ! process.stdin.isTTY ) {
			console.error(
				'Refusing to overwrite the dev DB without a terminal; pass --yes.'
			);
			return 1;
		}
		const rl = createInterface( {
			input: process.stdin,
			output: process.stdout,
		} );
		const answer = await rl.question(
			`Overwrite the dev database (8888) with ${ host }? Run db:export first if unsure. [y/N] `
		);
		rl.close();
		if ( ! /^(y|yes|t|tak)$/i.test( answer.trim() ) ) {
			console.log( 'Aborted.' );
			return 1;
		}
	}
	return devWp( 'db', 'import', container );
}

/**
 * Until #37 adds the PHPUnit / Playwright config there is nothing to run.
 *
 * @param {string[]} files  Any of these makes the suite runnable.
 * @param {string}   ticket Ticket that adds them.
 * @return {boolean} True when the suite should be skipped.
 */
function missing( files, ticket ) {
	if ( files.some( ( file ) => existsSync( join( root, file ) ) ) ) {
		return false;
	}
	console.log( `brak ${ files.join( ' / ' ) } – wypełnia ${ ticket }` );
	return true;
}

async function testPhp( args ) {
	if ( missing( [ 'phpunit.xml.dist' ], '#37' ) ) {
		return 0;
	}
	return runInTestsInstance(
		wpEnv(
			'run',
			'cli',
			`--env-cwd=${ REPO_MOUNT }`,
			'vendor/bin/phpunit',
			'-c',
			'phpunit.xml.dist',
			'--testsuite',
			'integration',
			...args
		),
		{ exec, signals: process, workDir }
	);
}

async function testE2e( args ) {
	if (
		missing( [ 'playwright.config.ts', 'playwright.config.js' ], '#37' )
	) {
		return 0;
	}
	// Same starting state as the Playground blueprint (tests/e2e/blueprint.json, #54).
	const seed = wpEnv( 'run', 'cli', 'wp', 'pw', 'seed', '--force' );
	return runInTestsInstance(
		[ seed, [ 'npx', 'playwright', 'test', ...args ] ],
		{
			exec: ( argv ) =>
				exec( argv, {
					PW_E2E_BASE_URL: `http://localhost:${ TESTS_PORT }`,
				} ),
			signals: process,
			workDir,
		}
	);
}

const commands = {
	start: ( args ) => {
		mkdirSync( join( root, DATA_DIR ), { recursive: true } );
		return dev( 'start', ...args );
	},
	stop: ( args ) => dev( 'stop', ...args ),
	reset: () => destroyTestsInstance( { exec, workDir } ),
	cli: ( args ) => devWp( ...args ),
	'db:export': dbExport,
	'db:import': dbImport,
	seed: ( args ) => devWp( 'pw', 'seed', ...args ),
	'test:php': testPhp,
	'test:e2e': testE2e,
};

if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	const [ name, ...args ] = process.argv.slice( 2 );
	if ( ! commands[ name ] ) {
		console.error(
			`Usage: node tools/env/wp-env.mjs <${ Object.keys( commands ).join( '|' ) }> [args…]`
		);
		process.exit( 2 );
	}
	try {
		process.exitCode = await commands[ name ]( args );
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}
