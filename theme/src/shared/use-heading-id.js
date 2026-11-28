/**
 * Keeps `naglowekId` of the current block set and unique on the page (#56): generates it on
 * first insert, regenerates it after a duplicate or copy-paste. Logic in heading-id.js.
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useRegistry } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { collectHeadingIds, nextHeadingId } from './heading-id.js';

/**
 * @param {string}                       clientId      Block client id.
 * @param {string|undefined}             naglowekId    Current attribute value.
 * @param {(attributes: Object) => void} setAttributes Block `setAttributes`.
 */
export function useHeadingId( clientId, naglowekId, setAttributes ) {
	const registry = useRegistry();

	useEffect( () => {
		// Read once, not subscribed: the check only matters when the block appears or its
		// id changes, and a store subscription would re-run it on every keystroke.
		const blocks = registry.select( blockEditorStore ).getBlocks();
		const id = nextHeadingId(
			naglowekId,
			collectHeadingIds( blocks, clientId )
		);
		if ( id ) {
			setAttributes( { naglowekId: id } );
		}
	}, [ registry, clientId, naglowekId, setAttributes ] );
}
