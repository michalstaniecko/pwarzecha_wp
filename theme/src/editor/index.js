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

// Post type of this editor screen, printed before the script by inc/editor/assets.php.
const postType = window.pwEditor?.postType ?? '';

addFilter(
	'blocks.registerBlockType',
	'pw/editor/restrict-block-settings',
	( settings, name ) => restrictBlockSettings( settings, name, postType )
);

registerFormatType( 'pw/url-paste', urlPasteFormat );
