<?php
/**
 * Styleguide group „Zdjęcia” (#42): pw_photo() frames in every ratio, with and without priority.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'zdjecia',
			'title'  => 'Zdjęcia',
			'note'   => 'pw_photo(): kadr sp-photo w proporcjach custom.ratio (16:9, 4:3, 1:1), object-position 50% 30%, promień custom.radius.medium. Domyślnie loading="lazy"; priority (Hero) = eager + fetchpriority="high". Zdjęcia testowe importuje styleguide (meta _pw_sg_fixture).',
			'order'  => 60,
			'render' => static function (): void {
				pw_sg_row( 'Proporcje: 16:9, 4:3, 1:1', 'proporcje' );
				foreach ( array( 'wide', 'photo', 'square' ) as $ratio ) {
					// phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_photo().
					echo '<div class="sg-demo__col">' . pw_sg_photo( $ratio, array( 'sizes' => '(min-width: 1200px) 370px, 100vw' ) ) . '</div>';
				}
				pw_sg_row_end();

				pw_sg_row( 'Z priority (Hero, eager)', 'priority' );
				$hero = pw_sg_photo(
					'wide',
					array(
						'priority' => true,
						'sizes'    => '(min-width: 1200px) 1200px, 100vw',
					)
				);
				echo '<div class="sg-demo__full">' . $hero . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_photo().
				pw_sg_row_end();

				pw_sg_row( 'Zaokrąglony kadr i bleed (sp-photo--bleed, bez promienia)', 'bleed' );
				foreach ( array( '', 'sp-photo--bleed' ) as $class ) {
					echo '<div class="sg-demo__col">' . pw_sg_photo( 'photo', array( 'class' => $class ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in pw_photo().
				}
				echo '<div class="sg-demo__col"></div>';
				pw_sg_row_end();
			},
		);
		return $groups;
	}
);
