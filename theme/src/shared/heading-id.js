/**
 * Editor side of the H2 `naglowekId` (#56): stable, page-unique id that `pw/section` points
 * to with `aria-labelledby`. Shared by every block that renders its own H2 (`pw/section-heading`,
 * `pw/cta-bar` #63, `pw/price-teaser` #70) – use it through `useHeadingId()` (use-heading-id.js).
 *
 * Pure functions only (Vitest, no DOM). The server fallback for an empty attribute is
 * `pw_heading_id()` in inc/helpers/render.php.
 */

/** DS rule for a Sekcja H2 (#31): longer titles get an editor warning. */
export const HEADING_MAX_LENGTH = 60;

const PREFIX = 'pw-h-';
const ALPHABET = 'abcdefghijklmnopqrstuvwxyz0123456789';
const ID_LENGTH = 6;

/**
 * @param {() => number} [random] Source of numbers in [0, 1), `Math.random` by default.
 * @return {string} `pw-h-` + 6 characters `[a-z0-9]`.
 */
export function generateHeadingId( random = Math.random ) {
	let id = PREFIX;
	for ( let i = 0; i < ID_LENGTH; i++ ) {
		id += ALPHABET[ Math.floor( random() * ALPHABET.length ) ];
	}
	return id;
}

/**
 * Every non-empty `naglowekId` in the block tree, except the given block's own.
 *
 * @param {Array<Object>} blocks   Blocks from `getBlocks()` (with `innerBlocks`).
 * @param {string}        clientId Block to leave out.
 * @param {Set<string>}   [ids]    Accumulator.
 * @return {Set<string>} Ids taken by other blocks.
 */
export function collectHeadingIds( blocks, clientId, ids = new Set() ) {
	for ( const item of blocks ?? [] ) {
		const id = item.attributes?.naglowekId;
		if ( item.clientId !== clientId && id ) {
			ids.add( id );
		}
		collectHeadingIds( item.innerBlocks, clientId, ids );
	}
	return ids;
}

/**
 * New id for a block without one (first insert) or with one another block already has
 * (duplicated block, copy-paste).
 *
 * @param {string|undefined} current    Current `naglowekId`.
 * @param {Set<string>}      taken      Ids of the other blocks (collectHeadingIds()).
 * @param {() => string}     [generate] Id generator.
 * @return {string|null} New id, or `null` when the current one is fine.
 */
export function nextHeadingId( current, taken, generate = generateHeadingId ) {
	if ( current && ! taken.has( current ) ) {
		return null;
	}
	let id = generate();
	while ( taken.has( id ) ) {
		id = generate();
	}
	return id;
}

/**
 * @param {string|undefined} html RichText value.
 * @return {number} Characters of the visible text (tags dropped, an entity counts as one).
 */
export function headingLength( html ) {
	const text = String( html ?? '' )
		.replace( /<[^>]*>/g, '' )
		.replace( /&(?:[a-z]+|#\d+|#x[0-9a-f]+);/gi, '_' );
	return [ ...text ].length;
}

/**
 * @param {string|undefined} html RichText value.
 * @return {boolean} The heading is longer than HEADING_MAX_LENGTH.
 */
export function isHeadingTooLong( html ) {
	return headingLength( html ) > HEADING_MAX_LENGTH;
}
