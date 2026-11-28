/**
 * pw/section-heading (#56): dynamic block, markup from render.php (ADR 0001).
 */
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit.js';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
