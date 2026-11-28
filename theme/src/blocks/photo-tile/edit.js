/**
 * Editor of pw/photo-tile (#60): photo on the canvas (placeholder when empty), H3 and text
 * inline, character counter of the text.
 */
import {
	BlockControls,
	MediaPlaceholder,
	MediaReplaceFlow,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { ToolbarButton } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { tekstCounter } from './counter.js';

const ALLOWED_MEDIA = [ 'image' ];

export default function Edit( { attributes, setAttributes } ) {
	const { zdjecieId, tytul, tekst } = attributes;
	const media = useSelect(
		( select ) =>
			zdjecieId ? select( coreStore ).getMedia( zdjecieId ) : null,
		[ zdjecieId ]
	);
	const counter = tekstCounter( tekst );
	const onSelect = ( item ) => setAttributes( { zdjecieId: item?.id ?? 0 } );
	const src =
		media?.media_details?.sizes?.[ 'pw-photo' ]?.source_url ??
		media?.source_url;

	return (
		<article { ...useBlockProps( { className: 'sp-photo-tile' } ) }>
			{ zdjecieId > 0 && (
				<BlockControls group="other">
					<MediaReplaceFlow
						mediaId={ zdjecieId }
						allowedTypes={ ALLOWED_MEDIA }
						accept="image/*"
						onSelect={ onSelect }
						name={ __( 'Zamień zdjęcie', 'pw-theme' ) }
					/>
					<ToolbarButton
						onClick={ () => setAttributes( { zdjecieId: 0 } ) }
					>
						{ __( 'Usuń zdjęcie', 'pw-theme' ) }
					</ToolbarButton>
				</BlockControls>
			) }
			{ zdjecieId > 0 ? (
				<div className="sp-photo sp-photo--photo">
					{ src && <img src={ src } alt={ media?.alt_text ?? '' } /> }
				</div>
			) : (
				<MediaPlaceholder
					icon="format-image"
					labels={ {
						title: __( 'Zdjęcie kafelka (4:3)', 'pw-theme' ),
						instructions: __(
							'Bez zdjęcia kafelek pokaże tylko tytuł i tekst.',
							'pw-theme'
						),
					} }
					allowedTypes={ ALLOWED_MEDIA }
					accept="image/*"
					onSelect={ onSelect }
				/>
			) }
			<div className="sp-photo-tile__body">
				<RichText
					tagName="h3"
					className="sp-photo-tile__title"
					value={ tytul }
					onChange={ ( value ) => setAttributes( { tytul: value } ) }
					placeholder={ __(
						'Tytuł kafelka (bez tytułu kafelek się nie wyświetli)',
						'pw-theme'
					) }
					allowedFormats={ [] }
					disableLineBreaks
				/>
				<RichText
					tagName="p"
					value={ tekst }
					onChange={ ( value ) => setAttributes( { tekst: value } ) }
					placeholder={ __(
						'Krótki tekst, do 160 znaków',
						'pw-theme'
					) }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
					disableLineBreaks
				/>
				<small
					className={
						counter.over
							? 'pw-photo-tile__counter is-over'
							: 'pw-photo-tile__counter'
					}
					aria-live="polite"
				>
					{ sprintf(
						/* translators: 1: characters used, 2: limit. */
						__( '%1$d / %2$d znaków', 'pw-theme' ),
						counter.count,
						counter.limit
					) }
				</small>
			</div>
		</article>
	);
}
