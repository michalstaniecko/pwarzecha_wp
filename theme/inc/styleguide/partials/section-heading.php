<?php
/**
 * Styleguide group: pw/section-heading (#56) – left full, left H2 only, center full, on navy.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'section-heading',
			'title'  => 'Nagłówek Sekcji',
			'note'   => 'Tokeny: style tekstu eyebrow, h2, lead (theme.json), spacing 20/30/60, content-size, kolory z kontekstu (--sp-eyebrow, --sp-heading, --sp-fg), kreska accent (size rule × border accent). Wyjątki: brak.',
			'order'  => 100,
			'render' => static function (): void {
				$full = array(
					'nadtytul'   => 'Oferta',
					'tytul'      => 'Zajęcia dla każdego',
					'lead'       => 'Wybierz formę zajęć dopasowaną do wieku i celu: w stałej grupie, indywidualnie albo u Was na miejscu.',
					'naglowekId' => 'pw-h-sg0001',
				);
				$rows = array(
					array( 'Do lewej, pełny', 'left-pelny', '', $full ),
					array(
						'Do lewej, sam H2',
						'left-h2',
						'',
						array(
							'tytul'      => 'Grafik zajęć',
							'naglowekId' => 'pw-h-sg0002',
						),
					),
					array(
						'Do środka, pełny',
						'center-pelny',
						'',
						array(
							'wyrownanie' => 'center',
							'naglowekId' => 'pw-h-sg0003',
						) + $full,
					),
					array( 'Na granacie', 'navy', 'navy', array( 'naglowekId' => 'pw-h-sg0004' ) + $full ),
				);
				foreach ( $rows as $row ) {
					pw_sg_row( $row[0], $row[1], $row[2] );
					// phpcs:ignore WordPress.Security.EscapeOutput -- block output.
					echo do_blocks(
						serialize_block(
							array(
								'blockName'    => 'pw/section-heading',
								'attrs'        => $row[3],
								'innerBlocks'  => array(),
								'innerHTML'    => '',
								'innerContent' => array(),
							)
						)
					);
					pw_sg_row_end();
				}
			},
		);
		return $groups;
	}
);
