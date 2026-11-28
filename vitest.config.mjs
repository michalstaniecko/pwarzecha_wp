import { configDefaults, defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		// tools/ stays on node:test (`npm run test:tools`, ADR 0011).
		exclude: [
			...configDefaults.exclude,
			'tools/**',
			// Playwright specs (`npm run test:e2e`, #37).
			'tests/e2e/**',
			'vendor/**',
			'.wp/**',
			'theme/build/**',
		],
	},
} );
