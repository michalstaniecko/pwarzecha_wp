<?php
/**
 * Inline SVG icons: Lucide + Simple Icons (`brand-*`) from theme/assets/icons (#39, ADR 0004).
 *
 * The SVG files and `manifest.json` come from `npm run icons` (tools/icons/sync-icons.mjs), which
 * already normalized them (currentColor, no width/height). Size comes from the `sp-icon` class.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Root presentation attributes copied from the SVG file onto the rendered <svg>.
 */
const PW_ICON_SVG_ATTRIBUTES = array( 'xmlns', 'viewBox', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin' );

/**
 * Sizes mapped to `sp-icon--<size>` (theme.json `custom.size.icon*` tokens).
 */
const PW_ICON_SIZES = array( 'small', 'medium', 'large' );

/**
 * Icon manifest: name → { source, file, label, meaning }. Read once per request.
 *
 * @return array<string, array{source: string, file: string, label: string, meaning: string}>
 */
function pw_icon_manifest(): array {
	static $manifest = null;
	if ( null === $manifest ) {
		$json     = file_get_contents( pw_icon_dir() . 'manifest.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		$manifest = is_string( $json ) ? (array) json_decode( $json, true ) : array();
	}
	return $manifest;
}

/**
 * Absolute path of theme/assets/icons/ with a trailing slash.
 */
function pw_icon_dir(): string {
	return dirname( __DIR__, 2 ) . '/assets/icons/';
}

/**
 * Icons for the editor: name → { label (PL), meaning } from the manifest.
 *
 * @return array<string, array{label: string, meaning: string}>
 */
function pw_icon_names(): array {
	return array_map(
		static fn( array $icon ): array => array(
			'label'   => (string) $icon['label'],
			'meaning' => (string) $icon['meaning'],
		),
		pw_icon_manifest()
	);
}

/**
 * Parsed SVG file: root attributes and inner markup. Cached per request.
 *
 * @param string $name Icon name from the manifest.
 * @return array{attributes: array<string, string>, inner: string}|null Null for an unknown name.
 */
function pw_icon_svg( string $name ): ?array {
	static $cache = array();
	if ( array_key_exists( $name, $cache ) ) {
		return $cache[ $name ];
	}

	$manifest = pw_icon_manifest();
	$svg      = isset( $manifest[ $name ]['file'] )
		? file_get_contents( pw_icon_dir() . $manifest[ $name ]['file'] ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		: false;

	$cache[ $name ] = null;
	if ( is_string( $svg ) && preg_match( '#^\s*<svg\b([^>]*)>(.*)</svg>\s*$#s', $svg, $match ) ) {
		preg_match_all( '/([\w:-]+)="([^"]*)"/', $match[1], $pairs, PREG_SET_ORDER );
		$attributes = array();
		foreach ( $pairs as $pair ) {
			if ( in_array( $pair[1], PW_ICON_SVG_ATTRIBUTES, true ) ) {
				$attributes[ $pair[1] ] = $pair[2];
			}
		}
		$cache[ $name ] = array(
			'attributes' => $attributes,
			'inner'      => $match[2],
		);
	}
	return $cache[ $name ];
}

/**
 * Inline SVG icon.
 *
 * @param string $name Manifest name, e.g. `phone`, `brand-facebook`.
 * @param array  $args {
 *     Optional.
 *
 *     @type string $label Accessible name; without it the icon is decorative (aria-hidden).
 *     @type string $class Extra classes next to `sp-icon`.
 *     @type string $size  `small` | `medium` | `large` (`sp-icon--<size>`); default 1.5em via `sp-icon`.
 * }
 * @phpstan-param array{label?: string, class?: string, size?: string} $args
 * @return string SVG markup, '' for an unknown name.
 */
function pw_icon( string $name, array $args = array() ): string {
	$svg = pw_icon_svg( $name );
	if ( null === $svg ) {
		_doing_it_wrong(
			'pw_icon',
			sprintf( 'Unknown icon "%s" (theme/assets/icons/manifest.json).', esc_html( $name ) ),
			'0.1.0'
		);
		return '';
	}

	$classes = array( 'sp-icon' );
	$size    = (string) ( $args['size'] ?? '' );
	if ( in_array( $size, PW_ICON_SIZES, true ) ) {
		$classes[] = 'sp-icon--' . $size;
	}
	$extra = trim( (string) ( $args['class'] ?? '' ) );
	if ( '' !== $extra ) {
		$classes[] = $extra;
	}

	$label      = trim( (string) ( $args['label'] ?? '' ) );
	$attributes = array( 'class' => implode( ' ', $classes ) );
	if ( '' === $label ) {
		$attributes['aria-hidden'] = 'true';
		$attributes['focusable']   = 'false';
	} else {
		$attributes['role']       = 'img';
		$attributes['aria-label'] = $label;
	}
	$attributes += $svg['attributes'];

	$html = '<svg';
	foreach ( $attributes as $attribute => $value ) {
		$html .= ' ' . $attribute . '="' . esc_attr( $value ) . '"';
	}
	$html .= '>';
	if ( '' !== $label ) {
		$html .= '<title>' . esc_html( $label ) . '</title>';
	}
	return $html . $svg['inner'] . '</svg>';
}
