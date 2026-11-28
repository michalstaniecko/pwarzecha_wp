<?php
/**
 * Styleguide chrome `.sg-*` (#42): sections and rows around the demos. Never on real pages.
 *
 * Markup contract read by tests/e2e/styleguide.spec.ts: a section has `id="sg-<group>"` and
 * `data-sg-section="<group>"` (plus `data-sg-viewports="375 768"` when the group asks for extra
 * screenshot widths), a row `data-sg-state="<state>"` and, on navy, `data-sg-bg="navy"`.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Opens a section (one per group). The page calls it for every registered group.
 *
 * @param string $id        Group slug.
 * @param string $title     Section heading.
 * @param string $note      Design note (token rule, exceptions); '' = none.
 * @param int[]  $viewports Extra screenshot widths (px), see pw_sg_groups(); [] = desktop only.
 */
function pw_sg_section( string $id, string $title, string $note = '', array $viewports = array() ): void {
	$id = sanitize_key( $id );
	printf(
		'<section class="sg-section" id="sg-%1$s" data-sg-section="%1$s"%3$s aria-labelledby="sg-%1$s-title"><h2 id="sg-%1$s-title">%2$s</h2>',
		esc_attr( $id ),
		esc_html( $title ),
		array() === $viewports ? '' : ' data-sg-viewports="' . esc_attr( implode( ' ', array_map( 'intval', $viewports ) ) ) . '"'
	);
	if ( '' !== $note ) {
		printf( '<p class="sg-section__note">%s</p>', esc_html( $note ) );
	}
}

/**
 * Closes pw_sg_section().
 */
function pw_sg_section_end(): void {
	echo '</section>';
}

/**
 * Opens a row: one state of the demo (e.g. „Pełne dane”, „Puste dane”, „Na granacie”).
 *
 * @param string $state_label Visible label.
 * @param string $state_slug  `data-sg-state` value.
 * @param string $bg          `navy` = navy background + `sp-on-navy` context; '' = light.
 */
function pw_sg_row( string $state_label, string $state_slug, string $bg = '' ): void {
	$navy = 'navy' === $bg;
	printf(
		'<div class="%s" data-sg-state="%s"%s><p class="sg-row__label">%s</p><div class="sg-demo">',
		$navy ? 'sg-row sp-on-navy' : 'sg-row',
		esc_attr( sanitize_key( $state_slug ) ),
		$navy ? ' data-sg-bg="navy"' : '',
		esc_html( $state_label )
	);
}

/**
 * Closes pw_sg_row().
 */
function pw_sg_row_end(): void {
	echo '</div></div>';
}
