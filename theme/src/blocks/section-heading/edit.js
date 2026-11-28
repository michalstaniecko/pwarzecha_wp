/**
 * Editor of pw/section-heading (#56): three RichText fields on the canvas, alignment in the
 * toolbar, H2 character counter with a warning above 60 (#31).
 */
import {
	AlignmentControl,
	BlockControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { __, sprintf } from '@wordpress/i18n';
import { HEADING_MAX_LENGTH, headingLength } from '../../shared/heading-id.js';
import { useHeadingId } from '../../shared/use-heading-id.js';
import './editor.css';

const ALIGNMENTS = [
	{
		align: 'left',
		title: __( 'Do lewej', 'pw-theme' ),
		icon: 'editor-alignleft',
	},
	{
		align: 'center',
		title: __( 'Do środka', 'pw-theme' ),
		icon: 'editor-aligncenter',
	},
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { nadtytul, tytul, lead, wyrownanie, naglowekId } = attributes;
	useHeadingId( clientId, naglowekId, setAttributes );

	const length = headingLength( tytul );
	const tooLong = length > HEADING_MAX_LENGTH;
	const blockProps = useBlockProps( {
		className:
			'sp-section-head' +
			( wyrownanie === 'center' ? ' sp-section-head--center' : '' ),
	} );

	return (
		<>
			<BlockControls group="block">
				<AlignmentControl
					value={ wyrownanie }
					alignmentControls={ ALIGNMENTS }
					onChange={ ( value ) =>
						setAttributes( { wyrownanie: value ?? 'left' } )
					}
				/>
			</BlockControls>
			<header { ...blockProps }>
				<RichText
					tagName="p"
					className="sp-eyebrow"
					value={ nadtytul }
					allowedFormats={ [] }
					placeholder={ __( 'Nadtytuł (opcjonalnie)', 'pw-theme' ) }
					onChange={ ( value ) =>
						setAttributes( { nadtytul: value } )
					}
				/>
				<RichText
					tagName="h2"
					className="sp-h2"
					id={ naglowekId || undefined }
					value={ tytul }
					allowedFormats={ [ 'core/bold', 'core/link' ] }
					placeholder={ __( 'Tytuł Sekcji (H2)', 'pw-theme' ) }
					onChange={ ( value ) => setAttributes( { tytul: value } ) }
				/>
				<p
					className={
						'pw-section-heading__counter' +
						( tooLong ? ' is-too-long' : '' )
					}
					role={ tooLong ? 'alert' : undefined }
				>
					{ tooLong
						? sprintf(
								/* translators: 1: characters, 2: limit. */
								__(
									'Tytuł ma %1$d znaków – zalecane najwyżej %2$d.',
									'pw-theme'
								),
								length,
								HEADING_MAX_LENGTH
							)
						: sprintf(
								/* translators: 1: characters, 2: limit. */
								__( '%1$d / %2$d znaków', 'pw-theme' ),
								length,
								HEADING_MAX_LENGTH
							) }
				</p>
				<RichText
					tagName="p"
					className="sp-lead"
					value={ lead }
					allowedFormats={ [
						'core/bold',
						'core/italic',
						'core/link',
					] }
					placeholder={ __( 'Lead (opcjonalnie)', 'pw-theme' ) }
					onChange={ ( value ) => setAttributes( { lead: value } ) }
				/>
			</header>
		</>
	);
}
