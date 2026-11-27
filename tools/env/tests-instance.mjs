/**
 * Lifecycle of the disposable wp-env tests instance (MariaDB, ADR 0011/0015).
 *
 * The tests instance lives only for one run: start → command → destroy.
 * `destroy` (containers, volumes, network) runs on success, failure, throw
 * and Ctrl+C; the dev instance (.wp-env.json) is never addressed.
 */

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
 * Runs a command against a freshly started tests instance and destroys it afterwards.
 *
 * @param {string[]}                            command      Argv of the command to run once the instance is up.
 * @param {Object}                              deps
 * @param {(argv: string[]) => Promise<number>} deps.exec    Runs an argv, resolves with its exit code.
 * @param {import('node:events').EventEmitter}  deps.signals Source of SIGINT/SIGTERM (usually `process`).
 * @return {Promise<number>} Exit code: the first failure, or 130/143 when interrupted.
 */
export async function runInTestsInstance( command, { exec, signals } ) {
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
		if ( code === 0 && ! interrupted ) {
			code = await exec( command );
		}
	} finally {
		await exec( wpEnv( 'destroy', '--force' ) );
		for ( const [ signal, handler ] of Object.entries( handlers ) ) {
			signals.off( signal, handler );
		}
	}
	return interrupted || code;
}
