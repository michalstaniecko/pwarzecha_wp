<?php
/**
 * Styleguide group „Typografia” (#42): core headings and paragraphs styled by theme.json.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'typografia',
			'title'  => 'Typografia',
			'note'   => 'Style z theme.json styles.elements (custom.typography.h-1…meta). Archivo: oś wdth 125 dla H1/H2, 112 dla H3/H4 i przycisków; text-wrap: balance. W edytorze H1 jest niedostępny (poziomy 2–3).',
			'order'  => 20,
			'render' => static function (): void {
				$headings = '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Samoobrona dla dzieci</h1><!-- /wp:heading -->'
					. '<!-- wp:heading --><h2 class="wp-block-heading">Bezpieczeństwo na co dzień</h2><!-- /wp:heading -->'
					. '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Pierwszy trening bezpłatny</h3><!-- /wp:heading -->'
					. '<!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Co zabrać na zajęcia</h4><!-- /wp:heading -->';
				$text     = '<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">Lead: zajęcia uczą reagować spokojnie i pewnie w sytuacjach zagrożenia.</p><!-- /wp:paragraph -->'
					. '<!-- wp:paragraph --><p>Tekst główny w kroju Source Sans 3. <strong>Pogrubienie</strong> i <a href="#sg-typografia">link w treści</a> korzystają ze zmiennych kontekstu.</p><!-- /wp:paragraph -->'
					. '<!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">Meta: 12 listopada 2026 · Chorzów</p><!-- /wp:paragraph -->';

				foreach ( array(
					''     => array( 'Jasne tło', 'jasne' ),
					'navy' => array( 'Na granacie', 'navy' ),
				) as $bg => $row ) {
					pw_sg_row( 'Nagłówki H1–H4 · ' . $row[0], 'naglowki-' . $row[1], $bg );
					echo '<div class="sg-demo__full">' . do_blocks( $headings ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
					pw_sg_row_end();

					pw_sg_row( 'Tekst: lead, body, meta · ' . $row[0], 'tekst-' . $row[1], $bg );
					echo '<div class="sg-demo__full">' . do_blocks( $text ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
					pw_sg_row_end();
				}

				pw_sg_row( 'Długie słowa i wersaliki', 'dlugie-slowa' );
				echo '<div class="sg-demo__full">' . do_blocks( '<!-- wp:heading --><h2 class="wp-block-heading">Konstantynopolitańczykowianeczka i bezpieczeństwo</h2><!-- /wp:heading -->' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
				pw_sg_row_end();
			},
		);
		return $groups;
	}
);
