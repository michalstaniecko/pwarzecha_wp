import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Only the inner tiles are saved; the grid wrapper comes from render.php (ADR 0001).
 */
export default function save() {
	return <InnerBlocks.Content />;
}
