<?php
/**
 * Styleguide group „Kolory i tokeny” (#42): presets from theme.json.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'tokeny',
			'title'  => 'Kolory i tokeny',
			'note'   => 'Jedyne źródło wartości to theme.json (ADR 0003, 0009). CSS motywu używa wyłącznie var(--wp--preset--…) i var(--wp--custom--…); literały px/rem i kolory blokuje Stylelint.',
			'order'  => 10,
			'render' => static function (): void {
				pw_sg_row( 'Kolory (presety palety)', 'kolory' );
				foreach ( wp_get_global_settings( array( 'color', 'palette' ) )['theme'] ?? array() as $color ) {
					printf(
						'<div class="sg-swatch"><span class="sg-swatch__chip" style="background: var(--wp--preset--color--%1$s)"></span><code>%1$s</code><span>%2$s</span></div>',
						esc_attr( $color['slug'] ),
						esc_html( $color['color'] )
					);
				}
				pw_sg_row_end();

				pw_sg_row( 'Odstępy (presety spacing)', 'odstepy' );
				echo '<div class="sg-demo__full">';
				foreach ( wp_get_global_settings( array( 'spacing', 'spacingSizes' ) )['theme'] ?? array() as $size ) {
					printf(
						'<div class="sg-scale"><code>%1$s</code><span class="sg-scale__bar" style="width: var(--wp--preset--spacing--%1$s)"></span></div>',
						esc_attr( $size['slug'] )
					);
				}
				echo '</div>';
				pw_sg_row_end();

				pw_sg_row( 'Promienie i cienie', 'promienie-cienie' );
				foreach ( array( 'small', 'medium', 'full' ) as $radius ) {
					printf(
						'<div class="sg-swatch"><span class="sg-swatch__chip sg-swatch__chip--outline" style="border-radius: var(--wp--custom--radius--%1$s)"></span><code>radius %1$s</code></div>',
						esc_attr( $radius )
					);
				}
				foreach ( array( 'card', 'raised' ) as $shadow ) {
					printf(
						'<div class="sg-swatch"><span class="sg-swatch__chip sg-swatch__chip--card" style="box-shadow: var(--wp--preset--shadow--%1$s)"></span><code>shadow %1$s</code></div>',
						esc_attr( $shadow )
					);
				}
				pw_sg_row_end();
			},
		);
		return $groups;
	}
);
