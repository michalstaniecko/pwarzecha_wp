// Installable theme ZIP validation (#95, ADR 0006/0007).
//
//   node tools/release/verify-zip.mjs <pw-theme-X.Y.Z.zip> [--tag vX.Y.Z]
//
// Exits 1 with a list of errors when the ZIP would not install as `pw-theme` or ships dev code.
import { execFile } from 'node:child_process';
import { stat } from 'node:fs/promises';
import { basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import { parseArgs, promisify } from 'node:util';
import { MAX_BYTES, REQUIRED, SLUG, isForbidden } from './config.mjs';
import { readHeaderVersion } from './sync-version.mjs';

const run = promisify( execFile );

/**
 * @param {string}                            zip       Path to the ZIP.
 * @param {{tag?: string, maxBytes?: number}} [options] Optional release tag and size limit.
 * @return {Promise<string[]>} Errors; empty when the ZIP is valid.
 */
export async function verifyZip( zip, { tag, maxBytes = MAX_BYTES } = {} ) {
	const errors = [];
	const { stdout } = await run( 'unzip', [ '-Z1', zip ], {
		maxBuffer: 64 * 1024 * 1024,
	} );
	const entries = stdout.split( '\n' ).filter( Boolean );

	const roots = [ ...new Set( entries.map( ( e ) => e.split( '/' )[ 0 ] ) ) ];
	if ( roots.length !== 1 || roots[ 0 ] !== SLUG ) {
		errors.push(
			`ZIP must have exactly one root directory "${ SLUG }/", found: ${ roots
				.map( ( r ) => `"${ r }"` )
				.join( ', ' ) }`
		);
	}

	const present = new Set( entries );
	for ( const required of REQUIRED ) {
		if ( ! present.has( required ) ) {
			errors.push( `missing required file: ${ required }` );
		}
	}

	for ( const entry of entries.filter( isForbidden ) ) {
		errors.push( `forbidden path: ${ entry }` );
	}

	const fileVersion = basename( zip ).match(
		new RegExp( `^${ SLUG }-(\\d+\\.\\d+\\.\\d+)\\.zip$` )
	)?.[ 1 ];
	if ( ! fileVersion ) {
		errors.push(
			`file name "${ basename( zip ) }" is not ${ SLUG }-X.Y.Z.zip`
		);
	}

	const stylePath = `${ SLUG }/style.css`;
	if ( present.has( stylePath ) ) {
		const { stdout: css } = await run( 'unzip', [ '-p', zip, stylePath ] );
		const version = readHeaderVersion( css );
		if ( ! version ) {
			errors.push( `${ stylePath } has no Version header` );
		} else {
			if ( fileVersion && version !== fileVersion ) {
				errors.push(
					`${ stylePath } Version ${ version } ≠ file name version ${ fileVersion }`
				);
			}
			if ( tag && tag !== `v${ version }` ) {
				errors.push(
					`${ stylePath } Version ${ version } ≠ tag ${ tag }`
				);
			}
		}
	}

	const { size } = await stat( zip );
	if ( size > maxBytes ) {
		errors.push(
			`ZIP is ${ ( size / 1024 / 1024 ).toFixed( 2 ) } MB (${ size } bytes), limit ${ maxBytes } bytes`
		);
	}

	return errors;
}

async function main() {
	const { values, positionals } = parseArgs( {
		allowPositionals: true,
		options: { tag: { type: 'string' } },
	} );
	if ( positionals.length !== 1 ) {
		process.stderr.write(
			'usage: verify-zip.mjs <pw-theme-X.Y.Z.zip> [--tag vX.Y.Z]\n'
		);
		process.exit( 2 );
	}
	const errors = await verifyZip( positionals[ 0 ], { tag: values.tag } );
	if ( errors.length ) {
		process.stderr.write(
			`${ positionals[ 0 ] }: ${ errors.length } error(s)\n${ errors
				.map( ( e ) => `  - ${ e }` )
				.join( '\n' ) }\n`
		);
		process.exit( 1 );
	}
	process.stdout.write( `${ positionals[ 0 ] }: OK\n` );
}

if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	await main();
}
