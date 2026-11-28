/**
 * Meta `pw_lokalizacja` of a page (#52): Lokalizacja IDs in the order the editor checked them.
 */

/**
 * New value of the meta after (un)checking one Lokalizacja.
 *
 * Checking appends the ID at the end, unchecking removes it; the rest keeps its order. The result
 * has no duplicates, no non-positive IDs and – when `available` is given – only IDs of
 * Lokalizacje that still exist (a deleted one disappears on the next change).
 *
 * @param {unknown}  ids         Current meta value (array of IDs; anything else = none).
 * @param {number}   id          Toggled Lokalizacja ID.
 * @param {number[]} [available] IDs of the existing Lokalizacje; omitted = no filtering.
 * @return {number[]} IDs to store.
 */
export function toggleLokalizacja( ids, id, available ) {
	const allowed = available ? new Set( available.map( Number ) ) : null;
	const current = [
		...new Set( ( Array.isArray( ids ) ? ids : [] ).map( Number ) ),
	].filter(
		( value ) =>
			Number.isInteger( value ) &&
			value > 0 &&
			( ! allowed || allowed.has( value ) )
	);

	if ( current.includes( id ) ) {
		return current.filter( ( value ) => value !== id );
	}
	if ( allowed && ! allowed.has( id ) ) {
		return current;
	}
	return [ ...current, id ];
}
