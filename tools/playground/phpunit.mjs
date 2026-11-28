#!/usr/bin/env node
/**
 * Integration PHPUnit on a fresh WordPress Playground (`npm run test:php`, #37, ADR 0011).
 *
 * Every run boots a clean WP (SQLite) – no Docker, no state between runs. `@group mariadb` is
 * excluded here and runs in `npm run test:php:mariadb` (#38). Extra arguments go to PHPUnit:
 *   npm run test:php -- --filter BlockTestCase
 *
 * The `php` command keeps everything in memory; only the downloaded WP zip is cached
 * (~/.wordpress-playground), which ADR 0015 allows.
 */
import { spawnSync } from 'node:child_process';
import { join } from 'node:path';
import { PHP_VERSION, REPO_VFS, WP_VERSION, mountArgs, root } from './site.mjs';

const result = spawnSync(
	join( root, 'node_modules/.bin/wp-playground-cli' ),
	[
		'php',
		`--php=${ PHP_VERSION }`,
		`--wp=${ WP_VERSION }`,
		'--verbosity=quiet',
		...mountArgs(),
		'--',
		// Not vendor/bin/phpunit: Composer's proxy script prints nothing under Playground.
		`${ REPO_VFS }/vendor/phpunit/phpunit/phpunit`,
		'-c',
		`${ REPO_VFS }/phpunit.xml.dist`,
		'--testsuite',
		'integration',
		'--exclude-group',
		'mariadb',
		...process.argv.slice( 2 ),
	],
	{ cwd: root, stdio: 'inherit' }
);

process.exit( result.status ?? 1 );
