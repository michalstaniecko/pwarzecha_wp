/**
 * Theme build (#36, ADR 0013).
 *
 * - Blocks: the default wp-scripts config, source from `--webpack-src-dir=theme/src/blocks`
 *   (`npm run build`), output `theme/build/blocks/`, manifest `theme/build/blocks-manifest.php`.
 * - Block `viewScriptModule` files: `--experimental-modules` (in `build`/`start`) adds wp-scripts'
 *   ES-module compiler; its output also goes to `theme/build/blocks/`. With the flag wp-scripts
 *   turns off `output.clean`, so a removed block's build dir survives until `theme/build/` is wiped.
 * - Extra entries: `theme/src/<name>/index.js` → `theme/build/<name>/`. An entry is built only
 *   once its `index.js` exists, so empty directories don't produce empty bundles.
 *
 * Output paths live here, not in CLI flags: webpack-cli applies `--output-path` to every
 * compiler, which would put all extra entries into `theme/build/blocks/`.
 */
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { existsSync, globSync, mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import defaultConfig from '@wordpress/scripts/config/webpack.config.js';

const require = createRequire( import.meta.url );
const root = fileURLToPath( new URL( '.', import.meta.url ) );
const BLOCKS_OUTPUT = join( root, 'theme/build/blocks' );
const BLOCKS_MANIFEST = join( root, 'theme/build/blocks-manifest.php' );
const EXTRA_ENTRIES = [ 'facade', 'editor', 'styles', 'admin' ];

// Plugins that only make sense for the block build (block.json/render.php copy, manifest).
const BLOCK_ONLY_PLUGINS = [
	'CopyPlugin',
	'PhpFilePathsPlugin',
	'BlocksManifestPlugin',
];

/**
 * Writes the blocks manifest after every emit. wp-scripts' own manifest script exits with an
 * error when there are no blocks yet, so an empty manifest is written in that case.
 */
class ThemeBlocksManifestPlugin {
	apply( compiler ) {
		compiler.hooks.afterEmit.tap( 'ThemeBlocksManifest', () => {
			const hasBlocks =
				existsSync( BLOCKS_OUTPUT ) &&
				globSync( '**/block.json', { cwd: BLOCKS_OUTPUT } ).length > 0;
			if ( ! hasBlocks ) {
				mkdirSync( join( root, 'theme/build' ), { recursive: true } );
				writeFileSync(
					BLOCKS_MANIFEST,
					'<?php\n// This file is generated. Do not modify it manually.\nreturn array();\n'
				);
				return;
			}
			execFileSync( process.execPath, [
				require.resolve( '@wordpress/scripts/scripts/build-blocks-manifest.js' ),
				`--input=${ BLOCKS_OUTPUT }`,
				`--output=${ BLOCKS_MANIFEST }`,
			] );
		} );
	}
}

const [ blocksConfig, ...moduleConfigs ] = Array.isArray( defaultConfig )
	? defaultConfig
	: [ defaultConfig ];

const withoutBlockPlugins = ( plugins ) =>
	plugins.filter(
		( plugin ) =>
			plugin && ! BLOCK_ONLY_PLUGINS.includes( plugin.constructor.name )
	);

const themeBlocksConfig = {
	...blocksConfig,
	output: { ...blocksConfig.output, path: BLOCKS_OUTPUT },
	plugins: [
		...blocksConfig.plugins.filter(
			( plugin ) =>
				plugin && plugin.constructor.name !== 'BlocksManifestPlugin'
		),
		new ThemeBlocksManifestPlugin(),
	],
};

const themeModuleConfigs = moduleConfigs.map( ( config ) => ( {
	...config,
	output: { ...config.output, path: BLOCKS_OUTPUT },
} ) );

const extraConfigs = EXTRA_ENTRIES.filter( ( name ) =>
	existsSync( join( root, 'theme/src', name, 'index.js' ) )
).map( ( name ) => ( {
	...blocksConfig,
	name,
	entry: { index: join( root, 'theme/src', name, 'index.js' ) },
	output: {
		...blocksConfig.output,
		path: join( root, 'theme/build', name ),
	},
	plugins: withoutBlockPlugins( blocksConfig.plugins ),
} ) );

export default [ themeBlocksConfig, ...themeModuleConfigs, ...extraConfigs ];
