/**
 * Page document panel (#52), registered on every editor screen; it renders only for pages.
 */
import { registerPlugin } from '@wordpress/plugins';
import PagePanel from './PagePanel.js';

registerPlugin( 'pw-page-panel', { render: PagePanel } );
