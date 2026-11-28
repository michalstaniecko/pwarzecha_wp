/**
 * Section „Schema” of the page panel (#53): page meta `pw_schema_typ` (which theme node the
 * Yoast graph gets) and, for Service pages, `pw_area_served` (cities; empty = Chorzów, Katowice).
 * The values mirror `PW\Schema\TYPY` (inc/schema/meta.php); REST rejects any other.
 */
import {
	BaseControl,
	Flex,
	FormTokenField,
	SelectControl,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { normalizeCities } from './normalize-cities.js';

const TYP_KEY = 'pw_schema_typ';
const AREA_KEY = 'pw_area_served';

const TYPY = [
	{ value: 'brak', label: __( 'Brak (tylko strona)', 'pw-theme' ) },
	{
		value: 'oferta',
		label: __( 'Strona oferty (Lokalizacje strony)', 'pw-theme' ),
	},
	{
		value: 'kontakt',
		label: __( 'Kontakt (wszystkie Lokalizacje)', 'pw-theme' ),
	},
	{ value: 'trener', label: __( 'Trener (osoba)', 'pw-theme' ) },
	{ value: 'service', label: __( 'Usługa (1:1, B2B)', 'pw-theme' ) },
];

export default function SchemaSection() {
	const [ meta, setMeta ] = useEntityProp( 'postType', 'page', 'meta' );
	const typ = meta?.[ TYP_KEY ] || 'brak';
	const cities = normalizeCities( meta?.[ AREA_KEY ] );

	return (
		<fieldset className="pw-page-schema">
			<legend>{ __( 'Schema', 'pw-theme' ) }</legend>
			<Flex direction="column" gap={ 4 } align="stretch">
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Typ strony w schemie', 'pw-theme' ) }
					help={ __(
						'Strona główna zawsze pokazuje wszystkie Lokalizacje. Lokalizacje strony trafiają do schemy tylko dla „Strona oferty” i „Usługa”. Grafik: „Brak”.',
						'pw-theme'
					) }
					value={ typ }
					options={ TYPY }
					onChange={ ( value ) =>
						setMeta( { ...meta, [ TYP_KEY ]: value } )
					}
				/>
				{ typ === 'service' && (
					<BaseControl
						__nextHasNoMarginBottom
						help={ __(
							'Wpisz miasto i naciśnij Enter. Puste = Chorzów, Katowice.',
							'pw-theme'
						) }
					>
						<FormTokenField
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Obszar usługi (miasta)', 'pw-theme' ) }
							value={ cities }
							onChange={ ( tokens ) =>
								setMeta( {
									...meta,
									[ AREA_KEY ]: normalizeCities( tokens ),
								} )
							}
							__experimentalShowHowTo={ false }
						/>
					</BaseControl>
				) }
			</Flex>
		</fieldset>
	);
}
