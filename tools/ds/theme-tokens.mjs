// theme.json → Design System tokens.json (ADR 0009, #33). Pure: objects in,
// objects out. File access and exit codes live in `theme-to-tokens.mjs`.
//
// A map entry ties one DS value to one theme.json path (see `theme-map.mjs`):
//   ds:    { family, token, prop? } – `family` is a tokens.json family with a
//          `tokens` array (`color`, `spacing`, `fluid`, …), `type.families`
//          (key of `type.families`) or `type.styles` (text style `token`, field `prop`).
//   theme: path segments from the theme.json root; `{ slug }` picks the
//          preset with that slug from an array.
//   format (optional): how the DS value is derived from the theme.json value.

const isObject = ( v ) =>
	typeof v === 'object' && v !== null && ! Array.isArray( v );

function readTheme( theme, path ) {
	let node = theme;
	for ( const seg of path ) {
		if ( isObject( seg ) ) {
			node = Array.isArray( node )
				? node.find( ( item ) => item?.slug === seg.slug )
				: undefined;
		} else {
			node = isObject( node ) ? node[ seg ] : undefined;
		}
		if ( node === undefined ) {
			return undefined;
		}
	}
	return node;
}

const ROOT_PX = 16;
const round = ( n, digits ) => String( Number( n.toFixed( digits ) ) );

function toPx( length ) {
	const m = /^(-?\d*\.?\d+)(px|rem)$/.exec( String( length ).trim() );
	if ( m === null ) {
		return undefined;
	}
	return Number( m[ 1 ] ) * ( m[ 2 ] === 'rem' ? ROOT_PX : 1 );
}

function toRem( length ) {
	const px = toPx( length );
	return px === undefined ? undefined : px / ROOT_PX;
}

// `px`: the length a static DS value stands for – the upper bound of a clamp(),
// the size of a referenced font-size preset (its fluid max), or a rem/px length.
function staticPx( value, theme ) {
	let v = String( value ).trim();
	const clamp = /^clamp\(.*,\s*([^,]+)\)$/.exec( v );
	if ( clamp !== null ) {
		v = clamp[ 1 ].trim();
	}
	const preset = /^var\(--wp--preset--font-size--([a-z0-9-]+)\)$/.exec( v );
	if ( preset !== null ) {
		const size = readTheme( theme, [
			'settings',
			'typography',
			'fontSizes',
			{ slug: preset[ 1 ] },
		] );
		v = size?.fluid?.max ?? size?.size;
	}
	const n = v === undefined ? undefined : toPx( v );
	return n === undefined ? undefined : round( n, 3 ) + 'px';
}

// `fluid-clamp`: a font-size preset with `fluid: { min, max }` as the DS
// writes it – clamp(min, intercept + slope·vw, max) in rem, linear between
// `settings.typography.fluid` min and max viewport widths, 4 decimals.
function fluidClamp( preset, theme ) {
	const viewport = theme.settings?.typography?.fluid;
	const min = toRem( preset?.fluid?.min );
	const max = toRem( preset?.fluid?.max );
	const vwMin = toRem( viewport?.minViewportWidth );
	const vwMax = toRem( viewport?.maxViewportWidth );
	if ( [ min, max, vwMin, vwMax ].includes( undefined ) || vwMax <= vwMin ) {
		return undefined;
	}
	const slope = ( max - min ) / ( vwMax - vwMin );
	const intercept = min - slope * vwMin;
	return (
		`clamp(${ round( min, 4 ) }rem, ${ round( intercept, 4 ) }rem + ` +
		`${ round( slope * 100, 4 ) }vw, ${ round( max, 4 ) }rem)`
	);
}

const FORMATS = { px: staticPx, 'fluid-clamp': fluidClamp };

function dsValue( theme, entry ) {
	const value = readTheme( theme, entry.theme );
	if ( value === undefined || entry.format === undefined ) {
		return value;
	}
	return FORMATS[ entry.format ]( value, theme );
}

// The DS slot holding a map entry's value: `{ get, set }`, or undefined when
// tokens.json has no such token.
function dsSlot( tokens, { family, token, prop } ) {
	if ( family === 'type.families' ) {
		const families = tokens.type?.families;
		return isObject( families ) && Object.hasOwn( families, token )
			? {
					get: () => families[ token ],
					set: ( v ) => ( families[ token ] = v ),
				}
			: undefined;
	}
	if ( family === 'type.styles' ) {
		const style = ( tokens.type?.groups ?? [] )
			.flatMap( ( g ) => g?.styles ?? [] )
			.find( ( s ) => s?.name === token );
		return style !== undefined && Object.hasOwn( style, prop )
			? { get: () => style[ prop ], set: ( v ) => ( style[ prop ] = v ) }
			: undefined;
	}
	const item = ( tokens[ family ]?.tokens ?? [] ).find(
		( t ) => t?.name === token
	);
	if ( item === undefined ) {
		return undefined;
	}
	// Themed colors and shadows: the first theme's value is the theme.json one.
	if ( isObject( item.value ) ) {
		const first = Object.keys( item.value )[ 0 ];
		return {
			get: () => item.value[ first ],
			set: ( v ) => ( item.value[ first ] = v ),
		};
	}
	return { get: () => item.value, set: ( v ) => ( item.value = v ) };
}

/**
 * Builds tokens.json with every mapped value taken from theme.json. Only values
 * change: usage, samples, groups and key order stay. A mapped token missing
 * from tokens.json is reported in `warnings`, never added.
 * @param {Object} theme  theme.json
 * @param {Object} tokens DS tokens.json
 * @param {Array}  map    entries as in `theme-map.mjs`
 * @return {{tokens: object, warnings: string[]}} updated copy of tokens.json
 */
export function themeToTokens( theme, tokens, map ) {
	const out = structuredClone( tokens );
	const warnings = [];
	for ( const entry of map ) {
		const value = dsValue( theme, entry );
		const slot = dsSlot( out, entry.ds );
		if ( value === undefined ) {
			warnings.push(
				`${ dsLabel( entry.ds ) }: ${ themeLabel(
					entry.theme
				) } not in theme.json`
			);
		} else if ( slot === undefined ) {
			warnings.push( `${ dsLabel( entry.ds ) }: not in tokens.json` );
		} else {
			slot.set( value );
		}
	}
	return { tokens: out, warnings };
}

/**
 * Mapped values that differ between theme.json and tokens.json (missing on
 * either side counts as different). Empty when tokens.json is in sync.
 * @param {Object} theme  theme.json
 * @param {Object} tokens DS tokens.json
 * @param {Array}  map    entries as in `theme-map.mjs`
 * @return {Array<{token: string, theme: (string|number|undefined), tokens: (string|number|undefined)}>} differences in map order
 */
export function diffThemeTokens( theme, tokens, map ) {
	const diff = [];
	for ( const entry of map ) {
		const expected = dsValue( theme, entry );
		const actual = dsSlot( tokens, entry.ds )?.get();
		if ( expected === undefined || actual !== expected ) {
			diff.push( {
				token: dsLabel( entry.ds ),
				theme: expected,
				tokens: actual,
			} );
		}
	}
	return diff;
}

function themeLabel( path ) {
	return path
		.map( ( seg ) => ( isObject( seg ) ? `[${ seg.slug }]` : `.${ seg }` ) )
		.join( '' )
		.slice( 1 );
}

function dsLabel( { family, token, prop } ) {
	return `${ family }/${ token }${ prop !== undefined ? '.' + prop : '' }`;
}
