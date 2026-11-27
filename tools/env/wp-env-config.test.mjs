import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { DATA_DIR, DATA_MOUNT } from './db.mjs';
import { TESTS_CONFIG } from './tests-instance.mjs';
import { REPO_MOUNT } from './wp-env.mjs';

const root = new URL( '../../', import.meta.url );
const load = ( file ) =>
	JSON.parse( readFileSync( new URL( file, root ), 'utf8' ) );

const dev = load( '.wp-env.json' );
const tests = load( TESTS_CONFIG );

test( 'dev runs WP 7.1.5, PHP 8.4, MariaDB 11.8 on port 8888 as a local environment', () => {
	assert.equal( dev.core, 'WordPress/WordPress#7.1.5' );
	assert.equal( dev.phpVersion, '8.4' );
	assert.equal( dev.mariadbVersion, '11.8' );
	assert.equal( dev.port, 8888 );
	assert.equal( dev.config.WP_ENVIRONMENT_TYPE, 'local' );
	assert.equal( dev.config.WP_DEBUG, true );
	assert.equal( dev.config.SCRIPT_DEBUG, false );
} );

test( 'dev has the theme as pw-theme (same slug as Playground) and the pinned plugins', () => {
	assert.equal( dev.mappings[ 'wp-content/themes/pw-theme' ], './theme' );
	assert.deepEqual( dev.plugins, [
		'./.wp/plugins/wordpress-seo',
		'./.wp/plugins/cookie-law-info',
		'./.wp/plugins/theme-check',
	] );
} );

test( 'each instance activates pw-theme in itself after start, without re-switching an active theme', () => {
	const activate =
		"run cli bash -c 'wp theme is-active pw-theme || wp theme activate pw-theme'";
	assert.equal( dev.lifecycleScripts.afterStart, `npx wp-env ${ activate }` );
	assert.equal(
		tests.lifecycleScripts.afterStart,
		`npx wp-env --config ${ TESTS_CONFIG } ${ activate }`
	);
} );

test( 'dev mounts .data/ for dumps', () => {
	assert.equal( dev.mappings[ DATA_MOUNT ], `./${ DATA_DIR }` );
} );

test( 'tests instance mirrors dev (core, PHP, MariaDB, theme, plugins, config) on port 8889', () => {
	for ( const key of [ 'core', 'phpVersion', 'mariadbVersion', 'plugins' ] ) {
		assert.deepEqual( tests[ key ], dev[ key ], key );
	}
	assert.deepEqual( tests.config, dev.config );
	assert.equal( tests.port, 8889 );
	assert.equal(
		tests.mappings[ 'wp-content/themes/pw-theme' ],
		dev.mappings[ 'wp-content/themes/pw-theme' ]
	);
} );

test( 'tests instance mounts the repo for PHPUnit, but not the dev dumps', () => {
	assert.equal( tests.mappings[ REPO_MOUNT ], '.' );
	assert.equal( tests.mappings[ DATA_MOUNT ], undefined );
} );

test( 'neither config spins up the deprecated built-in tests environment', () => {
	// wp-env 12 still creates tests-* containers on 8889 unless this is explicitly false.
	for ( const config of [ dev, tests ] ) {
		assert.equal( config.testsEnvironment, false );
		assert.equal( config.env, undefined );
	}
} );
