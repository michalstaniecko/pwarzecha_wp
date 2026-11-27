/**
 * Narrows core block settings at registration (`blocks.registerBlockType` filter, #43).
 *
 * Done client side because core blocks register from their bundled block.json, which overrides
 * attribute defaults changed on the server (`register_block_type_args`).
 */
import { embedVariationsToRemove } from './embed-variations.js';

/**
 * Heading levels offered in the toolbar of an Aktualność (`post`): H1 is the page title, H4+
 * breaks the DS scale (#21). Pages and the Site Editor keep every level (#43).
 */
const HEADING_LEVELS = [ 2, 3 ];

/**
 * @param {Object}  settings   Block type settings.
 * @param {string}  name       Block name.
 * @param {string=} [postType] Post type of the editor screen (`window.pwEditor.postType`,
 *                             inc/editor/assets.php); '' in the Site Editor.
 * @return {Object} Settings, a modified copy when the block is restricted.
 */
export function restrictBlockSettings( settings, name, postType ) {
	if ( name === 'core/heading' && postType === 'post' ) {
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
