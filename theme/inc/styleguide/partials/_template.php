<?php // phpcs:disable WordPress.Files.FileName -- leading `_` keeps the template out of the partial loader.
/**
 * Template of a styleguide partial (#42). Not loaded (files starting with `_` are skipped).
 *
 * New block: copy to partials/<block-slug>.php, rename, keep one file per block. Nothing else
 * to edit – the page and tests/e2e/styleguide.spec.ts pick the group up automatically (axe,
 * screenshot `<slug>.png`, console errors). Then `npm run test:visual -- --update-snapshots=missing`
 * for its baseline.
 *
 * Rules:
 * - Render through production code only: `do_blocks()` of the block markup (→ render.php),
 *   pw_* helpers. Never copy block markup into the partial.
 * - One row (`pw_sg_row( label, state-slug[, 'navy'] )` … `pw_sg_row_end()`) per variant and
 *   state: full data, empty data, long values, on navy, with and without actions.
 * - Global data per row: `pw_sg_with_data( 'kontakt', $value, $render )` (filter `pw/data/…`
 *   only while the row renders). Photos: `pw_sg_fixture_image( 'wide'|'photo'|'square' )`.
 * - The note says which tokens the block uses and its exceptions.
 * - Mobile screenshots: `'viewports' => array( 375 )` (widths in px) adds `<slug>-375.png` next to
 *   the desktop `<slug>.png`; omit the key for desktop only. New baselines only:
 *   `npm run test:visual -- --update-snapshots=missing`.
 *
 * @package PW
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'pw/styleguide/groups',
	static function ( array $groups ): array {
		$groups[] = array(
			'slug'      => 'example',       // Block slug; screenshot tests/e2e/__screenshots__/…/example.png.
			'title'     => 'Przykład',      // Section heading.
			'note'      => 'Tokeny: … Wyjątki: …',
			'order'     => 100,             // Primitives 10–90, blocks 100+.
			'viewports' => array( 375 ),    // Optional: extra screenshot widths → example-375.png.
			'render'    => static function (): void {
				pw_sg_row( 'Pełne dane', 'pelne' );
				echo do_blocks( '<!-- wp:pw/example {"tytul":"Tytuł"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
				pw_sg_row_end();

				pw_sg_row( 'Na granacie', 'navy', 'navy' );
				echo do_blocks( '<!-- wp:pw/example {"tytul":"Tytuł"} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
				pw_sg_row_end();

				pw_sg_with_data(
					'kontakt',
					null,
					static function (): void {
						pw_sg_row( 'Puste dane', 'puste' );
						echo do_blocks( '<!-- wp:pw/example /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput -- block output.
						pw_sg_row_end();
					}
				);
			},
		);
		return $groups;
	}
);
