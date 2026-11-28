/**
 * Document panel of a page (#52): data of the page that blocks and the schema read.
 *
 * Sections („Lokalizacje” #52, „Schema” #53) are rendered in the order of `SECTIONS`; a new one is a
 * component added to that list. Each section reads and writes its own meta via `useEntityProp`.
 */
import { useSelect } from '@wordpress/data';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import LokalizacjeSection from './LokalizacjeSection.js';
import SchemaSection from './SchemaSection.js';

export const SECTIONS = [ LokalizacjeSection, SchemaSection ];

export default function PagePanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);
	if ( postType !== 'page' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="pw-strona"
			title={ __( 'Lokalizacje i schema', 'pw-theme' ) }
		>
			{ SECTIONS.map( ( Section, index ) => (
				<Section key={ index } />
			) ) }
		</PluginDocumentSettingPanel>
	);
}
