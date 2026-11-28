// DS tokens.json value ↔ theme.json path (ADR 0009, #33). The one place that
// says which theme.json setting feeds which DS token. Plain data, so it can be
// exported to JSON (`node tools/ds/theme-map.mjs > tools/ds/theme-map.json`)
// for the PHPUnit test that checks WordPress sees every preset.
//
// Entry: { ds: { family, token, prop? }, theme: [path…], format? }
//   ds.family `color`, `spacing`, … = tokens.json family with a `tokens` array;
//   `type.families` = key of `type.families`; `type.styles` = text style
//   `token`, field `prop`.
//   theme: path from the theme.json root; `{ slug }` picks a preset by slug.
//   format: `px` (upper bound of a clamp/preset/rem → px) or `fluid-clamp`
//   (fluid font size → DS clamp()); none = value copied as is.

const settings = ( ...path ) => [ 'settings', ...path ];

const COLORS = [
	'base',
	'contrast',
	'primary',
	'primary-dark',
	'primary-light',
	'accent',
	'accent-light',
	'accent-dark',
	'neutral-100',
	'neutral-300',
	'neutral-600',
	'success',
	'danger',
	'overlay',
	'overlay-soft',
];
const FONT_SIZES = [
	'small',
	'medium',
	'large',
	'x-large',
	'xx-large',
	'xxx-large',
];
const SPACING = [ '20', '30', '40', '50', '60', '70', '80' ];
// DS type.families key → theme.json fontFamilies slug.
const FONT_FAMILIES = { heading: 'archivo', body: 'source-sans-3' };
// DS text styles and the fields each one sets (letterSpacing only where the DS has it).
const TEXT_STYLES = {
	h1: [ 'letterSpacing' ],
	h2: [ 'letterSpacing' ],
	h3: [],
	h4: [],
	eyebrow: [ 'letterSpacing' ],
	button: [ 'letterSpacing' ],
	lead: [],
	body: [],
	'body-strong': [],
	meta: [],
};

export const THEME_MAP = [
	...COLORS.map( ( slug ) => ( {
		ds: { family: 'color', token: slug },
		theme: settings( 'color', 'palette', { slug }, 'color' ),
	} ) ),
	...Object.entries( FONT_FAMILIES ).map( ( [ token, slug ] ) => ( {
		ds: { family: 'type.families', token },
		theme: settings( 'typography', 'fontFamilies', { slug }, 'fontFamily' ),
	} ) ),
	...Object.entries( TEXT_STYLES ).flatMap( ( [ token, extra ] ) =>
		[ 'fontSize', 'lineHeight', 'fontWeight', ...extra ].map(
			( prop ) => ( {
				ds: { family: 'type.styles', token, prop },
				theme: settings( 'custom', 'typography', token, prop ),
				...( prop === 'fontSize' && { format: 'px' } ),
			} )
		)
	),
	...SPACING.map( ( slug ) => ( {
		ds: { family: 'spacing', token: `spacing-${ slug }` },
		theme: settings( 'spacing', 'spacingSizes', { slug }, 'size' ),
		format: 'px',
	} ) ),
	...[ 'small', 'medium', 'full' ].map( ( name ) => ( {
		ds: { family: 'radius', token: `radius-${ name }` },
		theme: settings( 'custom', 'radius', name ),
	} ) ),
	...[ 'card', 'raised' ].map( ( slug ) => ( {
		ds: { family: 'shadow', token: `shadow-${ slug }` },
		theme: settings( 'shadow', 'presets', { slug }, 'shadow' ),
	} ) ),
	...FONT_SIZES.map( ( slug ) => ( {
		ds: { family: 'fluid', token: `fluid-font-${ slug }` },
		theme: settings( 'typography', 'fontSizes', { slug } ),
		format: 'fluid-clamp',
	} ) ),
	...SPACING.map( ( slug ) => ( {
		ds: { family: 'fluid', token: `fluid-spacing-${ slug }` },
		theme: settings( 'spacing', 'spacingSizes', { slug }, 'size' ),
	} ) ),
	...[ 'hairline', 'control', 'accent', 'focus' ].map( ( name ) => ( {
		ds: { family: 'border', token: `border-${ name }` },
		theme: settings( 'custom', 'border', name ),
	} ) ),
	{
		ds: { family: 'layout', token: 'content-size' },
		theme: settings( 'layout', 'contentSize' ),
	},
	{
		ds: { family: 'layout', token: 'wide-size' },
		theme: settings( 'layout', 'wideSize' ),
	},
	{
		ds: { family: 'layout', token: 'hero-min-height-large' },
		theme: settings( 'custom', 'hero-min-height-large' ),
	},
	...[ 'wide', 'photo', 'square' ].map( ( name ) => ( {
		ds: { family: 'ratio', token: `ratio-${ name }` },
		theme: settings( 'custom', 'ratio', name ),
	} ) ),
];

if ( import.meta.main ) {
	process.stdout.write( JSON.stringify( THEME_MAP, null, '\t' ) + '\n' );
}
