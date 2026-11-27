<?php
/**
 * Render helpers: the only way blocks render DS buttons, links, photos and badges (#40).
 *
 * Pure functions returning escaped markup (classes 1:1 with tools/ds/fixtures/ds-bundle.css).
 * Echo the result with `// phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_*()`.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Button variants (`sp-btn--<variant>`; `link` renders `sp-link` via pw_link()).
 */
const PW_BUTTON_VARIANTS = array( 'primary', 'secondary', 'phone', 'email', 'link' );

/**
 * Colour contexts: `on` value => context class (src/styles/context.css).
 */
const PW_CONTEXTS = array(
	'light' => 'sp-on-light',
	'navy'  => 'sp-on-navy',
);

/**
 * DS button `sp-btn sp-btn--<variant>`.
 *
 * @param array $args {
 *     Button arguments.
 *
 *     @type string $variant `primary` (default) | `secondary` | `phone` | `email` | `link`.
 *     @type string $text    Visible text (phone number / e-mail address for those variants).
 *     @type string $href    Target URL.
 *     @type string $icon    pw_icon() name, rendered before the text.
 *     @type string $on      `light` | `navy` – colour context class.
 *     @type array  $attrs   Extra attributes (see pw_anchor()).
 * }
 * @phpstan-param array<string, mixed> $args
 */
function pw_button( array $args ): string {
	$variant = (string) ( $args['variant'] ?? 'primary' );
	if ( ! in_array( $variant, PW_BUTTON_VARIANTS, true ) ) {
		$variant = 'primary';
	}
	if ( 'link' === $variant ) {
		return pw_link( $args );
	}
	$classes = array( 'sp-btn', 'sp-btn--' . $variant, pw_context_class( $args ) );
	$text    = (string) ( $args['text'] ?? '' );
	$href    = (string) ( $args['href'] ?? '' );
	$icon    = (string) ( $args['icon'] ?? '' );
	$label   = esc_html( $text );
	if ( 'phone' === $variant ) {
		$href  = '' === $href ? pw_tel_href( $text ) : $href;
		$icon  = array_key_exists( 'icon', $args ) ? $icon : 'phone';
		$label = '<span class="sp-btn__number sp-nowrap">' . $label . '</span>';
	} elseif ( 'email' === $variant ) {
		$href  = '' === $href ? pw_mailto_href( $text ) : $href;
		$icon  = array_key_exists( 'icon', $args ) ? $icon : 'mail';
		$label = '<span class="sp-btn__email">' . $label . '</span>';
	}

	return pw_anchor( $classes, $href, (array) ( $args['attrs'] ?? array() ) )
		. ( '' === $icon ? '' : pw_icon( $icon ) )
		. $label . '</a>';
}

/**
 * DS text link `sp-link`: text, optional visually hidden context, trailing icon.
 *
 * @param array $args {
 *     Link arguments.
 *
 *     @type string $text          Visible text, e.g. „Czytaj dalej”.
 *     @type string $href          Target URL.
 *     @type string $icon          pw_icon() name after the text; default `arrow-right`, '' = none.
 *     @type string $hidden_suffix Context for screen readers (`sp-visually-hidden`), e.g. the post title.
 *     @type string $on            `light` | `navy` – colour context class.
 *     @type array  $attrs         Extra attributes (see pw_anchor()).
 * }
 * @phpstan-param array<string, mixed> $args
 */
function pw_link( array $args ): string {
	$icon   = (string) ( $args['icon'] ?? 'arrow-right' );
	$suffix = trim( (string) ( $args['hidden_suffix'] ?? '' ) );

	return pw_anchor( array( 'sp-link', pw_context_class( $args ) ), (string) ( $args['href'] ?? '' ), (array) ( $args['attrs'] ?? array() ) )
		. esc_html( (string) ( $args['text'] ?? '' ) )
		. ( '' === $suffix ? '' : '<span class="sp-visually-hidden"> ' . esc_html( $suffix ) . '</span>' )
		. ( '' === $icon ? '' : pw_icon( $icon ) )
		. '</a>';
}

/**
 * Context class for `$args['on']` (`light` | `navy`), '' otherwise.
 *
 * @param array<string, mixed> $args Helper arguments.
 */
function pw_context_class( array $args ): string {
	return PW_CONTEXTS[ (string) ( $args['on'] ?? '' ) ] ?? '';
}

