<?php
/**
 * `SportsActivityLocation` nodes (#53, docs/design/decyzje-ds-i-layoutow.md §5).
 *
 * Home page and pages with `pw_schema_typ` = `kontakt`: every Lokalizacja. Other pages (Strony
 * oferty): the Lokalizacje of the page (`pw_strona_lokalizacje()`). Grafik has none – it is `brak`
 * without page Lokalizacje, so only Yoast's WebPage describes it.
 *
 * @package PW
 */

namespace PW\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * One node per Lokalizacja of the current page.
 */
class Location_Piece extends Piece {

	/**
	 * Identifier for the `wpseo_schema_needs_<id>` and `wpseo_schema_<id>` filters.
	 *
	 * @var string
	 */
	public $identifier = 'pw-lokalizacja';

	/**
	 * Lokalizacje of the current page, memoised per piece.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private ?array $lokalizacje = null;

	/**
	 * Needed when the page has Lokalizacje.
	 */
	public function is_needed(): bool {
		return array() !== $this->lokalizacje();
	}

	/**
	 * Nodes of the Lokalizacje.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function generate(): array {
		$kontakt      = pw_kontakt();
		$organization = $this->organization();
		return array_map(
			static fn( array $l ): array => location_node( $l, $kontakt, $organization ),
			$this->lokalizacje()
		);
	}

	/**
	 * Every Lokalizacja (home page, Kontakt) or the page's own.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function lokalizacje(): array {
		if ( null === $this->lokalizacje ) {
			if ( $this->is_front_page() || 'kontakt' === $this->typ() ) {
				$this->lokalizacje = pw_lokalizacje();
			} else {
				$id                = $this->page_id();
				$this->lokalizacje = $id > 0 ? pw_strona_lokalizacje( $id ) : array();
			}
		}
		return $this->lokalizacje;
	}
}
