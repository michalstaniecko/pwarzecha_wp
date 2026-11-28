// Release ZIP contract (#95, ADR 0006/0007). One place for the slug, whitelist and limits.

/** Theme slug = the single root directory of the ZIP = wp-content/themes/<slug>. */
export const SLUG = 'pw-theme';

/** Zenbox upload limit (#47). */
export const MAX_BYTES = 5 * 1024 * 1024;

/**
 * Files and directories copied from theme/ into the ZIP. Missing optional entries are skipped.
 * Directories are copied recursively, minus EXCLUDE.
 */
export const WHITELIST = [
	'style.css',
	'functions.php',
	'theme.json',
	'screenshot.png',
	'readme.txt',
	'templates',
	'parts',
	'patterns',
	'styles',
	'inc',
	'build',
	'assets',
];

/**
 * Paths (relative to theme/) left out of the ZIP even though their parent is whitelisted.
 *
 * @param {string} path POSIX path relative to theme/.
 * @return {boolean} True when the path must not be copied.
 */
export function isExcluded( path ) {
	return (
		path === 'inc/styleguide/assets' ||
		path.startsWith( 'inc/styleguide/assets/' ) ||
		path.endsWith( '.map' ) ||
		path.split( '/' ).at( -1 ) === '.DS_Store'
	);
}

/** Entries that must exist in the ZIP. */
export const REQUIRED = [
	'style.css',
	'theme.json',
	'templates/index.html',
	'build/blocks-manifest.php',
	'functions.php',
].map( ( path ) => `${ SLUG }/${ path }` );

const FORBIDDEN_DIRS = new Set( [
	'node_modules',
	'vendor',
	'tests',
	'src',
	'.github',
	'docker',
	'__MACOSX',
] );

/**
 * Development code that must never ship (ADR 0007).
 *
 * @param {string} entry ZIP entry path.
 * @return {boolean} True when the entry is forbidden.
 */
export function isForbidden( entry ) {
	const segments = entry.split( '/' ).filter( Boolean );
	return segments.some(
		( segment, index ) =>
			FORBIDDEN_DIRS.has( segment ) ||
			segment.startsWith( '.git' ) ||
			segment.startsWith( '.env' ) ||
			segment === '.DS_Store' ||
			( index === segments.length - 1 &&
				! entry.endsWith( '/' ) &&
				( segment.endsWith( '.map' ) ||
					segment.endsWith( '.test.js' ) ||
					segment === 'pw-preview-mode.php' ||
					segment.startsWith( 'composer.' ) ||
					/^package.*\.json$/.test( segment ) ) )
	);
}
