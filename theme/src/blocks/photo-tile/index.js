/**
 * pw/photo-tile (#60): tile with a photo, dynamic (render.php).
 */
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit.js';
import './style.css';

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
