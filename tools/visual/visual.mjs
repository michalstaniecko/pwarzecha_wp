#!/usr/bin/env node
/**
 * Visual tests of the styleguide in the Playwright container (#42, ADR 0010):
 *   npm run test:visual            compare with tests/e2e/__screenshots__/
 *   npm run test:visual:update     rewrite the baselines
 * Extra arguments go to `playwright test` (e.g. `-- --grep przyciski`).
 *
 * Screenshots are only taken here, never on the host, so baselines are Linux-only and have no
 * platform suffix. The repo is mounted as is (node_modules has no native deps the container
 * cannot load); the container runs as the host user so test-results/ and baselines are not
 * root-owned, and the WordPress zip cache of Playground CLI is shared with the host.
 * Theme assets must be built first – the npm scripts run `npm run build`.
 */
import { spawnSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { homedir, userInfo } from 'node:os';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

export const IMAGE = 'mcr.microsoft.com/playwright:v1.65.1-noble';

const root = resolve( fileURLToPath( import.meta.url ), '../../..' );

/**
 * `docker run` arguments.
 *
 * @param {Object}   options
 * @param {string}   options.root     Repo root on the host.
 * @param {string}   options.cache    Host dir of the Playground WP cache.
 * @param {string}   options.user     `uid:gid` for the container ('' = image default).
 * @param {string[]} options.args     Arguments for `playwright test`.
 * @param {boolean}  [options.update] Rewrite the baselines.
 * @return {string[]} Arguments after `docker`.
 */
export function dockerArgs( {
	root: repo,
	cache,
	user,
	args,
	update = false,
} ) {
	return [
		'run',
		'--rm',
		'--ipc=host',
		...( user ? [ '--user', user ] : [] ),
		'-e',
		'PW_VISUAL=1',
		'-e',
		'CI',
		'-e',
		'HOME=/tmp/pw-home',
		'-v',
		`${ repo }:/work`,
		'-v',
		`${ cache }:/tmp/pw-home/.wordpress-playground`,
		'-w',
		'/work',
		IMAGE,
		'npx',
		'playwright',
		'test',
		'--project=visual',
		...( update ? [ '--update-snapshots' ] : [] ),
		...args,
	];
}

if ( process.argv[ 1 ] === import.meta.filename ) {
	const argv = process.argv.slice( 2 );
	const update = argv.includes( '--update' );
	const cache = join( homedir(), '.wordpress-playground' );
	mkdirSync( cache, { recursive: true } );
	const { uid, gid } = userInfo();
	const result = spawnSync(
		'docker',
		dockerArgs( {
			root,
			cache,
			user: uid >= 0 ? `${ uid }:${ gid }` : '',
			args: argv.filter( ( arg ) => arg !== '--update' ),
			update,
		} ),
		{ stdio: 'inherit' }
	);
	process.exit( result.status ?? 1 );
}
