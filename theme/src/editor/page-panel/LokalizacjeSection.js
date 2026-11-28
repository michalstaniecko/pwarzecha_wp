/**
 * Section „Lokalizacje” of the page panel (#52): checkboxes of the published Lokalizacje,
 * stored in the page meta `pw_lokalizacja` (array of IDs in the order of checking).
 *
 * Lokalizacje come from `/wp/v2/lokalizacja`, readable by editors only (#49).
 */
import {
	BaseControl,
	CheckboxControl,
	Flex,
	Spinner,
} from '@wordpress/components';
import { useEntityProp, useEntityRecords } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { toggleLokalizacja } from './toggle-lokalizacja.js';

const META_KEY = 'pw_lokalizacja';

const QUERY = {
	per_page: 100,
	orderby: 'menu_order',
	order: 'asc',
	status: 'publish',
};

export default function LokalizacjeSection() {
	const [ meta, setMeta ] = useEntityProp( 'postType', 'page', 'meta' );
	const { records, hasResolved } = useEntityRecords(
		'postType',
		'lokalizacja',
		QUERY
	);

	if ( ! hasResolved ) {
		return <Spinner />;
	}
	if ( ! records?.length ) {
		return <p>{ __( 'Brak opublikowanych Lokalizacji.', 'pw-theme' ) }</p>;
	}

	const selected = Array.isArray( meta?.[ META_KEY ] )
		? meta[ META_KEY ]
		: [];
	const available = records.map( ( record ) => record.id );

	return (
		<fieldset className="pw-page-lokalizacje">
			<legend>{ __( 'Lokalizacje', 'pw-theme' ) }</legend>
			<BaseControl
				__nextHasNoMarginBottom
				help={ __(
					'Zaznacz Lokalizację, której dotyczy strona; strona o obu salach – obie. Kolejność zaznaczania to kolejność na stronie.',
					'pw-theme'
				) }
			>
				<Flex direction="column" gap={ 3 } align="stretch">
					{ records.map( ( record ) => (
						<CheckboxControl
							key={ record.id }
							__nextHasNoMarginBottom
							label={ record.title?.raw || `#${ record.id }` }
							checked={ selected.includes( record.id ) }
							onChange={ () =>
								setMeta( {
									...meta,
									[ META_KEY ]: toggleLokalizacja(
										selected,
										record.id,
										available
									),
								} )
							}
						/>
					) ) }
				</Flex>
			</BaseControl>
		</fieldset>
	);
}