/**
 * Opening `<a>` tag: classes (+ `attrs['class']`), escaped href, extra attributes.
 *
 * Extra attributes cannot override `href`; names outside `[a-z0-9_:-]` and `on*` handlers are dropped.
 *
 * @param string[]             $classes Classes.
 * @param string               $href    URL.
 * @param array<string, mixed> $attrs   Extra attributes; `true` = boolean attribute, `false`/`null` = omitted.
 */
function pw_anchor( array $classes, string $href, array $attrs = array() ): string {
	if ( isset( $attrs['class'] ) ) {
		$classes[] = (string) $attrs['class'];
	}
	$html = '<a class="' . esc_attr( pw_classes( $classes ) ) . '" href="' . esc_url( $href ) . '"';
	foreach ( $attrs as $name => $value ) {
		$name = strtolower( (string) $name );
		if ( in_array( $name, array( 'class', 'href' ), true ) || str_starts_with( $name, 'on' )
			|| ! preg_match( '/^[a-z][a-z0-9_:-]*$/', $name ) || false === $value || null === $value ) {
			continue;
		}
		$html .= true === $value ? ' ' . $name : ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
	}
	return $html . '>';
}

/**
 * Class list from conditions: `'is-x' => $bool` adds `is-x` when truthy; a numeric key adds its
 * value (may hold several classes). Empty and duplicate classes are dropped.
 *
 * @param array<int|string, mixed> $map Classes.
 * @return string Space-separated classes (not escaped – wrap in esc_attr()).
 */
function pw_classes( array $map ): string {
	$classes = array();
	foreach ( $map as $key => $value ) {
		$list = is_int( $key ) ? (string) $value : ( $value ? (string) $key : '' );
		foreach ( preg_split( '/\s+/', $list, -1, PREG_SPLIT_NO_EMPTY ) as $class ) {
			$classes[ $class ] = true;
		}
	}
	return implode( ' ', array_keys( $classes ) );
}

/**
 * `tel:` URL: spaces, dashes, dots and brackets removed, `+48` added when there is no prefix.
 *
 * @param string $phone Number as displayed, e.g. `600 000 000`, `+48 600-000-000`.
 * @return string `tel:+48600000000`, '' without digits.
 */
function pw_tel_href( string $phone ): string {
	$number = preg_replace( '/[^\d+]/', '', $phone );
	if ( '' === $number || null === $number ) {
		return '';
	}
	if ( str_starts_with( $number, '00' ) ) {
		$number = '+' . substr( $number, 2 );
	} elseif ( ! str_starts_with( $number, '+' ) ) {
		$number = '+48' . $number;
	}
	return 'tel:' . $number;
}

/**
 * `mailto:` URL of a sanitized address.
 *
 * @param string $email Address.
 * @return string `mailto:…`, '' for an invalid address.
 */
function pw_mailto_href( string $email ): string {
	$email = sanitize_email( $email );
	return '' === $email ? '' : 'mailto:' . $email;
}

/**
 * Stable `id` of a Sekcja heading (target of `pw/section` `aria-labelledby`).
 *
 * Pure and deterministic – no per-page counter – so `pw/section` computes the same id from
 * `parsed_block` as the heading block does. Uniqueness on a page is the editor's job (#56).
 *
 * @param string      $text Heading text (tags ignored).
 * @param string|null $id   `naglowekId` attribute; wins when non-empty after sanitizing to `[a-z0-9-]`.
 * @return string `$id` sanitized, or `pw-h-` + 6 hex chars of md5( plain text ).
 */
function pw_heading_id( string $text, ?string $id = null ): string {
	$clean = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $id ) ), '-' );
	if ( '' !== $clean ) {
		return $clean;
	}
	return 'pw-h-' . substr( md5( wp_strip_all_tags( $text ) ), 0, 6 );
}

/**
 * Photo frame ratios => default image size (inc/helpers/images.php).
 */
const PW_PHOTO_RATIOS = array(
	'wide'   => 'pw-wide',
	'photo'  => 'pw-photo',
	'square' => 'pw-square',
);

