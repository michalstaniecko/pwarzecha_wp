/**
 * The WordPress Playground site shared by PHPUnit, e2e and `npm run wp:playground` (#37, ADR 0011).
 *
 * Explicit mounts instead of `--auto-mount`: the repo root is neither a theme nor a plugin,
 * so auto-mount cannot place it. The theme lands where WordPress expects it, the Composer
 * plugins (.wp/plugins) next to it, and the whole repo at /pw for vendor/ and tests/.
 */
import { existsSync, readdirSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

export const PHP_VERSION = '8.4';
export const WP_VERSION = '7.1.5';
export const THEME_SLUG = 'pw-theme';
export const REPO_VFS = '/pw';

export const root = resolve( fileURLToPath( import.meta.url ), '../../..' );

/**
 * @return {{hostPath: string, vfsPath: string}[]} Mounts of the theme, the plugins and the repo.
 */
export function mounts() {
	const plugins = join( root, '.wp/plugins' );
	if ( ! existsSync( plugins ) ) {
		throw new Error(
			'.wp/plugins is missing – run `composer install` first.'
		);
	}
	return [
		{ hostPath: root, vfsPath: REPO_VFS },
		{
			hostPath: join( root, 'theme' ),
			vfsPath: `/wordpress/wp-content/themes/${ THEME_SLUG }`,
		},
		...readdirSync( plugins, { withFileTypes: true } )
			.filter( ( entry ) => entry.isDirectory() )
			.map( ( entry ) => ( {
				hostPath: join( plugins, entry.name ),
				vfsPath: `/wordpress/wp-content/plugins/${ entry.name }`,
			} ) ),
	];
}

/**
 * @return {string[]} `--mount=host:vfs` arguments for the wp-playground-cli binary.
 */
export function mountArgs() {
	return mounts().map(
		( { hostPath, vfsPath } ) => `--mount=${ hostPath }:${ vfsPath }`
	);
}
