/**
 * Playwright (#37, ADR 0011): e2e against a fresh Playground site started in globalSetup.
 *
 * Tags:
 * - `@plugins` – needs configured third-party plugins (e.g. the CookieYes banner); skipped by
 *   `npm run test:e2e`, run by `npm run test:e2e:all` (check:full, full.yml).
 * - `@visual` – screenshot tests, only in the Playwright container (`PW_VISUAL=1`, #42).
 */
import { defineConfig, devices } from '@playwright/test';

const CI = !! process.env.CI;
const GITHUB_ACTIONS = process.env.GITHUB_ACTIONS === 'true';
const PORT = Number( process.env.PW_E2E_PORT ?? 9410 );
const PLUGINS = process.env.PW_E2E_PLUGINS === '1';
const VISUAL = process.env.PW_VISUAL === '1';
// Set by `npm run test:e2e:wp-env` (#38): run against that site instead of booting Playground.
const EXTERNAL_URL = process.env.PW_E2E_BASE_URL;

// Admin login state, written by globalSetup and read by the e2e-test-utils fixtures.
process.env.STORAGE_STATE_PATH ??= '.cache/e2e/storage-states/admin.json';
process.env.WP_BASE_URL = EXTERNAL_URL ?? `http://127.0.0.1:${ PORT }`;

export default defineConfig( {
	testDir: 'tests/e2e',
	outputDir: 'test-results',
	// Baselines in git, one set without a platform suffix: they are only ever taken and compared
	// in the Playwright container (`npm run test:visual`, Linux), locally and in CI (#42, #45).
	snapshotPathTemplate: '{testDir}/__screenshots__/{testFileName}/{arg}{ext}',
	globalSetup: './tests/e2e/global-setup.ts',
	timeout: 60_000,
	workers: 1,
	fullyParallel: false,
	forbidOnly: CI,
	retries: 0,
	// blob: for `merge-reports` once full.yml shards (#45); github: annotations in the PR; html: the
	// failure artifact (playwright-report/).
	reporter: CI
		? [
				[ 'blob' ],
				...( GITHUB_ACTIONS ? [ [ 'github' ] as [ string ] ] : [] ),
				[ 'html', { open: 'never' } ],
			]
		: [ [ 'list' ] ],
	use: {
		...devices[ 'Desktop Chrome' ],
		baseURL: process.env.WP_BASE_URL,
		// Logged in as admin; front-end tests opt out (see tests/e2e/smoke.spec.ts).
		storageState: process.env.STORAGE_STATE_PATH,
		trace: 'retain-on-failure',
	},
	projects: [
		{
			name: 'e2e',
			grepInvert: PLUGINS ? /@visual/ : /@visual|@plugins/,
		},
		...( VISUAL ? [ { name: 'visual', grep: /@visual/ } ] : [] ),
	],
} );
