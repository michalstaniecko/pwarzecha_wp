import wpScriptsConfig from '@wordpress/scripts/config/eslint.config.cjs';

export default [
	{
		ignores: [
			'theme/build/**',
			'.wp/**',
			'.cache/**',
			'dist/**',
			'test-results/**',
			'playwright-report/**',
			'.lighthouseci/**',
		],
	},
	...wpScriptsConfig,
	{
		// Node-only tooling: CLI scripts and node:test, not Vitest (ADR 0011).
		files: [ 'tools/**/*.mjs', '*.config.{js,mjs,cjs}' ],
		languageOptions: {
			globals: {
				process: 'readonly',
				console: 'readonly',
			},
		},
		rules: {
			'no-console': 'off',
			'vitest/no-import-node-test': 'off',
		},
	},
];
