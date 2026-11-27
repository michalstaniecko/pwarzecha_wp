/**
 * Block editor tweaks (#43). Enqueued by `inc/editor/assets.php` on every block editor screen.
 *
 * The filter is added at load time, before the editors register core blocks on DOM ready.
 */
import { addFilter } from '@wordpress/hooks';
import { restrictBlockSettings } from './block-settings.js';

addFilter(
	'blocks.registerBlockType',
	'pw/editor/restrict-block-settings',
	restrictBlockSettings
);
