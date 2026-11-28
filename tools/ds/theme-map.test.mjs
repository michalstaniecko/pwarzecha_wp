import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { THEME_MAP } from './theme-map.mjs';
import { diffThemeTokens } from './theme-tokens.mjs';

const read = async ( url ) =>
	JSON.parse( await readFile( new URL( url, import.meta.url ), 'utf8' ) );
const dsTokens = () => read( './fixtures/ds-tokens.json' );
const themeJson = () => read( '../../theme/theme.json' );

const key = ( { family, token, prop } ) =>
	`${ family }/${ token }${ prop !== undefined ? '.' + prop : '' }`;

// Every value the DS holds that theme.json owns, read straight from tokens.json.
function dsValues( ds ) {
	const keys = [];
	for ( const [ family, raw ] of Object.entries( ds ) ) {
		for ( const t of raw?.tokens ?? [] ) {
			keys.push( `${ family }/${ t.name }` );
		}
	}
	for ( const name of Object.keys( ds.type.families ) ) {
		keys.push( `type.families/${ name }` );
	}
	for ( const style of ds.type.groups.flatMap( ( g ) => g.styles ) ) {
		for ( const prop of [
			'fontSize',
			'lineHeight',
			'fontWeight',
			'letterSpacing',
		] ) {
			if ( style[ prop ] !== undefined ) {
				keys.push( `type.styles/${ style.name }.${ prop }` );
			}
		}
	}
	return keys;
}

test( 'every DS token value has exactly one theme.json mapping', async () => {
	const mapped = THEME_MAP.map( ( e ) => key( e.ds ) );
	assert.deepEqual(
		[ ...mapped ].sort(),
		dsValues( await dsTokens() ).sort()
	);
} );

test( 'theme.json holds every DS token value (no drift with the DS fixture)', async () => {
	assert.deepEqual(
		diffThemeTokens( await themeJson(), await dsTokens(), THEME_MAP ),
		[]
	);
} );

test( 'theme.json paths are unique except where the DS keeps a static and a fluid copy', () => {
	const paths = THEME_MAP.filter( ( e ) => e.format === undefined ).map(
		( e ) => JSON.stringify( e.theme )
	);
	assert.equal( new Set( paths ).size, paths.length );
} );

test( 'map entries are plain data (exportable to JSON for PHPUnit)', () => {
	assert.deepEqual( JSON.parse( JSON.stringify( THEME_MAP ) ), THEME_MAP );
} );

test( 'the JSON export for PHPUnit matches the map', async () => {
	assert.deepEqual( await read( './theme-map.json' ), THEME_MAP );
} );
