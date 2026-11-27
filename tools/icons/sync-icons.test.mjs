// Icon sync (#39, ADR 0004): normalization of Lucide / Simple Icons SVGs and drift check.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
	mkdir,
	mkdtemp,
	readFile,
	readdir,
	rm,
	writeFile,
} from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { normalizeSvg, syncIcons } from './sync-icons.mjs';

const LUCIDE_GIFT = `<!-- @license lucide-static v1.82.0 - ISC -->
<svg
  class="lucide lucide-gift"
  xmlns="http://www.w3.org/2000/svg"
  width="24"
  height="24"
  viewBox="0 0 24 24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <path d="M12 7v14" />
  <rect x="3" y="7" width="18" height="4" rx="1" />
</svg>
`;

const SIMPLE_GOOGLE =
	'<svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>Google</title><path d="M12.48 10.92v3.28h7.84"/></svg>';

test( 'normalizeSvg: Lucide keeps viewBox and stroke, drops size, class and comment', () => {
	assert.equal(
		normalizeSvg( LUCIDE_GIFT ),
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><rect x="3" y="7" width="18" height="4" rx="1"/></svg>\n'
	);
} );

test( 'normalizeSvg: Simple Icons get fill=currentColor, lose role and <title>', () => {
	assert.equal(
		normalizeSvg( SIMPLE_GOOGLE ),
		'<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M12.48 10.92v3.28h7.84"/></svg>\n'
	);
} );

test( 'normalizeSvg: hard-coded colours become currentColor', () => {
	assert.equal(
		normalizeSvg(
			'<svg viewBox="0 0 24 24" fill="#000"><path stroke="red" d="M0 0"/></svg>'
		),
		'<svg viewBox="0 0 24 24" fill="currentColor"><path stroke="currentColor" d="M0 0"/></svg>\n'
	);
} );

async function fixture( t ) {
	const dir = await mkdtemp( join( tmpdir(), 'pw-icons-' ) );
	t.after( () => rm( dir, { recursive: true, force: true } ) );
	await mkdir( join( dir, 'lucide-src' ) );
	await mkdir( join( dir, 'simple-src' ) );
	await writeFile( join( dir, 'lucide-src/gift.svg' ), LUCIDE_GIFT );
	await writeFile( join( dir, 'lucide-src/unused.svg' ), LUCIDE_GIFT );
	await writeFile( join( dir, 'simple-src/google.svg' ), SIMPLE_GOOGLE );
	return {
		dir,
		options: {
			manifest: {
				gift: {
					source: 'lucide',
					file: 'lucide/gift.svg',
					label: 'Prezent',
					meaning: 'Pierwszy trening bezpłatny',
				},
				'brand-google': {
					source: 'simple-icons',
					file: 'brand/google.svg',
					label: 'Google',
					meaning: 'Źródło opinii',
				},
			},
			sources: {
				lucide: join( dir, 'lucide-src' ),
				'simple-icons': join( dir, 'simple-src' ),
			},
			iconsDir: join( dir, 'icons' ),
			jsonPath: join( dir, 'build/icons.json' ),
		},
	};
}

test( 'syncIcons writes only manifest icons, normalized, and the editor list', async ( t ) => {
	const { dir, options } = await fixture( t );

	assert.deepEqual( await syncIcons( options ), [] );

	assert.deepEqual( await readdir( join( dir, 'icons/lucide' ) ), [
		'gift.svg',
	] );
	assert.equal(
		await readFile( join( dir, 'icons/brand/google.svg' ), 'utf8' ),
		normalizeSvg( SIMPLE_GOOGLE )
	);
	const list = JSON.parse( await readFile( options.jsonPath, 'utf8' ) );
	assert.deepEqual( list[ 0 ], {
		name: 'gift',
		label: 'Prezent',
		meaning: 'Pierwszy trening bezpłatny',
		svg: normalizeSvg( LUCIDE_GIFT ).trim(),
	} );
	assert.deepEqual(
		list.map( ( icon ) => icon.name ),
		[ 'gift', 'brand-google' ]
	);
} );

test( 'syncIcons --check passes on a synced tree and writes nothing', async ( t ) => {
	const { dir, options } = await fixture( t );
	await syncIcons( options );
	await rm( join( dir, 'build' ), { recursive: true } );

	assert.deepEqual( await syncIcons( { ...options, check: true } ), [] );
	await assert.rejects( readFile( options.jsonPath ) );
} );

test( 'syncIcons --check reports changed, missing and extra SVGs', async ( t ) => {
	const { dir, options } = await fixture( t );
	await syncIcons( options );
	await writeFile( join( dir, 'icons/lucide/gift.svg' ), '<svg/>\n' );
	await rm( join( dir, 'icons/brand/google.svg' ) );
	await writeFile( join( dir, 'icons/brand/extra.svg' ), '<svg/>\n' );

	const problems = await syncIcons( { ...options, check: true } );

	assert.deepEqual( problems.sort(), [
		'brand/extra.svg: not in manifest',
		'brand/google.svg: missing',
		'lucide/gift.svg: differs from lucide',
	] );
	assert.equal(
		await readFile( join( dir, 'icons/lucide/gift.svg' ), 'utf8' ),
		'<svg/>\n'
	);
} );

test( 'syncIcons fails on a manifest icon missing from its package', async ( t ) => {
	const { options } = await fixture( t );
	options.manifest.nope = {
		source: 'lucide',
		file: 'lucide/nope.svg',
		label: 'Nie',
		meaning: '-',
	};

	await assert.rejects( syncIcons( options ), /nope\.svg/ );
} );

test( 'syncIcons (write) removes SVGs no longer in the manifest', async ( t ) => {
	const { dir, options } = await fixture( t );
	await syncIcons( options );
	await writeFile( join( dir, 'icons/lucide/old.svg' ), '<svg/>\n' );

	await syncIcons( options );

	assert.deepEqual( await readdir( join( dir, 'icons/lucide' ) ), [
		'gift.svg',
	] );
} );
