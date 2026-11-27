<?php
/**
 * Styleguide group „Ikony” (#42): every icon of the manifest through pw_icon().
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'ikony',
			'title'  => 'Ikony',
			'note'   => 'Lucide (ADR 0004) i Simple Icons z theme/assets/icons/manifest.json, kolor currentColor. Rozmiary: domyślnie 1.5em, small/medium/large = custom.size.icon-small/icon/icon-large. Dekoracyjne mają aria-hidden; z etykietą role="img".',
			'order'  => 30,
			'render' => static function (): void {
				pw_sg_row( 'Rozmiary: small, medium, large, domyślny', 'rozmiary' );
				foreach ( array( 'small', 'medium', 'large', '' ) as $size ) {
					// phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_icon().
					echo pw_icon( 'shield-check', '' === $size ? array() : array( 'size' => $size ) );
				}
				pw_sg_row_end();

				foreach ( array(
					''     => array( 'Wszystkie ikony · jasne tło', 'wszystkie' ),
					'navy' => array( 'Wszystkie ikony · na granacie', 'wszystkie-navy' ),
				) as $bg => $row ) {
					pw_sg_row( $row[0], $row[1], $bg );
					foreach ( pw_icon_names() as $name => $icon ) {
						printf(
							'<figure class="sg-icon">%s<figcaption><code>%s</code> %s</figcaption></figure>',
							pw_icon( $name, array( 'size' => 'large' ) ), // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_icon().
							esc_html( $name ),
							esc_html( $icon['label'] )
						);
					}
					pw_sg_row_end();
				}
			},
		);
		return $groups;
	}
);
