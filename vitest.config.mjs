import { configDefaults, defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		// tools/ stays on node:test (`npm run test:tools`, ADR 0011).
		exclude: [
			...configDefaults.exclude,
			'tools/**',
			'vendor/**',
			'.wp/**',
			'theme/build/**',
		],
	},
} );
