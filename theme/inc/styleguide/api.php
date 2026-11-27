<?php
/**
 * Public styleguide API (#42): enablement and the group registry.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether `/?pw_styleguide=1` is served. Default: PW\Styleguide\enabled_by_default();
 * filter `pw/styleguide/enabled` overrides it (e.g. to switch it on for one stage session).
 */
function pw_styleguide_enabled(): bool {
	return (bool) apply_filters(
		'pw/styleguide/enabled',
		\PW\Styleguide\enabled_by_default( wp_get_environment_type(), defined( 'WP_DEBUG' ) && WP_DEBUG )
	);
}

/**
 * Registered styleguide groups, sorted by `order`, then `slug`.
 *
 * Partials add a group with the `pw/styleguide/groups` filter (template: partials/_template.php):
 * `[ 'slug' => 'hero', 'title' => 'Hero', 'note' => 'Design note', 'order' => 100, 'render' => callable ]`.
 * The section (`pw_sg_section()`) is opened by the page; `render` prints rows (`pw_sg_row()`).
 * Entries without a slug, a title or a callable `render` are dropped.
 *
 * @return array<int, array{slug: string, title: string, note: string, order: int, render: callable}>
 */
function pw_sg_groups(): array {
	$groups = array();
	foreach ( (array) apply_filters( 'pw/styleguide/groups', array() ) as $group ) {
		if ( ! is_array( $group ) || ! isset( $group['render'] ) || ! is_callable( $group['render'] ) ) {
			continue;
		}
		$slug  = sanitize_key( (string) ( $group['slug'] ?? '' ) );
		$title = (string) ( $group['title'] ?? '' );
		if ( '' === $slug || '' === $title ) {
			continue;
		}
		$groups[] = array(
			'slug'   => $slug,
			'title'  => $title,
			'note'   => (string) ( $group['note'] ?? '' ),
			'order'  => (int) ( $group['order'] ?? 100 ),
			'render' => $group['render'],
		);
	}
	usort(
		$groups,
		static fn( array $a, array $b ): int => array( $a['order'], $a['slug'] ) <=> array( $b['order'], $b['slug'] )
	);
	return $groups;
}
