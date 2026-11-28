import { test } from 'node:test';
import assert from 'node:assert/strict';
import { EventEmitter } from 'node:events';
import { runInTestsInstance, TESTS_CONFIG } from './tests-instance.mjs';

/**
 * Fake process runner: records every argv and answers with the exit code
 * given for the first matching word (default 0).
 *
 * @param {Object<string, number|(() => number)>} codes
 */
function fakeExec( codes = {} ) {
	const calls = [];
	const exec = async ( argv ) => {
		calls.push( argv );
		for ( const [ word, code ] of Object.entries( codes ) ) {
			if ( argv.includes( word ) ) {
				return typeof code === 'function' ? code() : code;
			}
		}
		return 0;
	};
	return { exec, calls };
}

const phpunit = [ 'npx', 'wp-env', 'run', 'cli', 'phpunit' ];

test( 'starts the tests instance, runs the command, then destroys it', async () => {
	const { exec, calls } = fakeExec();
	const code = await runInTestsInstance( phpunit, {
		exec,
		signals: new EventEmitter(),
	} );

	assert.equal( code, 0 );
	assert.deepEqual( calls, [
		[ 'npx', 'wp-env', 'start', '--config', TESTS_CONFIG ],
		phpunit,
		[ 'npx', 'wp-env', 'destroy', '--force', '--config', TESTS_CONFIG ],
	] );
} );

test( 'runs a list of commands in order and stops at the first failure', async () => {
	const seed = [ 'npx', 'wp-env', 'run', 'cli', 'wp', 'pw', 'seed' ];
	const { exec, calls } = fakeExec( { seed: 1 } );
	const code = await runInTestsInstance( [ seed, phpunit ], {
		exec,
		signals: new EventEmitter(),
	} );

	assert.equal( code, 1 );
	assert.deepEqual( calls, [
		[ 'npx', 'wp-env', 'start', '--config', TESTS_CONFIG ],
		seed,
		[ 'npx', 'wp-env', 'destroy', '--force', '--config', TESTS_CONFIG ],
	] );
} );

test( 'destroys the instance and returns the failure when the command fails', async () => {
	const { exec, calls } = fakeExec( { phpunit: 3 } );
	const code = await runInTestsInstance( phpunit, {
		exec,
		signals: new EventEmitter(),
	} );

	assert.equal( code, 3 );
	assert.ok( calls.at( -1 ).includes( 'destroy' ) );
} );

test( 'destroys a half-started instance and skips the command when start fails', async () => {
	const { exec, calls } = fakeExec( { start: 1 } );
	const code = await runInTestsInstance( phpunit, {
		exec,
		signals: new EventEmitter(),
	} );

	assert.equal( code, 1 );
	assert.equal( calls.length, 2 );
	assert.ok( calls[ 1 ].includes( 'destroy' ) );
} );

test( 'destroys the instance when the command throws', async () => {
	const { exec, calls } = fakeExec( {
		phpunit: () => {
			throw new Error( 'spawn failed' );
		},
	} );
	await assert.rejects(
		runInTestsInstance( phpunit, { exec, signals: new EventEmitter() } ),
		/spawn failed/
	);
	assert.ok( calls.at( -1 ).includes( 'destroy' ) );
} );

test( 'on Ctrl+C destroys the instance and reports the interruption', async () => {
	const signals = new EventEmitter();
	const { exec, calls } = fakeExec( {
		phpunit: () => {
			// The child dies with the terminal's SIGINT; we get the signal too.
			signals.emit( 'SIGINT' );
			return 130;
		},
	} );
	const code = await runInTestsInstance( phpunit, { exec, signals } );

	assert.equal( code, 130 );
	assert.ok( calls.at( -1 ).includes( 'destroy' ) );
	assert.equal( signals.listenerCount( 'SIGINT' ), 0 );
	assert.equal( signals.listenerCount( 'SIGTERM' ), 0 );
} );

test( 'an interruption during start skips the command', async () => {
	const signals = new EventEmitter();
	const { exec, calls } = fakeExec( {
		start: () => {
			signals.emit( 'SIGTERM' );
			return 0;
		},
	} );
	const code = await runInTestsInstance( phpunit, { exec, signals } );

	assert.equal( code, 143 );
	assert.equal( calls.length, 2 );
	assert.ok( calls[ 1 ].includes( 'destroy' ) );
} );

test( 'never addresses the dev instance (.wp-env.json)', async () => {
	const { exec, calls } = fakeExec( { phpunit: 1 } );
	await runInTestsInstance( phpunit, {
		exec,
		signals: new EventEmitter(),
	} );

	for ( const argv of calls.filter( ( a ) => a.includes( 'wp-env' ) ) ) {
		if ( argv === phpunit ) {
			continue;
		}
		assert.ok( argv.includes( TESTS_CONFIG ), argv.join( ' ' ) );
	}
	assert.equal( TESTS_CONFIG, '.wp-env.tests.json' );
} );

test( 'falls back to docker compose down when wp-env destroy refuses a never-started instance', async () => {
	// wp-env 12 records the runtime only after a successful start, so after a failed
	// start (e.g. MariaDB exits) `destroy` says "Environment not initialized" and
	// leaves the containers, volumes and network behind.
	const workDir = '/home/u/.wp-env/wp-env-pw-tests-abc123';
	const { exec, calls } = fakeExec( { start: 1, destroy: 1 } );
	const code = await runInTestsInstance( phpunit, {
		exec,
		signals: new EventEmitter(),
		workDir: async () => workDir,
	} );

	assert.equal( code, 1 );
	assert.deepEqual( calls.slice( 2 ), [
		[
			'docker',
			'compose',
			'-f',
			`${ workDir }/docker-compose.yml`,
			'down',
			'--volumes',
			'--remove-orphans',
			'--rmi',
			'local',
		],
		[ 'rm', '-rf', workDir ],
	] );
} );

test( 'does not fall back when wp-env destroy succeeds', async () => {
	const { exec, calls } = fakeExec();
	await runInTestsInstance( phpunit, {
		exec,
		signals: new EventEmitter(),
		workDir: async () => '/w',
	} );

	assert.ok( ! calls.some( ( argv ) => argv[ 0 ] === 'docker' ) );
} );
