/**
 * Database dumps of the dev instance (`npm run db:export` / `db:import`).
 *
 * Dumps live in `.data/` (ignored by git), which `.wp-env.json` mounts in the
 * container at DATA_MOUNT, so WP-CLI reads and writes them directly.
 */
import { basename, dirname, join, resolve, sep } from 'node:path';

export const DATA_DIR = '.data';
export const DATA_MOUNT = 'pw-data';
export const LATEST = 'latest.sql';

const pad = ( n ) => String( n ).padStart( 2, '0' );

/**
 * File name of a new dump, e.g. `pw-2026-11-27-153005.sql` (local time).
 *
 * @param {Date} date
 * @return {string} File name inside `.data/`.
 */
export function dumpName( date ) {
	const day = [ date.getFullYear(), date.getMonth() + 1, date.getDate() ]
		.map( pad )
		.join( '-' );
	const time = [ date.getHours(), date.getMinutes(), date.getSeconds() ]
		.map( pad )
		.join( '' );
	return `pw-${ day }-${ time }.sql`;
}

/**
 * Where a dump to import is on the host and in the container.
 *
 * @param {string|undefined} file Path relative to the repo, absolute, or a bare name in `.data/`; default `latest.sql`.
 * @param {string}           root Repository root.
 * @return {{host: string, container: string}} Absolute host path and path relative to the WordPress root in the container.
 */
export function importTarget( file, root ) {
	const dataDir = resolve( root, DATA_DIR );
	let host;
	if ( ! file ) {
		host = join( dataDir, LATEST );
	} else if ( ! file.includes( '/' ) && ! file.includes( sep ) ) {
		host = join( dataDir, file );
	} else {
		host = resolve( root, file );
	}
	if ( dirname( host ) !== dataDir ) {
		throw new Error(
			`Dump must be a file directly in ${ DATA_DIR }/ (the only directory mounted in the container): ${ file }`
		);
	}
	return { host, container: `${ DATA_MOUNT }/${ basename( host ) }` };
}
