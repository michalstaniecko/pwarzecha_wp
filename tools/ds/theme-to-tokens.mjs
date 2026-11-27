#!/usr/bin/env node
// theme.json → DS tokens.json (+ tokens.css), ADR 0009, #33. No dependencies.
//
//   node tools/ds/theme-to-tokens.mjs <theme.json> <tokens.json> [tokens.css]
//       writes theme.json values into tokens.json (values only), then compiles tokens.css
//   node tools/ds/theme-to-tokens.mjs --check <theme.json> <tokens.json>
//       0 = in sync, 1 = differences listed, 2 = bad arguments or unreadable file

import { readFile, writeFile } from 'node:fs/promises';
import { compileTokensCss } from './compile.mjs';
import { THEME_MAP } from './theme-map.mjs';
import { diffThemeTokens, themeToTokens } from './theme-tokens.mjs';

const USAGE =
	'Usage: node tools/ds/theme-to-tokens.mjs <theme.json> <tokens.json> [tokens.css]\n' +
	'       node tools/ds/theme-to-tokens.mjs --check <theme.json> <tokens.json>\n';

function parseArgs( argv ) {
	const check = argv[ 0 ] === '--check';
	const paths = check ? argv.slice( 1 ) : argv;
	if ( paths.some( ( p ) => p.startsWith( '--' ) ) ) {
		return null;
	}
	const [ theme, tokens, css ] = paths;
	const max = check ? 2 : 3;
	if ( tokens === undefined || paths.length > max ) {
		return null;
	}
	return { check, theme, tokens, css };
}

const readJson = async ( path ) => JSON.parse( await readFile( path, 'utf8' ) );
const show = ( v ) => ( v === undefined ? '(missing)' : JSON.stringify( v ) );

async function main( argv ) {
	const opts = parseArgs( argv );
	if ( opts === null ) {
		process.stderr.write( USAGE );
		return 2;
	}
	let theme;
	let tokens;
	let current = opts.theme;
	try {
		theme = await readJson( opts.theme );
		current = opts.tokens;
		tokens = await readJson( opts.tokens );
	} catch ( error ) {
		process.stderr.write( `Cannot use ${ current }: ${ error.message }\n` );
		return 2;
	}

	if ( opts.check ) {
		const diff = diffThemeTokens( theme, tokens, THEME_MAP );
		if ( diff.length === 0 ) {
			process.stdout.write(
				`${ opts.tokens } matches ${ opts.theme }\n`
			);
			return 0;
		}
		process.stderr.write(
			`${ opts.tokens } differs from ${ opts.theme }:\n` +
				diff
					.map(
						( d ) =>
							`  ${ d.token }: theme.json ${ show(
								d.theme
							) }, tokens.json ${ show( d.tokens ) }`
					)
					.join( '\n' ) +
				'\nUpdate: node tools/ds/theme-to-tokens.mjs ' +
				`${ opts.theme } ${ opts.tokens } [tokens.css]\n`
		);
		return 1;
	}

	const result = themeToTokens( theme, tokens, THEME_MAP );
	for ( const warning of result.warnings ) {
		process.stderr.write( `warning: ${ warning }\n` );
	}
	await writeFile(
		opts.tokens,
		JSON.stringify( result.tokens, null, 2 ) + '\n'
	);
	if ( opts.css !== undefined ) {
		await writeFile( opts.css, compileTokensCss( result.tokens ) );
	}
	return 0;
}

process.exitCode = await main( process.argv.slice( 2 ) );
