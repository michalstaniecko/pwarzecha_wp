<?php
/**
 * Styleguide group „Kafelki” (#60): pw/tiles grids (3 and 4 columns) with pw/photo-tile.
 * #61 (pw/icon-tile) adds its own tile closure and rows at the end of `render`; $grid() takes any
 * serialized tiles.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'      => 'tiles',
			'title'     => 'Kafelki',
			'note'      => 'pw/tiles: sp-grid--3 (1 kolumna, 2 od 640 px, 3 od 960 px) i sp-grid--4 (1, 2×2 od 640 px, 4 od 1100 px), jeden rodzaj kafelka na siatkę. pw/photo-tile: zdjęcie 4:3 (custom.ratio.photo, radius.medium, lazy), akcent nad H3 (custom.size.rule × custom.border.accent, accent), tekst do 160 znaków. Bez zdjęcia – sam tytuł i tekst; pusty tytuł – kafelek się nie renderuje.',
			'order'     => 100,
			'viewports' => array( 375, 768 ),
			'render'    => static function (): void {
				$photo_id = pw_sg_fixture_image( 'photo' );

				/**
				 * Prints a pw/tiles block (through do_blocks()) around the given tiles, full row width.
				 *
				 * @param string   $kolumny `3` | `4`.
				 * @param string[] $tiles   Serialized inner blocks.
				 */
				$grid = static function ( string $kolumny, array $tiles ): void {
					$markup = '<!-- wp:pw/tiles {"kolumny":"' . $kolumny . '"} -->' . implode( '', $tiles ) . '<!-- /wp:pw/tiles -->';
					echo '<div class="sg-demo__full">' . do_blocks( $markup ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
				};

				/**
				 * Serialized pw/photo-tile block.
				 *
				 * @param string $tytul     Title.
				 * @param string $tekst     Text.
				 * @param int    $zdjecie_id Attachment ID, 0 = no photo.
				 */
				$photo_tile = static function ( string $tytul, string $tekst, int $zdjecie_id ): string {
					return serialize_block(
						array(
							'blockName'    => 'pw/photo-tile',
							'attrs'        => array(
								'zdjecieId' => $zdjecie_id,
								'tytul'     => $tytul,
								'tekst'     => $tekst,
							),
							'innerBlocks'  => array(),
							'innerHTML'    => '',
							'innerContent' => array(),
						)
					);
				};

				$zalety = array(
					array( 'Bezpieczeństwo', 'Ćwiczymy w kontrolowanych warunkach, z rozgrzewką i asekuracją partnera.' ),
					array( 'Pewność siebie', 'Uczysz się reagować spokojnie, zanim sytuacja zrobi się groźna.' ),
					array( 'Kondycja', 'Trening angażuje całe ciało i poprawia wytrzymałość bez siłowni.' ),
					array( 'Dla każdego', 'Nie potrzebujesz doświadczenia ani formy – zaczynasz od podstaw.' ),
					array( 'Praktyka', 'Techniki sprawdzone w realnych sytuacjach, bez pokazowych akrobacji.' ),
					array( 'Grupa', 'Ćwiczysz z ludźmi o podobnym celu, w życzliwej atmosferze.' ),
				);
				$tiles = array_map( static fn( array $z ): string => $photo_tile( $z[0], $z[1], $photo_id ), $zalety );

				pw_sg_row( 'Kafelki ze zdjęciem ×6, sp-grid--3 (jasne tło)', 'photo-3' );
				$grid( '3', $tiles );
				pw_sg_row_end();

				pw_sg_row( 'Kafelek bez zdjęcia (i bez tekstu) obok pełnego', 'bez-zdjecia' );
				$grid(
					'3',
					array(
						$photo_tile( 'Bez zdjęcia', 'Kafelek bez obrazu: sam akcent, tytuł i tekst.', 0 ),
						$photo_tile( 'Sam tytuł', '', 0 ),
						$tiles[0],
					)
				);
				pw_sg_row_end();

				pw_sg_row( 'sp-grid--4, cztery kafelki', 'photo-4' );
				$grid( '4', array_slice( $tiles, 0, 4 ) );
				pw_sg_row_end();

				pw_sg_row( 'Na granacie, sp-grid--3', 'navy', 'navy' );
				$grid( '3', array_slice( $tiles, 0, 3 ) );
				pw_sg_row_end();
			},
		);
		return $groups;
	}
);