/**
 * DS photo `sp-photo sp-photo--<ratio>` around a responsive attachment image.
 *
 * Always `width`/`height` and `srcset` (wp_get_attachment_image()); alt from the media library.
 *
 * @param int   $attachment_id Image attachment.
 * @param array $args {
 *     Optional.
 *
 *     @type string $ratio    `wide` | `photo` (default) | `square`.
 *     @type string $size     Named size; default `pw-<ratio>` (`pw-wide`, `pw-photo`, `pw-square`).
 *     @type string $sizes    `sizes` attribute; default from WordPress.
 *     @type bool   $priority `true` (LCP image) = `loading="eager" fetchpriority="high"`; else lazy + async.
 *     @type string $class    Extra frame classes, e.g. `sp-photo--bleed`.
 * }
 * @phpstan-param array{ratio?: string, size?: string, sizes?: string, priority?: bool, class?: string} $args
 * @return string Markup, '' when the attachment is missing or not an image (blocks decide on a fallback).
 */
function pw_photo( int $attachment_id, array $args = array() ): string {
	$ratio = (string) ( $args['ratio'] ?? 'photo' );
	if ( ! isset( PW_PHOTO_RATIOS[ $ratio ] ) ) {
		$ratio = 'photo';
	}
	$attr = ! empty( $args['priority'] )
		? array(
			'loading'       => 'eager',
			'fetchpriority' => 'high',
		)
		: array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		);
	if ( ! empty( $args['sizes'] ) ) {
		$attr['sizes'] = (string) $args['sizes'];
	}

	$img = $attachment_id > 0
		? wp_get_attachment_image( $attachment_id, (string) ( $args['size'] ?? PW_PHOTO_RATIOS[ $ratio ] ), false, $attr )
		: '';
	if ( '' === $img ) {
		return '';
	}

	$classes = array( 'sp-photo', 'sp-photo--' . $ratio );
	foreach ( explode( ' ', (string) ( $args['class'] ?? '' ) ) as $extra ) {
		$classes[] = sanitize_html_class( $extra );
	}
	return '<div class="' . esc_attr( pw_classes( $classes ) ) . '">' . $img . '</div>';
}

/**
 * Where a CTA leads (punkt kontaktu, docs/design/decyzje-ds-i-layoutow.md §1).
 *
 * `#kontakt` when the post content has a `pw/contact` block (also nested or inside a synced
 * pattern), else the Kontakt page anchor. The Kontakt page is recognised by its block, not its slug.
 *
 * @param int|null $post_id Post; default the current post.
 * @return string URL (not escaped), filtered by `pw/contact_target` ( $target, $post_id ).
 */
function pw_contact_target( ?int $post_id = null ): string {
	$post   = get_post( $post_id );
	$target = $post && pw_content_has_block( $post->post_content, 'pw/contact' )
		? '#kontakt'
		: home_url( '/kontakt/#kontakt' );

	return (string) apply_filters( 'pw/contact_target', $target, $post ? $post->ID : $post_id );
}

/**
 * Whether block markup contains a block, looking into synced patterns (`core/block` refs).
 *
 * @param string     $content    Block markup.
 * @param string     $block_name Block name.
 * @param array<int> $seen       Synced pattern IDs already visited (loop guard).
 */
function pw_content_has_block( string $content, string $block_name, array $seen = array() ): bool {
	if ( has_block( $block_name, $content ) ) {
		return true;
	}
	if ( ! has_block( 'core/block', $content ) ) {
		return false;
	}

	$blocks = parse_blocks( $content );
	while ( $blocks ) {
		$block  = array_shift( $blocks );
		$blocks = array_merge( $blocks, $block['innerBlocks'] );
		$ref    = (int) ( $block['attrs']['ref'] ?? 0 );
		if ( 'core/block' !== $block['blockName'] || ! $ref || in_array( $ref, $seen, true ) ) {
			continue;
		}
		$pattern = get_post( $ref );
		$seen[]  = $ref;
		if ( $pattern && 'wp_block' === $pattern->post_type
			&& pw_content_has_block( $pattern->post_content, $block_name, $seen ) ) {
			return true;
		}
	}
	return false;
}

/**
 * DS badge `sp-badge` (accent), `sp-badge--outline` or `sp-badge--quiet`.
 *
 * @param string $text    Text.
 * @param string $variant `default` | `outline` | `quiet`; unknown = default.
 */
function pw_badge( string $text, string $variant = 'default' ): string {
	$classes = array(
		'sp-badge',
		'sp-badge--' . $variant => in_array( $variant, array( 'outline', 'quiet' ), true ),
	);
	return '<span class="' . esc_attr( pw_classes( $classes ) ) . '">' . esc_html( $text ) . '</span>';
}
