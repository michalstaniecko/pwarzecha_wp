#!/usr/bin/env node
/**
 * Icon sync (#39, ADR 0004).
 *
 * Copies only the icons listed in `theme/assets/icons/manifest.json` from `lucide-static` and
 * `simple-icons` to `theme/assets/icons/{lucide,brand}/*.svg`, normalized for inline use by
 * `pw_icon()`, and writes the editor list `theme/build/icons.json` (IconPicker). The SVGs lose their
 * licence comments, so the packages' licence files are copied verbatim to
 * `theme/assets/icons/LICENSE-{lucide,simple-icons}.txt` (ISC requires the notice in all copies).
 *
 *   node tools/icons/sync-icons.mjs           write SVGs + theme/build/icons.json
 *   node tools/icons/sync-icons.mjs --check   exit 1 when repo SVGs or licences differ from the packages
 */

import { existsSync } from 'node:fs';
import { mkdir, readFile, readdir, rm, writeFile } from 'node:fs/promises';
import { basename, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = fileURLToPath( new URL( '../../', import.meta.url ) );
const OUTPUT_DIRS = [ 'lucide', 'brand' ];
const ROOT_SVG = /<svg\b([^>]*)>/;
const ATTRIBUTE = /([\w:-]+)="([^"]*)"/g;
const DROPPED_ROOT_ATTRIBUTES = new Set( [
	'width',
	'height',
	'class',
	'role',
] );

const toCurrentColor = ( name, value ) =>
	( name === 'fill' || name === 'stroke' ) && value !== 'none'
		? 'currentColor'
		: value;

/**
 * Normalizes an icon SVG: one line, no comments or <title>, root without width/height/class/role,
 * every fill/stroke colour = currentColor (fill added when the root paints neither).
 *
 * @param {string} svg Source SVG.
 * @return {string} Normalized SVG with a trailing newline.
 */
export function normalizeSvg( svg ) {
	let body = svg
		.replace( /<!--[\s\S]*?-->/g, '' )
		.replace( /<title>[\s\S]*?<\/title>/g, '' )
		.replace( /\s+/g, ' ' )
		.replace( />\s+</g, '><' )
		.replace( /\s*\/>/g, '/>' )
		.trim();

	body = body.replace(
		/<(\w+)\b([^>]*?)(\/?)>/g,
		( tag, name, rest, close ) => {
			const isRoot = name === 'svg';
			const attributes = [];
			for ( const [ , attr, value ] of rest.matchAll( ATTRIBUTE ) ) {
				if ( isRoot && DROPPED_ROOT_ATTRIBUTES.has( attr ) ) {
					continue;
				}
				attributes.push( [ attr, toCurrentColor( attr, value ) ] );
			}
			if (
				isRoot &&
				! attributes.some(
					( [ attr ] ) => attr === 'fill' || attr === 'stroke'
				)
			) {
				attributes.push( [ 'fill', 'currentColor' ] );
			}
			const serialized = attributes
				.map( ( [ attr, value ] ) => ` ${ attr }="${ value }"` )
				.join( '' );
			return `<${ name }${ serialized }${ close }>`;
		}
	);

	if ( ! ROOT_SVG.test( body ) ) {
		throw new Error( 'Not an SVG document' );
	}
	return `${ body }\n`;
}

async function readOrNull( path ) {
	try {
		return await readFile( path, 'utf8' );
	} catch {
		return null;
	}
}

async function svgFilesIn( iconsDir ) {
	const files = [];
	for ( const sub of OUTPUT_DIRS ) {
		const dir = join( iconsDir, sub );
		const names = existsSync( dir ) ? await readdir( dir ) : [];
		files.push(
			...names
				.filter( ( name ) => name.endsWith( '.svg' ) )
				.map( ( name ) => `${ sub }/${ name }` )
		);
	}
	return files;
}

/**
 * Syncs the manifest icons from their packages into `iconsDir` (and writes `jsonPath`), or with
 * `check` only compares and returns the problems.
 *
 * @param {Object}                options
 * @param {Object}                options.manifest   Name → { source, file, label, meaning }.
 * @param {Object<string,string>} options.sources    Source id → directory with `<file name>.svg`.
 * @param {Object<string,string>} [options.licences] Licence file name in `iconsDir` → package licence path.
 * @param {string}                options.iconsDir   `theme/assets/icons`.
 * @param {string}                options.jsonPath   Editor list (`theme/build/icons.json`).
 * @param {boolean}               [options.check]    Compare only, write nothing.
 * @return {Promise<string[]>} Drift found (`check`), empty after a write.
 */
