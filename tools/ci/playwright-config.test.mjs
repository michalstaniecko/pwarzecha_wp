/**
 * Playwright reporters per environment (#45): annotations + blob in GitHub Actions (blob for a later
 * `merge-reports` of shards), HTML for the failure artifact, `list` locally.
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';

/**
 * @param {Record<string, string|undefined>} env Variables set while the config loads.
 * @return {Promise<string[]>} Reporter names.
 */
async function reporters( env ) {
	const saved = { ...process.env };
	Object.assign( process.env, env );
	for ( const [ key, value ] of Object.entries( env ) ) {
		if ( value === undefined ) {
			delete process.env[ key ];
		}
	}
	try {
		const { default: config } = await import(
			`../../playwright.config.ts?${ JSON.stringify( env ) }`
		);
		return config.reporter.map( ( [ name ] ) => name );
	} finally {
		process.env = saved;
	}
}

test( 'playwright.config: GitHub Actions gets annotations, blob and HTML', async () => {
	assert.deepEqual(
		await reporters( { CI: 'true', GITHUB_ACTIONS: 'true' } ),
		[ 'blob', 'github', 'html' ]
	);
} );

test( 'playwright.config: other CI (Playwright container locally) and the host', async () => {
	assert.deepEqual(
		await reporters( { CI: 'true', GITHUB_ACTIONS: undefined } ),
		[ 'blob', 'html' ]
	);
	assert.deepEqual(
		await reporters( { CI: undefined, GITHUB_ACTIONS: undefined } ),
		[ 'list' ]
	);
} );
