/**
 * Path filters of job `changes` (.github/ci-filters.yml, #44). Matched the way dorny/paths-filter
 * does it: picomatch with `dot: true`, a filter is hit when any of its rules matches.
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import picomatch from 'picomatch';
import { parse } from 'yaml';
import { root } from '../playground/site.mjs';

const filters = parse(
	readFileSync( join( root, '.github/ci-filters.yml' ), 'utf8' )
);

/**
 * @param {string} path Changed file, relative to the repo root.
 * @return {string[]} Names of the filters it triggers.
 */
function hits( path ) {
	return Object.entries( filters )
		.filter( ( [ , rules ] ) =>
			rules.some( ( rule ) => picomatch( rule, { dot: true } )( path ) )
		)
		.map( ( [ name ] ) => name );
}

test( 'filters: documentation-only changes trigger nothing', () => {
	for ( const path of [
		'docs/adr/0010-ci-w-github-actions-na-pr.md',
		'docs/agents/domain.md',
		'README.md',
		'GLOSSARY.md',
		'theme/README.md',
		'tools/ds/README.md',
	] ) {
		assert.deepEqual( hits( path ), [], path );
	}
} );

test( 'filters: each code area lands in its filter', () => {
	const cases = {
		'theme/functions.php': [ 'php' ],
		'theme/inc/migrations/001.php': [ 'php' ],
		'tests/php/integration/ThemeTest.php': [ 'php' ],
		'composer.lock': [ 'php' ],
		'phpstan.neon.dist': [ 'php' ],
		'theme/src/blocks/hero/view.js': [ 'js' ],
		'package-lock.json': [ 'js', 'php' ],
		'eslint.config.mjs': [ 'js' ],
		'vitest.config.mjs': [ 'js' ],
		'theme/src/styles/base.css': [ 'css', 'php' ],
		'.stylelintrc.json': [ 'css' ],
		'theme/theme.json': [ 'themejson', 'php' ],
		'theme/styles/dark.json': [ 'themejson', 'php' ],
		'tools/env/db.mjs': [ 'js', 'tools' ],
		'tools/playground/site.mjs': [ 'js', 'tools', 'php' ],
		'.github/workflows/ci.yml': [ 'workflows' ],
		'.github/ci-filters.yml': [ 'workflows' ],
	};
	for ( const [ path, expected ] of Object.entries( cases ) ) {
		assert.deepEqual( hits( path ).sort(), expected.sort(), path );
	}
} );

test( 'filters: files the integration suite reads from the theme run PHPUnit', () => {
	for ( const path of [
		'theme/style.css',
		'theme/templates/index.html',
		'theme/parts/header.html',
		'theme/src/blocks/hero/block.json',
		'tests/fixtures/migrations/001-demo.php',
		'tests/fixtures/data.json',
	] ) {
		assert.ok( hits( path ).includes( 'php' ), path );
	}
} );
