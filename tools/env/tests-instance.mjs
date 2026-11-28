/**
 * Lifecycle of the disposable wp-env tests instance (MariaDB, ADR 0011/0015).
 *
 * The tests instance lives only for one run: start → command → destroy.
 * `destroy` (containers, volumes, network) runs on success, failure, throw
 * and Ctrl+C; the dev instance (.wp-env.json) is never addressed.
 *
 * wp-env 12 records the runtime only after a successful start, so after a failed start
 * (e.g. MariaDB exits) its `destroy` answers "Environment not initialized" and leaves the
 * containers, volumes and network behind. Then we take the instance down ourselves with
 * the Compose file wp-env generated in its work directory (~/.wp-env/…).
 */
import { join } from 'node:path';
import { loadConfig } from '@wordpress/env/lib/config';

export const TESTS_CONFIG = '.wp-env.tests.json';

const SIGNAL_CODES = { SIGINT: 130, SIGTERM: 143 };

const wpEnv = ( ...args ) => [
	'npx',
	'wp-env',
	...args,
	'--config',
	TESTS_CONFIG,
];

/**
 * Work directory of the tests instance (~/.wp-env/<name>-<hash>), as wp-env computes it.
 *
 * @param {string} root Repository root.
 * @return {Promise<string>} Absolute path; the directory may not exist.
 */
export async function testsWorkDir( root ) {
	return ( await loadConfig( root, TESTS_CONFIG ) ).workDirectoryPath;
}

/**
 * Destroys the tests instance: `wp-env destroy`, or Compose `down` when wp-env refuses.
 *
 * Built images (`--rmi local`) go, base images (mariadb, wordpress) stay in the cache (ADR 0015).
 *
 * @param {Object}                              deps
 * @param {(argv: string[]) => Promise<number>} deps.exec    Runs an argv, resolves with its exit code.
 * @param {() => Promise<string>}               deps.workDir Resolves the instance's work directory.
 * @return {Promise<number>} Exit code of the last step.
 */
export async function destroyTestsInstance( { exec, workDir } ) {
	if ( ( await exec( wpEnv( 'destroy', '--force' ) ) ) === 0 ) {
		return 0;
	}
	const dir = await workDir();
	const code = await exec( [
		'docker',
		'compose',
		'-f',
		join( dir, 'docker-compose.yml' ),
		'down',
		'--volumes',
		'--remove-orphans',
		'--rmi',
		'local',
	] );
	return code || exec( [ 'rm', '-rf', dir ] );
}

/**
 * Runs a command (or several, in order, until one fails) against a freshly started tests
 * instance and destroys it afterwards.
 *
 * @param {string[]|string[][]}                 command        Argv of the command to run once the instance is up, or a list of them.
 * @param {Object}                              deps
 * @param {(argv: string[]) => Promise<number>} deps.exec      Runs an argv, resolves with its exit code.
 * @param {import('node:events').EventEmitter}  deps.signals   Source of SIGINT/SIGTERM (usually `process`).
 * @param {() => Promise<string>}               [deps.workDir] Work directory for the fallback cleanup.
 * @return {Promise<number>} Exit code: the first failure, or 130/143 when interrupted.
 */
export async function runInTestsInstance(
	command,
	{ exec, signals, workDir = () => testsWorkDir( process.cwd() ) }
) {
	let interrupted = 0;
	const handlers = Object.fromEntries(
		Object.entries( SIGNAL_CODES ).map( ( [ signal, code ] ) => [
			signal,
			() => {
				interrupted ||= code;
			},
		] )
	);
	for ( const [ signal, handler ] of Object.entries( handlers ) ) {
		signals.on( signal, handler );
	}

	let code = 0;
	try {
		code = await exec( wpEnv( 'start' ) );
		const commands = Array.isArray( command[ 0 ] ) ? command : [ command ];
		for ( const argv of commands ) {
			if ( code !== 0 || interrupted ) {
				break;
			}
			code = await exec( argv );
		}
	} finally {
		await destroyTestsInstance( { exec, workDir } );
		for ( const [ signal, handler ] of Object.entries( handlers ) ) {
			signals.off( signal, handler );
		}
	}
	return interrupted || code;
}
