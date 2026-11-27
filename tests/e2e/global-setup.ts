/**
 * Boots a fresh Playground site from tests/e2e/blueprint.json for the whole run (#37).
 * The returned function is Playwright's teardown: it stops the server even when tests fail.
 */
import type { FullConfig } from '@playwright/test';
import { startServer } from '../../tools/playground/server.mjs';

export default async function globalSetup( config: FullConfig ) {
	const { port } = new URL( String( config.projects[ 0 ].use.baseURL ) );
	const { stop } = await startServer( { port: Number( port ) } );
	return stop;
}
