/**
 * `core/embed` variations the editor must not offer (ADR 0008, #43).
 *
 * YouTube loads its iframe as soon as the page renders; films go through the VideoFacade block
 * (`pw/video`) instead. The server also refuses YouTube oEmbed (`inc/editor/embeds.php`), so a
 * pasted link never turns into an iframe even where `core/embed` itself is allowed.
 */
const BLOCKED_VARIATIONS = [ 'youtube' ];

/**
 * Names of the registered `core/embed` variations to unregister.
 *
 * @param {Array<{name: string}>|undefined} variations Result of `getBlockVariations( 'core/embed' )`.
 * @return {string[]} Variation names.
 */
export function embedVariationsToRemove( variations ) {
	return ( variations ?? [] )
		.map( ( variation ) => variation.name )
		.filter( ( name ) => BLOCKED_VARIATIONS.includes( name ) );
}
