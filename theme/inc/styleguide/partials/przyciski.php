<?php
/**
 * Styleguide group „Przyciski” (#42): pw_button() variants × context × state, core/button.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'przyciski',
			'title'  => 'Przyciski',
			'note'   => 'pw_button() (bloki) i core/button (edytor, style Główny/Drugorzędny). Wysokość min. custom.size.target (48 px), kolory z presetów i zmiennych kontekstu --sp-*. Stany :hover i :focus-visible są tu wymuszone klasami is-hover / is-focus, których prawdziwy markup nie używa.',
			'order'  => 40,
			'render' => static function (): void {
				$states = array(
					'domyslny' => array( 'domyślny', '' ),
					'hover'    => array( ':hover', 'is-hover' ),
					'focus'    => array( ':focus-visible', 'is-focus' ),
				);
				foreach ( array(
					'light' => array( 'Jasne tło', '' ),
					'navy'  => array( 'Na granacie', 'navy' ),
				) as $on => $context ) {
					foreach ( $states as $state => $state_info ) {
						pw_sg_row( $context[0] . ' · ' . $state_info[0], $on . '-' . $state, $context[1] );
						$attrs = '' === $state_info[1] ? array() : array( 'class' => $state_info[1] );
						foreach ( array(
							array(
								'variant' => 'primary',
								'text'    => 'Zapisz się',
							),
							array(
								'variant' => 'secondary',
								'text'    => 'Zobacz cennik',
							),
							array(
								'variant' => 'phone',
								'text'    => '+48 600 000 000',
							),
							array(
								'variant' => 'email',
								'text'    => 'kontakt@example.com',
							),
							array(
								'variant'       => 'link',
								'text'          => 'Czytaj dalej',
								'hidden_suffix' => 'o zajęciach',
							),
						) as $args ) {
							// phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_button().
							echo pw_button(
								$args + array(
									'href'  => '#sg-przyciski',
									'on'    => $on,
									'attrs' => $attrs,
								)
							);
						}
						pw_sg_row_end();
					}
				}

				pw_sg_row( 'Z ikoną i długi adres e-mail', 'ikona-dlugi-email' );
				// phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_button().
				echo pw_button(
					array(
						'text' => 'Pierwszy trening gratis',
						'href' => '#sg-przyciski',
						'icon' => 'gift',
					)
				);
				// phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_button().
				echo pw_button(
					array(
						'variant' => 'email',
						'text'    => 'zapisy.samoobrona.dla.dzieci@przyklad-dlugiej-domeny.pl',
					)
				);
				pw_sg_row_end();

				foreach ( array(
					''     => array( 'core/button (edytor) · jasne tło', 'core-button' ),
					'navy' => array( 'core/button (edytor) · na granacie', 'core-button-navy' ),
				) as $bg => $row ) {
					pw_sg_row( $row[0], $row[1], $bg );
					// phpcs:ignore WordPress.Security.EscapeOutput -- block output.
					echo do_blocks(
						'<!-- wp:buttons --><div class="wp-block-buttons">'
						. '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#sg-przyciski">Zapisz się</a></div><!-- /wp:button -->'
						. '<!-- wp:button {"className":"is-style-secondary"} --><div class="wp-block-button is-style-secondary"><a class="wp-block-button__link wp-element-button" href="#sg-przyciski">Zobacz cennik</a></div><!-- /wp:button -->'
						. '</div><!-- /wp:buttons -->'
					);
					pw_sg_row_end();
				}
			},
		);
		return $groups;
	}
);
