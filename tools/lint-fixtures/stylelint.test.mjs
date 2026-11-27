// Smoke test of the repo Stylelint config: block CSS must use tokens, not literals (ADR 0003).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import stylelint from 'stylelint';

const root = fileURLToPath( new URL( '../../', import.meta.url ) );
const fixture = ( name ) =>
	fileURLToPath( new URL( `./${ name }`, import.meta.url ) );

// The repo config, minus ignoreFiles: these fixtures are excluded from `npm run lint:css`.
const repoConfig = JSON.parse(
	await readFile( join( root, '.stylelintrc.json' ), 'utf8' )
);
const config = { ...repoConfig, ignoreFiles: [] };

async function lintFixture( name ) {
	const { results } = await stylelint.lint( {
		files: fixture( name ),
		config,
		configBasedir: root,
		cwd: root,
	} );
	assert.equal( results.length, 1, `fixture ${ name } was not linted` );
	return results[ 0 ].warnings.filter( ( w ) => w.severity === 'error' );
}

test( 'bad.css: every literal colour and size is rejected', async () => {
	const errors = await lintFixture( 'bad.css' );
	const rules = errors.map( ( e ) => e.rule ).sort();
	assert.deepEqual( rules, [
		'color-named',
		'color-no-hex',
		'declaration-property-value-disallowed-list',
		'declaration-property-value-disallowed-list',
		'declaration-property-value-disallowed-list',
		'declaration-property-value-disallowed-list',
		'function-disallowed-list',
		'function-disallowed-list',
	] );
} );

test( 'good.css: tokens via var(--wp--preset--…) pass', async () => {
	const errors = await lintFixture( 'good.css' );
	assert.deepEqual(
		errors.map( ( e ) => `${ e.line }: ${ e.text }` ),
		[]
	);
} );
