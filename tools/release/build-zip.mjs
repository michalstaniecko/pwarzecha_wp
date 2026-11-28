// Installable theme ZIP (#95, ADR 0006/0007): dist/pw-theme-X.Y.Z.zip with one root `pw-theme/`.
//
//   node tools/release/build-zip.mjs [--no-build]
//
// 1. `npm run build` (production), 2. whitelist copy of theme/ into dist/pw-theme/,
// 3. `zip -r -X` from dist/, 4. verify-zip. Depends only on the system `zip`/`unzip`.
import { execFile } from 'node:child_process';
import { cp, mkdir, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs, promisify } from 'node:util';
import { SLUG, WHITELIST, isExcluded } from './config.mjs';
import { readVersions } from './sync-version.mjs';
import { verifyZip } from './verify-zip.mjs';

const run = promisify( execFile );

/**
 * @param {{root: string, build?: boolean}} options Repository root; build=false skips `npm run build`.
 * @return {Promise<string>} Path of the created ZIP.
 */
export async function buildZip( { root, build = true } ) {
	const { pkg, css } = await readVersions( root );
	if ( pkg !== css ) {
		throw new Error(
			`theme/style.css Version (${ css }) ≠ package.json version (${ pkg }). Run: npm run version:sync`
		);
	}

	if ( build ) {
		await run( 'npm', [ 'run', 'build' ], {
			cwd: root,
			env: { ...process.env, NODE_ENV: 'production' },
			maxBuffer: 64 * 1024 * 1024,
		} );
	}

	const theme = join( root, 'theme' );
	const dist = join( root, 'dist' );
	const target = join( dist, SLUG );
	const zip = join( dist, `${ SLUG }-${ pkg }.zip` );
	await rm( target, { recursive: true, force: true } );
	await rm( zip, { force: true } );
	await mkdir( target, { recursive: true } );

	for ( const entry of WHITELIST ) {
		const from = join( theme, entry );
		if ( ! existsSync( from ) ) {
			continue;
		}
		await cp( from, join( target, entry ), {
			recursive: true,
			filter: ( source ) =>
				! isExcluded(
					relative( theme, source ).split( sep ).join( '/' )
				),
		} );
	}

	await run( 'zip', [ '-r', '-X', '-q', zip, SLUG ], { cwd: dist } );
	return zip;
}

async function main() {
	const { values } = parseArgs( {
		options: { 'no-build': { type: 'boolean', default: false } },
	} );
	const root = fileURLToPath( new URL( '../../', import.meta.url ) );
	const zip = await buildZip( { root, build: ! values[ 'no-build' ] } );
	const errors = await verifyZip( zip );
	if ( errors.length ) {
		process.stderr.write(
			`${ zip }: ${ errors.length } error(s)\n${ errors
				.map( ( e ) => `  - ${ e }` )
				.join( '\n' ) }\n`
		);
		process.exit( 1 );
	}
	process.stdout.write( `${ relative( root, zip ) }: OK\n` );
}

if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	await main();
}
