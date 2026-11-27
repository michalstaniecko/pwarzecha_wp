/**
 * Narrows core block settings at registration (`blocks.registerBlockType` filter, #43).
 *
 * Done client side because core blocks register from their bundled block.json, which overrides
 * attribute defaults changed on the server (`register_block_type_args`).
 */
import { embedVariationsToRemove } from './embed-variations.js';

/** Heading levels offered in the toolbar: H1 is the page title, H4+ breaks the DS scale (#21). */
const HEADING_LEVELS = [ 2, 3 ];

/**
 * @param {Object} settings Block type settings.
 * @param {string} name     Block name.
 * @return {Object} Settings, a modified copy when the block is restricted.
 */
export function restrictBlockSettings( settings, name ) {
	if ( name === 'core/heading' ) {
		return {
			...settings,
			attributes: {
				...settings.attributes,
				levelOptions: {
					...settings.attributes?.levelOptions,
					default: HEADING_LEVELS,
				},
			},
		};
	}

	if ( name === 'core/embed' ) {
		const removed = embedVariationsToRemove( settings.variations );
		return {
			...settings,
			variations: ( settings.variations ?? [] ).filter(
				( variation ) => ! removed.includes( variation.name )
			),
		};
	}

	return settings;
}
