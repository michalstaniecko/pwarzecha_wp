<?php
/**
 * Styleguide group „Kontekst navy” (#42): the same content in the light and the navy context.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'kontekst-navy',
			'title'  => 'Kontekst navy',
			'note'   => 'Komponenty czytają tylko zmienne --sp-* (context.css): jasne na body, .sp-section, .sp-on-light; granatowe na .sp-section--navy, .sp-on-navy, .sp-hero, .sp-cta-band, .sp-header, .sp-footer. Klasa kontekstu nie ustawia tła – daje je sekcja (tu wiersz z data-sg-bg="navy").',
			'order'  => 90,
			'render' => static function (): void {
				foreach ( array(
					''     => array( 'Jasny (domyślny)', 'jasny' ),
					'navy' => array( 'Granatowy (sp-on-navy)', 'navy' ),
				) as $bg => $row ) {
					pw_sg_row( $row[0], $row[1], $bg );
					echo '<div class="sg-demo__full">';
					// phpcs:ignore WordPress.Security.EscapeOutput -- block output.
					echo do_blocks(
						'<!-- wp:heading --><h2 class="wp-block-heading">Pierwszy trening jest bezpłatny</h2><!-- /wp:heading -->'
						. '<!-- wp:paragraph --><p>Tekst w kolorze --sp-fg, <a href="#sg-kontekst-navy">link --sp-link</a> i <strong>pogrubienie</strong>.</p><!-- /wp:paragraph -->'
					);
					echo '<p>' . pw_badge( 'Nowość' ) . ' ' . pw_badge( 'Chorzów', 'quiet' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_badge().
					$actions = array(
						pw_button(
							array(
								'text' => 'Zapisz się',
								'href' => '#sg-kontekst-navy',
							)
						),
						pw_button(
							array(
								'variant' => 'secondary',
								'text'    => 'Zobacz cennik',
								'href'    => '#sg-kontekst-navy',
							)
						),
						pw_button(
							array(
								'variant' => 'phone',
								'text'    => '+48 600 000 000',
							)
						),
						pw_link(
							array(
								'text' => 'Wszystkie Aktualności',
								'href' => '#sg-kontekst-navy',
							)
						),
						pw_icon( 'shield-check', array( 'size' => 'large' ) ),
					);
					echo '<div class="sg-demo">' . implode( '', $actions ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_*().
					pw_sg_row_end();
				}
			},
		);
		return $groups;
	}
);
