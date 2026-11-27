#!/usr/bin/env node
/**
 * Playground server with the e2e blueprint (#37, ADR 0011).
 *
 * Used by Playwright's globalSetup and by `npm run wp:playground` (manual preview:
 * http://127.0.0.1:9400, admin / password). Nothing persists: the site lives in memory and
 * Playground CLI removes its temp dir on exit (ADR 0015).
 */
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { runCLI } from '@wp-playground/cli';
import { PHP_VERSION, WP_VERSION, mounts, root } from './site.mjs';

export const BLUEPRINT = join( root, 'tests/e2e/blueprint.json' );
export const BOOT_TIMEOUT_MS = 180_000;

/**
 * Boots WordPress from the blueprint.
 *
 * @param {Object} options
 * @param {number} options.port Port on 127.0.0.1.
 * @return {Promise<{url: string, stop: () => Promise<void>}>} Running server.
 */
export async function startServer( { port } ) {
	let timer;
	const timeout = new Promise( ( resolve, reject ) => {
		timer = setTimeout(
			() =>
				reject(
					new Error(
						`Playground did not boot in ${ BOOT_TIMEOUT_MS / 1000 } s.`
					)
				),
			BOOT_TIMEOUT_MS
		);
	} );
	try {
		const server = await Promise.race( [
			runCLI( {
				command: 'server',
				php: PHP_VERSION,
				wp: WP_VERSION,
				port,
				mount: mounts(),
				blueprint: JSON.parse( readFileSync( BLUEPRINT, 'utf8' ) ),
				verbosity: 'quiet',
			} ),
			timeout,
		] );
		return {
			url: server.serverUrl,
			stop: () => server[ Symbol.asyncDispose ](),
		};
	} finally {
		clearTimeout( timer );
	}
}

if ( process.argv[ 1 ] === import.meta.filename ) {
	const port = Number( process.env.PW_PLAYGROUND_PORT ?? 9400 );
	const { url, stop } = await startServer( { port } );
	console.log( `WordPress: ${ url } (admin / password). Ctrl+C stops it.` );
	const shutdown = async () => {
		await stop();
		process.exit( 0 );
	};
	process.on( 'SIGINT', shutdown );
	process.on( 'SIGTERM', shutdown );
}