export async function syncIcons( {
	manifest,
	sources,
	licences = {},
	iconsDir,
	jsonPath,
	check = false,
} ) {
	const problems = [];
	const list = [];
	const expected = new Set();

	for ( const [ name, icon ] of Object.entries( manifest ) ) {
		const sourceDir = sources[ icon.source ];
		if ( ! sourceDir ) {
			throw new Error( `${ name }: unknown source "${ icon.source }"` );
		}
		const sourcePath = join( sourceDir, basename( icon.file ) );
		const source = await readOrNull( sourcePath );
		if ( source === null ) {
			throw new Error( `${ name }: ${ sourcePath } not found` );
		}
		const svg = normalizeSvg( source );
		expected.add( icon.file );
		list.push( {
			name,
			label: icon.label,
			meaning: icon.meaning,
			svg: svg.trim(),
		} );

		const target = join( iconsDir, icon.file );
		if ( check ) {
			const current = await readOrNull( target );
			if ( current === null ) {
				problems.push( `${ icon.file }: missing` );
			} else if ( current !== svg ) {
				problems.push(
					`${ icon.file }: differs from ${ icon.source }`
				);
			}
			continue;
		}
		await mkdir( dirname( target ), { recursive: true } );
		await writeFile( target, svg );
	}

	for ( const file of await svgFilesIn( iconsDir ) ) {
		if ( expected.has( file ) ) {
			continue;
		}
		if ( check ) {
			problems.push( `${ file }: not in manifest` );
		} else {
			await rm( join( iconsDir, file ) );
		}
	}

	for ( const [ file, sourcePath ] of Object.entries( licences ) ) {
		const licence = await readOrNull( sourcePath );
		if ( licence === null ) {
			throw new Error( `${ file }: ${ sourcePath } not found` );
		}
		const target = join( iconsDir, file );
		if ( check ) {
			const current = await readOrNull( target );
			if ( current === null ) {
				problems.push( `${ file }: missing` );
			} else if ( current !== licence ) {
				problems.push( `${ file }: differs from package licence` );
			}
			continue;
		}
		await mkdir( iconsDir, { recursive: true } );
		await writeFile( target, licence );
	}

	if ( ! check ) {
		await mkdir( dirname( jsonPath ), { recursive: true } );
		await writeFile(
			jsonPath,
			`${ JSON.stringify( list, null, '\t' ) }\n`
		);
	}
	return problems;
}

async function main( args ) {
	const check = args.includes( '--check' );
	const iconsDir = join( ROOT, 'theme/assets/icons' );
	const manifest = JSON.parse(
		await readFile( join( iconsDir, 'manifest.json' ), 'utf8' )
	);
	const problems = await syncIcons( {
		manifest,
		sources: {
			lucide: join( ROOT, 'node_modules/lucide-static/icons' ),
			'simple-icons': join( ROOT, 'node_modules/simple-icons/icons' ),
		},
		licences: {
			'LICENSE-lucide.txt': join(
				ROOT,
				'node_modules/lucide-static/LICENSE'
			),
			'LICENSE-simple-icons.txt': join(
				ROOT,
				'node_modules/simple-icons/LICENSE.md'
			),
		},
		iconsDir,
		jsonPath: join( ROOT, 'theme/build/icons.json' ),
		check,
	} );
	if ( problems.length > 0 ) {
		process.stderr.write(
			`Icons or licences out of sync with lucide-static / simple-icons (run \`npm run icons\`):\n${ problems
				.map( ( problem ) => `  ${ problem }\n` )
				.join( '' ) }`
		);
		process.exitCode = 1;
		return;
	}
	process.stdout.write(
		check
			? `Icons in sync (${ Object.keys( manifest ).length }).\n`
			: `Synced ${ Object.keys( manifest ).length } icons.\n`
	);
}

if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	await main( process.argv.slice( 2 ) );
}
