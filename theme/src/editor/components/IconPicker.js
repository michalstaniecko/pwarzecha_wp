/**
 * IconPicker (#39, ADR 0004): grid of SVG previews with search, for the `ikona` attribute of
 * blocks (e.g. `pw/icon-tile`). Value = manifest name rendered on the front by `pw_icon()`.
 *
 * The list comes from theme/build/icons.json, written by `npm run icons` (also run by
 * `npm run build`), so the editor needs no REST endpoint.
 */
import { Button, SearchControl } from '@wordpress/components';
import { RawHTML, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import icons from '../../../build/icons.json';
import { filterIcons } from './filter-icons.js';

/**
 * @param {Object}                 props
 * @param {string}                 props.value    Selected icon name ('' = none).
 * @param {(name: string) => void} props.onChange Called with the picked name.
 * @param {string}                 [props.label]  Search field label.
 */
export default function IconPicker( { value, onChange, label } ) {
	const [ query, setQuery ] = useState( '' );
	const matches = filterIcons( icons, query );

	return (
		<div className="pw-icon-picker">
			<SearchControl
				__nextHasNoMarginBottom
				label={ label ?? __( 'Szukaj ikony', 'pw-theme' ) }
				value={ query }
				onChange={ setQuery }
			/>
			{ matches.length === 0 ? (
				<p>
					{ __(
						'Brak ikon pasujących do wyszukiwania.',
						'pw-theme'
					) }
				</p>
			) : (
				<div
					className="pw-icon-picker__grid"
					style={ {
						display: 'grid',
						gridTemplateColumns:
							'repeat(auto-fill, minmax(48px, 1fr))',
						gap: '4px',
					} }
				>
					{ matches.map( ( icon ) => (
						<Button
							key={ icon.name }
							label={ `${ icon.label } – ${ icon.meaning }` }
							showTooltip
							isPressed={ icon.name === value }
							onClick={ () =>
								onChange( icon.name === value ? '' : icon.name )
							}
							style={ {
								justifyContent: 'center',
								height: '48px',
							} }
						>
							<RawHTML
								className="pw-icon-picker__preview"
								style={ { width: '24px', height: '24px' } }
							>
								{ icon.svg }
							</RawHTML>
						</Button>
					) ) }
				</div>
			) }
		</div>
	);
}
