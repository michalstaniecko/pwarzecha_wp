// Theme version sync (#95): package.json `version` → `Version:` header of theme/style.css.
//
//   node tools/release/sync-version.mjs [--check] [--root <dir>]
//
// Without --check rewrites the header; with --check exits 1 when the versions differ.
// Hooked into `npm version` (script "version") and `npm run check`.
import { readFile, writeFile } from 'node:fs/promises';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs } from 'node:util';

const VERSION_LINE = /^(\s*Version:\s*)(\S+)(\s*)$/m;

/**
 * @param {string} css style.css contents.
 * @return {string|null} The Version header value, or null when missing.
 */
export function readHeaderVersion( css ) {
	return css.match( VERSION_LINE )?.[ 2 ] ?? null;
}

/**
 * @param {string} css     style.css contents.
 * @param {string} version New version.
 * @return {string} style.css with only the Version header changed.
 */
export function setHeaderVersion( css, version ) {
	if ( ! VERSION_LINE.test( css ) ) {
		throw new Error( 'style.css has no "Version:" header line' );
	}
	return css.replace(
		VERSION_LINE,
		( _, before, __, after ) => `${ before }${ version }${ after }`
	);
}

/**
 * @param {string} root Repository root.
 * @return {Promise<{pkg: string, css: string|null, cssPath: string, source: string}>} Both versions.
 */
export async function readVersions( root ) {
	const pkg = JSON.parse(
		await readFile( join( root, 'package.json' ), 'utf8' )
	).version;
	const cssPath = join( root, 'theme/style.css' );
	const source = await readFile( cssPath, 'utf8' );
	return { pkg, css: readHeaderVersion( source ), cssPath, source };
}

async function main() {
	const { values } = parseArgs( {
		options: {
			check: { type: 'boolean', default: false },
			root: {
				type: 'string',
				default: fileURLToPath( new URL( '../../', import.meta.url ) ),
			},
		},
	} );
	const { pkg, css, cssPath, source } = await readVersions(
		resolve( values.root )
	);

	if ( values.check ) {
		if ( pkg !== css ) {
			process.stderr.write(
				`theme/style.css Version (${ css }) ≠ package.json version (${ pkg }). Run: npm run version:sync\n`
			);
			process.exit( 1 );
		}
		return;
	}
	if ( pkg !== css ) {
		await writeFile( cssPath, setHeaderVersion( source, pkg ) );
		process.stdout.write(
			`theme/style.css Version: ${ css } → ${ pkg }\n`
		);
	}
}

if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	await main();
}
