/**
 * Search of the IconPicker (#39): Polish label or DS meaning, case- and diacritics-insensitive.
 */

/**
 * Lower case without diacritics ("Źródło" → "zrodlo"); ł has no decomposition, so it is mapped.
 *
 * @param {string} text Text.
 * @return {string} Folded text.
 */
const fold = ( text ) =>
	text
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /\p{Diacritic}/gu, '' )
		.replace( /ł/g, 'l' );

/**
 * @typedef {Object} Icon
 * @property {string} name    Manifest name (`pw_icon()`).
 * @property {string} label   Polish label.
 * @property {string} meaning Meaning from the DS icon map.
 * @property {string} svg     Normalized SVG markup.
 */

/**
 * Icons whose label or meaning contains the query.
 *
 * @param {Icon[]} icons Icons from theme/build/icons.json.
 * @param {string} query Search text.
 * @return {Icon[]} Matching icons in the original order.
 */
export function filterIcons( icons, query ) {
	const needle = fold( query.trim() );
	if ( needle === '' ) {
		return icons;
	}
	return icons.filter(
		( icon ) =>
			fold( icon.label ).includes( needle ) ||
			fold( icon.meaning ).includes( needle )
	);
}
