/**
 * Editor of pw/tiles (#60): column switch in the toolbar, „Dodaj kafelek”, warning when the
 * grid mixes tile kinds.
 */
import {
	BlockControls,
	InnerBlocks,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import {
	Button,
	Notice,
	ToolbarDropdownMenu,
	ToolbarGroup,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { hasMixedTiles, nextTileName, TILE_BLOCKS } from './tile-types.js';

const COLUMNS = [ '3', '4' ];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const kolumny = COLUMNS.includes( attributes.kolumny )
		? attributes.kolumny
		: '3';
	const names = useSelect(
		( select ) =>
			select( blockEditorStore )
				.getBlocks( clientId )
				.map( ( block ) => block.name ),
		[ clientId ]
	);
	const { insertBlock } = useDispatch( blockEditorStore );

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{ className: `sp-grid sp-grid--${ kolumny }` },
		{
			allowedBlocks: TILE_BLOCKS,
			renderAppender:
				names.length === 0 ? InnerBlocks.ButtonBlockAppender : false,
		}
	);

	return (
		<div { ...blockProps }>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon="columns"
						label={ __( 'Liczba kolumn', 'pw-theme' ) }
						text={ kolumny }
						controls={ COLUMNS.map( ( value ) => ( {
							title: value,
							isActive: value === kolumny,
							onClick: () => setAttributes( { kolumny: value } ),
						} ) ) }
					/>
				</ToolbarGroup>
			</BlockControls>
			{ hasMixedTiles( names ) && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Siatka miesza kafelki ze zdjęciem i z ikoną. Użyj jednego rodzaju kafelków na siatkę.',
						'pw-theme'
					) }
				</Notice>
			) }
			<div { ...innerBlocksProps } />
			{ names.length > 0 && (
				<Button
					variant="secondary"
					onClick={ () =>
						insertBlock(
							createBlock( nextTileName( names ) ),
							undefined,
							clientId
						)
					}
				>
					{ __( 'Dodaj kafelek', 'pw-theme' ) }
				</Button>
			) }
		</div>
	);
}
