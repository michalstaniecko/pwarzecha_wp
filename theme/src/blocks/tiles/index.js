/**
 * pw/tiles (#60): grid of tiles, dynamic (render.php) with saved inner blocks.
 */
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit.js';
import save from './save.js';

registerBlockType( metadata.name, { edit: Edit, save } );
