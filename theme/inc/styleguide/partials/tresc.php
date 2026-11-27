<?php
/**
 * Styleguide group „Treść (sp-prose)” (#42): core blocks inside a `sp-prose` container.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'tresc',
			'title'  => 'Treść (sp-prose)',
			'note'   => 'Klasa sp-prose na grupie lub post-content (Aktualność, strony prawne): rytm custom.prose.flow, markery list w accent, cytat, tabela, obraz z figcaption. Linki i nagłówki przez zmienne kontekstu.',
			'order'  => 70,
			'render' => static function (): void {
				$id    = pw_sg_fixture_image( 'photo' );
				$image = '';
				if ( $id ) {
					$image = sprintf(
						'<!-- wp:image {"id":%1$d,"sizeSlug":"pw-photo","className":"is-style-photo"} --><figure class="wp-block-image size-pw-photo is-style-photo"><img src="%2$s" alt="Zdjęcie testowe styleguide" class="wp-image-%1$d"/><figcaption class="wp-element-caption">Podpis zdjęcia: trening w parach.</figcaption></figure><!-- /wp:image -->',
						$id,
						esc_url( (string) wp_get_attachment_image_url( $id, 'pw-photo' ) )
					);
				}
				$prose = '<!-- wp:group {"className":"sp-prose"} --><div class="wp-block-group sp-prose">'
					. '<!-- wp:heading --><h2 class="wp-block-heading">Jak wyglądają zajęcia</h2><!-- /wp:heading -->'
					. '<!-- wp:paragraph --><p>Trening zaczyna się rozgrzewką, potem ćwiczymy w parach. <a href="#sg-tresc">Link w treści</a> i <strong>pogrubienie</strong>.</p><!-- /wp:paragraph -->'
					. '<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Wygodny strój sportowy</li><!-- /wp:list-item --><!-- wp:list-item --><li>Butelka wody</li><!-- /wp:list-item --><!-- wp:list-item --><li>Dobre nastawienie</li><!-- /wp:list-item --></ul><!-- /wp:list -->'
					. '<!-- wp:list {"ordered":true} --><ol class="wp-block-list"><!-- wp:list-item --><li>Rozgrzewka</li><!-- /wp:list-item --><!-- wp:list-item --><li>Technika</li><!-- /wp:list-item --><!-- wp:list-item --><li>Ćwiczenia w parach</li><!-- /wp:list-item --></ol><!-- /wp:list -->'
					. '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Opinie rodziców</h3><!-- /wp:heading -->'
					. '<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>Syn po trzech miesiącach jest pewniejszy siebie.</p><!-- /wp:paragraph --><cite>Rodzic, Chorzów</cite></blockquote><!-- /wp:quote -->'
					. '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Grupa</th><th>Dzień</th><th>Godzina</th></tr></thead><tbody><tr><td>Dzieci 6–9 lat</td><td>Wtorek</td><td>17:00</td></tr><tr><td>Dzieci 10–14 lat</td><td>Czwartek</td><td>18:00</td></tr></tbody></table></figure><!-- /wp:table -->'
					. $image
					. '</div><!-- /wp:group -->';

				foreach ( array(
					''     => array( 'Jasne tło', 'jasne' ),
					'navy' => array( 'Na granacie', 'navy' ),
				) as $bg => $row ) {
					pw_sg_row( $row[0], $row[1], $bg );
					echo do_blocks( $prose ); // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
					pw_sg_row_end();
				}
			},
		);
		return $groups;
	}
);
