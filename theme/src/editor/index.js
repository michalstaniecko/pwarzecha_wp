/**
 * Block editor tweaks (#43). Enqueued by `inc/editor/assets.php` on every block editor screen.
 *
 * The filter is added at load time, before the editors register core blocks on DOM ready. The
 * `pw/url-paste` format type only adds a paste rule (see url-paste.js).
 */
import { addFilter } from '@wordpress/hooks';
import { registerFormatType } from '@wordpress/rich-text';
import { restrictBlockSettings } from './block-settings.js';
import { urlPasteFormat } from './url-paste.js';

addFilter(
	'blocks.registerBlockType',
	'pw/editor/restrict-block-settings',
	restrictBlockSettings
);

registerFormatType( 'pw/url-paste', urlPasteFormat );
