/**
 * Once per run (#37): boot a fresh Playground site from tests/e2e/blueprint.json (unless
 * PW_E2E_BASE_URL points at another site, #38) and log the admin in. The returned function is
 * Playwright's teardown: it stops the server even when tests fail.
 */
import { rm } from 'node:fs/promises';
import type { FullConfig } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { startServer } from '../../tools/playground/server.mjs';

export default async function globalSetup( config: FullConfig ) {
	const baseURL = String( config.projects[ 0 ].use.baseURL );
	const storageStatePath = String( process.env.STORAGE_STATE_PATH );

	const server = process.env.PW_E2E_BASE_URL
		? null
		: await startServer( { port: Number( new URL( baseURL ).port ) } );

	try {
		// Cookies from an earlier run belong to a site that no longer exists.
		await rm( storageStatePath, { force: true } );
		const requestUtils = await RequestUtils.setup( {
			baseURL,
			storageStatePath,
		} );
		await requestUtils.setupRest();
		// The welcome guide covers the canvas; admin.createNewPost() turns it off only
		// client-side, and the stored preferences switch it back on after load.
		await requestUtils.rest( {
			path: '/wp/v2/users/me',
			method: 'PUT',
			data: {
				meta: {
					persisted_preferences: {
						'core/edit-post': { welcomeGuide: false },
						_modified: new Date().toISOString(),
					},
				},
			},
		} );
		await requestUtils.request.dispose();
	} catch ( error ) {
		await server?.stop();
		throw error;
	}

	return server?.stop;
}
