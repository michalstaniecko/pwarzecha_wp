<?php
/**
 * Styleguide group „Plakietki” (#42): pw_badge() variants and the paragraph style.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'plakietki',
			'title'  => 'Plakietki',
			'note'   => 'pw_badge(): domyślna (accent), outline, quiet (--sp-icon-bg). Typografia custom.typography.badge (13 px, wersaliki), promień custom.radius.full. W edytorze: styl akapitu „Plakietka” (is-style-badge-quiet).',
			'order'  => 50,
			'render' => static function (): void {
				foreach ( array(
					''     => array( 'Jasne tło', 'jasne' ),
					'navy' => array( 'Na granacie', 'navy' ),
				) as $bg => $row ) {
					pw_sg_row( $row[0], $row[1], $bg );
					foreach ( array(
						'default' => 'Nowość',
						'outline' => 'Dzieci 6–9 lat',
						'quiet'   => 'Chorzów',
					) as $variant => $text ) {
						echo pw_badge( $text, $variant ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_badge().
					}
					echo do_blocks( '<!-- wp:paragraph {"className":"is-style-badge-quiet"} --><p class="is-style-badge-quiet">Akapit: Plakietka</p><!-- /wp:paragraph -->' ); // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
					pw_sg_row_end();
				}
			},
		);
		return $groups;
	}
);
