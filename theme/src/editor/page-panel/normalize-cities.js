/**
 * Cities of `pw_area_served` (#53) as the Schema section stores them: trimmed, non-empty,
 * unique, in the given order. Mirrors `PW\Schema\sanitize_area_served()` on the server.
 *
 * @param {unknown} tokens Strings or FormTokenField tokens (`{ value }`).
 * @return {string[]} Cities.
 */
export function normalizeCities( tokens ) {
	if ( ! Array.isArray( tokens ) ) {
		return [];
	}
	const cities = [];
	for ( const token of tokens ) {
		const city = String(
			typeof token === 'object' && token !== null
				? ( token.value ?? '' )
				: ( token ?? '' )
		).trim();
		if ( city !== '' && ! cities.includes( city ) ) {
			cities.push( city );
		}
	}
	return cities;
}
