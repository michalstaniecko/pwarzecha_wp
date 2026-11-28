/**
 * Character counter of the pw/photo-tile text (#60, ≤ 160 visible characters).
 */
export const TEKST_LIMIT = 160;

/**
 * Counts visible characters of RichText HTML: tags do not count, an entity is one character.
 *
 * @param {string} html RichText value.
 * @return {{count: number, limit: number, over: boolean}} Counter state.
 */
export function tekstCounter( html ) {
	const text = String( html ?? '' )
		.replace( /<[^>]*>/g, '' )
		.replace( /&(?:[a-z\d]+|#\d+|#x[\da-f]+);/gi, '_' );
	const count = Array.from( text ).length;
	return { count, limit: TEKST_LIMIT, over: count > TEKST_LIMIT };
}
