<?php
/**
 * `SportsActivityLocation` nodes (#53, docs/design/decyzje-ds-i-layoutow.md §5).
 *
 * Home page and pages with `pw_schema_typ` = `kontakt`: every Lokalizacja. Strony oferty
 * (`oferta`) and Service pages (`service`, also offer pages): the Lokalizacje of the page
 * (`pw_strona_lokalizacje()`). Any other type has none, even with page Lokalizacje: Grafik is
 * `brak`, so only Yoast's WebPage describes it.
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
	 * Every Lokalizacja (home page, Kontakt), the page's own (Strona oferty, Service) or none.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function lokalizacje(): array {
		if ( null === $this->lokalizacje ) {
			$typ = $this->typ();
			if ( $this->is_front_page() || TYP_KONTAKT === $typ ) {
				$this->lokalizacje = pw_lokalizacje();
			} elseif ( in_array( $typ, array( TYP_OFERTA, TYP_SERVICE ), true ) ) {
				$this->lokalizacje = pw_strona_lokalizacje( $this->page_id() );
			} else {
				$this->lokalizacje = array();
			}
		}
		return $this->lokalizacje;
	}
}
