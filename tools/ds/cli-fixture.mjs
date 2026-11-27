// Shared helpers for the CLI tests in tools/ds/ (not a test file itself).
import { execFile } from 'node:child_process';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { promisify } from 'node:util';

/**
 * Runner for a CLI script: resolves with the exit code and output, never rejects.
 *
 * @param {string} script Absolute path of the script.
 * @return {(args: string[]) => Promise<{code: number, stdout: string, stderr: string}>} Runner.
 */
export function cli( script ) {
	return ( args ) =>
		promisify( execFile )( process.execPath, [ script, ...args ] ).then(
			( { stdout, stderr } ) => ( { code: 0, stdout, stderr } ),
			( e ) => ( { code: e.code, stdout: e.stdout, stderr: e.stderr } )
		);
}

/**
 * Temporary directory removed after the test.
 *
 * @param {import('node:test').TestContext} t      Test context.
 * @param {string}                          prefix Directory name prefix.
 * @return {Promise<string>} Directory path.
 */
export async function tempDir( t, prefix ) {
	const dir = await mkdtemp( join( tmpdir(), prefix ) );
	t.after( () => rm( dir, { recursive: true, force: true } ) );
	return dir;
}
