/**
 * Tile kinds of pw/tiles (#60): one kind per grid. pw/icon-tile is #61.
 */
export const TILE_BLOCKS = [ 'pw/photo-tile', 'pw/icon-tile' ];

/**
 * Block to insert with „Dodaj kafelek”: the kind already in the grid, else a photo tile.
 *
 * @param {string[]} names Names of the grid's inner blocks.
 * @return {string} Block name.
 */
export function nextTileName( names ) {
	return names[ 0 ] ?? TILE_BLOCKS[ 0 ];
}

/**
 * Whether the grid mixes tile kinds (the editor shows a warning).
 *
 * @param {string[]} names Names of the grid's inner blocks.
 * @return {boolean} More than one kind.
 */
export function hasMixedTiles( names ) {
	return new Set( names ).size > 1;
}
