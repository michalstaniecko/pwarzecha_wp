<?php
/**
 * Styleguide group „Paginacja i pusta lista” (#42): core query markup as WordPress prints it.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'   => 'paginacja',
			'title'  => 'Paginacja i pusta lista',
			'note'   => 'Style na markupie core/query-pagination i core/query-no-results (bez klas sp-pagination / sp-empty). Pola min. custom.size.target, na telefonie target-compact (7 pól w 335 px). Markup statyczny: blok paginacji potrzebuje zapytania.',
			'order'  => 80,
			'render' => static function (): void {
				$pagination = static function ( bool $first ): string {
					$numbers = $first
						? '<span aria-current="page" class="page-numbers current">1</span><a class="page-numbers" href="#sg-paginacja">2</a><a class="page-numbers" href="#sg-paginacja">3</a><span class="page-numbers dots">…</span><a class="page-numbers" href="#sg-paginacja">9</a>'
						: '<a class="page-numbers" href="#sg-paginacja">1</a><span class="page-numbers dots">…</span><a class="page-numbers" href="#sg-paginacja">4</a><span aria-current="page" class="page-numbers current">5</span><a class="page-numbers" href="#sg-paginacja">6</a><span class="page-numbers dots">…</span><a class="page-numbers" href="#sg-paginacja">9</a>';
					return '<nav class="wp-block-query-pagination is-layout-flex" aria-label="Paginacja">'
						. ( $first ? '' : '<a href="#sg-paginacja" class="wp-block-query-pagination-previous">Poprzednia</a>' )
						. '<div class="wp-block-query-pagination-numbers">' . $numbers . '</div>'
						. '<a href="#sg-paginacja" class="wp-block-query-pagination-next">Następna</a></nav>';
				};
				$empty      = '<div class="wp-block-query-no-results"><p>Nie znaleźliśmy wpisów pasujących do wyszukiwania. Spróbuj innego słowa.</p>'
					. '<div class="wp-block-buttons is-layout-flex"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#sg-paginacja">Wszystkie Aktualności</a></div></div></div>';

				foreach ( array(
					''     => array( 'Jasne tło', 'jasne' ),
					'navy' => array( 'Na granacie', 'navy' ),
				) as $bg => $row ) {
					pw_sg_row( 'Paginacja: pierwsza strona · ' . $row[0], 'pierwsza-' . $row[1], $bg );
					echo '<div class="sg-demo__full">' . $pagination( true ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
					pw_sg_row_end();

					pw_sg_row( 'Paginacja: środek listy · ' . $row[0], 'srodek-' . $row[1], $bg );
					echo '<div class="sg-demo__full">' . $pagination( false ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
					pw_sg_row_end();

					pw_sg_row( 'Pusta lista · ' . $row[0], 'pusta-' . $row[1], $bg );
					echo '<div class="sg-demo__full">' . $empty . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
					pw_sg_row_end();
				}
			},
		);
		return $groups;
	}
);
